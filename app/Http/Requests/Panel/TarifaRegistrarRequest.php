<?php

namespace App\Http\Requests\Panel;

use Illuminate\Foundation\Http\FormRequest;

class TarifaRegistrarRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'tipo_persona_id' => ['required', 'integer', 'exists:tipo_personas,id'],
            'monto' => ['required', 'numeric', 'min:0', 'max:99999999.99'],
            'resolucion' => ['required', 'string', 'max:100'],
            'vigente_hasta' => ['nullable', 'date', 'after_or_equal:today'],
        ];
    }

    public function messages(): array
    {
        return [
            'tipo_persona_id.required' => 'Selecciona un tipo de persona.',
            'tipo_persona_id.exists' => 'El tipo de persona no es válido.',
            'monto.required' => 'Ingresa el monto.',
            'monto.numeric' => 'El monto debe ser un número.',
            'monto.min' => 'El monto no puede ser negativo.',
            'resolucion.required' => 'Ingresa la resolución del Consejo Facultativo.',
            'resolucion.max' => 'La resolución no puede superar los 100 caracteres.',
            'vigente_hasta.date' => 'La fecha de vencimiento no es válida.',
            'vigente_hasta.after_or_equal' => 'La fecha de vencimiento no puede ser anterior a hoy.',
        ];
    }
}