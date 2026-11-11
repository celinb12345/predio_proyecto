<?php

namespace App\Http\Requests\Panel;

use Illuminate\Foundation\Http\FormRequest;

class RegistrarCuotaRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'cuota_id' => ['required', 'integer', 'exists:cuotas,id'],
            'nro_comprobante' => ['nullable', 'string', 'max:50'],
            'comprobante' => ['nullable', 'file', 'mimes:pdf,jpg,jpeg,png', 'max:5120'],
        ];
    }

    public function messages(): array
    {
        return [
            'cuota_id.required' => 'Selecciona la cuota que se va a cobrar.',
            'comprobante.mimes' => 'El comprobante debe ser PDF, JPG o PNG.',
            'comprobante.max' => 'El comprobante no puede pesar más de 5 MB.',
        ];
    }
}