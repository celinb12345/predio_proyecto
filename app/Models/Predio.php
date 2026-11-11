<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Predio extends Model
{
    protected $table = 'predios';

    protected $fillable = [
        'nombre',
        'capacidad',
        'ubicacion',
        'link_google_maps',
        'estado',
        'tipo_predio_id',
        'responsable_id',
    ];
}