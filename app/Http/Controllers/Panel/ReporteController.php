<?php

namespace App\Http\Controllers\Panel;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;
use Inertia\Inertia;
use Inertia\Response;

class ReporteController extends Controller
{
    /** Valor de solicitudes.estado que cuenta como préstamo aprobado. */
    private const ESTADO_APROBADO = 'aprobada';

    public function index(Request $request): Response
    {
        $this->soloSecretariaODecano($request);

        [$desde, $hasta] = $this->periodo($request);

        return Inertia::render('panel/reportes/Index', array_merge(
            ['usuario' => $this->datosUsuario($request->user())],
            $this->datosReporte($desde, $hasta),
        ));
    }

    public function imprimir(Request $request): Response
    {
        $this->soloSecretariaODecano($request);

        [$desde, $hasta] = $this->periodo($request);

        return Inertia::render('panel/reportes/Imprimir', array_merge(
            [
                'emitido' => now()->format('d/m/Y H:i'),
                'emitidoPor' => $request->user()->name,
            ],
            $this->datosReporte($desde, $hasta),
        ));
    }

    /** @return array{0: string, 1: string} */
    private function periodo(Request $request): array
    {
        $datos = $request->validate([
            'desde' => ['nullable', 'date_format:Y-m-d'],
            'hasta' => ['nullable', 'date_format:Y-m-d'],
        ]);

        $desde = $datos['desde'] ?? now()->startOfYear()->toDateString();
        $hasta = $datos['hasta'] ?? now()->toDateString();

        if ($hasta < $desde) {
            throw ValidationException::withMessages([
                'hasta' => 'La fecha final no puede ser anterior a la inicial.',
            ]);
        }

        return [$desde, $hasta];
    }

    private function datosReporte(string $desde, string $hasta): array
    {
        // Reservas activas de solicitudes aprobadas dentro del periodo.
        // Cada llamada crea un query nuevo para no reutilizar el mismo builder.
        $reservas = fn () => DB::table('solicitud_predios as sp')
            ->join('solicitudes as s', 's.id', '=', 'sp.solicitud_id')
            ->where('sp.activo', true)
            ->where('s.estado', self::ESTADO_APROBADO)
            ->whereBetween('sp.fecha', [$desde, $hasta]);

        // Préstamos aprobados = solicitudes distintas
        $totalPrestamos = (int) $reservas()->distinct()->count('s.id');

        // Predio más usado y horas de uso
        $usoPredios = $reservas()
            ->join('predios as p', 'p.id', '=', 'sp.predio_id')
            ->groupBy('p.id', 'p.nombre')
            ->orderByDesc('usos')
            ->get([
                'p.id',
                'p.nombre',
                DB::raw('COUNT(*) as usos'),
                DB::raw('COALESCE(SUM(EXTRACT(EPOCH FROM (sp.hora_fin - sp.hora_inicio))), 0) / 3600 as horas'),
            ])
            ->map(fn ($p) => [
                'id' => $p->id,
                'nombre' => $p->nombre,
                'usos' => (int) $p->usos,
                'horas' => round((float) $p->horas, 1),
            ])
            ->values();

        // Préstamos por tipo de evento (sin tipo también se muestra)
        $prestamosPorTipo = $reservas()
            ->leftJoin('tipo_eventos as te', 'te.id', '=', 's.tipo_evento_id')
            ->groupByRaw("COALESCE(te.descripcion, 'Sin tipo')")
            ->orderByDesc('total')
            ->get([
                DB::raw("COALESCE(te.descripcion, 'Sin tipo') as tipo"),
                DB::raw('COUNT(DISTINCT s.id) as total'),
            ])
            ->map(fn ($p) => [
                'tipo' => $p->tipo,
                'total' => (int) $p->total,
            ])
            ->values();

        // Ingresos por cuotas pagadas, agrupados por mes
        $ingresosMensuales = DB::table('cuotas')
            ->where('estado', 'pagada')
            ->whereRaw('fecha_pago::date BETWEEN ? AND ?', [$desde, $hasta])
            ->groupByRaw("to_char(fecha_pago, 'YYYY-MM')")
            ->orderByRaw("to_char(fecha_pago, 'YYYY-MM')")
            ->get([
                DB::raw("to_char(fecha_pago, 'YYYY-MM') as mes"),
                DB::raw('SUM(monto) as total'),
            ])
            ->map(fn ($i) => [
                'mes' => $i->mes,
                'total' => round((float) $i->total, 2),
            ])
            ->values();

        // Costos de mantenimiento por predio
        $costosMantenimiento = DB::table('mantenimientos as m')
            ->join('predios as p', 'p.id', '=', 'm.predio_id')
            ->whereBetween('m.fecha', [$desde, $hasta])
            ->groupBy('p.id', 'p.nombre')
            ->orderByDesc('total')
            ->get([
                'p.nombre as predio',
                DB::raw('COUNT(*) as registros'),
                DB::raw('SUM(m.costo) as total'),
            ])
            ->map(fn ($c) => [
                'predio' => $c->predio,
                'registros' => (int) $c->registros,
                'total' => round((float) $c->total, 2),
            ])
            ->values();

        return [
            'filtros' => [
                'desde' => $desde,
                'hasta' => $hasta,
            ],
            'resumen' => [
                'ingresos' => round((float) $ingresosMensuales->sum('total'), 2),
                'prestamos' => $totalPrestamos,
                'horas' => round((float) $usoPredios->sum('horas'), 1),
                'costo_mantenimiento' => round((float) $costosMantenimiento->sum('total'), 2),
            ],
            'usoPredios' => $usoPredios,
            'ingresosMensuales' => $ingresosMensuales,
            'prestamosPorTipo' => $prestamosPorTipo,
            'costosMantenimiento' => $costosMantenimiento,
        ];
    }

    private function soloSecretariaODecano(Request $request): void
    {
        $usuario = $request->user();

        abort_unless(
            $usuario->esSecretariaGeneral() || $usuario->rol?->descripcion === 'Decano',
            403
        );
    }

    private function datosUsuario(User $usuario): array
    {
        return [
            'name' => $usuario->name,
            'rol' => $usuario->rol?->descripcion,
        ];
    }
}