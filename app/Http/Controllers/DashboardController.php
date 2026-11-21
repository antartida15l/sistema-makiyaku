<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Parcela;
use App\Models\Alerta;
use App\Models\Cultivo;
use App\Models\ClimaHistorial;
use App\Services\AlertaService;
use App\Services\OpenWeatherService;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;

class DashboardController extends Controller
{
    protected AlertaService $alertaService;
    protected OpenWeatherService $weatherService;

    public function __construct(AlertaService $alertaService, OpenWeatherService $weatherService)
    {
        $this->alertaService = $alertaService;
        $this->weatherService = $weatherService;
    }

    public function index()
    {
        $userId = auth()->id();

        // Generar alertas automáticamente
        $this->alertaService->generarAlertasUsuario($userId);

        // Estadísticas de parcelas
        $totalParcelas = Parcela::where('user_id', $userId)
            ->activas()
            ->count();
        
        $parcelasNuevas = Parcela::where('user_id', $userId)
            ->whereMonth('created_at', Carbon::now()->month)
            ->whereYear('created_at', Carbon::now()->year)
            ->count();

        // Clima promedio en tiempo real
        $climaPromedio = $this->obtenerClimaPromedioRealTime($userId);

        // Alertas
        $alertasResumen = Alerta::resumenPorUsuario($userId);
        $alertasActivas = $alertasResumen['total'];
        $nuevasAlertas = $alertasResumen['nuevas_hoy'];

        // Parcelas para filtros
        $parcelas = Parcela::where('user_id', $userId)
            ->activas()
            ->with('ubicacion')
            ->select('id', 'nombre')
            ->orderBy('nombre')
            ->get();

        // Actividades recientes
        $actividadesRecientes = $this->obtenerActividadesReales($userId);

        // Distribución por cultivo
        $distribucionCultivos = $this->obtenerDistribucionCultivos($userId);

        return view('dashboard', compact(
            'totalParcelas',
            'parcelasNuevas',
            'climaPromedio',
            'alertasActivas',
            'nuevasAlertas',
            'alertasResumen',
            'parcelas',
            'actividadesRecientes',
            'distribucionCultivos'
        ));
    }

    private function obtenerClimaPromedioRealTime(int $userId): array
    {
        $parcelas = Parcela::where('user_id', $userId)
            ->activas()
            ->with('ubicacion')
            ->get();

        if ($parcelas->isEmpty()) {
            return [
                'temperatura' => 0,
                'humedad' => 0,
                'hay_datos' => false,
            ];
        }

        $temperaturas = [];
        $humedades = [];

        foreach ($parcelas as $parcela) {
            if ($parcela->ubicacion && $parcela->ubicacion->coordenadaLatitud) {
                $clima = $this->weatherService->getCurrentWeatherByCoordinates(
                    $parcela->ubicacion->coordenadaLatitud,
                    $parcela->ubicacion->coordenadaLongitud
                );

                if ($clima) {
                    $temperaturas[] = $clima['temp'];
                    $humedades[] = $clima['humidity'];
                }
            }
        }

        if (empty($temperaturas)) {
            return [
                'temperatura' => 0,
                'humedad' => 0,
                'hay_datos' => false,
            ];
        }

        return [
            'temperatura' => round(array_sum($temperaturas) / count($temperaturas), 1),
            'humedad' => round(array_sum($humedades) / count($humedades), 0),
            'hay_datos' => true,
        ];
    }

    public function getComparativaClima(Request $request)
    {
        $userId = auth()->id();
        $parcelasIds = $request->input('parcelas', []);

        if (empty($parcelasIds) || $parcelasIds[0] === 'all') {
            $parcelas = Parcela::where('user_id', $userId)
                ->activas()
                ->with('ubicacion')
                ->limit(5)
                ->get();
        } else {
            $parcelas = Parcela::whereIn('id', $parcelasIds)
                ->where('user_id', $userId)
                ->with('ubicacion')
                ->get();
        }

        $datos = [];

        foreach ($parcelas as $parcela) {
            if ($parcela->ubicacion && $parcela->ubicacion->coordenadaLatitud) {
                $clima = $this->weatherService->getCurrentWeatherByCoordinates(
                    $parcela->ubicacion->coordenadaLatitud,
                    $parcela->ubicacion->coordenadaLongitud
                );

                if ($clima) {
                    $datos[] = [
                        'parcela' => $parcela->nombre,
                        'temperatura' => round($clima['temp'], 1),
                        'humedad' => round($clima['humidity'], 0),
                        'viento' => round($clima['wind_speed'], 1),
                        'descripcion' => $clima['weather_description'],
                        'emoji' => $this->getEmojiClima($clima['weather_main']),
                    ];
                }
            }
        }

        if (empty($datos)) {
            return response()->json([
                'success' => false,
                'message' => 'No se pudo obtener datos climáticos. Verifica que las parcelas tengan ubicación.',
                'data' => []
            ]);
        }

        return response()->json([
            'success' => true,
            'data' => $datos
        ]);
    }

      public function getPronostico(Request $request)
    {
        $userId = auth()->id();
        $parcelaId = $request->input('parcela_id');

        // Obtener parcela
        if (!$parcelaId || $parcelaId === 'all') {
            $parcela = Parcela::where('user_id', $userId)
                ->activas()
                ->with('ubicacion')
                ->first();
        } else {
            $parcela = Parcela::where('id', $parcelaId)
                ->where('user_id', $userId)
                ->with('ubicacion')
                ->first();
        }

        if (!$parcela || !$parcela->ubicacion || !$parcela->ubicacion->coordenadaLatitud) {
            return response()->json([
                'success' => false,
                'message' => 'No se encontró ubicación para obtener pronóstico',
                'data' => []
            ]);
        }

        // Obtener pronóstico usando el servicio
        $forecast = $this->weatherService->getForecastByCoordinates(
            $parcela->ubicacion->coordenadaLatitud,
            $parcela->ubicacion->coordenadaLongitud
        );

        if (!$forecast || !isset($forecast['list']) || empty($forecast['list'])) {
            return response()->json([
                'success' => false,
                'message' => 'No se pudo obtener el pronóstico',
                'data' => []
            ]);
        }

        // Agrupar por día
        $datosPorDia = [];
        
        foreach ($forecast['list'] as $item) {
            // Tu servicio devuelve 'datetime' ya formateado
            $fecha = Carbon::parse($item['datetime']);
            $dia = $fecha->format('Y-m-d');
            
            if (!isset($datosPorDia[$dia])) {
                $datosPorDia[$dia] = [
                    'fecha_obj' => $fecha,
                    'temperaturas' => [],
                    'humedades' => [],
                    'lluvias' => [],
                    'descripciones' => [],
                    'weather_mains' => []
                ];
            }
            
            // Usar los campos que tu servicio YA formateó
            $datosPorDia[$dia]['temperaturas'][] = $item['temp'];
            $datosPorDia[$dia]['humedades'][] = $item['humidity'];
            $datosPorDia[$dia]['lluvias'][] = $item['pop']; // Ya viene en porcentaje 0-100
            $datosPorDia[$dia]['descripciones'][] = $item['weather_description'];
            $datosPorDia[$dia]['weather_mains'][] = $item['weather_main'];
        }

        // Procesar 5 días
        $pronosticoDias = [];
        $contador = 0;
        
        foreach ($datosPorDia as $dia => $datos) {
            if ($contador >= 5) break;
            
            $temps = $datos['temperaturas'];
            $fecha = $datos['fecha_obj'];
            
            // Descripción más frecuente
            $descripciones = array_count_values($datos['descripciones']);
            arsort($descripciones);
            $descripcion = array_key_first($descripciones);
            
            // Weather main más frecuente
            $mains = array_count_values($datos['weather_mains']);
            arsort($mains);
            $weatherMain = array_key_first($mains);
            
            $pronosticoDias[] = [
                'fecha' => $fecha->format('d/m'),
                'dia_semana' => $fecha->locale('es')->isoFormat('ddd'),
                'temp_max' => round(max($temps), 1),
                'temp_min' => round(min($temps), 1),
                'humedad' => round(array_sum($datos['humedades']) / count($datos['humedades']), 0),
                'lluvia_prob' => round(array_sum($datos['lluvias']) / count($datos['lluvias']), 0), // Ya es porcentaje
                'descripcion' => $descripcion,
                'emoji' => $this->getEmojiClima($weatherMain),
            ];
            
            $contador++;
        }

        if (empty($pronosticoDias)) {
            return response()->json([
                'success' => false,
                'message' => 'No se encontraron datos de pronóstico',
                'data' => []
            ]);
        }

        return response()->json([
            'success' => true,
            'parcela' => $parcela->nombre,
            'data' => $pronosticoDias
        ]);
    }


    private function obtenerActividadesReales(int $userId): array
    {
        $actividades = [];

        // Alertas recientes
        $alertasRecientes = Alerta::where('user_id', $userId)
            ->activas()
            ->with('parcela')
            ->orderBy('created_at', 'desc')
            ->limit(3)
            ->get();

        foreach ($alertasRecientes as $alerta) {
            $actividades[] = [
                'icon' => $alerta->icono,
                'titulo' => "{$alerta->titulo} - {$alerta->parcela->nombre}",
                'tiempo' => $alerta->tiempo_transcurrido,
                'color' => $this->getColorByNivel($alerta->nivel),
                'iconColor' => $alerta->color_hex,
            ];
        }

        // Riegos recientes
        $riegosRecientes = DB::table('eventos_riego')
            ->join('parcelas', 'eventos_riego.parcela_id', '=', 'parcelas.id')
            ->where('parcelas.user_id', $userId)
            ->select(
                'parcelas.nombre',
                'eventos_riego.fecha_riego',
                'eventos_riego.cantidad_agua',
                'eventos_riego.tipo_riego'
            )
            ->orderBy('eventos_riego.fecha_riego', 'desc')
            ->limit(3)
            ->get();

        foreach ($riegosRecientes as $riego) {
            $actividades[] = [
                'icon' => $riego->tipo_riego === 'automatico' ? '🤖' : '💧',
                'titulo' => "Riego {$riego->tipo_riego} en {$riego->nombre} - " . 
                           number_format($riego->cantidad_agua, 0) . " litros",
                'tiempo' => Carbon::parse($riego->fecha_riego)->diffForHumans(),
                'color' => 'rgba(32, 201, 151, 0.1)',
                'iconColor' => '#20c997',
            ];
        }

        // Parcelas nuevas
        $parcelasNuevas = Parcela::where('user_id', $userId)
            ->where('created_at', '>=', Carbon::now()->subDays(7))
            ->orderBy('created_at', 'desc')
            ->limit(2)
            ->get();

        foreach ($parcelasNuevas as $parcela) {
            $actividades[] = [
                'icon' => '🌱',
                'titulo' => "Nueva parcela creada: {$parcela->nombre}",
                'tiempo' => $parcela->created_at->diffForHumans(),
                'color' => 'rgba(40, 167, 69, 0.1)',
                'iconColor' => '#28a745',
            ];
        }

        usort($actividades, function($a, $b) {
            return strcmp($b['tiempo'], $a['tiempo']);
        });

        return array_slice($actividades, 0, 6);
    }

    private function obtenerDistribucionCultivos(int $userId): array
    {
        $distribucion = Parcela::where('user_id', $userId)
            ->activas()
            ->join('cultivos', 'parcelas.idCultivo', '=', 'cultivos.idCultivo')
            ->select(
                'cultivos.nombreCultivo',
                'cultivos.idCultivo',
                DB::raw('COUNT(*) as cantidad'),
                DB::raw('SUM(parcelas.area) as area_total')
            )
            ->groupBy('cultivos.nombreCultivo', 'cultivos.idCultivo')
            ->orderBy('area_total', 'desc')
            ->get();

        $totalArea = $distribucion->sum('area_total');

        // Mapeo de emojis por cultivo
        $emojis = [
            'Maíz' => '🌽',
            'Trigo' => '🌾',
            'Arroz' => '🌾',
            'Papa' => '🥔',
            'Tomate' => '🍅',
            'Lechuga' => '🥬',
            'Zanahoria' => '🥕',
            'Cebolla' => '🧅',
            'Fresa' => '🍓',
            'Uva' => '🍇',
            'Manzana' => '🍎',
            'Naranja' => '🍊',
            'Café' => '☕',
            'Cacao' => '🍫',
            'Caña de azúcar' => '🎋',
            'Algodón' => '☁️',
        ];

        return $distribucion->map(function($item) use ($totalArea, $emojis) {
            return [
                'nombre' => $item->nombreCultivo,
                'emoji' => $emojis[$item->nombreCultivo] ?? '🌱',
                'cantidad' => $item->cantidad,
                'area' => round($item->area_total, 2),
                'porcentaje' => $totalArea > 0 ? round(($item->area_total / $totalArea) * 100, 1) : 0,
            ];
        })->toArray();
    }

    private function getEmojiClima(string $condition): string
    {
        return match($condition) {
            'Clear' => '☀️',
            'Clouds' => '☁️',
            'Rain' => '🌧️',
            'Drizzle' => '🌦️',
            'Thunderstorm' => '⛈️',
            'Snow' => '❄️',
            'Mist', 'Fog' => '🌫️',
            default => '🌤️',
        };
    }

    private function getColorByNivel(string $nivel): string
    {
        return match($nivel) {
            'critico' => 'rgba(220, 53, 69, 0.1)',
            'alto' => 'rgba(255, 193, 7, 0.1)',
            'medio' => 'rgba(23, 162, 184, 0.1)',
            'info' => 'rgba(0, 123, 255, 0.1)',
            default => 'rgba(108, 117, 125, 0.1)',
        };
    }
}