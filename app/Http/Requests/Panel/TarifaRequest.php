<?php

namespace App\Http\Requests\Panel;

use Illuminate\Foundation\Http\FormRequest;

class TarifaRequest extends FormRequest
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
        ];
    }
}