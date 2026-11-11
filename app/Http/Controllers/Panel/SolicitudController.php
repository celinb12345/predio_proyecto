<?php

namespace App\Http\Controllers\Panel;

use App\Http\Controllers\Controller;
use App\Http\Requests\Panel\AprobarSolicitudRequest;
use App\Http\Requests\Panel\ProveidoRequest;
use App\Http\Requests\Panel\RespaldoRequest;
use App\Http\Requests\Panel\SolicitudRequest;
use App\Models\Reserva;
use App\Models\RespaldoSolicitud;
use App\Models\Solicitud;
use App\Models\SolicitudPredio;
use App\Models\User;
use Illuminate\Database\QueryException;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use Inertia\Inertia;
use Inertia\Response;

class SolicitudController extends Controller
{
    private const ROLES_CON_ACCESO_TOTAL = ['Secretaría General', 'Decano'];
    private const ROLES_QUE_SOLICITAN = ['Secretaría General', 'Director', 'Estudiante', 'Cliente'];

    public function index(Request $request): Response
    {
        $user = $request->user();
        $verTodas = $this->verTodas($user);

        $solicitudes = Solicitud::query()
            ->join('tipo_eventos as te', 'te.id', '=', 'solicitudes.tipo_evento_id')
            ->join('personas as p', 'p.id', '=', 'solicitudes.persona_id')
            ->when(! $verTodas, fn ($q) => $q->where('solicitudes.persona_id', $user->persona_id))
            ->orderByDesc('solicitudes.created_at')
            ->select([
                'solicitudes.id',
                'solicitudes.referencia',
                'solicitudes.estado',
                'solicitudes.created_at',
                'te.descripcion as evento',
                'p.nombre as persona_nombre',
                'p.apellido as persona_apellido',
            ])
            ->paginate(10)
            ->withQueryString()
            ->through(fn ($s) => [
                'id' => $s->id,
                'referencia' => $s->referencia,
                'evento' => $s->evento,
                'solicitante' => trim($s->persona_nombre.' '.$s->persona_apellido),
                'estado' => $s->estado,
                'fecha' => Carbon::parse($s->created_at)->format('d/m/Y'),
            ]);

        return Inertia::render('panel/solicitantes/Index', [
            'usuario' => $this->datosUsuario($user),
            'solicitudes' => $solicitudes,
            'puedeSolicitar' => $this->puedeSolicitar($user),
            'puedeVerCalendario' => $verTodas,
        ]);
    }

    public function create(Request $request): Response
    {
        $user = $request->user();
        abort_unless($this->puedeSolicitar($user), 403);

        return Inertia::render('panel/solicitantes/Create', [
            'usuario' => $this->datosUsuario($user),
            'tiposEvento' => DB::table('tipo_eventos')->orderBy('descripcion')->get(['id', 'descripcion']),
            'personas' => $user->esSecretariaGeneral()
                ? DB::table('personas')->orderBy('apellido')->get(['id', 'nombre', 'apellido'])
                : [],
            'puedeElegirPersona' => $user->esSecretariaGeneral(),
            'personaPropia' => $user->persona_id,
            'predios' => DB::table('predios as p')
                ->join('tipo_predios as tp', 'tp.id', '=', 'p.tipo_predio_id')
                ->where('p.estado', 'disponible')
                ->orderBy('p.nombre')
                ->get(['p.id', 'p.nombre', 'p.capacidad', 'tp.descripcion as tipo']),
        ]);
    }

    public function store(SolicitudRequest $request): RedirectResponse
    {
        $user = $request->user();
        abort_unless($this->puedeSolicitar($user), 403);

        $datos = $request->validated();
        $personaId = $user->esSecretariaGeneral() ? $datos['persona_id'] : $user->persona_id;
        abort_unless($personaId, 422, 'Tu usuario no tiene una persona asociada.');

        try {
            $solicitud = DB::transaction(function () use ($datos, $personaId) {
                $solicitud = Solicitud::create([
                    'referencia' => 'SOL-'.Str::upper(Str::random(8)),
                    'estado' => 'pendiente',
                    'tipo_evento_id' => $datos['tipo_evento_id'],
                    'persona_id' => $personaId,
                ]);

                foreach ($datos['predios'] as $fila) {
                    SolicitudPredio::create([
                        'solicitud_id' => $solicitud->id,
                        'predio_id' => $fila['predio_id'],
                        'fecha' => $fila['fecha'],
                        'hora_inicio' => $fila['hora_inicio'],
                        'hora_fin' => $fila['hora_fin'],
                        'activo' => true,
                    ]);
                }

                return $solicitud;
            });
        } catch (QueryException) {
            return back()->withErrors([
                'predios' => 'Uno de los predios ya está reservado en ese día y horario. Revisa las filas.',
            ]);
        }

        return redirect("/panel/solicitantes/{$solicitud->id}");
    }

    public function disponibilidad(Request $request): JsonResponse
    {
        $datos = $request->validate([
            'predio_id' => ['required', 'integer', 'exists:predios,id'],
            'fecha' => ['required', 'date'],
            'hora_inicio' => ['required', 'date_format:H:i'],
            'hora_fin' => ['required', 'date_format:H:i'],
        ]);

        if ($datos['hora_fin'] <= $datos['hora_inicio']) {
            return response()->json(['mensaje' => 'La hora de fin debe ser posterior a la de inicio.'], 422);
        }

        $ocupado = SolicitudPredio::predioOcupado(
            (int) $datos['predio_id'],
            $datos['fecha'],
            $datos['hora_inicio'],
            $datos['hora_fin'],
        );

        return response()->json([
            'ocupado' => $ocupado,
            'mensaje' => $ocupado ? 'Este predio ya está reservado en esa fecha y horario.' : null,
        ]);
    }

    public function show(Request $request, Solicitud $solicitud): Response
    {
        $user = $request->user();
        $this->autorizarVer($user, $solicitud);

        $datos = DB::table('solicitudes as s')
            ->join('tipo_eventos as te', 'te.id', '=', 's.tipo_evento_id')
            ->join('personas as p', 'p.id', '=', 's.persona_id')
            ->where('s.id', $solicitud->id)
            ->first([
                's.id',
                's.referencia',
                's.estado',
                's.proveido',
                's.created_at',
                'te.descripcion as evento',
                'p.nombre',
                'p.apellido',
            ]);

        $reserva = Reserva::where('solicitud_id', $solicitud->id)->first();

        return Inertia::render('panel/solicitantes/Show', [
            'usuario' => $this->datosUsuario($user),
            'solicitud' => [
                'id' => $datos->id,
                'referencia' => $datos->referencia,
                'estado' => $datos->estado,
                'proveido' => $datos->proveido,
                'evento' => $datos->evento,
                'solicitante' => trim($datos->nombre.' '.$datos->apellido),
                'fecha' => Carbon::parse($datos->created_at)->format('d/m/Y'),
            ],
            'predios' => SolicitudPredio::query()
                ->join('predios as pr', 'pr.id', '=', 'solicitud_predios.predio_id')
                ->where('solicitud_predios.solicitud_id', $solicitud->id)
                ->orderBy('solicitud_predios.fecha')
                ->orderBy('solicitud_predios.hora_inicio')
                ->get([
                    'pr.nombre',
                    'solicitud_predios.fecha',
                    'solicitud_predios.hora_inicio',
                    'solicitud_predios.hora_fin',
                    'solicitud_predios.activo',
                ])
                ->map(fn ($p) => [
                    'nombre' => $p->nombre,
                    'fecha' => Carbon::parse($p->fecha)->format('d/m/Y'),
                    'hora_inicio' => substr($p->hora_inicio, 0, 5),
                    'hora_fin' => substr($p->hora_fin, 0, 5),
                    'activo' => $p->activo,
                ]),
            'respaldos' => RespaldoSolicitud::where('solicitud_id', $solicitud->id)
                ->orderBy('id')
                ->get()
                ->map(fn (RespaldoSolicitud $r) => [
                    'id' => $r->id,
                    'tipo' => $r->tipo,
                    'url' => asset('storage/'.$r->archivo),
                ]),
            'puedeSubirRespaldo' => $solicitud->estado === 'pendiente',
            'reserva' => $reserva ? [
                'estado' => $reserva->estado,
                'tipo_prestamo' => $reserva->tipo_prestamo,
                'monto_total' => number_format((float) $reserva->monto_total, 2, '.', ','),
            ] : null,
        ]);
    }

    public function storeRespaldo(RespaldoRequest $request, Solicitud $solicitud): RedirectResponse
    {
        $this->autorizarVer($request->user(), $solicitud);
        abort_unless($solicitud->estado === 'pendiente', 422, 'La solicitud ya no admite respaldos.');

        RespaldoSolicitud::create([
            'tipo' => $request->validated('tipo'),
            'archivo' => $request->file('archivo')->store('respaldos_solicitud', 'public'),
            'solicitud_id' => $solicitud->id,
        ]);

        return redirect("/panel/solicitantes/{$solicitud->id}");
    }
    public function guardarProveido(ProveidoRequest $request, Solicitud $solicitud): RedirectResponse
    {
        abort_unless($this->esDecano($request->user()), 403);
        abort_unless($solicitud->estado === 'pendiente', 422, 'Solo se puede dar proveído a solicitudes pendientes.');

        try {
            $solicitud->update([
                'proveido' => $request->validated('proveido'),
            ]);
        } catch (QueryException) {
            return back()->withErrors([
                'proveido' => 'El texto del proveído es demasiado largo. Resúmelo en un máximo de 1000 caracteres.',
            ]);
        }

        return redirect("/panel/solicitantes/{$solicitud->id}");
    }

    public function aprobar(AprobarSolicitudRequest $request, Solicitud $solicitud): RedirectResponse
    {
        abort_unless($request->user()->esSecretariaGeneral(), 403);

        DB::transaction(function () use ($request, $solicitud) {
            $actual = Solicitud::whereKey($solicitud->id)->lockForUpdate()->firstOrFail();

            abort_unless($actual->estado === 'pendiente', 422, 'Solo se pueden aprobar solicitudes pendientes.');
            abort_if(blank($actual->proveido), 422, 'Falta el proveído del Decano.');

            $tipoPrestamo = $request->validated('tipo_prestamo');
            $monto = $tipoPrestamo === 'gratuito' ? 0 : $this->calcularMonto($actual);

            $actual->update(['estado' => 'aprobada']);

            Reserva::create([
                'estado' => 'pendiente',
                'tipo_prestamo' => $tipoPrestamo,
                'monto_total' => $monto,
                'solicitud_id' => $actual->id,
            ]);
        });

        return redirect("/panel/solicitantes/{$solicitud->id}");
    }

    public function rechazar(Request $request, Solicitud $solicitud): RedirectResponse
    {
        abort_unless($request->user()->esSecretariaGeneral(), 403);

        DB::transaction(function () use ($solicitud) {
            $actual = Solicitud::whereKey($solicitud->id)->lockForUpdate()->firstOrFail();

            abort_unless($actual->estado === 'pendiente', 422, 'Solo se pueden rechazar solicitudes pendientes.');

            $actual->update(['estado' => 'rechazada']);

            SolicitudPredio::where('solicitud_id', $actual->id)->update(['activo' => false]);
        });

        return redirect("/panel/solicitantes/{$solicitud->id}");
    }

    private function calcularMonto(Solicitud $solicitud): float
    {
        $tipoPersonaId = DB::table('personas')
            ->where('id', $solicitud->persona_id)
            ->value('tipo_persona_id');

        $predios = DB::table('solicitud_predios as sp')
            ->join('predios as p', 'p.id', '=', 'sp.predio_id')
            ->where('sp.solicitud_id', $solicitud->id)
            ->get(['sp.predio_id', 'p.nombre']);

        $total = 0.0;
        $sinTarifa = [];

        foreach ($predios as $predio) {
            $monto = DB::table('tarifas')
                ->where('predio_id', $predio->predio_id)
                ->where('tipo_persona_id', $tipoPersonaId)
                ->where('vigente', true)
                ->value('monto');

            if ($monto === null) {
                $sinTarifa[] = $predio->nombre;
                continue;
            }

            $total += (float) $monto;
        }

        if ($sinTarifa !== []) {
            throw ValidationException::withMessages([
                'tipo_prestamo' => 'Estos predios no tienen tarifa vigente para el tipo de persona del solicitante: '
                    .implode(', ', $sinTarifa).'. Registra la tarifa o aprueba como gratuito.',
            ]);
        }

        return round($total, 2);
    }

    private function autorizarVer(User $user, Solicitud $solicitud): void
    {
        $esDueno = (int) $solicitud->persona_id === (int) $user->persona_id;
        abort_unless($this->verTodas($user) || $esDueno, 403);
    }

    private function verTodas(User $user): bool
    {
        return in_array($user->rol?->descripcion, self::ROLES_CON_ACCESO_TOTAL, true);
    }

    private function puedeSolicitar(User $user): bool
    {
        return in_array($user->rol?->descripcion, self::ROLES_QUE_SOLICITAN, true);
    }

    private function esDecano(User $user): bool
    {
        return $user->rol?->descripcion === 'Decano';
    }

    private function datosUsuario(User $usuario): array
    {
        return [
            'name' => $usuario->name,
            'rol' => $usuario->rol?->descripcion,
        ];
    }
}