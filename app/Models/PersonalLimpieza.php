<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class PersonalLimpieza extends Model
{
    protected $table = 'personal_limpieza';

    protected $fillable = [
        'nombre',
        'apellido',
        'celular',
        'activo',
    ];

    protected function casts(): array
    {
        return [
            'activo' => 'boolean',
        ];
    }
}