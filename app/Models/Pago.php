<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Pago extends Model
{
    protected $table = 'pagos';

    protected $fillable = [
        'forma_pago',
        'monto_total',
        'fecha',
        'nro_comprobante',
        'comprobante',
        'reserva_id',
    ];
}