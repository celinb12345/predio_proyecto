<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Sancion extends Model
{
    protected $table = 'sanciones';

    protected $fillable = [
        'motivo',
        'fecha',
        'estado',
        'persona_id',
        'reserva_id',
    ];
}