<?php

namespace Tests\Support;

use Carbon\Carbon;
use Illuminate\Support\Facades\Http;

/**
 * Respuestas realistas de Open-Meteo (pronostico completo, aire, historial) para pruebas y demos sin internet.
 * Clima de sierra a ~3.900 m: noches frias, dias secos, lluvia por la tarde.
 */
class OpenMeteoFalso
{
    public static function activar(array $op = []): void
    {
        Http::fake([
            'air-quality-api.open-meteo.com/*' => Http::response(self::aire()),
            'archive-api.open-meteo.com/*' => Http::response(self::archivo()),
            'geocoding-api.open-meteo.com/*' => Http::response(['results' => [[
                'name' => 'Puno', 'latitude' => -15.84, 'longitude' => -70.02, 'country_code' => 'PE', 'country' => 'Perú', 'admin1' => 'Puno', 'elevation' => 3830,
            ]]]),
            'api.open-meteo.com/*' => function ($req) use ($op) {
                parse_str(parse_url($req->url(), PHP_URL_QUERY) ?? '', $q);

                return Http::response(isset($q['past_days']) && (int) $q['past_days'] === 60 ? self::historial() : self::pronostico($op));
            },
            'nominatim.openstreetmap.org/*' => Http::response([]),
        ]);
    }

    public static function pronostico(array $op = []): array
    {
        $op += ['noche' => -1.5, 'dia' => 18.0, 'lluvia_dia' => 1, 'et0' => 4.6, 'viento' => 3.0];
        $tz = 'America/Lima';
        $ahora = Carbon::now($tz)->startOfHour();
        $inicio = $ahora->copy()->subDays(3)->startOfDay();
        $fin = $ahora->copy()->addDays(14)->startOfDay();

        $h = array_fill_keys(['time', 'temperature_2m', 'relative_humidity_2m', 'dew_point_2m', 'apparent_temperature', 'precipitation_probability', 'precipitation', 'rain', 'showers', 'snowfall', 'snow_depth', 'weather_code', 'pressure_msl', 'surface_pressure', 'cloud_cover', 'cloud_cover_low', 'cloud_cover_mid', 'cloud_cover_high', 'visibility', 'evapotranspiration', 'et0_fao_evapotranspiration', 'vapour_pressure_deficit', 'wind_speed_10m', 'wind_speed_80m', 'wind_speed_120m', 'wind_direction_10m', 'wind_gusts_10m', 'temperature_80m', 'temperature_120m', 'soil_temperature_0cm', 'soil_temperature_6cm', 'soil_temperature_18cm', 'soil_temperature_54cm', 'soil_moisture_0_to_1cm', 'soil_moisture_1_to_3cm', 'soil_moisture_3_to_9cm', 'soil_moisture_9_to_27cm', 'soil_moisture_27_to_81cm', 'uv_index', 'uv_index_clear_sky', 'is_day', 'sunshine_duration', 'shortwave_radiation', 'direct_radiation', 'diffuse_radiation', 'direct_normal_irradiance', 'terrestrial_radiation', 'freezing_level_height', 'cape'], []);

        for ($t = $inicio->copy(); $t->lt($fin); $t->addHour()) {
            $hr = (int) $t->format('G');
            $d = (int) $t->diffInDays($inicio->copy()->startOfDay());
            $sol = max(0, sin(($hr - 6) / 12 * M_PI));
            $curva = $op['noche'] + ($op['dia'] - $op['noche']) * max(0, sin(($hr - 5) / 20 * M_PI));
            $llueve = $hr >= 15 && $hr <= 19 && ($d % 3 === 1) && $op['lluvia_dia'];
            $nubes = $llueve ? 85 : (int) (25 + 30 * sin($d + $hr / 7));
            $h['time'][] = $t->format('Y-m-d\TH:i');
            $h['temperature_2m'][] = round($curva + sin($d) * 1.2, 1);
            $h['relative_humidity_2m'][] = (int) (70 - 40 * $sol + ($llueve ? 20 : 0));
            $h['dew_point_2m'][] = round($curva - 7 + ($llueve ? 5 : 0), 1);
            $h['apparent_temperature'][] = round($curva - 2.5, 1);
            $h['precipitation_probability'][] = $llueve ? 70 + $hr : ($hr > 12 && $hr < 21 ? 15 : 5);
            $h['precipitation'][] = $llueve ? 1.2 : 0;
            $h['rain'][] = $llueve ? 1.2 : 0;
            $h['showers'][] = 0; $h['snowfall'][] = 0; $h['snow_depth'][] = 0;
            $h['weather_code'][] = $llueve ? 63 : ($nubes > 60 ? 3 : ($nubes > 40 ? 2 : 1));
            $h['pressure_msl'][] = round(1013 + 3 * sin($d / 2 + $hr / 24), 1);
            $h['surface_pressure'][] = round(640 + 2 * sin($d / 2), 1);
            $h['cloud_cover'][] = $nubes; $h['cloud_cover_low'][] = (int) ($nubes * .4); $h['cloud_cover_mid'][] = (int) ($nubes * .5); $h['cloud_cover_high'][] = (int) ($nubes * .7);
            $h['visibility'][] = $llueve ? 6000 : 24000;
            $h['evapotranspiration'][] = round(.28 * $sol, 2);
            $h['et0_fao_evapotranspiration'][] = round($op['et0'] / 9 * $sol, 3);
            $h['vapour_pressure_deficit'][] = round(.2 + 1.6 * $sol, 2);
            $v = $op['viento'] * (.6 + .9 * $sol);
            $h['wind_speed_10m'][] = round($v, 1); $h['wind_speed_80m'][] = round($v * 1.5, 1); $h['wind_speed_120m'][] = round($v * 1.7, 1);
            $h['wind_direction_10m'][] = (int) (200 + 40 * sin($d + $hr / 12)); $h['wind_gusts_10m'][] = round($v * 1.8, 1);
            $h['temperature_80m'][] = round($curva - .6, 1); $h['temperature_120m'][] = round($curva - .9, 1);
            $h['soil_temperature_0cm'][] = round($curva + 3 * $sol - 1, 1); $h['soil_temperature_6cm'][] = round(7 + 5 * $sol, 1);
            $h['soil_temperature_18cm'][] = round(9 + 2 * $sol, 1); $h['soil_temperature_54cm'][] = 10.4;
            $h['soil_moisture_0_to_1cm'][] = round(.12 + ($llueve ? .06 : 0), 3); $h['soil_moisture_1_to_3cm'][] = .14; $h['soil_moisture_3_to_9cm'][] = .17;
            $h['soil_moisture_9_to_27cm'][] = .21; $h['soil_moisture_27_to_81cm'][] = .24;
            $h['uv_index'][] = round(11.5 * $sol * (1 - $nubes / 220), 1); $h['uv_index_clear_sky'][] = round(12.5 * $sol, 1);
            $h['is_day'][] = $sol > 0 ? 1 : 0; $h['sunshine_duration'][] = round(3600 * $sol * (1 - $nubes / 130)); 
            $h['shortwave_radiation'][] = round(900 * $sol * (1 - $nubes / 160)); $h['direct_radiation'][] = round(650 * $sol * (1 - $nubes / 100)); $h['diffuse_radiation'][] = round(180 * $sol);
            $h['direct_normal_irradiance'][] = round(800 * $sol * (1 - $nubes / 100)); $h['terrestrial_radiation'][] = round(1050 * $sol);
            $h['freezing_level_height'][] = (int) (4900 + 350 * sin($d)); $h['cape'][] = $llueve ? 420 : 30;
        }

        $dias = [];
        for ($t = $inicio->copy(); $t->lt($fin); $t->addDay()) $dias[] = $t->toDateString();
        $n = count($dias);
        $col = fn (callable $f) => array_map($f, range(0, $n - 1));

        return [
            'latitude' => -14.88, 'longitude' => -70.58, 'elevation' => 3945.0, 'timezone' => $tz, 'utc_offset_seconds' => -18000,
            'current' => [
                'time' => $ahora->format('Y-m-d\TH:i'), 'temperature_2m' => 11.3, 'relative_humidity_2m' => 52, 'apparent_temperature' => 9.1, 'is_day' => 1,
                'precipitation' => 0, 'rain' => 0, 'showers' => 0, 'snowfall' => 0, 'weather_code' => 2, 'cloud_cover' => 45, 'pressure_msl' => 1014.2,
                'surface_pressure' => 641.5, 'wind_speed_10m' => 3.4, 'wind_direction_10m' => 215, 'wind_gusts_10m' => 6.8,
            ],
            'hourly' => $h,
            'daily' => [
                'time' => $dias,
                'weather_code' => $col(fn ($i) => [1, 2, 63, 3, 1, 2, 61, 1, 2, 3, 80, 1, 2, 2, 1, 1, 2][$i % 17]),
                'temperature_2m_max' => $col(fn ($i) => round($op['dia'] + 2 * sin($i), 1)), 'temperature_2m_min' => $col(fn ($i) => round($op['noche'] + 2.5 * cos($i / 2), 1)),
                'apparent_temperature_max' => $col(fn ($i) => round($op['dia'] - 1.5 + sin($i), 1)), 'apparent_temperature_min' => $col(fn ($i) => round($op['noche'] - 3, 1)),
                'sunrise' => array_map(fn ($d) => $d . 'T05:58', $dias), 'sunset' => array_map(fn ($d) => $d . 'T17:41', $dias),
                'daylight_duration' => array_fill(0, $n, 42150.0), 'sunshine_duration' => $col(fn ($i) => 30000 + 4000 * sin($i)),
                'uv_index_max' => $col(fn ($i) => round(10 + 2 * sin($i), 1)), 'uv_index_clear_sky_max' => array_fill(0, $n, 12.4),
                'precipitation_sum' => $col(fn ($i) => $i % 3 === 1 ? 6.0 : 0.0), 'rain_sum' => $col(fn ($i) => $i % 3 === 1 ? 6.0 : 0.0),
                'showers_sum' => array_fill(0, $n, 0.0), 'snowfall_sum' => array_fill(0, $n, 0.0), 'precipitation_hours' => $col(fn ($i) => $i % 3 === 1 ? 5.0 : 0.0),
                'precipitation_probability_max' => $col(fn ($i) => $i % 3 === 1 ? 80 : 10),
                'wind_speed_10m_max' => $col(fn ($i) => round(6 + 2 * sin($i), 1)), 'wind_gusts_10m_max' => $col(fn ($i) => round(11 + 3 * sin($i), 1)),
                'wind_direction_10m_dominant' => $col(fn ($i) => (200 + $i * 9) % 360), 'shortwave_radiation_sum' => $col(fn ($i) => round(24 + 3 * sin($i), 1)),
                'et0_fao_evapotranspiration' => $col(fn ($i) => round($op['et0'] + .6 * sin($i), 2)),
            ],
        ];
    }

    public static function historial(): array
    {
        $dias = [];
        for ($i = 60; $i >= 0; $i--) $dias[] = Carbon::today()->subDays($i)->toDateString();
        $n = count($dias);

        return ['latitude' => -14.88, 'longitude' => -70.58, 'timezone' => 'America/Lima', 'daily' => [
            'time' => $dias,
            'temperature_2m_max' => array_map(fn ($i) => round(17 + 3 * sin($i / 5), 1), range(0, $n - 1)),
            'temperature_2m_min' => array_map(fn ($i) => round(-1 + 3 * cos($i / 6), 1), range(0, $n - 1)),
            'temperature_2m_mean' => array_map(fn ($i) => round(8 + 2 * sin($i / 5), 1), range(0, $n - 1)),
            'precipitation_sum' => array_map(fn ($i) => $i % 4 === 0 ? round(3 + 4 * abs(sin($i)), 1) : 0.0, range(0, $n - 1)),
            'et0_fao_evapotranspiration' => array_map(fn ($i) => round(4.2 + .8 * sin($i / 4), 2), range(0, $n - 1)),
            'wind_speed_10m_max' => array_map(fn ($i) => round(6 + 2 * sin($i), 1), range(0, $n - 1)),
        ]];
    }

    public static function archivo(): array
    {
        return ['daily' => ['time' => [], 'precipitation_sum' => array_map(fn ($i) => $i % 5 === 0 ? 7.5 : 0.0, range(0, 29))]];
    }

    public static function aire(): array
    {
        $t = []; for ($i = 0; $i < 72; $i++) $t[] = Carbon::now('America/Lima')->startOfHour()->addHours($i)->format('Y-m-d\TH:i');

        return [
            'current' => ['us_aqi' => 38, 'european_aqi' => 24, 'pm10' => 14.2, 'pm2_5' => 7.1, 'carbon_monoxide' => 120, 'nitrogen_dioxide' => 6.5, 'sulphur_dioxide' => 2.1, 'ozone' => 62, 'aerosol_optical_depth' => 0.08, 'dust' => 3.2],
            'hourly' => ['time' => $t, 'us_aqi' => array_map(fn ($i) => 30 + (int) (15 * sin($i / 6)), range(0, 71)), 'pm10' => array_fill(0, 72, 14.0), 'pm2_5' => array_map(fn ($i) => round(7 + 3 * sin($i / 8), 1), range(0, 71)), 'dust' => array_fill(0, 72, 3.0)],
        ];
    }
}
