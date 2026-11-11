<?php

namespace App\Http\Requests\Panel;

use Illuminate\Foundation\Http\FormRequest;

class TarifaActualizarRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'monto' => ['required', 'numeric', 'min:0', 'max:99999999.99'],
            'resolucion' => ['required', 'string', 'max:100'],
            'vigente_hasta' => ['nullable', 'date'],
        ];
    }

    public function messages(): array
    {
        return [
            'monto.required' => 'Ingresa el monto.',
            'monto.numeric' => 'El monto debe ser un número.',
            'monto.min' => 'El monto no puede ser negativo.',
            'resolucion.required' => 'Ingresa la resolución del Consejo Facultativo.',
            'resolucion.max' => 'La resolución no puede superar los 100 caracteres.',
            'vigente_hasta.date' => 'La fecha de vencimiento no es válida.',
        ];
    }
}