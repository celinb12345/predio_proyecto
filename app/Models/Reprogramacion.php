<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Reprogramacion extends Model
{
    protected $table = 'reprogramaciones';

    protected $fillable = [
        'motivo',
        'fecha_anterior',
        'hora_inicio_anterior',
        'hora_fin_anterior',
        'fecha_registro',
        'solicitud_predio_id',
        'usuario_id',
    ];
}