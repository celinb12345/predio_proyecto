<?php

namespace App\Http\Requests\Panel;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class RegistrarDevolucionRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'detalles' => ['required', 'array', 'min:1'],
            'detalles.*.detalle_id' => ['required', 'integer', 'exists:detalle_actas,id'],
            'detalles.*.estado_devolucion' => ['required', Rule::in(['bueno', 'regular', 'malo', 'faltante'])],
        ];
    }

    public function messages(): array
    {
        return [
            'detalles.required' => 'No hay equipos para registrar la devolución.',
        ];
    }
}