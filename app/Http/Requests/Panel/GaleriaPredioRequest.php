<?php

namespace App\Http\Requests\Panel;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class GaleriaPredioRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'archivo' => ['required', 'image', 'mimes:jpg,jpeg,png,webp', 'max:4096'],
            'tipo' => ['required', Rule::in(['ambiente', 'entrada', 'otro'])],
            'descripcion' => ['nullable', 'string', 'max:255'],
        ];
    }
}