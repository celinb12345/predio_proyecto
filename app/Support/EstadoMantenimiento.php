<?php

namespace App\Support;

use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

class EstadoMantenimiento
{
    /**
     * Predios con mantenimiento hoy pasan a "en_mantenimiento".
     * Cuando el mantenimiento termina, vuelven a "disponible".
     * Los predios marcados como "inactivo" no se tocan.
     */
    public static function sincronizar(): void
    {
        $hoy = now()->toDateString();

        $enMantenimientoHoy = fn () => DB::table('mantenimientos')
            ->whereDate('fecha', '<=', $hoy)
            ->whereRaw('COALESCE(fecha_fin, fecha) >= ?', [$hoy])
            ->select('predio_id');

        DB::table('predios')
            ->where('estado', 'disponible')
            ->whereIn('id', $enMantenimientoHoy())
            ->update(['estado' => 'en_mantenimiento']);

        DB::table('predios')
            ->where('estado', 'en_mantenimiento')
            ->whereNotIn('id', $enMantenimientoHoy())
            ->update(['estado' => 'disponible']);
    }

    /**
     * Solicitudes aprobadas que caen en un rango de fechas de un predio.
     */
    public static function solicitudesEnRango(int $predioId, string $desde, string $hasta): Collection
    {
        return DB::table('solicitud_predios as sp')
            ->join('solicitudes as s', 's.id', '=', 'sp.solicitud_id')
            ->join('personas as p', 'p.id', '=', 's.persona_id')
            ->where('sp.predio_id', $predioId)
            ->where('sp.activo', true)
            ->where('s.estado', 'aprobada')
            ->whereBetween('sp.fecha', [$desde, $hasta])
            ->orderBy('sp.fecha')
            ->orderBy('sp.hora_inicio')
            ->get([
                'sp.fecha',
                'sp.hora_inicio',
                'sp.hora_fin',
                's.referencia',
                'p.nombre as persona_nombre',
                'p.apellido as persona_apellido',
            ]);
    }
}