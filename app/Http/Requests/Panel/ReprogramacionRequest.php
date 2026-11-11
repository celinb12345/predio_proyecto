<?php

namespace App\Http\Requests\Panel;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Validator;

class ReprogramacionRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'motivo' => ['required', 'string', 'min:5', 'max:500'],
            'fecha' => ['required', 'date', 'after_or_equal:today'],
            'hora_inicio' => ['required', 'date_format:H:i'],
            'hora_fin' => ['required', 'date_format:H:i'],
        ];
    }

    public function messages(): array
    {
        return [
            'motivo.required' => 'Escribe el motivo de la reprogramación.',
            'motivo.min' => 'El motivo debe tener al menos 5 caracteres.',
            'fecha.after_or_equal' => 'La nueva fecha no puede ser anterior a hoy.',
        ];
    }

    public function withValidator(Validator $validator): void
    {
        $validator->after(function (Validator $v) {
            if ($this->filled('hora_inicio') && $this->filled('hora_fin')
                && $this->input('hora_fin') <= $this->input('hora_inicio')) {
                $v->errors()->add('hora_fin', 'La hora de fin debe ser posterior a la de inicio.');
            }
        });
    }
}