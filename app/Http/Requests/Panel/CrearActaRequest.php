<?php

namespace App\Http\Requests\Panel;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class CrearActaRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'compromiso_buen_uso' => ['accepted'],
            'firmada' => ['accepted'],
            'detalles' => ['array'],
            'detalles.*.equipamiento_predio_id' => ['required', 'integer', 'exists:equipamiento_predios,id'],
            'detalles.*.cantidad' => ['required', 'integer', 'min:1'],
            'detalles.*.estado_entrega' => ['required', Rule::in(['bueno', 'regular', 'malo'])],
        ];
    }

    public function messages(): array
    {
        return [
            'compromiso_buen_uso.accepted' => 'El solicitante debe aceptar el compromiso de buen uso.',
            'firmada.accepted' => 'Confirma que el acta fue firmada.',
            'detalles.*.cantidad.min' => 'La cantidad debe ser al menos 1.',
        ];
    }
}