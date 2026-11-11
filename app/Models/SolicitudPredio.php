<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class SolicitudPredio extends Model
{
    protected $table = 'solicitud_predios';

    protected $fillable = [
        'fecha',
        'hora_inicio',
        'hora_fin',
        'activo',
        'solicitud_id',
        'predio_id',
    ];

    protected function casts(): array
    {
        return [
            'activo' => 'boolean',
        ];
    }

    public static function predioOcupado(int $predioId, string $fecha, string $horaInicio, string $horaFin): bool
    {
        return self::query()
            ->where('predio_id', $predioId)
            ->where('fecha', $fecha)
            ->where('activo', true)
            ->where('hora_inicio', '<', $horaFin)
            ->where('hora_fin', '>', $horaInicio)
            ->exists();
    }
}