<?php

namespace App\Http\Requests\Panel;

use Illuminate\Foundation\Http\FormRequest;

class PlanCuotasRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'cuotas' => ['required', 'array', 'min:1', 'max:2'],
            'cuotas.*.id' => ['required', 'integer', 'exists:cuotas,id'],
            'cuotas.*.monto' => ['required', 'numeric', 'min:0.01', 'max:99999999.99'],
            'cuotas.*.fecha_vencimiento' => ['required', 'date'],
        ];
    }

    public function messages(): array
    {
        return [
            'cuotas.required' => 'No hay cuotas para actualizar.',
            'cuotas.*.monto.required' => 'Ingresa el monto de cada cuota.',
            'cuotas.*.monto.numeric' => 'El monto debe ser un número.',
            'cuotas.*.monto.min' => 'El monto debe ser mayor a cero.',
            'cuotas.*.fecha_vencimiento.required' => 'Ingresa la fecha límite de cada cuota.',
            'cuotas.*.fecha_vencimiento.date' => 'La fecha límite no es válida.',
        ];
    }
}