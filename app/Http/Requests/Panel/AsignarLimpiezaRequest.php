<?php

namespace App\Http\Requests\Panel;

use Illuminate\Foundation\Http\FormRequest;

class AsignarLimpiezaRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'personal_limpieza_id' => ['required', 'integer', 'exists:personal_limpieza,id'],
            'fecha' => ['required', 'date'],
        ];
    }

    public function messages(): array
    {
        return [
            'personal_limpieza_id.required' => 'Selecciona a la persona que hará la limpieza.',
        ];
    }
}