<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Solicitud extends Model
{
    protected $table = 'solicitudes';

    protected $fillable = [
        'referencia',
        'proveido',
        'estado',
        'tipo_evento_id',
        'persona_id',
    ];
}