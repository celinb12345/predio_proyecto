<?php

namespace App\Http\Controllers\Panel;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Inertia\Inertia;
use Inertia\Response;

class MisPagosController extends Controller
{
    public function index(Request $request): Response
    {
        $usuario = $request->user();
        $personaId = $usuario->persona_id;

        $reservas = DB::table('reservas as r')
            ->join('solicitudes as s', 's.id', '=', 'r.solicitud_id')
            ->leftJoin('pagos as pg', 'pg.reserva_id', '=', 'r.id')
            ->where('s.persona_id', $personaId)
            ->orderByDesc('r.created_at')
            ->get([
                'r.id',
                'r.estado',
                'r.tipo_prestamo',
                'r.monto_total',
                's.id as solicitud_id',
                's.referencia',
                'pg.id as pago_id',
                'pg.forma_pago',
            ]);

        $nombresPredios = DB::table('solicitud_predios as sp')
            ->join('predios as p', 'p.id', '=', 'sp.predio_id')
            ->whereIn('sp.solicitud_id', $reservas->pluck('solicitud_id')->unique()->values()->all())
            ->where('sp.activo', true)
            ->orderBy('p.nombre')
            ->get(['sp.solicitud_id', 'p.nombre'])
            ->groupBy('solicitud_id')
            ->map(fn ($grupo) => $grupo->pluck('nombre')->implode(', '));

        $cuotas = DB::table('cuotas')
            ->whereIn('pago_id', $reservas->pluck('pago_id')->filter()->unique()->values()->all())
            ->orderBy('nro_cuota')
            ->get([
                'id',
                'pago_id',
                'nro_cuota',
                'monto',
                'fecha_vencimiento',
                'fecha_pago',
                'estado',
                'nro_comprobante',
            ])
            ->groupBy('pago_id');

        $reservas = $reservas->map(function ($r) use ($nombresPredios, $cuotas) {
            $listaCuotas = $cuotas->get($r->pago_id) ?? collect();
            $activa = in_array($r->estado, ['pendiente', 'confirmada'], true);
            $cuota1Pagada = $listaCuotas->firstWhere('nro_cuota', 1)?->estado === 'pagada';

            return [
                'id' => $r->id,
                'referencia' => $r->referencia,
                'predios' => $nombresPredios->get($r->solicitud_id, ''),
                'estado' => $r->estado,
                'tipo_prestamo' => $r->tipo_prestamo,
                'monto_total' => round((float) $r->monto_total, 2),
                'forma_pago' => $r->forma_pago,
                'puede_plan_qr' => $r->tipo_prestamo === 'cobrado' && $activa && $r->pago_id === null,
                'cuotas' => $listaCuotas->map(fn ($c) => [
                    'id' => $c->id,
                    'nro_cuota' => (int) $c->nro_cuota,
                    'monto' => round((float) $c->monto, 2),
                    'vencimiento' => Carbon::parse($c->fecha_vencimiento)->format('d/m/Y'),
                    'pagada_el' => $c->fecha_pago ? Carbon::parse($c->fecha_pago)->format('d/m/Y') : null,
                    'estado' => $c->estado,
                    'nro_comprobante' => $c->nro_comprobante,
                    'puede_qr' => $activa
                        && in_array($c->estado, ['pendiente', 'vencida'], true)
                        && ((int) $c->nro_cuota !== 2 || $cuota1Pagada),
                    'url_generar_qr' => route('panel.cuotas.qr.generar', $c->id),
                ])->values(),
            ];
        })->values();

        $beneficios = DB::table('beneficios')
            ->where('persona_id', $personaId)
            ->orderByDesc('fecha')
            ->get(['descripcion', 'fecha'])
            ->map(fn ($b) => [
                'descripcion' => $b->descripcion,
                'fecha' => Carbon::parse($b->fecha)->format('d/m/Y'),
            ])
            ->values();

        $sanciones = DB::table('sanciones')
            ->where('persona_id', $personaId)
            ->orderByDesc('fecha')
            ->get(['motivo', 'fecha', 'estado'])
            ->map(fn ($s) => [
                'motivo' => $s->motivo,
                'fecha' => Carbon::parse($s->fecha)->format('d/m/Y'),
                'estado' => $s->estado,
            ])
            ->values();

        return Inertia::render('panel/mis-pagos/Index', [
            'usuario' => $this->datosUsuario($usuario),
            'reservas' => $reservas,
            'beneficios' => $beneficios,
            'sanciones' => $sanciones,
        ]);
    }

    private function datosUsuario(User $usuario): array
    {
        return [
            'name' => $usuario->name,
            'rol' => $usuario->rol?->descripcion,
        ];
    }
}