<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Tarifa extends Model
{
    protected $table = 'tarifas';

    protected $fillable = [
        'monto',
        'resolucion',
        'vigente',
        'predio_id',
        'tipo_persona_id',
    ];

    protected function casts(): array
    {
        return [
            'vigente' => 'boolean',
        ];
    }
}