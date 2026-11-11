<?php

namespace App\Http\Controllers\Panel;

use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;

class CalendarioSolicitudController extends Controller
{
    private const ROLES_CON_ACCESO_TOTAL = ['Secretaría General', 'Decano'];

    public function index(Request $request): JsonResponse
    {
        abort_unless(
            in_array($request->user()->rol?->descripcion, self::ROLES_CON_ACCESO_TOTAL, true),
            403
        );

        $datos = $request->validate([
            'mes' => ['nullable', 'regex:/^\d{4}-(0[1-9]|1[0-2])$/'],
        ]);

   $inicio = ! empty($datos['mes'])
    ? Carbon::createFromFormat('Y-m-d', $datos['mes'].'-01')->startOfDay()
    : now()->startOfMonth()->startOfDay();

        $fin = $inicio->copy()->endOfMonth();

        $filas = DB::table('solicitud_predios as sp')
            ->join('solicitudes as s', 's.id', '=', 'sp.solicitud_id')
            ->join('predios as pr', 'pr.id', '=', 'sp.predio_id')
            ->join('tipo_eventos as te', 'te.id', '=', 's.tipo_evento_id')
            ->join('personas as p', 'p.id', '=', 's.persona_id')
            ->where('sp.activo', true)
            ->where('s.estado', 'aprobada')
            ->whereBetween('sp.fecha', [$inicio->toDateString(), $fin->toDateString()])
            ->orderBy('sp.fecha')
            ->orderBy('sp.hora_inicio')
            ->get([
                'sp.id',
                'sp.fecha',
                'sp.hora_inicio',
                'sp.hora_fin',
                's.referencia',
                'te.descripcion as evento',
                'p.nombre as persona_nombre',
                'p.apellido as persona_apellido',
                'pr.nombre as predio',
            ]);

        // Cuenta solicitudes distintas por día: una solicitud con varios predios cuenta una sola vez
        $dias = $filas
            ->groupBy('fecha')
            ->map(fn ($grupo) => $grupo->pluck('referencia')->unique()->count())
            ->all();

        // Días de mantenimiento que caen en este mes, recortados al rango del mes
        $mesInicio = $inicio->toDateString();
        $mesFin = $fin->toDateString();

        $mantenimientos = DB::table('mantenimientos as m')
            ->join('predios as pr', 'pr.id', '=', 'm.predio_id')
            ->whereDate('m.fecha', '<=', $mesFin)
            ->whereRaw('COALESCE(m.fecha_fin, m.fecha) >= ?', [$mesInicio])
            ->orderBy('m.fecha')
            ->get(['m.fecha', 'm.fecha_fin', 'm.descripcion', 'pr.nombre as predio'])
            ->map(fn ($m) => [
                'inicio' => max(Carbon::parse($m->fecha)->toDateString(), $mesInicio),
                'fin' => min(Carbon::parse($m->fecha_fin ?? $m->fecha)->toDateString(), $mesFin),
                'predio' => $m->predio,
                'descripcion' => $m->descripcion,
            ])
            ->values();

        return response()->json([
            'mes' => $inicio->format('Y-m'),
            'dias' => $dias,
            'eventos' => $filas->map(fn ($f) => [
                'id' => $f->id,
                'fecha' => $f->fecha,
                'inicio' => substr($f->hora_inicio, 0, 5),
                'fin' => substr($f->hora_fin, 0, 5),
                'referencia' => $f->referencia,
                'evento' => $f->evento,
                'solicitante' => trim($f->persona_nombre.' '.$f->persona_apellido),
                'predio' => $f->predio,
            ])->values(),
            'mantenimientos' => $mantenimientos,
        ]);
    }
}