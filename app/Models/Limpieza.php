<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Limpieza extends Model
{
    protected $table = 'limpiezas';

    protected $fillable = [
        'fecha',
        'estado',
        'reserva_id',
        'personal_limpieza_id',
    ];
}