<?php

namespace App\Http\Controllers;

use App\Models\Parcela;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use App\Http\Controllers\Controller;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;

class ParcelaController extends Controller
{
    use AuthorizesRequests;
    public function index()
    {
        $parcelas = Parcela::where('user_id', Auth::id())
            ->with(['lecturas' => function($q) {
                $q->latest()->limit(1);
            }])
            ->paginate(12);

        return view('parcelas.index', compact('parcelas'));
    }

    public function create()
    {
        return view('parcelas.create');
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'nombre' => 'required|string|max:255',
            'ubicacion' => 'nullable|string|max:255',
            'area' => 'required|numeric|min:0',
            'tipo_cultivo' => 'required|string|max:255',
            'humedad_minima' => 'required|numeric|min:0|max:100',
            'humedad_maxima' => 'required|numeric|min:0|max:100',
            'esp32_id' => 'nullable|string|max:255',
        ]);

        $validated['user_id'] = Auth::id();

        Parcela::create($validated);

        return redirect()->route('parcelas.index')
            ->with('success', 'Parcela creada exitosamente');
    }

    public function show(Parcela $parcela)
    {
        $this->authorize('view', $parcela);

        $parcela->load(['lecturas' => function($q) {
            $q->latest()->limit(50);
        }, 'eventosRiego' => function($q) {
            $q->latest()->limit(20);
        }]);

        return view('parcelas.show', compact('parcela'));
    }

    public function edit(Parcela $parcela)
    {
        $this->authorize('update', $parcela);

        return view('parcelas.edit', compact('parcela'));
    }

    public function update(Request $request, Parcela $parcela)
    {
        $this->authorize('update', $parcela);

        $validated = $request->validate([
            'nombre' => 'required|string|max:255',
            'ubicacion' => 'nullable|string|max:255',
            'area' => 'required|numeric|min:0',
            'tipo_cultivo' => 'required|string|max:255',
            'estado' => 'required|in:activa,inactiva,mantenimiento',
            'humedad_minima' => 'required|numeric|min:0|max:100',
            'humedad_maxima' => 'required|numeric|min:0|max:100',
            'esp32_id' => 'nullable|string|max:255',
        ]);

        $parcela->update($validated);

        return redirect()->route('parcelas.show', $parcela)
            ->with('success', 'Parcela actualizada exitosamente');
    }

    public function destroy(Parcela $parcela)
    {
        $this->authorize('delete', $parcela);

        $parcela->delete();

        return redirect()->route('parcelas.index')
            ->with('success', 'Parcela eliminada exitosamente');
    }
}