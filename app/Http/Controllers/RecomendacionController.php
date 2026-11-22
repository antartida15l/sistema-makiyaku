<?php

namespace App\Http\Controllers;

use App\Models\Parcela;
use App\Models\Recomendacion;
use App\Services\RecomendacionService;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class RecomendacionController extends Controller
{
    use AuthorizesRequests;

    public function __construct(private RecomendacionService $servicio)
    {
    }

    /** Recomendaciones de todas mis parcelas, con filtros. */
    public function todas(Request $request)
    {
        $parcelas = Parcela::where('user_id', Auth::id())->activas()->with(['cultivo', 'ubicacion', 'climaActual'])->orderBy('nombre')->get();

        // Se mantienen al dia sin pedirlo: como mucho una vez cada 10 minutos por usuario
        if (cache()->add('recos:usuario:' . Auth::id(), 1, now()->addMinutes(10))) {
            $parcelas->each(fn ($p) => $this->servicio->generarRecomendacionesParcela($p));
        }

        $base = Recomendacion::where('user_id', Auth::id())->activas()->with('parcela:id,nombre');

        $recomendaciones = (clone $base)
            ->when($request->filled('parcela'), fn ($q) => $q->where('parcela_id', $request->parcela))
            ->when($request->filled('categoria'), fn ($q) => $q->where('categoria', $request->categoria))
            ->when($request->filled('prioridad'), fn ($q) => $q->where('prioridad', $request->prioridad))
            ->ordenarPorRelevancia()->get();

        return view('recomendaciones.index', [
            'recomendaciones' => $recomendaciones,
            'parcelas' => $parcelas,
            'conteo' => [
                'total' => (clone $base)->count(),
                'urgentes' => (clone $base)->whereIn('prioridad', [Recomendacion::PRIORIDAD_CRITICA, Recomendacion::PRIORIDAD_ALTA])->count(),
            ],
            'categorias' => (clone $base)->get()->groupBy('categoria')->map->count(),
            'filtros' => $request->only(['parcela', 'categoria', 'prioridad']),
            'historico' => Recomendacion::where('user_id', Auth::id())->where(fn ($q) => $q->where('aplicada', true)->orWhere('descartada', true))->with('parcela:id,nombre')->latest('updated_at')->limit(8)->get(),
        ]);
    }

    /** Recomendaciones de una parcela. */
    public function index(Parcela $parcela)
    {
        $this->authorize('view', $parcela);

        $parcela->load(['cultivo', 'ubicacion', 'climaActual']);
        $this->servicio->generarRecomendacionesParcela($parcela);

        $recomendaciones = Recomendacion::where('parcela_id', $parcela->id)->activas()->ordenarPorRelevancia()->get();

        return view('recomendaciones.parcela', [
            'parcela' => $parcela,
            'recomendaciones' => $recomendaciones,
            'historico' => Recomendacion::where('parcela_id', $parcela->id)->where(fn ($q) => $q->where('aplicada', true)->orWhere('descartada', true))->latest('updated_at')->limit(10)->get(),
        ]);
    }

    public function regenerar(Parcela $parcela)
    {
        $this->authorize('update', $parcela);
        $parcela->load(['cultivo', 'ubicacion', 'climaActual']);
        $n = $this->servicio->generarRecomendacionesParcela($parcela);

        return back()->with('success', $n > 0 ? "Se agregaron {$n} recomendación(es) nueva(s)." : 'Las recomendaciones ya están al día.');
    }

    public function regenerarTodas()
    {
        $n = 0;
        Parcela::where('user_id', Auth::id())->activas()->with(['cultivo', 'ubicacion', 'climaActual'])->get()
            ->each(function ($p) use (&$n) { $n += $this->servicio->generarRecomendacionesParcela($p); });
        cache()->forget('recos:usuario:' . Auth::id());

        return back()->with('success', $n > 0 ? "Se agregaron {$n} recomendación(es) nueva(s)." : 'Las recomendaciones ya están al día.');
    }

    public function aplicar(Request $request, Recomendacion $recomendacion)
    {
        $this->authorize('update', $recomendacion->parcela);
        $recomendacion->marcarComoAplicada($request->validate(['notas' => 'nullable|string|max:500'])['notas'] ?? null);

        return back()->with('success', 'Marcada como aplicada.');
    }

    public function descartar(Request $request, Recomendacion $recomendacion)
    {
        $this->authorize('update', $recomendacion->parcela);
        $recomendacion->marcarComoDescartada($request->validate(['notas' => 'nullable|string|max:500'])['notas'] ?? null);

        return back()->with('success', 'Recomendación descartada.');
    }
}
