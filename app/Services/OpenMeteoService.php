<?php

namespace App\Services;

use Carbon\Carbon;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Throwable;

/**
 * Cliente de Open-Meteo (https://open-meteo.com).
 *
 * - Gratuito, sin API key y sin tarjeta (uso no comercial).
 * - Una sola consulta por coordenada trae clima actual, pronostico horario,
 *   lluvia de los ultimos 3 dias y evapotranspiracion (ET0), que es el dato
 *   que realmente importa para calcular cuanta agua necesita un cultivo.
 * - Pensado para zonas rurales: reintenta, usa cache de 15 minutos y, si no
 *   hay internet, devuelve el ultimo dato guardado marcado como desactualizado.
 *
 * Mantiene el mismo formato de salida que el servicio anterior (OpenWeather),
 * por eso los controladores y vistas existentes siguen funcionando.
 */
class OpenMeteoService
{
    private const URL_PRONOSTICO = 'https://api.open-meteo.com/v1/forecast';
    private const URL_GEOCODIFICACION = 'https://geocoding-api.open-meteo.com/v1/search';

    /** Minutos que se reutiliza una consulta antes de volver a pedirla. */
    private const CACHE_MINUTOS = 15;

    /** Dias que se conserva el ultimo dato bueno para cuando no hay internet. */
    private const STALE_DIAS = 3;

    /** Codigos WMO: [principal, descripcion en espanol, clave de icono]. */
    private const CODIGOS = [
        0  => ['Clear', 'Despejado', 'sol'],
        1  => ['Clear', 'Mayormente despejado', 'sol'],
        2  => ['Clouds', 'Parcialmente nublado', 'parcial'],
        3  => ['Clouds', 'Nublado', 'nublado'],
        45 => ['Fog', 'Neblina', 'niebla'],
        48 => ['Fog', 'Neblina con escarcha', 'niebla'],
        51 => ['Drizzle', 'Llovizna ligera', 'llovizna'],
        53 => ['Drizzle', 'Llovizna moderada', 'llovizna'],
        55 => ['Drizzle', 'Llovizna intensa', 'llovizna'],
        56 => ['Drizzle', 'Llovizna helada', 'llovizna'],
        57 => ['Drizzle', 'Llovizna helada intensa', 'llovizna'],
        61 => ['Rain', 'Lluvia ligera', 'lluvia'],
        63 => ['Rain', 'Lluvia moderada', 'lluvia'],
        65 => ['Rain', 'Lluvia fuerte', 'lluvia'],
        66 => ['Rain', 'Lluvia helada', 'lluvia'],
        67 => ['Rain', 'Lluvia helada fuerte', 'lluvia'],
        71 => ['Snow', 'Nevada ligera', 'nieve'],
        73 => ['Snow', 'Nevada moderada', 'nieve'],
        75 => ['Snow', 'Nevada fuerte', 'nieve'],
        77 => ['Snow', 'Granizo fino', 'nieve'],
        80 => ['Rain', 'Chubascos ligeros', 'lluvia'],
        81 => ['Rain', 'Chubascos moderados', 'lluvia'],
        82 => ['Rain', 'Chubascos fuertes', 'lluvia'],
        85 => ['Snow', 'Chubascos de nieve', 'nieve'],
        86 => ['Snow', 'Chubascos de nieve fuertes', 'nieve'],
        95 => ['Thunderstorm', 'Tormenta electrica', 'tormenta'],
        96 => ['Thunderstorm', 'Tormenta con granizo', 'tormenta'],
        99 => ['Thunderstorm', 'Tormenta fuerte con granizo', 'tormenta'],
    ];

    // =========================================================
    // API PUBLICA (mismo contrato que el servicio anterior)
    // =========================================================

    /**
     * Clima actual por coordenadas.
     */
    public function getCurrentWeatherByCoordinates(float $lat, float $lon, ?string $nombre = null): ?array
    {
        $b = $this->obtenerPaquete($lat, $lon);

        if (!$b) {
            return null;
        }

        $actual = $b['actual'];
        $actual['name'] = $nombre ?: sprintf('%.3f, %.3f', $lat, $lon);

        return $actual;
    }

    /**
     * Clima actual por nombre de ciudad (usa el geocodificador de Open-Meteo).
     */
    public function getCurrentWeather(string $city, string $country = ''): ?array
    {
        $lugar = $this->buscarLugar($city, $country);

        if (!$lugar) {
            return null;
        }

        $clima = $this->getCurrentWeatherByCoordinates($lugar['lat'], $lugar['lon'], $lugar['name']);

        if ($clima) {
            $clima['country'] = $lugar['country'];
        }

        return $clima;
    }

    /**
     * Pronostico en bloques de 3 horas (hasta 5 dias).
     */
    public function getForecastByCoordinates(float $lat, float $lon, ?string $nombre = null): ?array
    {
        $b = $this->obtenerPaquete($lat, $lon);

        if (!$b) {
            return null;
        }

        return [
            'city' => $nombre ?: sprintf('%.3f, %.3f', $lat, $lon),
            'country' => 'PE',
            'lat' => $b['lat'],
            'lon' => $b['lon'],
            'timezone' => $b['utc_offset'],
            'sunrise' => $b['actual']['sunrise'],
            'sunset' => $b['actual']['sunset'],
            'list' => $b['bloques_3h'],
            'desactualizado' => $b['desactualizado'],
        ];
    }

    public function getForecast(string $city, string $country = ''): ?array
    {
        $lugar = $this->buscarLugar($city, $country);

        if (!$lugar) {
            return null;
        }

        $f = $this->getForecastByCoordinates($lugar['lat'], $lugar['lon'], $lugar['name']);

        if ($f) {
            $f['country'] = $lugar['country'];
        }

        return $f;
    }

    /**
     * Resumen que usa el sistema de riego y alertas.
     */
    public function getWeatherSummaryForIrrigation(float $lat, float $lon): array
    {
        $b = $this->obtenerPaquete($lat, $lon);

        if (!$b) {
            return [
                'temperature' => null,
                'humidity' => null,
                'rain_forecast_24h' => 0,
                'rain_probability_24h' => 0,
                'conditions' => 'unknown',
            ];
        }

        return [
            'temperature' => $b['actual']['temp'],
            'humidity' => $b['actual']['humidity'],
            'rain_last_3h' => $b['actual']['rain_3h'],
            'rain_forecast_24h' => $b['resumen']['lluvia_24h_mm'],
            'rain_probability_24h' => $b['resumen']['prob_lluvia_24h'],
            'wind_speed' => $b['actual']['wind_speed'],
            'conditions' => $b['actual']['weather_main'],
            'description' => $b['actual']['weather_description'],
        ];
    }

    /**
     * Paquete completo con los datos agronomicos (ET0, lluvia pasada,
     * minima y maxima previstas). Lo usa ClimaService.
     */
    public function getPaqueteAgricola(float $lat, float $lon): ?array
    {
        return $this->obtenerPaquete($lat, $lon);
    }

    /**
     * Lluvia acumulada en los ultimos 3 dias (dato real, no pronostico).
     */
    public function getRainLast7Days(string $city, string $country = ''): float
    {
        $lugar = $this->buscarLugar($city, $country);

        if (!$lugar) {
            return 0;
        }

        $b = $this->obtenerPaquete($lugar['lat'], $lugar['lon']);

        return $b ? round($b['resumen']['lluvia_pasada_72h_mm'], 2) : 0;
    }

    public function forceRefresh(string $city, string $country = ''): ?array
    {
        $lugar = $this->buscarLugar($city, $country);

        if (!$lugar) {
            return null;
        }

        Cache::forget($this->claveCache($lugar['lat'], $lugar['lon']));

        return $this->getCurrentWeatherByCoordinates($lugar['lat'], $lugar['lon'], $lugar['name']);
    }

    public function clearCityCache(string $city, string $country = ''): void
    {
        $lugar = $this->buscarLugar($city, $country);

        if ($lugar) {
            Cache::forget($this->claveCache($lugar['lat'], $lugar['lon']));
        }
    }

    public function olvidarCoordenadas(float $lat, float $lon): void
    {
        Cache::forget($this->claveCache($lat, $lon));
    }

    /**
     * Buscar ciudades (autocompletado).
     */
    public function searchCity(string $cityName): array
    {
        return $this->buscarLugares($cityName, 10);
    }

    public function getWeatherIconUrl(string $icon, string $size = ''): string
    {
        return asset("img/clima/{$icon}.svg");
    }

    // =========================================================
    // GEOCODIFICACION
    // =========================================================

    public function buscarLugares(string $nombre, int $limite = 10, string $pais = ''): array
    {
        $nombre = trim($nombre);

        if (mb_strlen($nombre) < 2) {
            return [];
        }

        $clave = 'om:geo:' . md5(mb_strtolower($nombre) . $limite . $pais);

        return Cache::remember($clave, 86400, function () use ($nombre, $limite, $pais) {
            try {
                $params = [
                    'name' => $nombre,
                    'count' => $limite,
                    'language' => 'es',
                    'format' => 'json',
                ];

                if ($pais !== '') {
                    $params['countryCode'] = strtoupper($pais);
                }

                $resp = Http::timeout(8)->retry(2, 400, throw: false)->get(self::URL_GEOCODIFICACION, $params);

                if (!$resp->successful()) {
                    return [];
                }

                return collect($resp->json('results') ?? [])->map(fn ($r) => [
                    'name' => $r['name'],
                    'country' => $r['country_code'] ?? '',
                    'state' => $r['admin1'] ?? '',
                    'lat' => $r['latitude'],
                    'lon' => $r['longitude'],
                    'elevation' => $r['elevation'] ?? null,
                    'display_name' => implode(', ', array_filter([
                        $r['name'],
                        $r['admin1'] ?? null,
                        $r['country'] ?? null,
                    ])),
                ])->all();
            } catch (Throwable $e) {
                report($e);

                return [];
            }
        });
    }

    private function buscarLugar(string $nombre, string $pais = ''): ?array
    {
        // El nombre puede venir como "Distrito, Provincia": se usa solo el primer tramo.
        $primero = trim(explode(',', $nombre)[0]);
        $res = $this->buscarLugares($primero, 1, $pais);

        return $res[0] ?? null;
    }

    // =========================================================
    // CONSULTA Y NORMALIZACION
    // =========================================================

    private function claveCache(float $lat, float $lon): string
    {
        return sprintf('om:bundle:%.2f:%.2f', $lat, $lon);
    }

    private function obtenerPaquete(float $lat, float $lon): ?array
    {
        $clave = $this->claveCache($lat, $lon);

        $fresco = Cache::get($clave);
        if ($fresco) {
            return $fresco;
        }

        $paquete = $this->consultarApi($lat, $lon);

        if ($paquete) {
            Cache::put($clave, $paquete, now()->addMinutes(self::CACHE_MINUTOS));
            Cache::put($clave . ':stale', $paquete, now()->addDays(self::STALE_DIAS));

            return $paquete;
        }

        // Sin internet o API caida: devolver el ultimo dato bueno, marcado.
        $viejo = Cache::get($clave . ':stale');

        if ($viejo) {
            $viejo['desactualizado'] = true;
            $viejo['actual']['desactualizado'] = true;

            return $viejo;
        }

        return null;
    }

    private function consultarApi(float $lat, float $lon): ?array
    {
        try {
            $resp = Http::timeout(10)
                ->retry(2, 500, throw: false)
                ->get(self::URL_PRONOSTICO, [
                    'latitude' => round($lat, 4),
                    'longitude' => round($lon, 4),
                    'current' => implode(',', [
                        'temperature_2m', 'relative_humidity_2m', 'apparent_temperature',
                        'precipitation', 'rain', 'weather_code', 'cloud_cover',
                        'pressure_msl', 'wind_speed_10m', 'wind_direction_10m',
                        'wind_gusts_10m', 'is_day',
                    ]),
                    'hourly' => implode(',', [
                        'temperature_2m', 'relative_humidity_2m', 'precipitation_probability',
                        'precipitation', 'weather_code', 'cloud_cover', 'wind_speed_10m',
                        'wind_direction_10m', 'wind_gusts_10m', 'et0_fao_evapotranspiration',
                    ]),
                    'daily' => implode(',', [
                        'temperature_2m_max', 'temperature_2m_min', 'precipitation_sum',
                        'precipitation_probability_max', 'et0_fao_evapotranspiration',
                        'sunrise', 'sunset', 'weather_code',
                    ]),
                    'timezone' => 'auto',
                    'wind_speed_unit' => 'ms',
                    'past_days' => 3,
                    'forecast_days' => 6,
                ]);

            if (!$resp->successful() || !$resp->json('current')) {
                return null;
            }

            return $this->normalizar($resp->json());
        } catch (Throwable $e) {
            report($e);

            return null;
        }
    }

    private function normalizar(array $d): array
    {
        $tz = $d['timezone'] ?? 'America/Lima';
        $cur = $d['current'];
        $hor = $d['hourly'];
        $dia = $d['daily'];

        $ahora = Carbon::parse($cur['time'], $tz);
        $ts = fn (string $iso) => Carbon::parse($iso, $tz)->timestamp;

        // ---- Serie horaria (indexada por timestamp) ----
        $horas = [];
        foreach ($hor['time'] as $i => $iso) {
            $horas[] = [
                'ts' => $ts($iso),
                'temp' => $hor['temperature_2m'][$i],
                'hum' => $hor['relative_humidity_2m'][$i],
                'pop' => $hor['precipitation_probability'][$i] ?? 0,
                'lluvia' => $hor['precipitation'][$i] ?? 0,
                'codigo' => $hor['weather_code'][$i] ?? 0,
                'nubes' => $hor['cloud_cover'][$i] ?? 0,
                'viento' => $hor['wind_speed_10m'][$i] ?? 0,
                'dir' => $hor['wind_direction_10m'][$i] ?? 0,
                'rafaga' => $hor['wind_gusts_10m'][$i] ?? null,
                'et0' => $hor['et0_fao_evapotranspiration'][$i] ?? 0,
            ];
        }

        $t0 = $ahora->copy()->startOfHour()->timestamp;
        $futuras = array_values(array_filter($horas, fn ($h) => $h['ts'] >= $t0));
        $pasadas = array_values(array_filter($horas, fn ($h) => $h['ts'] < $t0));

        $prox24 = array_slice($futuras, 0, 24);
        $prox48 = array_slice($futuras, 0, 48);

        // ---- Resumen agronomico ----
        $lluvia24 = round(array_sum(array_column($prox24, 'lluvia')), 1);
        $prob24 = $prox24 ? (int) max(array_column($prox24, 'pop')) : 0;
        $pasada72 = round(array_sum(array_column(array_slice($pasadas, -72), 'lluvia')), 1);
        $lluvia3hPasada = round(array_sum(array_column(array_slice($pasadas, -3), 'lluvia')), 1);

        $minimo24 = $this->extremo($prox24, 'temp', 'min');
        $maximo24 = $this->extremo($prox24, 'temp', 'max');
        $minimo48 = $this->extremo($prox48, 'temp', 'min');

        // Hora de lluvia prevista (primera hora con probabilidad alta)
        $inicioLluvia = null;
        $finLluvia = null;
        foreach ($prox24 as $h) {
            if ($h['lluvia'] >= 0.3 && $h['pop'] >= 40) {
                $inicioLluvia ??= $h['ts'];
                $finLluvia = $h['ts'];
            }
        }

        // ET0 del dia de hoy (mm/dia)
        $hoy = $ahora->toDateString();
        $et0Hoy = null;
        foreach ($dia['time'] as $i => $f) {
            if ($f === $hoy) {
                $et0Hoy = $dia['et0_fao_evapotranspiration'][$i] ?? null;
                $idxHoy = $i;
                break;
            }
        }
        $idxHoy ??= 0;

        // Heuristica de helada radiativa: en noches despejadas y sin viento el
        // suelo se enfria 1-3 C por debajo de lo que mide el aire a 2 m.
        $ajusteSuelo = 0.5;
        if ($minimo24) {
            $alrededor = array_filter($prox24, fn ($h) => abs($h['ts'] - $minimo24['ts']) <= 3 * 3600);
            $nubes = $alrededor ? array_sum(array_column($alrededor, 'nubes')) / count($alrededor) : 100;
            $viento = $alrededor ? array_sum(array_column($alrededor, 'viento')) / count($alrededor) : 5;
            $ajusteSuelo = ($nubes < 30 && $viento < 2.5) ? 2.0 : (($nubes < 60 && $viento < 4) ? 1.0 : 0.5);
        }

        $resumen = [
            'lluvia_24h_mm' => $lluvia24,
            'prob_lluvia_24h' => $prob24,
            'lluvia_pasada_72h_mm' => $pasada72,
            'lluvia_inicio_ts' => $inicioLluvia,
            'lluvia_fin_ts' => $finLluvia,
            'temp_min_24h' => $minimo24['valor'] ?? null,
            'temp_min_24h_ts' => $minimo24['ts'] ?? null,
            'temp_min_48h' => $minimo48['valor'] ?? null,
            'temp_max_24h' => $maximo24['valor'] ?? null,
            'temp_min_suelo' => $minimo24 ? round($minimo24['valor'] - $ajusteSuelo, 1) : null,
            'et0_hoy_mm' => $et0Hoy,
        ];

        // ---- Clima actual en el formato historico ----
        [$principal, $descripcion, $icono] = $this->describir((int) $cur['weather_code'], (bool) $cur['is_day']);
        $amanece = $ts($dia['sunrise'][$idxHoy]);
        $anochece = $ts($dia['sunset'][$idxHoy]);
        $dir = (int) ($cur['wind_direction_10m'] ?? 0);

        $actual = [
            'name' => '',
            'country' => 'PE',
            'lat' => $d['latitude'],
            'lon' => $d['longitude'],
            'elevation' => isset($d['elevation']) ? (int) round($d['elevation']) : null,

            'temp' => round($cur['temperature_2m'], 1),
            'feels_like' => round($cur['apparent_temperature'] ?? $cur['temperature_2m'], 1),
            'temp_min' => round($dia['temperature_2m_min'][$idxHoy] ?? $cur['temperature_2m'], 1),
            'temp_max' => round($dia['temperature_2m_max'][$idxHoy] ?? $cur['temperature_2m'], 1),

            'humidity' => (int) round($cur['relative_humidity_2m']),
            'pressure' => (int) round($cur['pressure_msl'] ?? 0),

            'wind_speed' => round($cur['wind_speed_10m'], 1),
            'wind_deg' => $dir,
            'wind_gust' => isset($cur['wind_gusts_10m']) ? round($cur['wind_gusts_10m'], 1) : null,
            'wind_direction' => $this->direccionViento($dir),

            'clouds' => (int) $cur['cloud_cover'],
            'visibility' => null,

            'rain_1h' => round($cur['precipitation'] ?? 0, 2),
            'rain_3h' => $lluvia3hPasada,
            'snow_1h' => 0,
            'snow_3h' => 0,

            'weather_id' => (int) $cur['weather_code'],
            'weather_main' => $principal,
            'weather_description' => $descripcion,
            'weather_icon' => $icono,
            'icon_url' => $this->getWeatherIconUrl($icono),

            'sunrise' => $amanece,
            'sunset' => $anochece,
            'sunrise_formatted' => Carbon::createFromTimestamp($amanece, $tz)->format('H:i'),
            'sunset_formatted' => Carbon::createFromTimestamp($anochece, $tz)->format('H:i'),

            'dt' => $ahora->timestamp,
            'timezone' => (int) ($d['utc_offset_seconds'] ?? 0),
            'recorded_at' => $ahora->format('Y-m-d H:i:s'),

            'desactualizado' => false,
        ];

        // ---- Bloques de 3 horas (compatibles con el formato anterior) ----
        // Los bloques arrancan en horas multiplo de 3 (00, 03, 06...) como antes.
        $inicio = 0;
        foreach ($futuras as $i => $h) {
            if (Carbon::createFromTimestamp($h['ts'], $tz)->hour % 3 === 0) {
                $inicio = $i;
                break;
            }
        }

        $bloques = [];
        foreach (array_chunk(array_slice($futuras, $inicio, 120), 3) as $grupo) {
            if (count($grupo) < 3) {
                break;
            }

            $primero = $grupo[0];
            $peor = collect($grupo)->sortByDesc(fn ($h) => $this->gravedadCodigo($h['codigo']))->first();
            $hora = (int) Carbon::createFromTimestamp($primero['ts'], $tz)->format('G');
            $esDia = $hora >= 6 && $hora < 18;
            [$p, $desc, $ico] = $this->describir((int) $peor['codigo'], $esDia);
            $rafaga = array_filter(array_column($grupo, 'rafaga'), fn ($v) => $v !== null);
            $vel = max(array_column($grupo, 'viento'));
            $grados = (int) $primero['dir'];
            $temps = array_column($grupo, 'temp');

            $bloques[] = [
                'dt' => $primero['ts'],
                'datetime' => Carbon::createFromTimestamp($primero['ts'], $tz)->format('Y-m-d H:i:s'),
                'date_formatted' => Carbon::createFromTimestamp($primero['ts'], $tz)->format('d/m H:i'),
                'temp' => round($primero['temp'], 1),
                'feels_like' => round($primero['temp'], 1),
                'temp_min' => round(min($temps), 1),
                'temp_max' => round(max($temps), 1),
                'humidity' => (int) round(array_sum(array_column($grupo, 'hum')) / 3),
                'pressure' => $actual['pressure'],
                'weather_main' => $p,
                'weather_description' => $desc,
                'weather_icon' => $ico,
                'icon_url' => $this->getWeatherIconUrl($ico),
                'clouds' => (int) round(array_sum(array_column($grupo, 'nubes')) / 3),
                'wind_speed' => round($vel, 1),
                'wind_deg' => $grados,
                'wind_gust' => $rafaga ? round(max($rafaga), 1) : null,
                'wind_direction' => $this->direccionViento($grados),
                'pop' => (int) max(array_column($grupo, 'pop')),
                'rain_3h' => round(array_sum(array_column($grupo, 'lluvia')), 2),
                'snow_3h' => 0,
                'visibility' => null,
                'pod' => $esDia ? 'd' : 'n',
            ];
        }

        // Pronostico diario resumido (para tarjetas)
        $diario = [];
        foreach ($dia['time'] as $i => $f) {
            if ($f < $hoy) {
                continue;
            }
            [$p, $desc, $ico] = $this->describir((int) ($dia['weather_code'][$i] ?? 0), true);
            $diario[] = [
                'fecha' => $f,
                'temp_max' => round($dia['temperature_2m_max'][$i], 1),
                'temp_min' => round($dia['temperature_2m_min'][$i], 1),
                'lluvia_mm' => round($dia['precipitation_sum'][$i] ?? 0, 1),
                'pop' => (int) ($dia['precipitation_probability_max'][$i] ?? 0),
                'et0_mm' => round($dia['et0_fao_evapotranspiration'][$i] ?? 0, 1),
                'weather_main' => $p,
                'weather_description' => $desc,
                'weather_icon' => $ico,
                'icon_url' => $this->getWeatherIconUrl($ico),
            ];
        }

        return [
            'lat' => $d['latitude'],
            'lon' => $d['longitude'],
            'zona_horaria' => $tz,
            'utc_offset' => (int) ($d['utc_offset_seconds'] ?? 0),
            'consultado_en' => now()->toIso8601String(),
            'desactualizado' => false,
            'actual' => $actual,
            'resumen' => $resumen,
            'bloques_3h' => $bloques,
            'diario' => $diario,
        ];
    }

    // =========================================================
    // AUXILIARES
    // =========================================================

    /** @return array{0:string,1:string,2:string} */
    private function describir(int $codigo, bool $esDia): array
    {
        [$principal, $descripcion, $icono] = self::CODIGOS[$codigo] ?? ['Clouds', 'Variable', 'parcial'];

        // De noche el sol se muestra como luna y el parcialmente nublado cambia
        if (!$esDia && $icono === 'sol') {
            $icono = 'luna';
        }

        return [$principal, $descripcion, $icono];
    }

    private function gravedadCodigo(int $codigo): int
    {
        return match (true) {
            $codigo >= 95 => 6,
            $codigo >= 71 && $codigo <= 86 => 5,
            $codigo >= 61 => 4,
            $codigo >= 51 => 3,
            $codigo >= 45 => 2,
            $codigo >= 2 => 1,
            default => 0,
        };
    }

    /** @return array{valor:float,ts:int}|null */
    private function extremo(array $horas, string $campo, string $tipo): ?array
    {
        if (!$horas) {
            return null;
        }

        $elegida = $horas[0];
        foreach ($horas as $h) {
            if ($tipo === 'min' ? $h[$campo] < $elegida[$campo] : $h[$campo] > $elegida[$campo]) {
                $elegida = $h;
            }
        }

        return ['valor' => round($elegida[$campo], 1), 'ts' => $elegida['ts']];
    }

    private function direccionViento(int $grados): string
    {
        $dirs = ['N', 'NE', 'E', 'SE', 'S', 'SO', 'O', 'NO'];

        return $dirs[((int) round($grados / 45)) % 8];
    }
}
