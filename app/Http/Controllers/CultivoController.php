<?php

namespace App\Http\Controllers;

use App\Http\Requests\CultivoRequest;
use App\Models\Cultivo;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class CultivoController extends Controller
{
    /** Catalogo con busqueda y filtros por tipo y region. */
    public function index(Request $request)
    {
        $cultivos = Cultivo::query()
            ->when($request->filled('q'), fn ($q) => $q->where(fn ($w) => $w->where('nombreCultivo', 'like', '%' . $request->q . '%')->orWhere('nombreCientifico', 'like', '%' . $request->q . '%')))
            ->when($request->filled('tipo'), fn ($q) => $q->where('tipo', $request->tipo))
            ->when($request->filled('region'), fn ($q) => $q->where('region', $request->region))
            ->when($request->filled('sequia'), fn ($q) => $q->where('sensibilidadSequia', $request->sequia))
            ->withCount(['parcelas as mis_parcelas' => fn ($q) => $q->where('user_id', Auth::id())])
            ->orderBy('nombreCultivo')
            ->paginate(18)
            ->withQueryString();

        return view('cultivos.index', [
            'cultivos' => $cultivos,
            'filtros' => $request->only(['q', 'tipo', 'region', 'sequia']),
            'total' => Cultivo::count(),
        ]);
    }

    public function create()
    {
        return view('cultivos.create');
    }

    public function store(CultivoRequest $request)
    {
        $cultivo = Cultivo::create($request->validated());

        return redirect()->route('cultivos.show', $cultivo)->with('success', "Cultivo \"{$cultivo->nombreCultivo}\" creado.");
    }

    public function show(Cultivo $cultivo)
    {
        $cultivo->load(['parcelas' => fn ($q) => $q->where('user_id', Auth::id())->with('ubicacion')]);

        return view('cultivos.show', compact('cultivo'));
    }

    public function edit(Cultivo $cultivo)
    {
        return view('cultivos.edit', compact('cultivo'));
    }

    public function update(CultivoRequest $request, Cultivo $cultivo)
    {
        $cultivo->update($request->validated());

        return redirect()->route('cultivos.show', $cultivo)->with('success', 'Cambios guardados.');
    }

    public function destroy(Cultivo $cultivo)
    {
        $enUso = $cultivo->parcelas()->count();

        if ($enUso > 0) {
            return redirect()->route('cultivos.show', $cultivo)
                ->with('error', "No se puede eliminar: {$enUso} parcela(s) usan este cultivo. Cámbialas de cultivo primero.");
        }

        $cultivo->delete();

        return redirect()->route('cultivos.index')->with('success', 'Cultivo eliminado.');
    }
}
