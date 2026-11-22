<?php

namespace App\Services;

use App\Models\ClimaHistorial;
use App\Models\Parcela;
use Carbon\Carbon;

/**
 * Guarda en clima_historial lo que entrega Open-Meteo para cada parcela.
 *
 * Es el unico lugar que escribe registros de clima (antes lo hacian a la vez
 * un comando, el modelo Parcela y este servicio, con columnas distintas).
 */
class ClimaService
{
    /** Minutos que un registro se considera "reciente" y no se vuelve a pedir. */
    public const MINUTOS_MINIMOS_ENTRE_ACTUALIZACIONES = 25;

    public function __construct(private OpenMeteoService $meteo)
    {
    }

    /**
     * Actualiza el clima de una parcela.
     *
     * @param  bool  $forzar  ignora el limite de tiempo y la cache
     * @return ClimaHistorial|null null si no hay coordenadas o no hay datos
     */
    public function actualizarClimaParcela(Parcela $parcela, bool $forzar = false, string $tipo = 'automatica'): ?ClimaHistorial
    {
        $parcela->loadMissing('ubicacion');
        $ubicacion = $parcela->ubicacion;

        if (!$ubicacion || !$ubicacion->tieneCoordenadasGPS()) {
            return null;
        }

        if (!$forzar) {
            $ultimo = ClimaHistorial::where('parcela_id', $parcela->id)
                ->where('desactualizado', false)
                ->latest('fecha_registro')
                ->first();

            if ($ultimo && $ultimo->fecha_registro->gt(now()->subMinutes(self::MINUTOS_MINIMOS_ENTRE_ACTUALIZACIONES))) {
                return $ultimo;
            }
        }

        $lat = (float) $ubicacion->coordenadaLatitud;
        $lon = (float) $ubicacion->coordenadaLongitud;

        if ($forzar) {
            $this->meteo->olvidarCoordenadas($lat, $lon);
        }

        $paquete = $this->meteo->getPaqueteAgricola($lat, $lon);

        if (!$paquete) {
            return null;
        }

        // Sin internet se obtiene el ultimo dato guardado: no se vuelve a
        // registrar como nuevo, solo se avisa que esta desactualizado.
        if ($paquete['desactualizado']) {
            return null;
        }

        $a = $paquete['actual'];
        $r = $paquete['resumen'];
        $zona = config('app.timezone');
        $local = fn (?int $ts) => $ts ? Carbon::createFromTimestamp($ts, $zona) : null;

        return ClimaHistorial::create([
            'parcela_id' => $parcela->id,
            'ciudad' => $ubicacion->provincia,
            'distrito' => $ubicacion->distrito,
            'lat' => $a['lat'],
            'lon' => $a['lon'],

            'temperatura' => $a['temp'],
            'sensacion_termica' => $a['feels_like'],
            'temp_minima' => $a['temp_min'],
            'temp_maxima' => $a['temp_max'],

            'humedad' => $a['humidity'],
            'presion' => $a['pressure'],

            'viento_velocidad' => $a['wind_speed'],
            'viento_direccion' => $a['wind_deg'],
            'viento_direccion_texto' => $a['wind_direction'],
            'viento_rafaga' => $a['wind_gust'],

            'lluvia_1h' => $a['rain_1h'],
            'lluvia_3h' => $a['rain_3h'],
            'lluvia_24h' => $r['lluvia_24h_mm'],
            'probabilidad_lluvia_24h' => $r['prob_lluvia_24h'],

            'et0_mm' => $r['et0_hoy_mm'],
            'lluvia_72h_mm' => $r['lluvia_pasada_72h_mm'],
            'temp_min_prevista' => $r['temp_min_24h'],
            'temp_min_suelo' => $r['temp_min_suelo'],
            'temp_max_prevista' => $r['temp_max_24h'],
            'hora_temp_min' => $local($r['temp_min_24h_ts']),
            'lluvia_inicio' => $local($r['lluvia_inicio_ts']),
            'lluvia_fin' => $local($r['lluvia_fin_ts']),
            'elevacion' => $a['elevation'],

            'condicion_principal' => $a['weather_main'],
            'descripcion' => $a['weather_description'],
            'icono_codigo' => $a['weather_icon'],
            'nubosidad' => $a['clouds'],
            'visibilidad' => $a['visibility'],

            'amanecer' => $a['sunrise_formatted'],
            'atardecer' => $a['sunset_formatted'],

            // Las banderas de alerta las completa AlertaService con el mismo criterio de las alertas.
            'alerta_helada' => false,
            'alerta_calor_extremo' => false,
            'alerta_lluvia_proxima' => false,
            'necesita_riego_urgente' => false,

            'fuente' => 'open-meteo',
            'desactualizado' => false,
            'fecha_registro' => now(),
            'tipo_actualizacion' => $tipo,
        ]);
    }

    /**
     * Actualiza todas las parcelas activas de un usuario.
     */
    public function actualizarClimaUsuario(int $userId, bool $forzar = false): int
    {
        $actualizadas = 0;

        Parcela::where('user_id', $userId)
            ->activas()
            ->with(['ubicacion', 'cultivo'])
            ->get()
            ->each(function (Parcela $p) use ($forzar, &$actualizadas) {
                if ($this->actualizarClimaParcela($p, $forzar)) {
                    $actualizadas++;
                }
            });

        return $actualizadas;
    }
}
