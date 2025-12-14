<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Parcela;
use App\Models\Alerta;
use Carbon\Carbon;

class DashboardController extends Controller
{
    public function index()
    {
        $userId = auth()->id();

        // ═══════════════════════════════════════════════════════════
        // ESTADÍSTICAS DE PARCELAS
        // ═══════════════════════════════════════════════════════════
        $totalParcelas = Parcela::where('user_id', $userId)->count();
        
        $parcelasNuevas = Parcela::where('user_id', $userId)
            ->whereMonth('created_at', Carbon::now()->month)
            ->whereYear('created_at', Carbon::now()->year)
            ->count();

        // ═══════════════════════════════════════════════════════════
        // HUMEDAD - Con variación realista
        // ═══════════════════════════════════════════════════════════
        $humedadBase = 78;
        $variacion = rand(-8, 12);
        $humedadPromedio = max(60, min(95, $humedadBase + $variacion));
        $cambioHumedad = rand(-7, 10);

        // ═══════════════════════════════════════════════════════════
        // CONSUMO DE AGUA - Basado en número de parcelas
        // ═══════════════════════════════════════════════════════════
        $aguaPorParcela = 200; // Litros promedio por parcela
        $aguaBase = $totalParcelas * $aguaPorParcela;
        $variacionAgua = rand(-300, 500);
        $aguaHoy = max(0, $aguaBase + $variacionAgua);
        $cambioAgua = rand(-20, 15);

        // ═══════════════════════════════════════════════════════════
        // ALERTAS
        // ═══════════════════════════════════════════════════════════
        $alertasActivas = 0;
        $nuevasAlertas = 0;
        
        try {
            if (class_exists('App\Models\Alerta')) {
                $alertasActivas = Alerta::where('user_id', $userId)
                    ->where('leida', false)
                    ->count();
                
                $nuevasAlertas = Alerta::where('user_id', $userId)
                    ->where('leida', false)
                    ->whereDate('created_at', Carbon::today())
                    ->count();
            } else {
                // Simulación si no existe el modelo
                $alertasActivas = rand(0, 5);
                $nuevasAlertas = rand(0, 2);
            }
        } catch (\Exception $e) {
            $alertasActivas = 0;
            $nuevasAlertas = 0;
        }

        // ═══════════════════════════════════════════════════════════
        // PARCELAS PARA FILTRO
        // ═══════════════════════════════════════════════════════════
        $parcelas = Parcela::where('user_id', $userId)
            ->select('id', 'nombre')
            ->orderBy('nombre')
            ->get();

        // ═══════════════════════════════════════════════════════════
        // ACTIVIDADES RECIENTES - Dinámicas y realistas
        // ═══════════════════════════════════════════════════════════
        $actividadesRecientes = $this->generarActividadesRecientes($totalParcelas);

        // ═══════════════════════════════════════════════════════════
        // RETORNAR VISTA CON TODOS LOS DATOS
        // ═══════════════════════════════════════════════════════════
       return view('dashboard', compact(
            'totalParcelas',
            'parcelasNuevas',
            'humedadPromedio',
            'cambioHumedad',
            'aguaHoy',
            'cambioAgua',
            'alertasActivas',
            'nuevasAlertas',
            'parcelas',
            'actividadesRecientes'
        ));

    }

    /**
     * Genera actividades recientes dinámicas y realistas
     * 
     * @param int $totalParcelas
     * @return array
     */
    private function generarActividadesRecientes($totalParcelas)
    {
        $actividades = [];

        // Si no hay parcelas, mostrar actividades predeterminadas
        if ($totalParcelas == 0) {
            return [
                [
                    'icon' => '🌱',
                    'titulo' => 'Bienvenido al sistema MAKIYAKU',
                    'tiempo' => 'Hace unos momentos',
                    'color' => 'rgba(40, 167, 69, 0.1)',
                    'iconColor' => '#28a745'
                ],
                [
                    'icon' => '📊',
                    'titulo' => 'Sistema iniciado correctamente',
                    'tiempo' => 'Hace 1 minuto',
                    'color' => 'rgba(0, 123, 255, 0.1)',
                    'iconColor' => '#007bff'
                ],
                [
                    'icon' => '✓',
                    'titulo' => 'Todos los sensores funcionando',
                    'tiempo' => 'Hace 5 minutos',
                    'color' => 'rgba(40, 167, 69, 0.1)',
                    'iconColor' => '#28a745'
                ],
                [
                    'icon' => '➕',
                    'titulo' => 'Crea tu primera parcela para comenzar',
                    'tiempo' => 'Hoy',
                    'color' => 'rgba(255, 193, 7, 0.1)',
                    'iconColor' => '#ffc107'
                ]
            ];
        }

        // Pool de actividades posibles con diferentes tipos
        $tiposActividades = [
            // Actividades de riego
            [
                'icon' => '💧',
                'titulo' => 'Riego automático completado en Parcela {{parcela}}',
                'color' => 'rgba(32, 201, 151, 0.1)',
                'iconColor' => '#20c997'
            ],
            [
                'icon' => '🚿',
                'titulo' => 'Sistema de riego iniciado en Parcela {{parcela}}',
                'color' => 'rgba(0, 123, 255, 0.1)',
                'iconColor' => '#007bff'
            ],
            
            // Monitoreo de sensores
            [
                'icon' => '📡',
                'titulo' => 'Datos de sensores actualizados en Parcela {{parcela}}',
                'color' => 'rgba(108, 117, 125, 0.1)',
                'iconColor' => '#6c757d'
            ],
            [
                'icon' => '✓',
                'titulo' => 'Sensores de humedad calibrados en Parcela {{parcela}}',
                'color' => 'rgba(40, 167, 69, 0.1)',
                'iconColor' => '#28a745'
            ],
            
            // Alertas y notificaciones
            [
                'icon' => '⚠️',
                'titulo' => 'Nivel de humedad bajo detectado en Parcela {{parcela}}',
                'color' => 'rgba(255, 193, 7, 0.1)',
                'iconColor' => '#ffc107'
            ],
            [
                'icon' => '🔔',
                'titulo' => 'Alerta de mantenimiento para Parcela {{parcela}}',
                'color' => 'rgba(255, 193, 7, 0.1)',
                'iconColor' => '#ffc107'
            ],
            
            // Análisis y reportes
            [
                'icon' => '📊',
                'titulo' => 'Reporte semanal generado para Parcela {{parcela}}',
                'color' => 'rgba(111, 66, 193, 0.1)',
                'iconColor' => '#6f42c1'
            ],
            [
                'icon' => '📈',
                'titulo' => 'Análisis de eficiencia completado en Parcela {{parcela}}',
                'color' => 'rgba(0, 123, 255, 0.1)',
                'iconColor' => '#007bff'
            ],
            
            // Mantenimiento
            [
                'icon' => '🔧',
                'titulo' => 'Mantenimiento preventivo realizado en Parcela {{parcela}}',
                'color' => 'rgba(23, 162, 184, 0.1)',
                'iconColor' => '#17a2b8'
            ],
            [
                'icon' => '⚙️',
                'titulo' => 'Configuración actualizada en Parcela {{parcela}}',
                'color' => 'rgba(108, 117, 125, 0.1)',
                'iconColor' => '#6c757d'
            ],
            
            // Optimización
            [
                'icon' => '🎯',
                'titulo' => 'Optimización de riego aplicada en Parcela {{parcela}}',
                'color' => 'rgba(40, 167, 69, 0.1)',
                'iconColor' => '#28a745'
            ],
            [
                'icon' => '💡',
                'titulo' => 'Sugerencia de mejora disponible para Parcela {{parcela}}',
                'color' => 'rgba(255, 193, 7, 0.1)',
                'iconColor' => '#ffc107'
            ]
        ];

        // Generar tiempo aleatorio realista
        $tiemposDisponibles = [
            'Hace unos momentos',
            'Hace 5 minutos',
            'Hace 15 minutos',
            'Hace 30 minutos',
            'Hace 1 hora',
            'Hace 2 horas',
            'Hace 3 horas',
            'Hoy a las ' . Carbon::now()->subHours(rand(4, 8))->format('H:i'),
            'Ayer a las ' . Carbon::now()->subDay()->format('H:i')
        ];

        // Generar 5-8 actividades aleatorias
        $numActividades = rand(5, 8);
        $indicesUsados = [];

        for ($i = 0; $i < $numActividades; $i++) {
            // Seleccionar una actividad aleatoria sin repetir
            do {
                $indiceActividad = array_rand($tiposActividades);
            } while (in_array($indiceActividad, $indicesUsados) && count($indicesUsados) < count($tiposActividades));
            
            $indicesUsados[] = $indiceActividad;
            $actividad = $tiposActividades[$indiceActividad];

            // Reemplazar {{parcela}} con un número de parcela
            $numeroParcela = rand(1, max(1, $totalParcelas));
            $titulo = str_replace('{{parcela}}', $numeroParcela, $actividad['titulo']);

            // Asignar tiempo aleatorio
            $tiempo = $tiemposDisponibles[array_rand($tiemposDisponibles)];

            $actividades[] = [
                'icon' => $actividad['icon'],
                'titulo' => $titulo,
                'tiempo' => $tiempo,
                'color' => $actividad['color'],
                'iconColor' => $actividad['iconColor']
            ];
        }

        return $actividades;
    }

    /**
     * Obtiene datos de humedad para gráficos (AJAX)
     * 
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function getHumedadData(Request $request)
    {
        $userId = auth()->id();
        $parcelaId = $request->input('parcela_id', 'all');

        // Generar datos simulados de los últimos 7 días
        $datos = [];
        for ($i = 6; $i >= 0; $i--) {
            $fecha = Carbon::now()->subDays($i);
            $datos[] = [
                'fecha' => $fecha->format('d/m'),
                'humedad' => rand(65, 90)
            ];
        }

        return response()->json([
            'success' => true,
            'data' => $datos
        ]);
    }

    /**
     * Obtiene estadísticas en tiempo real (AJAX)
     * 
     * @return \Illuminate\Http\JsonResponse
     */
    public function getRealtimeStats()
    {
        $userId = auth()->id();

        $stats = [
            'humedad' => rand(70, 90),
            'temperatura' => rand(18, 28),
            'agua_hoy' => rand(800, 2500),
            'alertas' => rand(0, 3)
        ];

        return response()->json([
            'success' => true,
            'stats' => $stats,
            'timestamp' => Carbon::now()->toIso8601String()
        ]);
    }
}