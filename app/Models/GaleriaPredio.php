<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class GaleriaPredio extends Model
{
    protected $table = 'galeria_predios';

    protected $fillable = [
        'tipo',
        'archivo',
        'descripcion',
        'predio_id',
    ];
}