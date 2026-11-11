<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Mantenimiento extends Model
{
    protected $fillable = [
        'fecha',
        'fecha_fin',
        'descripcion',
        'costo',
        'predio_id',
        'responsable_id',
    ];

    protected function casts(): array
    {
        return [
            'fecha' => 'date',
            'fecha_fin' => 'date',
            'costo' => 'decimal:2',
        ];
    }
}