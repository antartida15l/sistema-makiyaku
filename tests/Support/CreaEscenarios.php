<?php

namespace Tests\Support;

use App\Models\ClimaHistorial;
use App\Models\Cultivo;
use App\Models\Parcela;
use App\Models\Ubicacion;
use App\Models\User;

/**
 * Datos de prueba: un cultivo de sierra, una parcela y un registro de clima.
 */
trait CreaEscenarios
{
    protected function crearCultivo(array $extra = []): Cultivo
    {
        return Cultivo::create($extra + [
            'nombreCultivo' => 'Papa ' . uniqid(),
            'tipo' => 'Tubérculos',
            'region' => 'Sierra',
            'frecuenciaRiegoMin' => 5,
            'frecuenciaRiegoMax' => 7,
            'litrosPorM2Dia' => 3.0,
            'humedadOptimaMin' => 60,
            'humedadOptimaMax' => 80,
            'sensibilidadSequia' => 'Media',
            'diasCriticosSinAgua' => 12,
            'etapaMayorConsumo' => 'Tuberizacion',
            'reduccionConHidrogel' => 25,
            'tempCriticaMinima' => -2,
            'tempCriticaMaxima' => 30,
            'sensibilidadHeladas' => 'Alta',
            'cicloProductivo' => 150,
        ]);
    }

    protected function crearParcela(array $extra = [], ?Cultivo $cultivo = null): Parcela
    {
        $user = User::factory()->create();
        $cultivo ??= $this->crearCultivo();
        $ubicacion = Ubicacion::create([
            'distrito' => 'Ayaviri',
            'provincia' => 'Melgar',
            'region' => 'Sierra',
            'coordenadaLatitud' => -14.88,
            'coordenadaLongitud' => -70.58,
        ]);

        return Parcela::create($extra + [
            'user_id' => $user->id,
            'nombre' => 'Lote Alto',
            'area' => 100,
            'estado' => 'activa',
            'idUbicacion' => $ubicacion->idUbicacion,
            'idCultivo' => $cultivo->idCultivo,
            'ultimo_riego' => now()->subDays(3),
            'frecuencia_riego_dias' => 6,
            'proximo_riego_estimado' => now()->addDays(3)->toDateString(),
        ]);
    }

    protected function crearClima(Parcela $parcela, array $extra = []): ClimaHistorial
    {
        return ClimaHistorial::create($extra + [
            'parcela_id' => $parcela->id,
            'temperatura' => 15,
            'humedad' => 45,
            'viento_velocidad' => 2,
            'probabilidad_lluvia_24h' => 5,
            'lluvia_24h' => 0,
            'lluvia_72h_mm' => 0,
            'et0_mm' => 4.0,
            'temp_min_prevista' => 3,
            'temp_min_suelo' => 2,
            'temp_max_prevista' => 20,
            'condicion_principal' => 'Clear',
            'descripcion' => 'Despejado',
            'fecha_registro' => now(),
            'tipo_actualizacion' => 'automatica',
        ]);
    }
    /**
     * Respuesta realista de Open-Meteo para pruebas (3 dias pasados + 5 futuros).
     * $hora es la hora local "actual" (0-23).
     */
    protected function respuestaOpenMeteo(array $op = []): array
    {
        $op += ['temp' => 14.0, 'noche' => 1.0, 'dia' => 20.0, 'lluvia_pron' => 0.0, 'prob' => 10, 'lluvia_pasada' => 0.0, 'et0' => 4.2, 'nubes' => 20, 'viento' => 1.5];

        $tz = 'America/Lima';
        $ahora = \Carbon\Carbon::now($tz)->startOfHour();
        $inicio = $ahora->copy()->subDays(3)->startOfDay();
        $time = $temp = $hum = $pop = $prec = $cod = $nub = $vie = $dir = $raf = $et0 = [];

        for ($t = $inicio->copy(); $t->lt($ahora->copy()->addDays(5)->startOfDay()); $t->addHour()) {
            $h = (int) $t->format('G');
            $futuro = $t->gte($ahora);
            $time[] = $t->format('Y-m-d\TH:i');
            // curva diaria: minima a las 5, maxima a las 15
            $curva = $op['noche'] + ($op['dia'] - $op['noche']) * max(0, sin(($h - 5) / 20 * M_PI));
            $temp[] = round($curva, 1);
            $hum[] = 55;
            $llueve = $futuro && $op['lluvia_pron'] > 0 && $t->lt($ahora->copy()->addHours(8));
            $prec[] = $llueve ? round($op['lluvia_pron'] / 8, 2) : ((!$futuro && $op['lluvia_pasada'] > 0 && $t->gte($ahora->copy()->subHours(24))) ? round($op['lluvia_pasada'] / 24, 2) : 0);
            $pop[] = $llueve ? $op['prob'] : ($futuro ? 5 : 0);
            $cod[] = $llueve ? 61 : 1;
            $nub[] = $op['nubes'];
            $vie[] = $op['viento'];
            $dir[] = 180;
            $raf[] = $op['viento'] * 1.6;
            $et0[] = 0.2;
        }

        $dias = [];
        for ($d = 0; $d < 8; $d++) {
            $dias[] = $inicio->copy()->addDays($d)->toDateString();
        }

        return [
            'latitude' => -14.88, 'longitude' => -70.58, 'elevation' => 3928,
            'timezone' => $tz, 'utc_offset_seconds' => -18000,
            'current' => [
                'time' => $ahora->format('Y-m-d\TH:i'), 'temperature_2m' => $op['temp'],
                'relative_humidity_2m' => 52, 'apparent_temperature' => $op['temp'] - 1,
                'precipitation' => 0, 'rain' => 0, 'weather_code' => 1, 'cloud_cover' => $op['nubes'],
                'pressure_msl' => 1012, 'wind_speed_10m' => $op['viento'], 'wind_direction_10m' => 180,
                'wind_gusts_10m' => 4.2, 'is_day' => 1,
            ],
            'hourly' => [
                'time' => $time, 'temperature_2m' => $temp, 'relative_humidity_2m' => $hum,
                'precipitation_probability' => $pop, 'precipitation' => $prec, 'weather_code' => $cod,
                'cloud_cover' => $nub, 'wind_speed_10m' => $vie, 'wind_direction_10m' => $dir,
                'wind_gusts_10m' => $raf, 'et0_fao_evapotranspiration' => $et0,
            ],
            'daily' => [
                'time' => $dias,
                'temperature_2m_max' => array_fill(0, 8, $op['dia']),
                'temperature_2m_min' => array_fill(0, 8, $op['noche']),
                'precipitation_sum' => array_fill(0, 8, 0),
                'precipitation_probability_max' => array_fill(0, 8, 10),
                'et0_fao_evapotranspiration' => array_fill(0, 8, $op['et0']),
                'sunrise' => array_map(fn ($d) => $d . 'T06:05', $dias),
                'sunset' => array_map(fn ($d) => $d . 'T17:50', $dias),
                'weather_code' => array_fill(0, 8, 1),
            ],
        ];
    }
}
