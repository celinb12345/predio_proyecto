<?php

namespace App\Http\Requests\Panel;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class MantenimientoRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'predio_id' => ['required', 'integer', Rule::exists('predios', 'id')],
            'responsable_id' => ['required', 'integer', Rule::exists('responsables', 'id')->where('activo', true)],
            'fecha' => ['required', 'date'],
            'fecha_fin' => ['required', 'date', 'after_or_equal:fecha'],
            'descripcion' => ['required', 'string', 'min:5', 'max:1000'],
            'costo' => ['required', 'numeric', 'min:0', 'max:99999999.99'],
        ];
    }

    public function messages(): array
    {
        return [
            'predio_id.required' => 'Selecciona el predio.',
            'predio_id.exists' => 'El predio seleccionado no existe.',
            'responsable_id.required' => 'Selecciona al responsable.',
            'responsable_id.exists' => 'El responsable seleccionado no está activo.',
            'fecha.required' => 'Indica la fecha de inicio del mantenimiento.',
            'fecha_fin.required' => 'Indica la fecha fin del mantenimiento.',
            'fecha_fin.after_or_equal' => 'La fecha fin no puede ser anterior a la fecha de inicio.',
            'descripcion.required' => 'Describe el trabajo realizado.',
            'descripcion.min' => 'La descripción debe tener al menos 5 caracteres.',
            'costo.required' => 'Indica el costo. Si no hubo costo, escribe 0.',
            'costo.numeric' => 'El costo debe ser un número.',
            'costo.min' => 'El costo no puede ser negativo.',
        ];
    }
}