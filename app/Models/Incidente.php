<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Incidente extends Model
{
    protected $table = 'incidentes';

    protected $fillable = [
        'descripcion',
        'fecha',
        'acta_entrega_id',
        'persona_id',
    ];
}