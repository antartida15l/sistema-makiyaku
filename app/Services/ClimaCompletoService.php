<?php

namespace App\Services;

use Carbon\Carbon;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Throwable;

/**
 * Todo lo que Open-Meteo ofrece para una coordenada, listo para la pantalla de clima:
 * actual, 72 horas, 14 dias, suelo (temperatura y humedad por profundidad), radiacion,
 * UV, viento en altura, calidad del aire e historial de 60 dias con comparacion del
 * ano anterior. Cada bloque se pide por separado: si uno falla, el resto sigue.
 */
class ClimaCompletoService
{
    private const URL = 'https://api.open-meteo.com/v1/forecast';
    private const URL_AIRE = 'https://air-quality-api.open-meteo.com/v1/air-quality';
    private const URL_ARCHIVO = 'https://archive-api.open-meteo.com/v1/archive';

    private const ACTUAL = [
        'temperature_2m', 'relative_humidity_2m', 'apparent_temperature', 'is_day', 'precipitation', 'rain', 'showers',
        'snowfall', 'weather_code', 'cloud_cover', 'pressure_msl', 'surface_pressure', 'wind_speed_10m',
        'wind_direction_10m', 'wind_gusts_10m',
    ];

    private const HORARIO = [
        'temperature_2m', 'relative_humidity_2m', 'dew_point_2m', 'apparent_temperature', 'precipitation_probability',
        'precipitation', 'rain', 'showers', 'snowfall', 'snow_depth', 'weather_code', 'pressure_msl', 'surface_pressure',
        'cloud_cover', 'cloud_cover_low', 'cloud_cover_mid', 'cloud_cover_high', 'visibility', 'evapotranspiration',
        'et0_fao_evapotranspiration', 'vapour_pressure_deficit', 'wind_speed_10m', 'wind_speed_80m', 'wind_speed_120m',
        'wind_direction_10m', 'wind_gusts_10m', 'temperature_80m', 'temperature_120m',
        'soil_temperature_0cm', 'soil_temperature_6cm', 'soil_temperature_18cm', 'soil_temperature_54cm',
        'soil_moisture_0_to_1cm', 'soil_moisture_1_to_3cm', 'soil_moisture_3_to_9cm', 'soil_moisture_9_to_27cm', 'soil_moisture_27_to_81cm',
        'uv_index', 'uv_index_clear_sky', 'is_day', 'sunshine_duration', 'shortwave_radiation', 'direct_radiation',
        'diffuse_radiation', 'direct_normal_irradiance', 'terrestrial_radiation', 'freezing_level_height', 'cape',
    ];

    private const DIARIO = [
        'weather_code', 'temperature_2m_max', 'temperature_2m_min', 'apparent_temperature_max', 'apparent_temperature_min',
        'sunrise', 'sunset', 'daylight_duration', 'sunshine_duration', 'uv_index_max', 'uv_index_clear_sky_max',
        'precipitation_sum', 'rain_sum', 'showers_sum', 'snowfall_sum', 'precipitation_hours',
        'precipitation_probability_max', 'wind_speed_10m_max', 'wind_gusts_10m_max', 'wind_direction_10m_dominant',
        'shortwave_radiation_sum', 'et0_fao_evapotranspiration',
    ];

    private const CODIGOS = [
        0 => ['Despejado', 'sol'], 1 => ['Mayormente despejado', 'sol'], 2 => ['Parcialmente nublado', 'parcial'],
        3 => ['Nublado', 'nublado'], 45 => ['Neblina', 'niebla'], 48 => ['Neblina con escarcha', 'niebla'],
        51 => ['Llovizna ligera', 'llovizna'], 53 => ['Llovizna moderada', 'llovizna'], 55 => ['Llovizna intensa', 'llovizna'],
        56 => ['Llovizna helada', 'llovizna'], 57 => ['Llovizna helada intensa', 'llovizna'],
        61 => ['Lluvia ligera', 'lluvia'], 63 => ['Lluvia moderada', 'lluvia'], 65 => ['Lluvia fuerte', 'lluvia'],
        66 => ['Lluvia helada', 'lluvia'], 67 => ['Lluvia helada fuerte', 'lluvia'],
        71 => ['Nevada ligera', 'nieve'], 73 => ['Nevada moderada', 'nieve'], 75 => ['Nevada fuerte', 'nieve'], 77 => ['Granizo fino', 'nieve'],
        80 => ['Chubascos ligeros', 'lluvia'], 81 => ['Chubascos moderados', 'lluvia'], 82 => ['Chubascos fuertes', 'lluvia'],
        85 => ['Chubascos de nieve', 'nieve'], 86 => ['Chubascos de nieve fuertes', 'nieve'],
        95 => ['Tormenta eléctrica', 'tormenta'], 96 => ['Tormenta con granizo', 'tormenta'], 99 => ['Tormenta fuerte con granizo', 'tormenta'],
    ];

    /**
     * @return array|null null si no hay datos ni respaldo
     */
    public function paraCoordenadas(float $lat, float $lon, ?string $nombre = null, ?string $region = null, bool $extras = true): ?array
    {
        $clave = sprintf('om:full:%.2f:%.2f', $lat, $lon);

        $principal = Cache::get($clave);

        if (!$principal) {
            $principal = $this->pedirPrincipal($lat, $lon);

            if ($principal) {
                Cache::put($clave, $principal, now()->addMinutes(15));
                Cache::put($clave . ':stale', $principal, now()->addDays(2));
            } elseif ($viejo = Cache::get($clave . ':stale')) {
                $principal = $viejo;
                $principal['desactualizado'] = true;
            } else {
                return null;
            }
        }

        $principal['lugar']['nombre'] = $nombre ?: sprintf('%.3f, %.3f', $lat, $lon);
        $principal['lugar']['region'] = $region;
        // Calidad del aire e historial son pedidos aparte: se omiten donde no hacen falta (ficha de parcela).
        $principal['aire'] = $extras ? $this->aire($lat, $lon) : null;
        $principal['historial'] = $extras ? $this->historial($lat, $lon) : null;
        $principal['agro'] = $this->agro($principal);

        return $principal;
    }

    // =========================================================
    // PRONOSTICO PRINCIPAL
    // =========================================================

    private function pedirPrincipal(float $lat, float $lon): ?array
    {
        $vars = ['current' => self::ACTUAL, 'hourly' => self::HORARIO, 'daily' => self::DIARIO];

        try {
            // Si Open-Meteo rechaza una variable (cambian de nombre con el tiempo) se quita y se reintenta:
            // la pantalla pierde ese dato, pero no se cae.
            for ($intento = 0; $intento < 4; $intento++) {
                $r = Http::timeout(15)->retry(2, 600, throw: false)->get(self::URL, [
                    'latitude' => round($lat, 4), 'longitude' => round($lon, 4),
                    'current' => implode(',', $vars['current']),
                    'hourly' => implode(',', $vars['hourly']),
                    'daily' => implode(',', $vars['daily']),
                    'timezone' => 'auto', 'wind_speed_unit' => 'ms', 'past_days' => 3, 'forecast_days' => 14,
                ]);

                if ($r->successful() && $r->json('current')) {
                    return $this->normalizar($r->json());
                }

                if ($r->status() === 400 && preg_match('/invalid String value ([a-z0-9_]+)/i', (string) $r->json('reason'), $m)) {
                    logger()->warning("Open-Meteo no reconoce la variable {$m[1]}; se omite.");
                    foreach ($vars as $k => $lista) {
                        $vars[$k] = array_values(array_diff($lista, [$m[1]]));
                    }
                    continue;
                }

                return null;
            }
        } catch (Throwable $e) {
            report($e);
        }

        return null;
    }

    private function normalizar(array $j): array
    {
        $tz = $j['timezone'] ?? 'America/Lima';
        $cur = $j['current'];
        $ahora = Carbon::parse($cur['time'], $tz);
        $horaActual = $ahora->copy()->startOfHour();
        $ts = fn (string $iso) => Carbon::parse($iso, $tz)->timestamp;

        // ---- horas: 72 desde ahora ----
        $h = $j['hourly'];
        $tiempos = $h['time'];
        $inicio = 0;
        foreach ($tiempos as $i => $iso) {
            if ($ts($iso) >= $horaActual->timestamp) {
                $inicio = $i;
                break;
            }
        }

        $campos = array_keys($h);
        $horas = [];
        for ($i = $inicio; $i < min($inicio + 72, count($tiempos)); $i++) {
            $fila = ['ts' => $ts($tiempos[$i]), 'iso' => $tiempos[$i]];
            foreach ($campos as $c) {
                if ($c !== 'time') {
                    $fila[$c] = $h[$c][$i] ?? null;
                }
            }
            [$fila['descripcion'], $fila['icono']] = $this->describir((int) ($fila['weather_code'] ?? 0), (bool) ($fila['is_day'] ?? true));
            $horas[] = $fila;
        }

        // ---- dias ----
        $dd = $j['daily'];
        $dias = [];
        foreach ($dd['time'] as $i => $fecha) {
            $fila = ['fecha' => $fecha];
            foreach (array_keys($dd) as $c) {
                if ($c !== 'time') {
                    $fila[$c] = $dd[$c][$i] ?? null;
                }
            }
            [$fila['descripcion'], $fila['icono']] = $this->describir((int) ($fila['weather_code'] ?? 0), true);
            $fila['es_hoy'] = $fecha === $ahora->toDateString();
            $dias[] = $fila;
        }

        $hoy = collect($dias)->firstWhere('es_hoy', true) ?? $dias[0];
        [$desc, $icono] = $this->describir((int) $cur['weather_code'], (bool) ($cur['is_day'] ?? true));
        $primeraHora = $horas[0] ?? [];

        $actual = [
            'temp' => $cur['temperature_2m'], 'sensacion' => $cur['apparent_temperature'] ?? $cur['temperature_2m'], 'humedad' => $cur['relative_humidity_2m'],
            'es_dia' => (bool) ($cur['is_day'] ?? true), 'lluvia' => $cur['precipitation'] ?? 0, 'nubes' => $cur['cloud_cover'] ?? 0,
            'presion' => $cur['pressure_msl'] ?? null, 'presion_estacion' => $cur['surface_pressure'] ?? null,
            'viento' => $cur['wind_speed_10m'] ?? 0, 'viento_dir' => $cur['wind_direction_10m'] ?? 0, 'rafaga' => $cur['wind_gusts_10m'] ?? null,
            'codigo' => $cur['weather_code'], 'descripcion' => $desc, 'icono' => $icono,
            'punto_rocio' => $primeraHora['dew_point_2m'] ?? null, 'visibilidad_km' => isset($primeraHora['visibility']) ? round($primeraHora['visibility'] / 1000, 1) : null,
            'uv' => $primeraHora['uv_index'] ?? null, 'radiacion' => $primeraHora['shortwave_radiation'] ?? null,
            'nieve_profundidad' => $primeraHora['snow_depth'] ?? null, 'isoterma_0' => $primeraHora['freezing_level_height'] ?? null,
            'cape' => $primeraHora['cape'] ?? null, 'vpd' => $primeraHora['vapour_pressure_deficit'] ?? null,
            'tmin_hoy' => $hoy['temperature_2m_min'] ?? null, 'tmax_hoy' => $hoy['temperature_2m_max'] ?? null,
            'hora' => $ahora->format('H:i'), 'fecha' => $ahora->translatedFormat('l j \d\e F'),
        ];

        $sol = [
            'amanecer' => isset($hoy['sunrise']) ? Carbon::parse($hoy['sunrise'], $tz)->format('H:i') : '06:00',
            'atardecer' => isset($hoy['sunset']) ? Carbon::parse($hoy['sunset'], $tz)->format('H:i') : '18:00',
            'duracion_h' => round(($hoy['daylight_duration'] ?? 0) / 3600, 1),
            'horas_sol' => round(($hoy['sunshine_duration'] ?? 0) / 3600, 1),
            'ahora' => $ahora->format('H:i'),
        ];

        return [
            'lugar' => [
                'lat' => $j['latitude'], 'lon' => $j['longitude'], 'elevacion' => isset($j['elevation']) ? (int) round($j['elevation']) : null,
                'zona' => $tz, 'ahora_ts' => $ahora->timestamp,
            ],
            'consultado_en' => now()->toIso8601String(),
            'desactualizado' => false,
            'actual' => $actual,
            'sol' => $sol,
            'horas' => $horas,
            'dias' => $dias,
        ];
    }

    // =========================================================
    // CALIDAD DEL AIRE
    // =========================================================

    private function aire(float $lat, float $lon): ?array
    {
        return Cache::remember(sprintf('om:aire:%.2f:%.2f', $lat, $lon), 3600, function () use ($lat, $lon) {
            try {
                $r = Http::timeout(10)->retry(1, 400, throw: false)->get(self::URL_AIRE, [
                    'latitude' => round($lat, 4), 'longitude' => round($lon, 4), 'timezone' => 'auto', 'forecast_days' => 3,
                    'current' => 'us_aqi,european_aqi,pm10,pm2_5,carbon_monoxide,nitrogen_dioxide,sulphur_dioxide,ozone,aerosol_optical_depth,dust',
                    'hourly' => 'us_aqi,pm10,pm2_5,dust',
                ]);

                if (!$r->successful() || !$r->json('current')) {
                    return null;
                }

                $c = $r->json('current');
                $aqi = $c['us_aqi'] ?? null;

                return [
                    'aqi' => $aqi, 'aqi_europeo' => $c['european_aqi'] ?? null,
                    'pm10' => $c['pm10'] ?? null, 'pm25' => $c['pm2_5'] ?? null, 'co' => $c['carbon_monoxide'] ?? null,
                    'no2' => $c['nitrogen_dioxide'] ?? null, 'so2' => $c['sulphur_dioxide'] ?? null, 'o3' => $c['ozone'] ?? null,
                    'aod' => $c['aerosol_optical_depth'] ?? null, 'polvo' => $c['dust'] ?? null,
                    'categoria' => $this->categoriaAqi($aqi),
                    'horas' => collect($r->json('hourly.time') ?? [])->take(48)->map(fn ($t, $i) => [
                        'iso' => $t, 'aqi' => $r->json('hourly.us_aqi')[$i] ?? null, 'pm25' => $r->json('hourly.pm2_5')[$i] ?? null,
                    ])->values()->all(),
                ];
            } catch (Throwable $e) {
                report($e);

                return null;
            }
        });
    }

    private function categoriaAqi($aqi): array
    {
        return match (true) {
            $aqi === null => ['Sin dato', 'info'],
            $aqi <= 50 => ['Buena', 'ok'],
            $aqi <= 100 => ['Moderada', 'medio'],
            $aqi <= 150 => ['Dañina para sensibles', 'alto'],
            $aqi <= 200 => ['Dañina', 'critico'],
            default => ['Muy dañina', 'critico'],
        };
    }

    // =========================================================
    // HISTORIAL (60 dias) + mismo periodo del ano anterior
    // =========================================================

    private function historial(float $lat, float $lon): ?array
    {
        return Cache::remember(sprintf('om:hist:%.2f:%.2f', $lat, $lon), 6 * 3600, function () use ($lat, $lon) {
            try {
                $r = Http::timeout(15)->retry(1, 500, throw: false)->get(self::URL, [
                    'latitude' => round($lat, 4), 'longitude' => round($lon, 4), 'timezone' => 'auto',
                    'past_days' => 60, 'forecast_days' => 1,
                    'daily' => 'temperature_2m_max,temperature_2m_min,temperature_2m_mean,precipitation_sum,et0_fao_evapotranspiration,wind_speed_10m_max',
                    'wind_speed_unit' => 'ms',
                ]);

                if (!$r->successful() || !$r->json('daily.time')) {
                    return null;
                }

                $dd = $r->json('daily');
                $dias = [];
                foreach ($dd['time'] as $i => $f) {
                    $dias[] = [
                        'fecha' => $f, 'tmax' => $dd['temperature_2m_max'][$i], 'tmin' => $dd['temperature_2m_min'][$i],
                        'tmedia' => $dd['temperature_2m_mean'][$i], 'lluvia' => $dd['precipitation_sum'][$i] ?? 0,
                        'et0' => $dd['et0_fao_evapotranspiration'][$i] ?? null, 'viento' => $dd['wind_speed_10m_max'][$i] ?? null,
                    ];
                }

                $ultimos30 = array_slice($dias, -31, 30);
                $lluvia30 = round(array_sum(array_column($ultimos30, 'lluvia')), 1);
                $gdd = 0;
                foreach ($ultimos30 as $d) {
                    $gdd += max(0, (($d['tmax'] + $d['tmin']) / 2) - 10);
                }

                return [
                    'dias' => $dias,
                    'lluvia_30d' => $lluvia30,
                    'lluvia_30d_anterior' => $this->lluviaAnoAnterior($lat, $lon),
                    'dias_con_lluvia_30d' => count(array_filter($ultimos30, fn ($d) => ($d['lluvia'] ?? 0) >= 1)),
                    'dias_helada_60d' => count(array_filter($dias, fn ($d) => ($d['tmin'] ?? 99) <= 0)),
                    'tmedia_30d' => round(array_sum(array_column($ultimos30, 'tmedia')) / max(1, count($ultimos30)), 1),
                    'gdd_30d' => round($gdd),
                    'et0_30d' => round(array_sum(array_filter(array_column($ultimos30, 'et0'))), 1),
                ];
            } catch (Throwable $e) {
                report($e);

                return null;
            }
        });
    }

    private function lluviaAnoAnterior(float $lat, float $lon): ?float
    {
        try {
            $fin = Carbon::today()->subYear();
            $r = Http::timeout(15)->retry(1, 500, throw: false)->get(self::URL_ARCHIVO, [
                'latitude' => round($lat, 4), 'longitude' => round($lon, 4), 'timezone' => 'auto',
                'start_date' => $fin->copy()->subDays(29)->toDateString(), 'end_date' => $fin->toDateString(),
                'daily' => 'precipitation_sum',
            ]);

            return $r->successful() ? round(array_sum(array_filter($r->json('daily.precipitation_sum') ?? [])), 1) : null;
        } catch (Throwable $e) {
            return null;
        }
    }

    // =========================================================
    // INDICADORES AGRICOLAS DERIVADOS
    // =========================================================

    private function agro(array $p): array
    {
        $horas24 = array_slice($p['horas'], 0, 24);
        $horas48 = array_slice($p['horas'], 0, 48);

        $min = collect($horas24)->sortBy('temperature_2m')->first();
        $max = collect($horas24)->sortByDesc('temperature_2m')->first();

        // Delta T (temperatura menos bulbo humedo): fumigar entre 2 y 8, con poco viento y de dia.
        // Riego: de dia, sin lluvia ni viento fuerte y sin riesgo de helada (se evita regar de noche).
        $fumigar = [];
        $regar = [];
        $zona = $p['lugar']['zona'];
        foreach ($horas24 as $h) {
            $t = $h['temperature_2m']; $hr = $h['relative_humidity_2m']; $v = $h['wind_speed_10m'] ?? 0;
            if ($t === null || $hr === null) {
                continue;
            }
            $dt = round($t - $this->bulboHumedo($t, $hr), 1);
            $sinLluvia = ($h['precipitation_probability'] ?? 0) < 30 && ($h['precipitation'] ?? 0) < .2;
            $f = Carbon::createFromTimestamp($h['ts'], $zona);
            $esDia = (bool) ($h['is_day'] ?? true);

            if ($sinLluvia && $v <= 3.3 && $dt >= 2 && $dt <= 8 && $esDia) {
                $fumigar[] = ['ts' => $h['ts']];
            }
            if ($sinLluvia && $v <= 4.5 && $t >= 4 && $t <= 26 && $esDia && ($f->hour < 10 || $f->hour >= 16)) {
                $regar[] = ['ts' => $h['ts']];
            }
        }

        return [
            'min_24h' => $min ? ['valor' => $min['temperature_2m'], 'hora' => Carbon::createFromTimestamp($min['ts'], $p['lugar']['zona'])->format('H:i'), 'ts' => $min['ts']] : null,
            'max_24h' => $max ? ['valor' => $max['temperature_2m'], 'hora' => Carbon::createFromTimestamp($max['ts'], $p['lugar']['zona'])->format('H:i')] : null,
            'lluvia_24h' => round(array_sum(array_column($horas24, 'precipitation')), 1),
            'lluvia_48h' => round(array_sum(array_column($horas48, 'precipitation')), 1),
            'lluvia_72h' => round(array_sum(array_column($p['horas'], 'precipitation')), 1),
            'prob_lluvia_24h' => $horas24 ? (int) max(array_column($horas24, 'precipitation_probability')) : 0,
            'horas_helada_72h' => count(array_filter($p['horas'], fn ($h) => ($h['temperature_2m'] ?? 99) <= 0)),
            'horas_frio_72h' => count(array_filter($p['horas'], fn ($h) => ($h['temperature_2m'] ?? 99) <= 7)),
            'et0_24h' => round(array_sum(array_column($horas24, 'et0_fao_evapotranspiration')), 1),
            'ventanas_fumigar' => $this->agruparVentanas($fumigar, $zona),
            'ventanas_riego' => $this->agruparVentanas($regar, $zona),
            'humedad_suelo' => $this->perfilSuelo($p['horas'][0] ?? []),
        ];
    }

    private function perfilSuelo(array $h): array
    {
        $prof = [
            ['0 a 1 cm', 'soil_moisture_0_to_1cm', 'soil_temperature_0cm'], ['1 a 3 cm', 'soil_moisture_1_to_3cm', null],
            ['3 a 9 cm', 'soil_moisture_3_to_9cm', 'soil_temperature_6cm'], ['9 a 27 cm', 'soil_moisture_9_to_27cm', 'soil_temperature_18cm'],
            ['27 a 81 cm', 'soil_moisture_27_to_81cm', 'soil_temperature_54cm'],
        ];

        return array_map(fn ($f) => [
            'profundidad' => $f[0],
            'humedad' => isset($h[$f[1]]) ? round($h[$f[1]] * 100, 1) : null,       // m3/m3 a %
            'temperatura' => $f[2] && isset($h[$f[2]]) ? round($h[$f[2]], 1) : null,
        ], $prof);
    }

    /** Une horas consecutivas en rangos legibles: "hoy 16:00 a 17:59", "mañana 06:00 a 09:59". */
    private function agruparVentanas(array $horas, string $zona): array
    {
        $rangos = [];
        $ini = $fin = null;

        foreach ($horas as $h) {
            if ($ini !== null && $h['ts'] - $fin === 3600) {
                $fin = $h['ts'];
                continue;
            }
            if ($ini !== null) {
                $rangos[] = [$ini, $fin];
            }
            $ini = $fin = $h['ts'];
        }
        if ($ini !== null) {
            $rangos[] = [$ini, $fin];
        }

        return array_map(function ($r) use ($zona) {
            $a = Carbon::createFromTimestamp($r[0], $zona);
            $b = Carbon::createFromTimestamp($r[1], $zona);
            $dia = $a->isToday() ? 'hoy' : ($a->isTomorrow() ? 'mañana' : $a->translatedFormat('D j'));

            return $dia . ' ' . $a->format('H:i') . ' a ' . $b->copy()->addMinutes(59)->format('H:i');
        }, $rangos);
    }

    /** Temperatura de bulbo humedo (formula de Stull). */
    private function bulboHumedo(float $t, float $hr): float
    {
        return $t * atan(0.151977 * sqrt($hr + 8.313659)) + atan($t + $hr) - atan($hr - 1.676331)
            + 0.00391838 * pow($hr, 1.5) * atan(0.023101 * $hr) - 4.686035;
    }

    private function describir(int $codigo, bool $esDia): array
    {
        [$texto, $icono] = self::CODIGOS[$codigo] ?? ['Variable', 'parcial'];

        return [$texto, (!$esDia && $icono === 'sol') ? 'luna' : $icono];
    }
}
