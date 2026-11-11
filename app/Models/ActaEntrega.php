<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ActaEntrega extends Model
{
    protected $table = 'actas_entrega';

    protected $fillable = [
        'fecha',
        'compromiso_buen_uso',
        'firmada',
        'fecha_devolucion',
        'reserva_id',
    ];

    protected function casts(): array
    {
        return [
            'fecha' => 'datetime',
            'fecha_devolucion' => 'datetime',
            'compromiso_buen_uso' => 'boolean',
            'firmada' => 'boolean',
        ];
    }
}