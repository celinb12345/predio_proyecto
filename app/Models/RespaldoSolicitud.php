<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class RespaldoSolicitud extends Model
{
    protected $table = 'respaldos_solicitud';

    protected $fillable = [
        'tipo',
        'archivo',
        'solicitud_id',
    ];
}