<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Cuota extends Model
{
    protected $table = 'cuotas';

    protected $fillable = [
        'nro_cuota',
        'monto',
        'fecha_vencimiento',
        'fecha_pago',
        'estado',
        'nro_comprobante',
        'comprobante',
        'pago_id',
        'medio_pago',
        'qr_estado',
        'qr_id_empresa',
        'qr_id_transaccion',
        'qr_imagen',
        'qr_expira_en',
        'qr_pagado_en',
    ];

    // Estos datos no deben salir hacia el navegador por accidente
    protected $hidden = [
        'qr_imagen',
        'qr_id_empresa',
    ];

    protected function casts(): array
    {
        return [
            'fecha_vencimiento' => 'date',
            'fecha_pago' => 'datetime',
            'qr_expira_en' => 'datetime',
            'qr_pagado_en' => 'datetime',
        ];
    }
}