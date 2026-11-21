<?php

namespace App\Http\Controllers;

use App\Models\Cultivo;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class CultivoController extends Controller
{
    /**
     * Muestra listado de cultivos.
     */
    public function index()
    {
        $cultivos = Cultivo::orderBy('nombreCultivo')->paginate(15);
        
        return view('cultivos.index', compact('cultivos'));
    }

    /**
     * Muestra formulario de creación.
     */
    public function create()
    {
        return view('cultivos.create');
    }

    /**
     * Almacena un nuevo cultivo.
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            // Datos básicos
            'nombreCultivo' => 'required|string|max:100|unique:cultivos,nombreCultivo',
            'nombreCientifico' => 'nullable|string|max:150',
            'tipo' => 'required|in:Hortalizas,Cereales,Frutales,Legumbres,Tubérculos,Aromáticas,Oleaginosas,Otros',
            'region' => 'nullable|in:Costa Norte,Costa Central y Sur,Sierra,Selva',
            
            // Datos de riego
            'frecuenciaRiegoMin' => 'required|integer|min:1|max:365',
            'frecuenciaRiegoMax' => 'required|integer|min:1|max:365|gte:frecuenciaRiegoMin',
            'litrosPorM2Dia' => 'required|numeric|min:0.01|max:50',
            'humedadOptimaMin' => 'required|numeric|min:0|max:100',
            'humedadOptimaMax' => 'required|numeric|min:0|max:100|gte:humedadOptimaMin',
            'sensibilidadSequia' => 'required|in:Muy Baja,Baja,Media,Media-Alta,Alta,Muy Alta',
            'diasCriticosSinAgua' => 'required|integer|min:1|max:90',
            'etapaMayorConsumo' => 'required|string|max:255',
            'diasEtapaConsumoMin' => 'nullable|integer|min:0',
            'diasEtapaConsumoMax' => 'nullable|integer|min:0|gte:diasEtapaConsumoMin',
            'reduccionConHidrogel' => 'nullable|numeric|min:0|max:100',
            
            // Datos de clima crítico
            'tempCriticaMinima' => 'required|numeric|min:-20|max:50',
            'tempCriticaMaxima' => 'required|numeric|min:-20|max:50|gte:tempCriticaMinima',
            'sensibilidadHeladas' => 'required|in:Muy Baja,Baja,Media,Media-Alta,Alta,Muy Alta',
            'efectoTempMinima' => 'nullable|string|max:255',
            'efectoTempMaxima' => 'nullable|string|max:255',
            
            // Datos generales
            'cicloProductivo' => 'nullable|integer|min:1|max:730',
            'descripcion' => 'nullable|string',
            'observaciones' => 'nullable|string',
        ], [
            'nombreCultivo.unique' => 'Ya existe un cultivo con ese nombre.',
            'frecuenciaRiegoMax.gte' => 'La frecuencia máxima debe ser mayor o igual a la mínima.',
            'humedadOptimaMax.gte' => 'La humedad máxima debe ser mayor o igual a la mínima.',
            'tempCriticaMaxima.gte' => 'La temperatura máxima debe ser mayor o igual a la mínima.',
        ]);

        Cultivo::create($validated);

        return redirect()->route('cultivos.index')
            ->with('success', ' Cultivo creado exitosamente');
    }

    /**
     * Muestra detalles de un cultivo.
     */
    public function show(Cultivo $cultivo)
    {
        $cultivo->load(['parcelas' => function($q) {
            $q->where('user_id', Auth::id());
        }]);

        return view('cultivos.show', compact('cultivo'));
    }

    /**
     * Muestra formulario de edición.
     */
    public function edit(Cultivo $cultivo)
    {
        return view('cultivos.edit', compact('cultivo'));
    }

    /**
     * Actualiza un cultivo existente.
     */
    public function update(Request $request, Cultivo $cultivo)
    {
        $validated = $request->validate([
            // Datos básicos
            'nombreCultivo' => 'required|string|max:100|unique:cultivos,nombreCultivo,' . $cultivo->idCultivo . ',idCultivo',
            'nombreCientifico' => 'nullable|string|max:150',
            'tipo' => 'required|in:Hortalizas,Cereales,Frutales,Legumbres,Tubérculos,Aromáticas,Oleaginosas,Otros',
            'region' => 'nullable|in:Costa Norte,Costa Central y Sur,Sierra,Selva',
            
            // Datos de riego
            'frecuenciaRiegoMin' => 'required|integer|min:1|max:365',
            'frecuenciaRiegoMax' => 'required|integer|min:1|max:365|gte:frecuenciaRiegoMin',
            'litrosPorM2Dia' => 'required|numeric|min:0.01|max:50',
            'humedadOptimaMin' => 'required|numeric|min:0|max:100',
            'humedadOptimaMax' => 'required|numeric|min:0|max:100|gte:humedadOptimaMin',
            'sensibilidadSequia' => 'required|in:Muy Baja,Baja,Media,Media-Alta,Alta,Muy Alta',
            'diasCriticosSinAgua' => 'required|integer|min:1|max:90',
            'etapaMayorConsumo' => 'required|string|max:255',
            'diasEtapaConsumoMin' => 'nullable|integer|min:0',
            'diasEtapaConsumoMax' => 'nullable|integer|min:0|gte:diasEtapaConsumoMin',
            'reduccionConHidrogel' => 'nullable|numeric|min:0|max:100',
            
            // Datos de clima crítico
            'tempCriticaMinima' => 'required|numeric|min:-20|max:50',
            'tempCriticaMaxima' => 'required|numeric|min:-20|max:50|gte:tempCriticaMinima',
            'sensibilidadHeladas' => 'required|in:Muy Baja,Baja,Media,Media-Alta,Alta,Muy Alta',
            'efectoTempMinima' => 'nullable|string|max:255',
            'efectoTempMaxima' => 'nullable|string|max:255',
            
            // Datos generales
            'cicloProductivo' => 'nullable|integer|min:1|max:730',
            'descripcion' => 'nullable|string',
            'observaciones' => 'nullable|string',
        ]);

        $cultivo->update($validated);

        return redirect()->route('cultivos.show', $cultivo)
            ->with('success', ' Cultivo actualizado exitosamente');
    }

    /**
     * Elimina un cultivo.
     */
    public function destroy(Cultivo $cultivo)
    {
        // Verificar si hay parcelas usando este cultivo
        if ($cultivo->parcelas()->count() > 0) {
            return redirect()->route('cultivos.index')
                ->with('error', ' No se puede eliminar: hay ' . $cultivo->parcelas()->count() . ' parcela(s) usando este cultivo');
        }

        $cultivo->delete();

        return redirect()->route('cultivos.index')
            ->with('success', ' Cultivo eliminado exitosamente');
    }
}