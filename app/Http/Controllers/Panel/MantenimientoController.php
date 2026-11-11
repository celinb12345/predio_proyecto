<?php

namespace App\Http\Controllers\Panel;

use App\Http\Controllers\Controller;
use App\Http\Requests\Panel\MantenimientoRequest;
use App\Models\Mantenimiento;
use App\Models\User;
use App\Support\EstadoMantenimiento;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Inertia\Inertia;
use Inertia\Response;

class MantenimientoController extends Controller
{
    public function index(Request $request): Response
    {
        $this->soloSecretaria($request);

        EstadoMantenimiento::sincronizar();

        $mantenimientos = DB::table('mantenimientos as m')
            ->join('predios as p', 'p.id', '=', 'm.predio_id')
            ->join('responsables as r', 'r.id', '=', 'm.responsable_id')
            ->orderByDesc('m.fecha')
            ->orderByDesc('m.id')
            ->select([
                'm.id',
                'm.fecha',
                'm.fecha_fin',
                'm.descripcion',
                'm.costo',
                'p.nombre as predio',
                'r.nombre as responsable_nombre',
                'r.apellido as responsable_apellido',
            ])
            ->paginate(10)
            ->withQueryString()
            ->through(fn ($m) => [
                'id' => $m->id,
                'fecha' => Carbon::parse($m->fecha)->format('d/m/Y'),
                'fecha_fin' => Carbon::parse($m->fecha_fin ?? $m->fecha)->format('d/m/Y'),
                'descripcion' => $m->descripcion,
                'costo' => number_format((float) $m->costo, 2, '.', ''),
                'predio' => $m->predio,
                'responsable' => trim($m->responsable_nombre.' '.$m->responsable_apellido),
            ]);

        $predios = DB::table('predios')
            ->orderBy('nombre')
            ->get(['id', 'nombre'])
            ->values();

        $responsables = DB::table('responsables')
            ->where('activo', true)
            ->orderBy('apellido')
            ->get(['id', 'nombre', 'apellido'])
            ->values();

        return Inertia::render('panel/mantenimiento/Index', [
            'usuario' => $this->datosUsuario($request->user()),
            'mantenimientos' => $mantenimientos,
            'predios' => $predios,
            'responsables' => $responsables,
            'conflictos' => $this->conflictosPendientes(),
        ]);
    }

    public function store(MantenimientoRequest $request): RedirectResponse
    {
        $this->soloSecretaria($request);

        $mantenimiento = Mantenimiento::create($request->validated());

        EstadoMantenimiento::sincronizar();

        $conflictos = EstadoMantenimiento::solicitudesEnRango(
            (int) $mantenimiento->predio_id,
            $mantenimiento->fecha->toDateString(),
            ($mantenimiento->fecha_fin ?? $mantenimiento->fecha)->toDateString(),
        )->count();

        $respuesta = redirect('/panel/mantenimiento')
            ->with('exito', 'Mantenimiento registrado.');

        if ($conflictos > 0) {
            $respuesta->with(
                'advertencia',
                "Hay {$conflictos} solicitud(es) aprobada(s) en esas fechas. Revísalas abajo para liberarlas o reprogramarlas."
            );
        }

        return $respuesta;
    }

    public function destroy(Request $request, Mantenimiento $mantenimiento): RedirectResponse
    {
        $this->soloSecretaria($request);

        $mantenimiento->delete();

        EstadoMantenimiento::sincronizar();

        return redirect('/panel/mantenimiento')
            ->with('exito', 'Mantenimiento eliminado.');
    }

    // Solicitudes aprobadas que caen en fechas de mantenimiento que todavía no terminaron
    private function conflictosPendientes(): array
    {
        $hoy = now()->toDateString();

        return DB::table('mantenimientos as m')
            ->join('predios as p', 'p.id', '=', 'm.predio_id')
            ->whereRaw('COALESCE(m.fecha_fin, m.fecha) >= ?', [$hoy])
            ->orderBy('m.fecha')
            ->get(['m.predio_id', 'm.fecha', 'm.fecha_fin', 'p.nombre as predio'])
            ->flatMap(fn ($m) => EstadoMantenimiento::solicitudesEnRango(
                (int) $m->predio_id,
                (string) $m->fecha,
                (string) ($m->fecha_fin ?? $m->fecha),
            )->map(fn ($s) => [
                'clave' => $m->predio_id.'|'.$s->fecha.'|'.$s->referencia,
                'predio' => $m->predio,
                'fecha' => Carbon::parse($s->fecha)->format('d/m/Y'),
                'fecha_iso' => (string) $s->fecha,
                'horario' => substr($s->hora_inicio, 0, 5).' – '.substr($s->hora_fin, 0, 5),
                'referencia' => $s->referencia,
                'solicitante' => trim($s->persona_nombre.' '.$s->persona_apellido),
                'enlace' => "/panel/financiera?predio={$m->predio_id}&desde={$s->fecha}&hasta={$s->fecha}",
            ]))
            ->unique('clave')
            ->sortBy('fecha_iso')
            ->values()
            ->all();
    }

    private function soloSecretaria(Request $request): void
    {
        abort_unless($request->user()->esSecretariaGeneral(), 403);
    }

    private function datosUsuario(User $usuario): array
    {
        return [
            'name' => $usuario->name,
            'rol' => $usuario->rol?->descripcion,
        ];
    }
}