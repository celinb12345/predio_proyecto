<?php

namespace App\Http\Requests\Panel;

use App\Models\SolicitudPredio;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Validator;

class SolicitudRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'tipo_evento_id' => ['required', 'integer', 'exists:tipo_eventos,id'],
            'persona_id' => [
                $this->user()->esSecretariaGeneral() ? 'required' : 'nullable',
                'integer',
                'exists:personas,id',
            ],
            'predios' => ['required', 'array', 'min:1', 'max:10'],
            'predios.*.predio_id' => ['required', 'integer', 'exists:predios,id'],
            'predios.*.fecha' => ['required', 'date', 'after_or_equal:today'],
            'predios.*.hora_inicio' => ['required', 'date_format:H:i'],
            'predios.*.hora_fin' => ['required', 'date_format:H:i'],
        ];
    }

    public function withValidator(Validator $validator): void
    {
        $validator->after(function (Validator $v) {
            $filas = $this->input('predios', []);

            foreach ($filas as $i => $fila) {
                if (! isset($fila['predio_id'], $fila['fecha'], $fila['hora_inicio'], $fila['hora_fin'])) {
                    continue;
                }

                if ($fila['hora_fin'] <= $fila['hora_inicio']) {
                    $v->errors()->add("predios.$i.hora_fin", 'La hora de fin debe ser posterior a la de inicio.');
                    continue;
                }

                // Conflicto con reservas activas ya guardadas (RF14)
                $ocupado = SolicitudPredio::where('predio_id', $fila['predio_id'])
                    ->where('fecha', $fila['fecha'])
                    ->where('activo', true)
                    ->where('hora_inicio', '<', $fila['hora_fin'])
                    ->where('hora_fin', '>', $fila['hora_inicio'])
                    ->exists();

                if ($ocupado) {
                    $v->errors()->add("predios.$i.hora_inicio", 'Este predio ya está reservado en esa fecha y horario.');
                }

                // Conflicto entre filas de esta misma solicitud
                foreach ($filas as $j => $otra) {
                    if ($j <= $i || ! isset($otra['predio_id'], $otra['fecha'], $otra['hora_inicio'], $otra['hora_fin'])) {
                        continue;
                    }

                    if (
                        $otra['predio_id'] == $fila['predio_id']
                        && $otra['fecha'] === $fila['fecha']
                        && $otra['hora_inicio'] < $fila['hora_fin']
                        && $otra['hora_fin'] > $fila['hora_inicio']
                    ) {
                        $v->errors()->add("predios.$j.hora_inicio", 'Se cruza con otra fila de esta misma solicitud.');
                    }
                }
            }
        });
    }
}