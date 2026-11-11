<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class DetalleActa extends Model
{
    protected $table = 'detalle_actas';

    protected $fillable = [
        'cantidad',
        'estado_entrega',
        'estado_devolucion',
        'acta_entrega_id',
        'equipamiento_predio_id',
    ];
}