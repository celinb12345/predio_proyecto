<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Liberacion extends Model
{
    protected $table = 'liberaciones_fecha';

    protected $fillable = [
        'motivo',
        'fecha_liberacion',
        'solicitud_predio_id',
        'usuario_id',
    ];
}