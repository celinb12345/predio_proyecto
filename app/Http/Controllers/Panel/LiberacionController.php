<?php

namespace App\Http\Controllers\Panel;

use App\Http\Controllers\Controller;
use App\Http\Requests\Panel\LiberacionRequest;
use App\Http\Requests\Panel\ReprogramacionRequest;
use App\Models\Liberacion;
use App\Models\Reprogramacion;
use App\Models\Reserva;
use App\Models\SolicitudPredio;
use App\Models\User;
use App\Support\CategoriasPersona;
use Illuminate\Database\QueryException;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;
use Inertia\Inertia;
use Inertia\Response;

class LiberacionController extends Controller
{
         public function index(Request $request): Response
    {
        $this->soloSecretaria($request);

        $datos = $request->validate([
            'predio' => ['nullable', 'integer'],
            'desde' => ['nullable', 'date_format:Y-m-d'],
            'hasta' => ['nullable', 'date_format:Y-m-d'],
        ]);

        $predioId = $datos['predio'] ?? null;
        $desde = $datos['desde'] ?? null;
        $hasta = $datos['hasta'] ?? null;

        $filas = DB::table('solicitud_predios as sp')
            ->join('solicitudes as s', 's.id', '=', 'sp.solicitud_id')
            ->join('reservas as r', 'r.solicitud_id', '=', 's.id')
            ->join('predios as p', 'p.id', '=', 'sp.predio_id')
            ->join('personas as pe', 'pe.id', '=', 's.persona_id')
            ->leftJoin('tipo_personas as tp', 'tp.id', '=', 'pe.tipo_persona_id')
            ->join('tipo_eventos as te', 'te.id', '=', 's.tipo_evento_id')
            ->where('sp.activo', true)
            ->whereIn('r.estado', ['pendiente', 'confirmada'])
            ->when($predioId, fn ($q) => $q->where('sp.predio_id', $predioId))
            ->when($desde, fn ($q) => $q->where('sp.fecha', '>=', $desde))
            ->when($hasta, fn ($q) => $q->where('sp.fecha', '<=', $hasta))
            ->orderBy('sp.fecha')
            ->orderBy('sp.hora_inicio')
            ->get([
                'sp.id',
                'sp.fecha',
                'sp.hora_inicio',
                'sp.hora_fin',
                'p.nombre as predio',
                's.referencia',
                'pe.nombre as persona_nombre',
                'pe.apellido as persona_apellido',
                'tp.descripcion as tipo_persona',
                'te.descripcion as evento',
                'r.estado as estado_reserva',
            ]);

        // Agrupa por tipo de persona. Siempre se muestran las categorías de la jerarquía,
        // aunque no tengan reservas activas, y cualquier otro tipo que exista en los datos.
        $porClave = $filas->groupBy(fn ($f) => CategoriasPersona::clave($f->tipo_persona));

        $grupos = collect(array_keys(CategoriasPersona::ORDEN))
            ->merge($porClave->keys())
            ->unique()
            ->filter(fn ($clave) => array_key_exists($clave, CategoriasPersona::ORDEN) || $porClave->has($clave))
            ->map(function ($clave) use ($porClave) {
                $items = $porClave->get($clave, collect());

                return [
                    'clave' => $clave !== '' ? $clave : 'sin-categoria',
                    'categoria' => CategoriasPersona::etiqueta($clave),
                    'orden' => CategoriasPersona::orden($clave),
                    'total' => $items->count(),
                    'filas' => $items->map(fn ($f) => [
                        'id' => $f->id,
                        'predio' => $f->predio,
                        'fecha' => Carbon::parse($f->fecha)->format('d/m/Y'),
                        'horario' => $this->rangoHora($f->hora_inicio, $f->hora_fin),
                        'referencia' => $f->referencia,
                        'solicitante' => trim($f->persona_nombre.' '.$f->persona_apellido),
                        'evento' => $f->evento,
                        'estado_reserva' => $f->estado_reserva,
                    ])->values()->all(),
                ];
            })
            ->sortBy('orden')
            ->values()
            ->all();

        $nombrePredio = $predioId
            ? DB::table('predios')->where('id', $predioId)->value('nombre')
            : null;

        return Inertia::render('panel/financiera/Index', [
            'usuario' => $this->datosUsuario($request->user()),
            'grupos' => $grupos,
            'total' => $filas->count(),
            'filtro' => [
                'activo' => $predioId !== null || $desde !== null || $hasta !== null,
                'predio' => $nombrePredio,
                'desde' => $desde ? Carbon::parse($desde)->format('d/m/Y') : null,
                'hasta' => $hasta ? Carbon::parse($hasta)->format('d/m/Y') : null,
            ],
        ]);
    }

    public function historial(Request $request): Response
    {
        $this->soloSecretaria($request);

        $liberaciones = DB::table('liberaciones_fecha as l')
            ->join('solicitud_predios as sp', 'sp.id', '=', 'l.solicitud_predio_id')
            ->join('solicitudes as s', 's.id', '=', 'sp.solicitud_id')
            ->join('predios as p', 'p.id', '=', 'sp.predio_id')
            ->join('personas as pe', 'pe.id', '=', 's.persona_id')
            ->leftJoin('tipo_personas as tp', 'tp.id', '=', 'pe.tipo_persona_id')
            ->join('users as u', 'u.id', '=', 'l.usuario_id')
            ->leftJoin('roles as ro', 'ro.id', '=', 'u.role_id')
            ->get([
                'l.id',
                'l.motivo',
                'l.fecha_liberacion as momento',
                'sp.fecha',
                'sp.hora_inicio',
                'sp.hora_fin',
                'p.nombre as predio',
                's.referencia',
                'pe.nombre as persona_nombre',
                'pe.apellido as persona_apellido',
                'tp.descripcion as tipo_persona',
                'u.name as usuario',
                'ro.descripcion as rol',
            ])
            ->map(fn ($l) => [
                'id' => 'l-'.$l->id,
                'tipo' => 'liberacion',
                'momento' => Carbon::parse($l->momento),
                'predio' => $l->predio,
                'referencia' => $l->referencia,
                'solicitante' => trim($l->persona_nombre.' '.$l->persona_apellido),
                'categoria' => CategoriasPersona::etiqueta($l->tipo_persona),
                'orden' => CategoriasPersona::orden($l->tipo_persona),
                'horario' => $this->horarioCompleto($l->fecha, $l->hora_inicio, $l->hora_fin),
                'horario_nuevo' => null,
                'motivo' => $l->motivo,
                'usuario' => $l->usuario,
                'rol' => $l->rol,
            ]);

        $reprogramaciones = DB::table('reprogramaciones as rp')
            ->join('solicitud_predios as sp', 'sp.id', '=', 'rp.solicitud_predio_id')
            ->join('solicitudes as s', 's.id', '=', 'sp.solicitud_id')
            ->join('predios as p', 'p.id', '=', 'sp.predio_id')
            ->join('personas as pe', 'pe.id', '=', 's.persona_id')
            ->leftJoin('tipo_personas as tp', 'tp.id', '=', 'pe.tipo_persona_id')
            ->join('users as u', 'u.id', '=', 'rp.usuario_id')
            ->leftJoin('roles as ro', 'ro.id', '=', 'u.role_id')
            ->get([
                'rp.id',
                'rp.motivo',
                'rp.fecha_registro as momento',
                'rp.fecha_anterior',
                'rp.hora_inicio_anterior',
                'rp.hora_fin_anterior',
                'sp.id as solicitud_predio_id',
                'sp.solicitud_id',
                'sp.predio_id',
                'p.nombre as predio',
                's.referencia',
                'pe.nombre as persona_nombre',
                'pe.apellido as persona_apellido',
                'tp.descripcion as tipo_persona',
                'u.name as usuario',
                'ro.descripcion as rol',
            ])
            ->map(function ($r) {
                // La nueva fecha es la siguiente fila de la misma solicitud y predio creada después de la anterior
                $nueva = DB::table('solicitud_predios')
                    ->where('solicitud_id', $r->solicitud_id)
                    ->where('predio_id', $r->predio_id)
                    ->where('id', '>', $r->solicitud_predio_id)
                    ->orderBy('id')
                    ->first(['fecha', 'hora_inicio', 'hora_fin']);

                return [
                    'id' => 'r-'.$r->id,
                    'tipo' => 'reprogramacion',
                    'momento' => Carbon::parse($r->momento),
                    'predio' => $r->predio,
                    'referencia' => $r->referencia,
                    'solicitante' => trim($r->persona_nombre.' '.$r->persona_apellido),
                    'categoria' => CategoriasPersona::etiqueta($r->tipo_persona),
                    'orden' => CategoriasPersona::orden($r->tipo_persona),
                    'horario' => $this->horarioCompleto($r->fecha_anterior, $r->hora_inicio_anterior, $r->hora_fin_anterior),
                    'horario_nuevo' => $nueva
                        ? $this->horarioCompleto($nueva->fecha, $nueva->hora_inicio, $nueva->hora_fin)
                        : null,
                    'motivo' => $r->motivo,
                    'usuario' => $r->usuario,
                    'rol' => $r->rol,
                ];
            });

        $movimientos = $liberaciones
            ->concat($reprogramaciones)
            ->sortByDesc(fn (array $m) => $m['momento']->timestamp)
            ->values();

        $resumen = [
            'liberaciones' => $liberaciones->count(),
            'reprogramaciones' => $reprogramaciones->count(),
            'hoy' => $movimientos->filter(fn (array $m) => $m['momento']->isToday())->count(),
        ];

        $categorias = $movimientos
            ->map(fn (array $m) => ['etiqueta' => $m['categoria'], 'orden' => $m['orden']])
            ->unique('etiqueta')
            ->sortBy('orden')
            ->values()
            ->all();

        return Inertia::render('panel/financiera/Historial', [
            'usuario' => $this->datosUsuario($request->user()),
            'movimientos' => $movimientos->map(fn (array $m) => [
                'id' => $m['id'],
                'tipo' => $m['tipo'],
                'momento' => $m['momento']->format('d/m/Y H:i'),
                'predio' => $m['predio'],
                'referencia' => $m['referencia'],
                'solicitante' => $m['solicitante'],
                'categoria' => $m['categoria'],
                'horario' => $m['horario'],
                'horario_nuevo' => $m['horario_nuevo'],
                'motivo' => $m['motivo'],
                'usuario' => $m['usuario'],
                'rol' => $m['rol'],
            ])->values()->all(),
            'resumen' => $resumen,
            'categorias' => $categorias,
        ]);
    }

    public function show(Request $request, SolicitudPredio $solicitudPredio): Response
    {
        $this->soloSecretaria($request);

        $fila = DB::table('solicitud_predios as sp')
            ->join('solicitudes as s', 's.id', '=', 'sp.solicitud_id')
            ->join('predios as p', 'p.id', '=', 'sp.predio_id')
            ->join('personas as pe', 'pe.id', '=', 's.persona_id')
            ->join('tipo_eventos as te', 'te.id', '=', 's.tipo_evento_id')
            ->leftJoin('reservas as r', 'r.solicitud_id', '=', 's.id')
            ->where('sp.id', $solicitudPredio->id)
            ->first([
                'sp.id',
                'sp.solicitud_id',
                'sp.fecha',
                'sp.hora_inicio',
                'sp.hora_fin',
                'sp.activo',
                'p.nombre as predio',
                's.referencia',
                'pe.nombre as persona_nombre',
                'pe.apellido as persona_apellido',
                'te.descripcion as evento',
                'r.estado as estado_reserva',
                'r.tipo_prestamo',
                'r.monto_total',
            ]);

        abort_unless($fila, 404);

        $liberaciones = DB::table('liberaciones_fecha as l')
            ->join('solicitud_predios as sp', 'sp.id', '=', 'l.solicitud_predio_id')
            ->join('users as u', 'u.id', '=', 'l.usuario_id')
            ->where('sp.solicitud_id', $fila->solicitud_id)
            ->orderByDesc('l.fecha_liberacion')
            ->get([
                'l.motivo',
                'l.fecha_liberacion',
                'sp.fecha',
                'sp.hora_inicio',
                'sp.hora_fin',
                'u.name as usuario',
            ])
            ->map(fn ($l) => [
                'motivo' => $l->motivo,
                'registro' => Carbon::parse($l->fecha_liberacion)->format('d/m/Y H:i'),
                'horario' => Carbon::parse($l->fecha)->format('d/m/Y').' '.substr($l->hora_inicio, 0, 5).' – '.substr($l->hora_fin, 0, 5),
                'usuario' => $l->usuario,
            ])
            ->values();

        $reprogramaciones = DB::table('reprogramaciones as rp')
            ->join('solicitud_predios as sp', 'sp.id', '=', 'rp.solicitud_predio_id')
            ->join('users as u', 'u.id', '=', 'rp.usuario_id')
            ->where('sp.solicitud_id', $fila->solicitud_id)
            ->orderByDesc('rp.fecha_registro')
            ->get([
                'rp.motivo',
                'rp.fecha_registro',
                'rp.fecha_anterior',
                'rp.hora_inicio_anterior',
                'rp.hora_fin_anterior',
                'u.name as usuario',
            ])
            ->map(fn ($r) => [
                'motivo' => $r->motivo,
                'registro' => Carbon::parse($r->fecha_registro)->format('d/m/Y H:i'),
                'horario' => Carbon::parse($r->fecha_anterior)->format('d/m/Y').' '.substr($r->hora_inicio_anterior, 0, 5).' – '.substr($r->hora_fin_anterior, 0, 5),
                'usuario' => $r->usuario,
            ])
            ->values();

        return Inertia::render('panel/financiera/Show', [
            'usuario' => $this->datosUsuario($request->user()),
            'fila' => [
                'id' => $fila->id,
                'predio' => $fila->predio,
                'fecha_texto' => Carbon::parse($fila->fecha)->format('d/m/Y'),
                'horario' => substr($fila->hora_inicio, 0, 5).' – '.substr($fila->hora_fin, 0, 5),
                'activo' => (bool) $fila->activo,
                'referencia' => $fila->referencia,
                'solicitante' => trim($fila->persona_nombre.' '.$fila->persona_apellido),
                'evento' => $fila->evento,
                'estado_reserva' => $fila->estado_reserva,
                'tipo_prestamo' => $fila->tipo_prestamo,
                'monto_total' => $fila->monto_total !== null
                    ? number_format((float) $fila->monto_total, 2, '.', ',')
                    : null,
            ],
            'liberaciones' => $liberaciones,
            'reprogramaciones' => $reprogramaciones,
        ]);
    }

    public function liberar(LiberacionRequest $request, SolicitudPredio $solicitudPredio): RedirectResponse
    {
        $this->soloSecretaria($request);

        try {
            DB::transaction(function () use ($request, $solicitudPredio) {
                $fila = SolicitudPredio::whereKey($solicitudPredio->id)->lockForUpdate()->firstOrFail();
                abort_unless($fila->activo, 422, 'Esta fecha ya fue liberada.');

                Liberacion::create([
                    'motivo' => $request->validated('motivo'),
                    'fecha_liberacion' => now(),
                    'solicitud_predio_id' => $fila->id,
                    'usuario_id' => $request->user()->id,
                ]);

                $this->cancelarReservaSiNoQuedanFechas((int) $fila->solicitud_id);
            });
        } catch (QueryException) {
            return back()->withErrors([
                'motivo' => 'No se pudo registrar la liberación. Intenta de nuevo.',
            ]);
        }

        return redirect('/panel/financiera');
    }

    public function reprogramar(ReprogramacionRequest $request, SolicitudPredio $solicitudPredio): RedirectResponse
    {
        $this->soloSecretaria($request);
        $datos = $request->validated();

        try {
            DB::transaction(function () use ($request, $solicitudPredio, $datos) {
                $anterior = SolicitudPredio::whereKey($solicitudPredio->id)->lockForUpdate()->firstOrFail();
                abort_unless($anterior->activo, 422, 'Esta fecha ya fue liberada.');

                if (SolicitudPredio::predioOcupado(
                    (int) $anterior->predio_id,
                    $datos['fecha'],
                    $datos['hora_inicio'],
                    $datos['hora_fin'],
                )) {
                    throw ValidationException::withMessages([
                        'hora_inicio' => 'El predio ya está reservado en la nueva fecha y horario.',
                    ]);
                }

                Liberacion::create([
                    'motivo' => $datos['motivo'],
                    'fecha_liberacion' => now(),
                    'solicitud_predio_id' => $anterior->id,
                    'usuario_id' => $request->user()->id,
                ]);

                Reprogramacion::create([
                    'motivo' => $datos['motivo'],
                    'fecha_anterior' => $anterior->fecha,
                    'hora_inicio_anterior' => $anterior->hora_inicio,
                    'hora_fin_anterior' => $anterior->hora_fin,
                    'fecha_registro' => now(),
                    'solicitud_predio_id' => $anterior->id,
                    'usuario_id' => $request->user()->id,
                ]);

                SolicitudPredio::create([
                    'solicitud_id' => $anterior->solicitud_id,
                    'predio_id' => $anterior->predio_id,
                    'fecha' => $datos['fecha'],
                    'hora_inicio' => $datos['hora_inicio'],
                    'hora_fin' => $datos['hora_fin'],
                    'activo' => true,
                ]);
            });
        } catch (QueryException) {
            return back()->withErrors([
                'hora_inicio' => 'No se pudo reprogramar: el horario choca con otra reserva.',
            ]);
        }

        return redirect('/panel/financiera');
    }

    private function cancelarReservaSiNoQuedanFechas(int $solicitudId): void
    {
        $quedanFechas = SolicitudPredio::where('solicitud_id', $solicitudId)
            ->where('activo', true)
            ->exists();

        if (! $quedanFechas) {
            Reserva::where('solicitud_id', $solicitudId)->update(['estado' => 'cancelada']);
        }
    }

    private function rangoHora(string $inicio, string $fin): string
    {
        return substr($inicio, 0, 5).' – '.substr($fin, 0, 5);
    }

    private function horarioCompleto(string $fecha, string $inicio, string $fin): string
    {
        return Carbon::parse($fecha)->format('d/m/Y').' · '.$this->rangoHora($inicio, $fin);
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