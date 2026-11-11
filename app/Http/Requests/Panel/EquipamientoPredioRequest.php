<?php

namespace App\Http\Requests\Panel;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class EquipamientoPredioRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        $reglas = [
            'cantidad' => ['required', 'integer', 'min:0', 'max:999999'],
            'estado' => ['required', Rule::in(['bueno', 'regular', 'malo', 'baja'])],
        ];

        // Al agregar se escribe el nombre del equipo. Al editar solo cambian cantidad y estado.
        if ($this->route('equipamiento') === null) {
            $reglas = ['equipo' => ['required', 'string', 'min:2', 'max:150']] + $reglas;
        }

        return $reglas;
    }

    public function messages(): array
    {
        return [
            'equipo.required' => 'Escribe el nombre del equipo.',
            'equipo.min' => 'El nombre del equipo es demasiado corto.',
            'equipo.max' => 'El nombre del equipo no puede superar los 150 caracteres.',
            'cantidad.required' => 'Ingresa la cantidad.',
            'cantidad.integer' => 'La cantidad debe ser un número entero.',
            'cantidad.min' => 'La cantidad no puede ser negativa.',
            'estado.required' => 'Selecciona el estado del equipo.',
            'estado.in' => 'El estado seleccionado no es válido.',
        ];
    }
}