<?php

namespace App\Http\Controllers;

use App\Services\OpenWeatherService;
use App\Services\GeocodingService;
use Illuminate\Http\Request;

class ClimaController extends Controller
{
    protected $weatherService;
    protected $geocodingService;

    public function __construct(
        OpenWeatherService $weatherService,
        GeocodingService $geocodingService
    ) {
        $this->weatherService = $weatherService;
        $this->geocodingService = $geocodingService;
    }

    /**
     * Muestra el clima actual de una ciudad con gráficos
     */
    public function index(Request $request)
    {
        // Obtener parámetros
        $ciudad = $request->input('ciudad', 'Puno');
        $pais = $request->input('pais', 'PE');
        $lat = $request->input('lat');
        $lon = $request->input('lon');

        // Si vienen coordenadas, usarlas directamente
        if ($lat && $lon) {
            $climaActual = $this->weatherService->getCurrentWeatherByCoordinates($lat, $lon);
            $pronostico = $this->weatherService->getForecastByCoordinates($lat, $lon);
        } else {
            // Obtener datos del clima por ciudad
            $climaActual = $this->weatherService->getCurrentWeather($ciudad, $pais);
            $pronostico = $this->weatherService->getForecast($ciudad, $pais);
        }

        // Si no se encontraron datos
        if (!$climaActual) {
            return view('clima.index', [
                'error' => 'No se pudieron obtener datos del clima para ' . $ciudad,
                'ciudad' => $ciudad,
                'pais' => $pais,
                'ciudades_principales' => $this->geocodingService->getCiudadesPrincipalesPeru(),
            ]);
        }

        // Preparar datos adicionales
        $pronosticoHora = $this->prepararPronosticoHorario($pronostico);
        $pronosticoDiario = $this->prepararPronosticoDiario($pronostico);
        $alertas = $this->generarAlertas($climaActual, $pronostico);
        $graficos = $this->prepararDatosGraficos($pronostico);

        return view('clima.index', [
            'datos' => $climaActual,
            'pronostico' => $pronostico['list'] ?? [],
            'pronostico_horario' => $pronosticoHora,
            'pronostico_diario' => $pronosticoDiario,
            'alertas' => $alertas,
            'ciudad' => $climaActual['name'],
            'pais' => $climaActual['country'],
            'graficos' => $graficos,
            'ciudades_principales' => $this->geocodingService->getCiudadesPrincipalesPeru(),
        ]);
    }

    /**
     * Preparar pronóstico horario (próximas 24 horas)
     */
    protected function prepararPronosticoHorario(?array $pronostico): array
    {
        if (!$pronostico || empty($pronostico['list'])) {
            return [];
        }

        $horario = [];
        $datos = array_slice($pronostico['list'], 0, 8); // 8 registros = 24 horas

        foreach ($datos as $item) {
            $horario[] = [
                'hora' => date('H:i', $item['dt']),
                'hora_corta' => date('H', $item['dt']) . 'h',
                'temp' => $item['temp'],
                'icon' => $item['weather_icon'],
                'icon_url' => $item['icon_url'],
                'pop' => $item['pop'],
                'lluvia' => $item['rain_3h'],
                'viento' => $item['wind_speed'],
                'descripcion' => $item['weather_description'],
            ];
        }

        return $horario;
    }

    /**
     * Preparar pronóstico diario (próximos 5 días)
     */
    protected function prepararPronosticoDiario(?array $pronostico): array
    {
        if (!$pronostico || empty($pronostico['list'])) {
            return [];
        }

        $dias = [];
        $diaActual = null;

        foreach ($pronostico['list'] as $item) {
            $fecha = date('Y-m-d', $item['dt']);
            
            if ($fecha === $diaActual) {
                continue; // Solo queremos un registro por día (mediodía aprox)
            }

            // Buscar el registro más cercano al mediodía (12:00)
            $hora = (int)date('H', $item['dt']);
            if ($hora >= 12 && $hora <= 15) {
                $diaActual = $fecha;
                
                // Calcular temp max/min del día
                $tempsDia = array_filter($pronostico['list'], function($i) use ($fecha) {
                    return date('Y-m-d', $i['dt']) === $fecha;
                });
                
                $tempMax = max(array_column($tempsDia, 'temp_max'));
                $tempMin = min(array_column($tempsDia, 'temp_min'));
                
                // Calcular probabilidad máxima de lluvia del día
                $popMax = max(array_column($tempsDia, 'pop'));
                
                $dias[] = [
                    'fecha' => $fecha,
                    'dia_semana' => $this->getDiaSemana($item['dt']),
                    'dia_mes' => date('d/m', $item['dt']),
                    'temp' => $item['temp'],
                    'temp_max' => $tempMax,
                    'temp_min' => $tempMin,
                    'icon' => $item['weather_icon'],
                    'icon_url' => $item['icon_url'],
                    'descripcion' => $item['weather_description'],
                    'pop' => $popMax,
                    'humedad' => $item['humidity'],
                    'viento' => $item['wind_speed'],
                ];
                
                if (count($dias) >= 5) break;
            }
        }

        return $dias;
    }

    /**
     * Generar alertas inteligentes
     */
    protected function generarAlertas(array $climaActual, ?array $pronostico): array
    {
        $alertas = [];

        // Alerta de lluvia próxima
        if ($pronostico && !empty($pronostico['list'])) {
            $lluviaProxima = null;
            $minutos = 0;
            
            foreach (array_slice($pronostico['list'], 0, 4) as $i => $item) {
                $minutos = ($i + 1) * 180; // cada registro = 3 horas = 180 min
                
                if ($item['pop'] > 50 || $item['rain_3h'] > 0) {
                    $lluviaProxima = $item;
                    break;
                }
            }

            if ($lluviaProxima) {
                $horas = floor($minutos / 60);
                $alertas[] = [
                    'tipo' => 'lluvia',
                    'icono' => '🌧️',
                    'clase' => 'info',
                    'titulo' => 'Lluvia próxima',
                    'mensaje' => sprintf(
                        'Se espera lluvia en aproximadamente %s con %d%% de probabilidad',
                        $horas > 0 ? "{$horas}h" : "{$minutos}min",
                        $lluviaProxima['pop']
                    ),
                ];
            } else {
                // Mensaje positivo si no hay lluvia
                $alertas[] = [
                    'tipo' => 'sin-lluvia',
                    'icono' => '☀️',
                    'clase' => 'success',
                    'titulo' => 'Sin lluvia próxima',
                    'mensaje' => 'No se espera precipitación en las próximas 12 horas',
                ];
            }
        }

        // Alerta de temperatura extrema
        if ($climaActual['temp'] < 5) {
            $alertas[] = [
                'tipo' => 'frio',
                'icono' => '🥶',
                'clase' => 'warning',
                'titulo' => 'Temperatura baja',
                'mensaje' => sprintf('Temperatura actual de %s°C. Abrígate bien.', $climaActual['temp']),
            ];
        } elseif ($climaActual['temp'] > 30) {
            $alertas[] = [
                'tipo' => 'calor',
                'icono' => '🔥',
                'clase' => 'warning',
                'titulo' => 'Temperatura alta',
                'mensaje' => sprintf('Temperatura actual de %s°C. Mantente hidratado.', $climaActual['temp']),
            ];
        }

        // Alerta de viento fuerte
        if ($climaActual['wind_speed'] > 10) {
            $alertas[] = [
                'tipo' => 'viento',
                'icono' => '💨',
                'clase' => 'warning',
                'titulo' => 'Viento fuerte',
                'mensaje' => sprintf('Velocidad del viento: %s m/s. Ten precaución.', $climaActual['wind_speed']),
            ];
        }

        // Alerta de humedad alta
        if ($climaActual['humidity'] > 85) {
            $alertas[] = [
                'tipo' => 'humedad',
                'icono' => '💧',
                'clase' => 'info',
                'titulo' => 'Humedad alta',
                'mensaje' => sprintf('Humedad relativa del %d%%. Ambiente muy húmedo.', $climaActual['humidity']),
            ];
        }

        return $alertas;
    }

    /**
     * Obtener día de la semana en español
     */
    protected function getDiaSemana(int $timestamp): string
    {
        $dias = ['Dom', 'Lun', 'Mar', 'Mié', 'Jue', 'Vie', 'Sáb'];
        return $dias[date('w', $timestamp)];
    }

    /**
     * Buscar ciudades por nombre (AJAX - autocompletado)
     */
    public function buscarCiudades(Request $request)
    {
        $query = $request->input('q', '');
        
        if (strlen($query) < 2) {
            return response()->json([]);
        }

        $ciudades = $this->geocodingService->searchCities($query, 10);

        return response()->json($ciudades);
    }

    /**
     * Obtener clima por coordenadas (AJAX)
     */
    public function climaPorCoordenadas(Request $request)
    {
        $lat = $request->input('lat');
        $lon = $request->input('lon');

        if (!$lat || !$lon) {
            return response()->json([
                'error' => 'Se requieren coordenadas (lat y lon)'
            ], 400);
        }

        $clima = $this->weatherService->getCurrentWeatherByCoordinates($lat, $lon);

        if (!$clima) {
            return response()->json([
                'error' => 'No se pudo obtener el clima para esas coordenadas'
            ], 404);
        }

        return response()->json($clima);
    }

    /**
     * Actualizar clima (forzar refresco - AJAX)
     */
    public function refrescarClima(Request $request)
    {
        $ciudad = $request->input('ciudad');
        $pais = $request->input('pais', '');

        if (!$ciudad) {
            return response()->json([
                'error' => 'Se requiere el nombre de la ciudad'
            ], 400);
        }

        $clima = $this->weatherService->forceRefresh($ciudad, $pais);

        if (!$clima) {
            return response()->json([
                'error' => 'No se pudo actualizar el clima'
            ], 500);
        }

        return response()->json([
            'success' => true,
            'message' => 'Clima actualizado correctamente',
            'data' => $clima,
            'updated_at' => now()->format('d/m/Y H:i:s')
        ]);
    }

    /**
     * Obtener pronóstico extendido (AJAX)
     */
    public function pronosticoExtendido(Request $request)
    {
        $ciudad = $request->input('ciudad');
        $pais = $request->input('pais', '');

        if (!$ciudad) {
            return response()->json([
                'error' => 'Se requiere el nombre de la ciudad'
            ], 400);
        }

        $pronostico = $this->weatherService->getForecast($ciudad, $pais);

        if (!$pronostico) {
            return response()->json([
                'error' => 'No se pudo obtener el pronóstico'
            ], 500);
        }

        return response()->json($pronostico);
    }

    /**
     * Preparar datos para los gráficos de Chart.js
     */
    protected function prepararDatosGraficos(?array $pronostico): array
    {
        if (!$pronostico || empty($pronostico['list'])) {
            return [
                'temperatura' => ['labels' => [], 'data' => []],
                'lluvia' => ['labels' => [], 'data' => [], 'probabilidad' => []],
                'humedad' => ['labels' => [], 'data' => []],
                'viento' => ['labels' => [], 'data' => []],
            ];
        }

        $labels = [];
        $temperaturas = [];
        $lluvias = [];
        $probabilidadesLluvia = [];
        $humedades = [];
        $vientos = [];

        // Tomar datos de las próximas 48 horas (16 registros de 3h cada uno)
        $datos = array_slice($pronostico['list'], 0, 16);

        foreach ($datos as $item) {
            $labels[] = $item['date_formatted'] ?? date('d/m H:i', $item['dt'] ?? time());
            $temperaturas[] = $item['temp'] ?? 0;
            $lluvias[] = $item['rain_3h'] ?? 0;
            $probabilidadesLluvia[] = $item['pop'] ?? 0;
            $humedades[] = $item['humidity'] ?? 0;
            $vientos[] = $item['wind_speed'] ?? 0;
        }

        return [
            'temperatura' => [
                'labels' => $labels,
                'data' => $temperaturas,
            ],
            'lluvia' => [
                'labels' => $labels,
                'data' => $lluvias,
                'probabilidad' => $probabilidadesLluvia,
            ],
            'humedad' => [
                'labels' => $labels,
                'data' => $humedades,
            ],
            'viento' => [
                'labels' => $labels,
                'data' => $vientos,
            ],
        ];
    }

    /**
     * Obtener resumen de lluvia (AJAX)
     */
    public function resumenLluvia(Request $request)
    {
        $ciudad = $request->input('ciudad');
        $pais = $request->input('pais', '');

        if (!$ciudad) {
            return response()->json([
                'error' => 'Se requiere el nombre de la ciudad'
            ], 400);
        }

        $pronostico = $this->weatherService->getForecast($ciudad, $pais);

        if (!$pronostico) {
            return response()->json([
                'error' => 'No se pudo obtener el pronóstico'
            ], 500);
        }

        // Calcular resumen de lluvia
        $lluviaTotal = 0;
        $lluviaDias = [];
        $probabilidadMaxima = 0;

        foreach ($pronostico['list'] as $item) {
            $fecha = date('Y-m-d', $item['dt']);
            
            if (!isset($lluviaDias[$fecha])) {
                $lluviaDias[$fecha] = [
                    'fecha' => $fecha,
                    'fecha_formatted' => date('d/m', $item['dt']),
                    'lluvia' => 0,
                    'probabilidad_max' => 0,
                ];
            }

            $lluviaDias[$fecha]['lluvia'] += $item['rain_3h'] ?? 0;
            $lluviaDias[$fecha]['probabilidad_max'] = max(
                $lluviaDias[$fecha]['probabilidad_max'],
                $item['pop'] ?? 0
            );
            
            $lluviaTotal += $item['rain_3h'] ?? 0;
            $probabilidadMaxima = max($probabilidadMaxima, $item['pop'] ?? 0);
        }

        return response()->json([
            'lluvia_total_pronostico' => round($lluviaTotal, 2),
            'probabilidad_maxima' => $probabilidadMaxima,
            'lluvia_por_dia' => array_values($lluviaDias),
            'dias_con_lluvia' => count(array_filter($lluviaDias, fn($dia) => $dia['lluvia'] > 0)),
        ]);
    }
}