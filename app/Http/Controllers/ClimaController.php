<?php

namespace App\Http\Controllers;

use App\Models\Parcela;
use App\Services\ClimaCompletoService;
use App\Services\GeocodingService;
use App\Services\OpenMeteoService;
use Carbon\Carbon;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;

class ClimaController extends Controller
{
    public function __construct(
        private ClimaCompletoService $clima,
        private OpenMeteoService $meteo,
        private GeocodingService $geocoding,
    ) {
    }

    /**
     * Pantalla de clima. El lugar sale de (en este orden): una parcela, unas coordenadas,
     * un nombre de ciudad o, si no hay nada, la primera parcela del usuario.
     */
    public function index(Request $request)
    {
        $parcelas = Parcela::where('user_id', $request->user()->id)
            ->activas()->with('ubicacion')->orderBy('nombre')->get()
            ->filter(fn ($p) => $p->ubicacion && $p->ubicacion->tieneCoordenadasGPS())->values();

        $lugar = $this->resolverLugar($request, $parcelas);

        if ($request->boolean('refrescar') && $lugar) {
            foreach (['', ':stale'] as $s) {
                Cache::forget(sprintf('om:full:%.2f:%.2f', $lugar['lat'], $lugar['lon']) . $s);
            }
            Cache::forget(sprintf('om:aire:%.2f:%.2f', $lugar['lat'], $lugar['lon']));
        }

        $datos = $lugar ? $this->clima->paraCoordenadas($lugar['lat'], $lugar['lon'], $lugar['nombre'], $lugar['region'] ?? null) : null;

        return view('clima.index', [
            'datos' => $datos,
            'lugar' => $lugar,
            'parcelas' => $parcelas,
            'parcelaId' => $lugar['parcela_id'] ?? null,
            'ciudades' => $this->geocoding->getCiudadesPrincipalesPeru(),
            'avisos' => $datos ? $this->avisos($datos) : [],
            'serie' => $datos ? $this->serieGraficos($datos) : null,
        ]);
    }

    /** Autocompletado de ciudades. */
    public function buscarCiudades(Request $request): JsonResponse
    {
        return response()->json($this->geocoding->searchCities((string) $request->input('q', ''), 8));
    }

    /** Datos completos en JSON (para integraciones y pruebas). */
    public function datos(Request $request): JsonResponse
    {
        $request->validate(['lat' => ['required', 'numeric', 'between:-90,90'], 'lon' => ['required', 'numeric', 'between:-180,180']]);

        $d = $this->clima->paraCoordenadas((float) $request->lat, (float) $request->lon, $request->input('nombre'));

        return $d ? response()->json($d) : response()->json(['error' => 'No se pudo obtener el clima.'], 503);
    }

    // =========================================================

    private function resolverLugar(Request $request, $parcelas): ?array
    {
        if ($request->filled('parcela')) {
            $p = $parcelas->firstWhere('id', (int) $request->parcela);
            if ($p) {
                return [
                    'lat' => (float) $p->ubicacion->coordenadaLatitud, 'lon' => (float) $p->ubicacion->coordenadaLongitud,
                    'nombre' => $p->nombre . ' - ' . $p->ubicacion->distrito, 'region' => $p->ubicacion->region, 'parcela_id' => $p->id,
                ];
            }
        }

        if ($request->filled('lat') && $request->filled('lon')) {
            return ['lat' => (float) $request->lat, 'lon' => (float) $request->lon, 'nombre' => $request->input('nombre') ?: null];
        }

        if ($request->filled('ciudad')) {
            $r = $this->meteo->buscarLugares((string) $request->ciudad, 1, 'PE') ?: $this->meteo->buscarLugares((string) $request->ciudad, 1);
            if ($r) {
                return ['lat' => (float) $r[0]['lat'], 'lon' => (float) $r[0]['lon'], 'nombre' => $r[0]['display_name']];
            }
        }

        if ($p = $parcelas->first()) {
            return [
                'lat' => (float) $p->ubicacion->coordenadaLatitud, 'lon' => (float) $p->ubicacion->coordenadaLongitud,
                'nombre' => $p->nombre . ' - ' . $p->ubicacion->distrito, 'region' => $p->ubicacion->region, 'parcela_id' => $p->id,
            ];
        }

        return ['lat' => -15.8402, 'lon' => -70.0219, 'nombre' => 'Puno'];
    }

    /**
     * Lo que importa hoy para el campo, en frases cortas y con nivel.
     *
     * @return array<int, array{nivel:string, titulo:string, texto:string}>
     */
    private function avisos(array $d): array
    {
        $a = $d['agro'];
        $act = $d['actual'];
        $avisos = [];
        $kmh = fn ($ms) => round(($ms ?? 0) * 3.6);

        if ($a['min_24h'] && $a['min_24h']['valor'] <= 3) {
            $m = $a['min_24h']['valor'];
            $mt = number_format($m, 1, ',', '');
            $avisos[] = [
                'nivel' => $m <= -2 ? 'critico' : ($m <= 0 ? 'alto' : 'medio'), 'icono' => 'helada',
                'titulo' => $m <= 0 ? 'Riesgo de helada' : 'Noche muy fría',
                'texto' => "Mínima de {$mt} °C hacia las {$a['min_24h']['hora']}. Hay {$a['horas_helada_72h']} h bajo 0 °C en las próximas 72 h. Cubre los cultivos sensibles antes del anochecer.",
            ];
        }

        if ($a['lluvia_24h'] >= 1 && $a['prob_lluvia_24h'] >= 40) {
            $avisos[] = [
                'nivel' => $a['lluvia_24h'] >= 20 ? 'alto' : 'medio', 'icono' => 'lluvia', 'titulo' => 'Lluvia en las próximas 24 horas',
                'texto' => 'Se esperan ' . number_format($a['lluvia_24h'], 1, ',', '') . " mm (probabilidad hasta {$a['prob_lluvia_24h']}%). Si ibas a regar, conviene esperar.",
            ];
        }

        if (($act['tmax_hoy'] ?? 0) >= 30) {
            $avisos[] = ['nivel' => $act['tmax_hoy'] >= 34 ? 'alto' : 'medio', 'icono' => 'calor', 'titulo' => 'Calor',
                'texto' => "Máxima de " . number_format($act['tmax_hoy'], 1, ',', '') . " °C. Riega temprano o al atardecer y cubre el suelo para que no pierda humedad."];
        }

        if ($kmh(max($act['viento'] ?? 0, $act['rafaga'] ?? 0)) >= 36) {
            $avisos[] = ['nivel' => 'medio', 'icono' => 'viento', 'titulo' => 'Viento fuerte',
                'texto' => 'Con este viento se pierde agua y producto: evita fumigar o regar por aspersión.'];
        }

        if (($act['uv'] ?? 0) >= 8) {
            $avisos[] = ['nivel' => 'medio', 'icono' => 'sol', 'titulo' => 'Radiación UV muy alta',
                'texto' => 'Usa sombrero y manga larga si trabajas entre las 10:00 y las 15:00.'];
        }

        if (($d['aire']['aqi'] ?? 0) > 100) {
            $avisos[] = ['nivel' => 'alto', 'icono' => 'aire', 'titulo' => 'Aire con partículas', 'texto' => 'La calidad del aire es ' . mb_strtolower($d['aire']['categoria'][0]) . '. Evita quemas y esfuerzo prolongado al aire libre.'];
        }

        if (!empty($a['ventanas_riego'])) {
            $avisos[] = ['nivel' => 'ok', 'icono' => 'agua', 'titulo' => 'Mejor momento para regar', 'texto' => implode(' y ', array_slice($a['ventanas_riego'], 0, 3)) . ' (poco viento, sin lluvia y temperatura moderada).'];
        }

        if (!empty($a['ventanas_fumigar'])) {
            $avisos[] = ['nivel' => 'ok', 'icono' => 'tierra', 'titulo' => 'Mejor momento para fumigar', 'texto' => implode(' y ', array_slice($a['ventanas_fumigar'], 0, 3)) . ' (viento bajo y Delta T entre 2 y 8).'];
        }

        if (!empty($d['desactualizado'])) {
            array_unshift($avisos, ['nivel' => 'alto', 'icono' => 'aviso', 'titulo' => 'Datos guardados', 'texto' => 'No hay conexión a internet. Se muestran los últimos datos disponibles y pueden estar atrasados.']);
        }

        return $avisos;
    }

    /**
     * Series ya preparadas para los graficos (viento en km/h, humedad del suelo en %, etc.).
     */
    private function serieGraficos(array $d): array
    {
        $z = $d['lugar']['zona'];
        $h = $d['horas'];
        $col = fn (string $k, int $dec = 1) => array_map(fn ($x) => isset($x[$k]) ? round($x[$k], $dec) : null, $h);
        $kmh = fn (string $k) => array_map(fn ($x) => isset($x[$k]) ? round($x[$k] * 3.6, 1) : null, $h);
        $pct = fn (string $k) => array_map(fn ($x) => isset($x[$k]) ? round($x[$k] * 100, 1) : null, $h);

        $dias = $d['dias'];
        $dcol = fn (string $k, int $dec = 1) => array_map(fn ($x) => isset($x[$k]) ? round($x[$k], $dec) : null, $dias);

        $hist = $d['historial']['dias'] ?? [];
        $hcol = fn (string $k) => array_map(fn ($x) => $x[$k] ?? null, $hist);

        return [
            'h' => [
                'lab' => array_map(fn ($x) => Carbon::createFromTimestamp($x['ts'], $z)->translatedFormat('D G\\h'), $h),
                'tit' => array_map(fn ($x) => Carbon::createFromTimestamp($x['ts'], $z)->translatedFormat('D j, H:i'), $h),
                'temp' => $col('temperature_2m'), 'sens' => $col('apparent_temperature'), 'rocio' => $col('dew_point_2m'), 'hum' => $col('relative_humidity_2m', 0),
                'lluvia' => $col('precipitation'), 'prob' => $col('precipitation_probability', 0),
                'v10' => $kmh('wind_speed_10m'), 'v80' => $kmh('wind_speed_80m'), 'v120' => $kmh('wind_speed_120m'), 'raf' => $kmh('wind_gusts_10m'),
                'pres' => $col('pressure_msl'), 'pres_est' => $col('surface_pressure'),
                'nub' => $col('cloud_cover', 0), 'nub_b' => $col('cloud_cover_low', 0), 'nub_m' => $col('cloud_cover_mid', 0), 'nub_a' => $col('cloud_cover_high', 0),
                'vis' => array_map(fn ($x) => isset($x['visibility']) ? round($x['visibility'] / 1000, 1) : null, $h),
                'uv' => $col('uv_index'), 'rad' => $col('shortwave_radiation', 0), 'rad_dir' => $col('direct_radiation', 0), 'rad_dif' => $col('diffuse_radiation', 0),
                'et0' => $col('et0_fao_evapotranspiration', 2), 'vpd' => $col('vapour_pressure_deficit', 2),
                's0' => $col('soil_temperature_0cm'), 's6' => $col('soil_temperature_6cm'), 's18' => $col('soil_temperature_18cm'), 's54' => $col('soil_temperature_54cm'),
                'iso0' => $col('freezing_level_height', 0), 'cape' => $col('cape', 0),
                'hs' => $pct('soil_moisture_3_to_9cm'), 'hs0' => $pct('soil_moisture_0_to_1cm'), 'hs27' => $pct('soil_moisture_9_to_27cm'),
            ],
            'd' => [
                'lab' => array_map(fn ($x) => Carbon::parse($x['fecha'])->translatedFormat('D j'), $dias),
                'tit' => array_map(fn ($x) => Carbon::parse($x['fecha'])->translatedFormat('l j \\d\\e F'), $dias),
                'tmax' => $dcol('temperature_2m_max'), 'tmin' => $dcol('temperature_2m_min'), 'lluvia' => $dcol('precipitation_sum'),
                'prob' => $dcol('precipitation_probability_max', 0), 'et0' => $dcol('et0_fao_evapotranspiration', 1),
                'uv' => $dcol('uv_index_max'), 'sol' => array_map(fn ($x) => round(($x['sunshine_duration'] ?? 0) / 3600, 1), $dias),
                'rad' => $dcol('shortwave_radiation_sum'),
            ],
            'hist' => [
                'lab' => array_map(fn ($x) => Carbon::parse($x['fecha'])->format('d/m'), $hist),
                'tmax' => $hcol('tmax'), 'tmin' => $hcol('tmin'), 'tmedia' => $hcol('tmedia'), 'lluvia' => $hcol('lluvia'), 'et0' => $hcol('et0'),
            ],
            'aire' => [
                'lab' => array_map(fn ($x) => Carbon::parse($x['iso'], $z)->translatedFormat('D G\\h'), $d['aire']['horas'] ?? []),
                'aqi' => array_column($d['aire']['horas'] ?? [], 'aqi'), 'pm25' => array_column($d['aire']['horas'] ?? [], 'pm25'),
            ],
        ];
    }
}
