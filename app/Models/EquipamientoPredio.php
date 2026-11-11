<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class EquipamientoPredio extends Model
{
    protected $table = 'equipamiento_predios';

    protected $fillable = [
        'cantidad',
        'estado',
        'predio_id',
        'equipo_id',
    ];
}