<?php

namespace App\Http\Controllers;

use App\Models\Recomendacion;
use App\Models\Parcela;
use App\Services\RecomendacionService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;

class RecomendacionController extends Controller
{
    use AuthorizesRequests;

    protected RecomendacionService $recomendacionService;

    public function __construct(RecomendacionService $recomendacionService)
    {
        $this->recomendacionService = $recomendacionService;
    }

    /**
     * Mostrar todas las recomendaciones de una parcela
     */
    public function index(Parcela $parcela)
    {
        // Verificar que el usuario sea dueño de la parcela
        $this->authorize('view', $parcela);

        try {
            // Cargar relaciones necesarias
            $parcela->load([
                'cultivo',
                'ubicacion',
                'climaActual',
                'eventosRiego' => fn($q) => $q->latest()->limit(30),
            ]);

            // Generar nuevas recomendaciones si es necesario
            $generadas = $this->recomendacionService->generarRecomendacionesParcela($parcela);
            
            if ($generadas > 0) {
                Log::info("Recomendaciones generadas para parcela {$parcela->id}: {$generadas}");
            }

            // Obtener recomendaciones actuales
            $recomendaciones = Recomendacion::where('parcela_id', $parcela->id)
                ->activas()
                ->ordenarPorRelevancia()
                ->get();

            // Estadísticas
            $estadisticas = [
                'total' => $recomendaciones->count(),
                'criticas' => $recomendaciones->where('prioridad', Recomendacion::PRIORIDAD_CRITICA)->count(),
                'altas' => $recomendaciones->where('prioridad', Recomendacion::PRIORIDAD_ALTA)->count(),
                'por_categoria' => $recomendaciones->groupBy('categoria')->map->count(),
            ];

            // Histórico
            $historico = Recomendacion::where('parcela_id', $parcela->id)
                ->where(function($q) {
                    $q->where('aplicada', true)
                      ->orWhere('descartada', true);
                })
                ->orderBy('updated_at', 'desc')
                ->limit(10)
                ->get();

            return view('recomendaciones.index', compact(
                'parcela',
                'recomendaciones',
                'estadisticas',
                'historico'
            ));
        } catch (\Exception $e) {
            Log::error("Error en recomendaciones para parcela {$parcela->id}: " . $e->getMessage());
            Log::error($e->getTraceAsString());
            
            return back()->with('error', 'Error al cargar recomendaciones: ' . $e->getMessage());
        }
    }

    /**
     * Marcar recomendación como aplicada
     */
    public function aplicar(Request $request, Recomendacion $recomendacion)
    {
        $this->authorize('update', $recomendacion->parcela);

        $request->validate([
            'notas' => 'nullable|string|max:500',
        ]);

        try {
            $recomendacion->marcarComoAplicada($request->notas);
            
            return back()->with('success', 'Recomendación marcada como aplicada');
        } catch (\Exception $e) {
            Log::error("Error al aplicar recomendación {$recomendacion->id}: " . $e->getMessage());
            
            return back()->with('error', 'Error al aplicar recomendación');
        }
    }

    /**
     * Marcar recomendación como descartada
     */
    public function descartar(Request $request, Recomendacion $recomendacion)
    {
        $this->authorize('update', $recomendacion->parcela);

        $request->validate([
            'notas' => 'nullable|string|max:500',
        ]);

        try {
            $recomendacion->marcarComoDescartada($request->notas);
            
            return back()->with('success', ' Recomendación descartada');
        } catch (\Exception $e) {
            Log::error("Error al descartar recomendación {$recomendacion->id}: " . $e->getMessage());
            
            return back()->with('error', 'Error al descartar recomendación');
        }
    }

    /**
     * Regenerar recomendaciones
     */
    public function regenerar(Parcela $parcela)
    {
        $this->authorize('update', $parcela);

        try {
            // Desactivar recomendaciones antiguas
            Recomendacion::where('parcela_id', $parcela->id)
                ->activas()
                ->update(['activa' => false]);

            // Generar nuevas
            $nuevas = $this->recomendacionService->generarRecomendacionesParcela($parcela);

            return back()->with('success', " Se generaron {$nuevas} nuevas recomendaciones");
        } catch (\Exception $e) {
            Log::error("Error al regenerar recomendaciones para parcela {$parcela->id}: " . $e->getMessage());
            
            return back()->with('error', 'Error al regenerar recomendaciones');
        }
    }

    /**
     * Ver detalle de una recomendación
     */
    public function show(Recomendacion $recomendacion)
    {
        $this->authorize('view', $recomendacion->parcela);

        $recomendacion->load('parcela.cultivo', 'parcela.ubicacion');

        return view('recomendaciones.show', compact('recomendacion'));
    }
}