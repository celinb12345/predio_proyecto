<?php

namespace App\Http\Controllers\Panel;

use App\Http\Controllers\Controller;
use App\Http\Requests\Panel\CrearActaRequest;
use App\Http\Requests\Panel\RegistrarDevolucionRequest;
use App\Http\Requests\Panel\RegistrarIncidenteRequest;
use App\Models\ActaEntrega;
use App\Models\Beneficio;
use App\Models\DetalleActa;
use App\Models\Incidente;
use App\Models\PersonalLimpieza;
use App\Models\Reserva;
use App\Models\User;
use Illuminate\Database\QueryException;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;
use Inertia\Inertia;
use Inertia\Response;

class EntregaController extends Controller
{
    public function index(Request $request): Response
    {
        $this->soloSecretaria($request);

        $filas = DB::table('reservas as r')
            ->join('solicitudes as s', 's.id', '=', 'r.solicitud_id')
            ->join('personas as p', 'p.id', '=', 's.persona_id')
            ->leftJoin('actas_entrega as a', 'a.reserva_id', '=', 'r.id')
            ->whereIn('r.estado', ['pendiente', 'confirmada', 'finalizada'])
            ->orderByDesc('r.created_at')
            ->select([
                'r.id',
                'r.estado as estado_reserva',
                's.referencia',
                'p.nombre as persona_nombre',
                'p.apellido as persona_apellido',
                'a.id as acta_id',
                'a.fecha_devolucion',
            ])
            ->paginate(10)
            ->withQueryString()
            ->through(fn ($f) => [
                'id' => $f->id,
                'referencia' => $f->referencia,
                'solicitante' => trim($f->persona_nombre.' '.$f->persona_apellido),
                'estado_reserva' => $f->estado_reserva,
                'situacion' => match (true) {
                    $f->acta_id === null => 'Sin acta de entrega',
                    $f->fecha_devolucion === null => 'Predio entregado',
                    default => 'Devuelto',
                },
            ]);

        $personal = PersonalLimpieza::orderBy('apellido')
            ->get(['id', 'nombre', 'apellido', 'celular', 'activo'])
            ->map(fn ($p) => [
                'id' => $p->id,
                'nombre' => $p->nombre,
                'apellido' => $p->apellido,
                'celular' => $p->celular,
                'activo' => (bool) $p->activo,
            ])
            ->values();

        return Inertia::render('panel/entrega/Index', [
            'usuario' => $this->datosUsuario($request->user()),
            'filas' => $filas,
            'personal' => $personal,
        ]);
    }

    public function show(Request $request, Reserva $reserva): Response
    {
        $this->soloSecretaria($request);

        $solicitud = DB::table('solicitudes as s')
            ->join('personas as p', 'p.id', '=', 's.persona_id')
            ->where('s.id', $reserva->solicitud_id)
            ->first(['s.referencia', 's.persona_id', 'p.nombre', 'p.apellido']);

        $acta = ActaEntrega::where('reserva_id', $reserva->id)->first();
        $activa = in_array($reserva->estado, ['pendiente', 'confirmada'], true);
        $devuelta = $acta !== null && $acta->fecha_devolucion !== null;

        $detalles = collect();
        $incidentes = collect();

        if ($acta) {
            $detalles = DB::table('detalle_actas as d')
                ->join('equipamiento_predios as ep', 'ep.id', '=', 'd.equipamiento_predio_id')
                ->join('equipos as eq', 'eq.id', '=', 'ep.equipo_id')
                ->join('predios as p', 'p.id', '=', 'ep.predio_id')
                ->where('d.acta_entrega_id', $acta->id)
                ->orderBy('p.nombre')
                ->orderBy('eq.descripcion')
                ->get([
                    'd.id',
                    'd.cantidad',
                    'd.estado_entrega',
                    'd.estado_devolucion',
                    'eq.descripcion as equipo',
                    'p.nombre as predio',
                ])
                ->map(fn ($d) => [
                    'id' => $d->id,
                    'predio' => $d->predio,
                    'equipo' => $d->equipo,
                    'cantidad' => (int) $d->cantidad,
                    'estado_entrega' => $d->estado_entrega,
                    'estado_devolucion' => $d->estado_devolucion,
                ])
                ->values();

            $incidentes = DB::table('incidentes as i')
                ->join('personas as pe', 'pe.id', '=', 'i.persona_id')
                ->where('i.acta_entrega_id', $acta->id)
                ->orderByDesc('i.fecha')
                ->get(['i.descripcion', 'i.fecha', 'pe.nombre', 'pe.apellido'])
                ->map(fn ($i) => [
                    'descripcion' => $i->descripcion,
                    'fecha' => Carbon::parse($i->fecha)->format('d/m/Y H:i'),
                    'persona' => trim($i->nombre.' '.$i->apellido),
                ])
                ->values();
        }

        $limpieza = DB::table('limpiezas as l')
            ->join('personal_limpieza as pl', 'pl.id', '=', 'l.personal_limpieza_id')
            ->where('l.reserva_id', $reserva->id)
            ->first(['l.estado', 'l.fecha', 'pl.nombre', 'pl.apellido']);

        $sanciones = DB::table('sanciones as sa')
            ->join('personas as p', 'p.id', '=', 'sa.persona_id')
            ->where('sa.reserva_id', $reserva->id)
            ->orderByDesc('sa.fecha')
            ->get(['sa.id', 'sa.motivo', 'sa.fecha', 'sa.estado', 'p.nombre', 'p.apellido'])
            ->map(fn ($s) => [
                'id' => $s->id,
                'motivo' => $s->motivo,
                'fecha' => Carbon::parse($s->fecha)->format('d/m/Y'),
                'estado' => $s->estado,
                'persona' => trim($s->nombre.' '.$s->apellido),
            ])
            ->values();

        $beneficios = DB::table('beneficios')
            ->where('persona_id', $solicitud->persona_id)
            ->orderByDesc('fecha')
            ->limit(10)
            ->get(['descripcion', 'fecha'])
            ->map(fn ($b) => [
                'descripcion' => $b->descripcion,
                'fecha' => Carbon::parse($b->fecha)->format('d/m/Y'),
            ])
            ->values();

        $personalActivo = PersonalLimpieza::where('activo', true)
            ->orderBy('apellido')
            ->get(['id', 'nombre', 'apellido'])
            ->map(fn ($p) => ['id' => $p->id, 'nombre' => $p->nombre, 'apellido' => $p->apellido])
            ->values();

        $equipamiento = $this->equipamientoDelReserva($reserva)
            ->map(fn ($e) => [
                'id' => $e->id,
                'predio' => $e->predio,
                'equipo' => $e->equipo,
                'cantidad' => (int) $e->cantidad,
                'estado' => $e->estado,
            ])
            ->values();

        $noCancelada = $reserva->estado !== 'cancelada';

        return Inertia::render('panel/entrega/Show', [
            'usuario' => $this->datosUsuario($request->user()),
            'reserva' => [
                'id' => $reserva->id,
                'referencia' => $solicitud->referencia,
                'solicitante' => trim($solicitud->nombre.' '.$solicitud->apellido),
                'persona_id' => $solicitud->persona_id,
                'estado' => $reserva->estado,
            ],
            'equipamiento' => $equipamiento,
            'acta' => $acta ? [
                'fecha' => $acta->fecha->format('d/m/Y H:i'),
                'fecha_devolucion' => $acta->fecha_devolucion?->format('d/m/Y H:i'),
                'devuelta' => $devuelta,
            ] : null,
            'detalles' => $detalles,
            'incidentes' => $incidentes,
            'limpieza' => $limpieza ? [
                'estado' => $limpieza->estado,
                'fecha' => Carbon::parse($limpieza->fecha)->format('d/m/Y'),
                'personal' => trim($limpieza->nombre.' '.$limpieza->apellido),
            ] : null,
            'sanciones' => $sanciones,
            'beneficios' => $beneficios,
            'personalActivo' => $personalActivo,
            'puedeCrearActa' => $activa && $acta === null,
            'puedeDevolver' => $activa && $acta !== null && ! $devuelta,
            'puedeReportarIncidente' => $acta !== null,
            'puedeAsignarLimpieza' => $noCancelada && $limpieza === null,
            'puedeMarcarRealizada' => $limpieza !== null && $limpieza->estado === 'asignada',
            'puedeSancionar' => $noCancelada && $acta !== null,
        ]);
    }

      public function crearActa(CrearActaRequest $request, Reserva $reserva): RedirectResponse
    {
        $this->soloSecretaria($request);
        abort_unless(in_array($reserva->estado, ['pendiente', 'confirmada'], true), 422, 'La reserva no está activa.');
        abort_if(ActaEntrega::where('reserva_id', $reserva->id)->exists(), 422, 'Esta reserva ya tiene acta de entrega.');

        $detalles = $request->validated('detalles') ?? [];
        $equipos = $this->equipamientoDelReserva($reserva)->keyBy('id');

        foreach ($detalles as $fila) {
            $equipo = $equipos->get((int) $fila['equipamiento_predio_id']);

            if (! $equipo) {
                throw ValidationException::withMessages([
                    'detalles' => 'Hay un equipo que no pertenece a los predios de esta solicitud.',
                ]);
            }

            if ((int) $fila['cantidad'] > (int) $equipo->cantidad) {
                throw ValidationException::withMessages([
                    'detalles' => "La cantidad de {$equipo->equipo} supera lo registrado en el predio ({$equipo->cantidad}).",
                ]);
            }
        }

        try {
            DB::transaction(function () use ($reserva, $detalles) {
                $acta = ActaEntrega::create([
                    'fecha' => now(),
                    'compromiso_buen_uso' => true,
                    'firmada' => true,
                    'reserva_id' => $reserva->id,
                ]);

                foreach ($detalles as $fila) {
                    DetalleActa::create([
                        'cantidad' => (int) $fila['cantidad'],
                        'estado_entrega' => $fila['estado_entrega'],
                        'acta_entrega_id' => $acta->id,
                        'equipamiento_predio_id' => (int) $fila['equipamiento_predio_id'],
                    ]);
                }
            });
        } catch (QueryException) {
            return back()->withErrors([
                'detalles' => 'No se pudo registrar el acta. Revisa que no haya equipos repetidos.',
            ]);
        }

        return redirect("/panel/entrega/{$reserva->id}")
            ->with('exito', 'Entrega registrada. Se guardaron '.count($detalles).' equipos en el acta.');
    }

       public function registrarDevolucion(RegistrarDevolucionRequest $request, Reserva $reserva): RedirectResponse
    {
        $this->soloSecretaria($request);
        abort_unless(in_array($reserva->estado, ['pendiente', 'confirmada'], true), 422, 'La reserva no está activa.');

        $acta = ActaEntrega::where('reserva_id', $reserva->id)->first();
        abort_unless($acta, 422, 'Esta reserva todavía no tiene acta de entrega.');
        abort_if($acta->fecha_devolucion !== null, 422, 'La devolución ya fue registrada.');

        $filas = $request->validated('detalles');
        $beneficioOtorgado = false;

        try {
            DB::transaction(function () use ($reserva, $acta, $filas, &$beneficioOtorgado) {
                $detalles = DetalleActa::where('acta_entrega_id', $acta->id)->get()->keyBy('id');

                foreach ($filas as $fila) {
                    $detalle = $detalles->get((int) $fila['detalle_id']);

                    if (! $detalle) {
                        throw ValidationException::withMessages([
                            'detalles' => 'Hay un equipo que no pertenece a esta acta.',
                        ]);
                    }

                    $detalle->update(['estado_devolucion' => $fila['estado_devolucion']]);
                }

                if (DetalleActa::where('acta_entrega_id', $acta->id)->whereNull('estado_devolucion')->exists()) {
                    throw ValidationException::withMessages([
                        'detalles' => 'Indica el estado de devolución de todos los equipos.',
                    ]);
                }

                $acta->update(['fecha_devolucion' => now()]);

                Reserva::whereKey($reserva->id)->update(['estado' => 'finalizada']);

                $hayIncidentes = Incidente::where('acta_entrega_id', $acta->id)->exists();
                $todoBueno = ! DetalleActa::where('acta_entrega_id', $acta->id)
                    ->where('estado_devolucion', '!=', 'bueno')
                    ->exists();

                if (! $hayIncidentes && $todoBueno) {
                    Beneficio::create([
                        'descripcion' => 'Devolución del predio en buen estado',
                        'fecha' => now()->toDateString(),
                        'persona_id' => DB::table('solicitudes')
                            ->where('id', $reserva->solicitud_id)
                            ->value('persona_id'),
                    ]);

                    $beneficioOtorgado = true;
                }
            });
        } catch (QueryException) {
            return back()->withErrors([
                'detalles' => 'No se pudo registrar la devolución. Intenta de nuevo.',
            ]);
        }

        $mensaje = 'Devolución registrada. La reserva quedó finalizada.';

        if ($beneficioOtorgado) {
            $mensaje .= ' Se otorgó un beneficio al solicitante por devolver el predio en buen estado.';
        }

        return redirect("/panel/entrega/{$reserva->id}")->with('exito', $mensaje);
    }

       public function agregarIncidente(RegistrarIncidenteRequest $request, Reserva $reserva): RedirectResponse
    {
        $this->soloSecretaria($request);

        $acta = ActaEntrega::where('reserva_id', $reserva->id)->first();
        abort_unless($acta, 422, 'Esta reserva todavía no tiene acta de entrega.');

        $datos = $request->validated();

        Incidente::create([
            'descripcion' => $datos['descripcion'],
            'fecha' => now(),
            'acta_entrega_id' => $acta->id,
            'persona_id' => $datos['persona_id'],
        ]);

        return redirect("/panel/entrega/{$reserva->id}")
            ->with('exito', 'Incidente registrado en el acta de entrega.');
    }
    private function equipamientoDelReserva(Reserva $reserva)
    {
        $predioIds = DB::table('solicitud_predios')
            ->where('solicitud_id', $reserva->solicitud_id)
            ->where('activo', true)
            ->pluck('predio_id');

        return DB::table('equipamiento_predios as ep')
            ->join('equipos as eq', 'eq.id', '=', 'ep.equipo_id')
            ->join('predios as p', 'p.id', '=', 'ep.predio_id')
            ->whereIn('ep.predio_id', $predioIds)
            ->orderBy('p.nombre')
            ->orderBy('eq.descripcion')
            ->get([
                'ep.id',
                'ep.cantidad',
                'ep.estado',
                'eq.descripcion as equipo',
                'p.nombre as predio',
            ]);
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