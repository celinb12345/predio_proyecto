<?php

namespace App\Http\Requests\Panel;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class PredioRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'nombre' => [
                'required',
                'string',
                'max:150',
                Rule::unique('predios', 'nombre')->ignore($this->route('predio')),
            ],
            'capacidad' => ['nullable', 'integer', 'min:0'],
            'ubicacion' => ['nullable', 'string', 'max:255'],
            'link_google_maps' => ['nullable', 'url:https', 'max:500'],
            'estado' => ['required', Rule::in(['disponible', 'en_mantenimiento', 'inactivo'])],
            'tipo_predio_id' => ['required', 'integer', 'exists:tipo_predios,id'],
            'responsable_id' => ['required', 'integer', 'exists:responsables,id'],
        ];
    }

    public function messages(): array
    {
        return [
            'nombre.unique' => 'Ya existe un predio con ese nombre.',
            'nombre.required' => 'Escribe el nombre del predio.',
            'link_google_maps.url' => 'El enlace de Google Maps debe comenzar con https://',
            'estado.in' => 'Selecciona un estado válido.',
        ];
    }
}