<?php

namespace App\Http\Requests\Panel;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class RegistrarPagoRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'forma_pago' => ['required', Rule::in(['contado', 'credito_2_cuotas'])],
        ];
    }

    public function messages(): array
    {
        return [
            'forma_pago.required' => 'Selecciona la forma de pago.',
            'forma_pago.in' => 'La forma de pago no es válida.',
        ];
    }
}