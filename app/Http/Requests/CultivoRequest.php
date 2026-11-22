<?php

namespace App\Http\Requests;

use App\Models\Cultivo;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class CultivoRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        $niveles = array_keys(Cultivo::SENSIBILIDAD);
        $actual = $this->route('cultivo');

        return [
            'nombreCultivo' => ['required', 'string', 'max:100', Rule::unique('cultivos', 'nombreCultivo')->ignore($actual?->idCultivo, 'idCultivo')],
            'nombreCientifico' => ['nullable', 'string', 'max:150'],
            'tipo' => ['required', Rule::in(array_keys(Cultivo::TIPOS))],
            'region' => ['nullable', Rule::in(array_keys(Cultivo::REGIONES))],

            'frecuenciaRiegoMin' => ['required', 'integer', 'min:1', 'max:365'],
            'frecuenciaRiegoMax' => ['required', 'integer', 'min:1', 'max:365', 'gte:frecuenciaRiegoMin'],
            'litrosPorM2Dia' => ['required', 'numeric', 'min:0.01', 'max:50'],
            'humedadOptimaMin' => ['required', 'numeric', 'min:0', 'max:100'],
            'humedadOptimaMax' => ['required', 'numeric', 'min:0', 'max:100', 'gte:humedadOptimaMin'],
            'sensibilidadSequia' => ['required', Rule::in($niveles)],
            'diasCriticosSinAgua' => ['required', 'integer', 'min:1', 'max:90'],
            'etapaMayorConsumo' => ['required', 'string', 'max:255'],
            'diasEtapaConsumoMin' => ['nullable', 'integer', 'min:0'],
            'diasEtapaConsumoMax' => ['nullable', 'integer', 'min:0', 'gte:diasEtapaConsumoMin'],
            'reduccionConHidrogel' => ['nullable', 'numeric', 'min:0', 'max:100'],

            'tempCriticaMinima' => ['required', 'numeric', 'min:-30', 'max:50'],
            'tempCriticaMaxima' => ['required', 'numeric', 'min:-30', 'max:60', 'gte:tempCriticaMinima'],
            'sensibilidadHeladas' => ['required', Rule::in($niveles)],
            'efectoTempMinima' => ['nullable', 'string', 'max:255'],
            'efectoTempMaxima' => ['nullable', 'string', 'max:255'],

            'cicloProductivo' => ['nullable', 'integer', 'min:1', 'max:730'],
            'descripcion' => ['nullable', 'string', 'max:5000'],
            'observaciones' => ['nullable', 'string', 'max:5000'],
        ];
    }

    public function messages(): array
    {
        return [
            'nombreCultivo.required' => 'Escribe el nombre del cultivo.',
            'nombreCultivo.unique' => 'Ya existe un cultivo con ese nombre.',
            'frecuenciaRiegoMax.gte' => 'La frecuencia máxima debe ser mayor o igual a la mínima.',
            'humedadOptimaMax.gte' => 'La humedad máxima debe ser mayor o igual a la mínima.',
            'tempCriticaMaxima.gte' => 'La temperatura máxima debe ser mayor o igual a la mínima.',
            'diasEtapaConsumoMax.gte' => 'El fin de la etapa debe ser posterior al inicio.',
        ];
    }

    public function attributes(): array
    {
        return [
            'nombreCultivo' => 'nombre', 'frecuenciaRiegoMin' => 'frecuencia mínima', 'frecuenciaRiegoMax' => 'frecuencia máxima',
            'litrosPorM2Dia' => 'consumo de agua', 'diasCriticosSinAgua' => 'días críticos', 'tempCriticaMinima' => 'temperatura mínima',
            'tempCriticaMaxima' => 'temperatura máxima', 'cicloProductivo' => 'ciclo productivo',
        ];
    }
}
