<?php

namespace App\Http\Controllers;

use App\Models\Cultivo;
use App\Models\Parcela;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Throwable;

/**
 * Portada publica con datos reales: el clima de ahora en zonas agricolas de costa, sierra y selva
 * (una sola consulta a Open-Meteo para todas) y el tamano real del catalogo.
 */
class InicioController extends Controller
{
    public const LUGARES = [
        ['Puno', 'Sierra sur', -15.8402, -70.0219],
        ['Cusco', 'Sierra', -13.5319, -71.9675],
        ['Huancayo', 'Valle del Mantaro', -12.0658, -75.2049],
        ['Ica', 'Costa', -14.0678, -75.7286],
        ['Piura', 'Costa norte', -5.1945, -80.6328],
        ['Tarapoto', 'Selva', -6.4900, -76.3700],
    ];

    private const ICONOS = [0 => 'sol', 1 => 'sol', 2 => 'parcial', 3 => 'nublado', 45 => 'niebla', 48 => 'niebla'];

    public function __invoke()
    {
        return view('welcome', [
            'lugares' => $this->climaLugares(),
            'cultivos' => rescue(fn () => Cultivo::count(), 0, false),
            'parcelas' => rescue(fn () => Parcela::count(), 0, false),
        ]);
    }

    private function climaLugares(): ?array
    {
        $guardado = Cache::get('inicio:clima');
        if ($guardado !== null) {
            return $guardado ?: null;
        }

        try {
            $r = Http::timeout(6)->get('https://api.open-meteo.com/v1/forecast', [
                'latitude' => implode(',', array_column(self::LUGARES, 2)),
                'longitude' => implode(',', array_column(self::LUGARES, 3)),
                'current' => 'temperature_2m,weather_code,is_day,relative_humidity_2m',
                'daily' => 'temperature_2m_min,temperature_2m_max,precipitation_sum,precipitation_probability_max,et0_fao_evapotranspiration',
                'timezone' => 'auto', 'forecast_days' => 1,
            ]);

            if (!$r->successful()) {
                throw new \RuntimeException('Open-Meteo ' . $r->status());
            }

            $j = $r->json();
            $lista = array_is_list($j) ? $j : [$j];
            $datos = [];

            foreach (self::LUGARES as $i => [$nombre, $zona]) {
                $x = $lista[$i] ?? null;
                if (!$x || !isset($x['current'])) {
                    continue;
                }
                $cod = (int) $x['current']['weather_code'];
                $icono = self::ICONOS[$cod] ?? ($cod >= 95 ? 'tormenta' : ($cod >= 71 && $cod <= 86 && !in_array($cod, [80, 81, 82]) ? 'nieve' : ($cod >= 61 ? 'lluvia' : ($cod >= 51 ? 'llovizna' : 'parcial'))));
                if ($icono === 'sol' && !$x['current']['is_day']) {
                    $icono = 'luna';
                }
                $min = $x['daily']['temperature_2m_min'][0] ?? null;
                $lluvia = $x['daily']['precipitation_sum'][0] ?? 0;
                $et0 = $x['daily']['et0_fao_evapotranspiration'][0] ?? null;

                $estado = match (true) {
                    $min !== null && $min <= 0 => ['critico', 'Helada: mínima de ' . number_format($min, 1, ',', '') . ' °C'],
                    $min !== null && $min <= 3 => ['alto', 'Noche fría: mínima de ' . number_format($min, 1, ',', '') . ' °C'],
                    $lluvia >= 5 => ['medio', 'Lluvia: ' . number_format($lluvia, 1, ',', '') . ' mm hoy'],
                    $et0 !== null && $et0 >= 5 => ['alto', 'Día seco, ET₀ de ' . number_format($et0, 1, ',', '') . ' mm'],
                    default => ['ok', 'Buen día para el campo'],
                };

                $datos[] = [
                    'nombre' => $nombre, 'zona' => $zona, 'temp' => $x['current']['temperature_2m'], 'humedad' => $x['current']['relative_humidity_2m'],
                    'icono' => $icono, 'min' => $min, 'max' => $x['daily']['temperature_2m_max'][0] ?? null, 'lluvia' => $lluvia,
                    'prob' => $x['daily']['precipitation_probability_max'][0] ?? null, 'et0' => $et0, 'estado' => $estado,
                ];
            }

            Cache::put('inicio:clima', $datos, now()->addMinutes(15));

            return $datos ?: null;
        } catch (Throwable $e) {
            Cache::put('inicio:clima', [], now()->addMinutes(5));   // sin internet: no volver a esperar en cada visita

            return null;
        }
    }
}
