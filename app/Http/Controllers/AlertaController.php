<?php

namespace App\Http\Controllers;

use App\Models\Alerta;
use App\Services\AlertaService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Carbon\Carbon;

class AlertaController extends Controller
{
    protected AlertaService $alertaService;

    public function __construct(AlertaService $alertaService)
    {
        $this->alertaService = $alertaService;
    }

    /**
     * Muestra el listado de alertas activas
     */
    public function index()
    {
        // Regenerar alertas solo si han pasado más de 15 minutos
        $ultimaActualizacion = session('ultima_actualizacion_alertas');
        
        if (!$ultimaActualizacion || $ultimaActualizacion->diffInMinutes(now()) >= 15) {
            $this->alertaService->generarAlertasUsuario(Auth::id());
            session(['ultima_actualizacion_alertas' => now()]);
        }

        // Obtener alertas activas agrupadas por nivel
        $alertasPorNivel = [
            'criticas' => Alerta::porUsuario(Auth::id())
                ->activas()
                ->where('nivel', Alerta::NIVEL_CRITICO)
                ->ordenarPorUrgencia()
                ->get(),
            
            'altas' => Alerta::porUsuario(Auth::id())
                ->activas()
                ->where('nivel', Alerta::NIVEL_ALTO)
                ->ordenarPorUrgencia()
                ->get(),
            
            'medias' => Alerta::porUsuario(Auth::id())
                ->activas()
                ->where('nivel', Alerta::NIVEL_MEDIO)
                ->ordenarPorUrgencia()
                ->get(),
            
            'info' => Alerta::porUsuario(Auth::id())
                ->activas()
                ->where('nivel', Alerta::NIVEL_INFO)
                ->ordenarPorUrgencia()
                ->get(),
        ];

        // Resumen de alertas
        $resumen = Alerta::resumenPorUsuario(Auth::id());

        return view('alertas.index', compact('alertasPorNivel', 'resumen'));
    }

    /**
     * Endpoint AJAX para verificar alertas recientes
     * Lo usa el sistema de notificaciones sonoras
     */
    public function obtenerRecientes()
    {
        try {
            // Obtener alertas de las últimas 2 horas que no han sido leídas
            $alertas = Alerta::porUsuario(Auth::id())
                ->activas()
                ->where('created_at', '>=', now()->subHours(2))
                ->noLeidas()
                ->ordenarPorUrgencia()
                ->get()
                ->map(function($alerta) {
                    return [
                        'id' => $alerta->id,
                        'nivel' => $alerta->nivel,
                        'icono' => $alerta->icono,
                        'titulo' => $alerta->titulo,
                        'mensaje' => $alerta->mensaje,
                        'parcela' => $alerta->parcela->nombre ?? 'Sin parcela',
                        'url' => $alerta->accion_url ?? route('alertas.index'),
                        'created_at' => $alerta->created_at->toISOString(),
                        'es_nueva' => $alerta->created_at->isAfter(now()->subMinutes(5)),
                    ];
                });

            return response()->json([
                'success' => true,
                'alertas' => $alertas,
                'total' => $alertas->count(),
                'criticas' => $alertas->where('nivel', Alerta::NIVEL_CRITICO)->count(),
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Endpoint AJAX para obtener contador de alertas (badge del sidebar)
     */
    public function contador()
    {
        try {
            $resumen = Alerta::resumenPorUsuario(Auth::id());

            return response()->json([
                'success' => true,
                'total' => $resumen['total'],
                'criticas' => $resumen['criticas'],
                'altas' => $resumen['altas'],
                'medias' => $resumen['medias'],
                'info' => $resumen['info'],
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Marcar una alerta específica como leída (AJAX)
     */
    public function marcarLeida(Alerta $alerta)
    {
        try {
            // Verificar que la alerta pertenece al usuario
            if ($alerta->user_id !== Auth::id()) {
                return response()->json([
                    'success' => false,
                    'message' => 'No autorizado'
                ], 403);
            }

            // Archivar la alerta (la marca como leída también)
            $alerta->archivar();

            return response()->json([
                'success' => true,
                'message' => 'Alerta archivada correctamente'
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error al archivar alerta: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Marcar TODAS las alertas como leídas
     */
    public function marcarTodasLeidas()
    {
        try {
            $alertas = Alerta::porUsuario(Auth::id())->activas()->get();
            
            foreach ($alertas as $alerta) {
                $alerta->archivar();
            }

            return response()->json([
                'success' => true,
                'count' => $alertas->count(),
                'message' => $alertas->count() . ' alertas archivadas'
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error al archivar alertas: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Refrescar/Regenerar alertas manualmente
     */
    public function refrescar()
    {
        try {
            $alertasGeneradas = $this->alertaService->generarAlertasUsuario(Auth::id());
            
            session([
                'ultima_actualizacion_alertas' => now(),
                'alertas_nuevas' => $alertasGeneradas
            ]);

            return redirect()->route('alertas.index')
                ->with('success', " Alertas actualizadas. {$alertasGeneradas} nueva(s) generada(s)");
        } catch (\Exception $e) {
            return redirect()->back()
                ->with('error', ' Error al actualizar alertas: ' . $e->getMessage());
        }
    }

    /**
     */
    public function archivadas()
    {
        $alertas = Alerta::porUsuario(Auth::id())
            ->archivadas()
            ->orderBy('archivada_en', 'desc')
            ->paginate(20);

        return view('alertas.archivadas', compact('alertas'));
    }
}