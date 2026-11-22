<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

/**
 * Validacion para crear y editar parcelas.
 */
class ParcelaRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true; // la propiedad de la parcela se revisa con ParcelaPolicy en el controlador
    }

    protected function prepareForValidation(): void
    {
        $this->merge([
            'tieneHidrogelAplicado' => $this->boolean('tieneHidrogelAplicado'),
            'latitud' => $this->filled('latitud') ? str_replace(',', '.', $this->latitud) : null,
            'longitud' => $this->filled('longitud') ? str_replace(',', '.', $this->longitud) : null,
        ]);
    }

    public function rules(): array
    {
        $editando = $this->isMethod('PUT') || $this->isMethod('PATCH');

        return [
            'nombreParcela' => ['required', 'string', 'max:255'],
            'area' => ['required', 'numeric', 'min:0.01', 'max:100000000'],
            'idCultivo' => ['required', 'exists:cultivos,idCultivo'],
            'estado' => [$editando ? 'required' : 'nullable', 'in:activa,inactiva,mantenimiento'],
            'region' => ['required', 'string', 'max:100'],
            'provincia' => ['required', 'string', 'max:100'],
            'distrito' => ['required', 'string', 'max:100'],
            'latitud' => ['nullable', 'numeric', 'between:-90,90'],
            'longitud' => ['nullable', 'numeric', 'between:-180,180', 'required_with:latitud'],
            'fecha_siembra' => ['nullable', 'date', 'before_or_equal:today'],
            'ultimo_riego' => ['nullable', 'date', 'before_or_equal:today'],
            'tieneHidrogelAplicado' => ['boolean'],
            'hidrogel_fecha_aplicacion' => ['nullable', 'date', 'before_or_equal:today', 'required_if:tieneHidrogelAplicado,1'],
            'hidrogel_cantidad_kg' => ['nullable', 'numeric', 'min:0', 'required_if:tieneHidrogelAplicado,1'],
            'notas_usuario' => ['nullable', 'string', 'max:1000'],
        ];
    }

    public function messages(): array
    {
        return [
            'nombreParcela.required' => 'Escribe un nombre para la parcela.',
            'area.required' => 'Indica el área en metros cuadrados.',
            'area.min' => 'El área debe ser mayor que cero.',
            'idCultivo.required' => 'Elige un cultivo.',
            'idCultivo.exists' => 'El cultivo elegido no existe.',
            'region.required' => 'Indica la región.', 'provincia.required' => 'Indica la provincia.', 'distrito.required' => 'Indica el distrito.',
            'latitud.between' => 'La latitud debe estar entre -90 y 90.',
            'longitud.between' => 'La longitud debe estar entre -180 y 180.',
            'longitud.required_with' => 'Si indicas la latitud, indica también la longitud.',
            'fecha_siembra.before_or_equal' => 'La fecha de siembra no puede ser futura.',
            'ultimo_riego.before_or_equal' => 'La fecha del último riego no puede ser futura.',
            'hidrogel_fecha_aplicacion.required_if' => 'Indica cuándo aplicaste el hidrogel.',
            'hidrogel_cantidad_kg.required_if' => 'Indica cuántos kilos de hidrogel aplicaste.',
        ];
    }

    public function attributes(): array
    {
        return ['nombreParcela' => 'nombre', 'idCultivo' => 'cultivo', 'fecha_siembra' => 'fecha de siembra', 'ultimo_riego' => 'último riego'];
    }
}
