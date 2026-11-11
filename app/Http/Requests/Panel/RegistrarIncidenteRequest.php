<?php

namespace App\Http\Requests\Panel;

use Illuminate\Foundation\Http\FormRequest;

class RegistrarIncidenteRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'descripcion' => ['required', 'string', 'min:5', 'max:1000'],
            'persona_id' => ['required', 'integer', 'exists:personas,id'],
        ];
    }

    public function messages(): array
    {
        return [
            'descripcion.required' => 'Describe el incidente.',
            'descripcion.min' => 'La descripción debe tener al menos 5 caracteres.',
            'persona_id.required' => 'Indica la persona responsable.',
        ];
    }
}