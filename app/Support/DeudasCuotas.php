<?php

namespace App\Support;

use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

class DeudasCuotas
{
    // Se avisa cuando una cuota vence dentro de estos días (o ya venció)
    public const DIAS_AVISO = 2;

    // Cuotas sin pagar de préstamos cobrados que siguen activos, con los datos de quien debe pagar
    public static function pendientes(): Collection
    {
        $hoy = Carbon::today();

        return DB::table('cuotas as c')
            ->join('pagos as pg', 'pg.id', '=', 'c.pago_id')
            ->join('reservas as r', 'r.id', '=', 'pg.reserva_id')
            ->join('solicitudes as s', 's.id', '=', 'r.solicitud_id')
            ->join('personas as p', 'p.id', '=', 's.persona_id')
            ->whereIn('c.estado', ['pendiente', 'vencida'])
            ->whereIn('r.estado', ['pendiente', 'confirmada'])
            ->where('r.tipo_prestamo', 'cobrado')
            ->orderBy('c.fecha_vencimiento')
            ->orderBy('c.id')
            ->get([
                'c.id',
                'c.nro_cuota',
                'c.monto',
                'c.fecha_vencimiento',
                'r.id as reserva_id',
                's.referencia',
                'p.id as persona_id',
                'p.nombre as persona_nombre',
                'p.apellido as persona_apellido',
            ])
            ->map(function ($c) use ($hoy) {
                $vence = Carbon::parse($c->fecha_vencimiento)->startOfDay();
                $dias = (int) $hoy->diffInDays($vence, false);

                return [
                    'id' => (int) $c->id,
                    'reserva_id' => (int) $c->reserva_id,
                    'persona_id' => (int) $c->persona_id,
                    'deudor' => trim($c->persona_nombre.' '.$c->persona_apellido),
                    'referencia' => $c->referencia,
                    'nro_cuota' => (int) $c->nro_cuota,
                    'monto' => (float) $c->monto,
                    'vence' => $vence->toDateString(),
                    'dias' => $dias,
                    'vencida' => $dias < 0,
                ];
            });
    }

    // Cuotas que vencen dentro de los próximos días o que ya vencieron
    public static function alertas(): array
    {
        return self::pendientes()
            ->filter(fn (array $d) => $d['dias'] <= self::DIAS_AVISO)
            ->values()
            ->all();
    }
}