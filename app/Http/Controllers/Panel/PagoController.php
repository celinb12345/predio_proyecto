<?php

namespace App\Http\Controllers\Panel;

use App\Http\Controllers\Controller;
use App\Http\Requests\Panel\RegistrarCuotaRequest;
use App\Http\Requests\Panel\RegistrarPagoRequest;
use App\Models\Cuota;
use App\Models\Pago;
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

class PagoController extends Controller
{
    private const DIAS_CUOTA_1 = 30;
    private const DIAS_CUOTA_2 = 60;

    public function index(Request $request): Response
    {
        $user = $request->user();
        $this->marcarVencidas();

        $verTodas = $user->esSecretariaGeneral();

        $filas = DB::table('reservas as r')
            ->join('solicitudes as s', 's.id', '=', 'r.solicitud_id')
            ->join('personas as p', 'p.id', '=', 's.persona_id')
            ->where('r.tipo_prestamo', 'cobrado')
            ->when(! $verTodas, fn ($q) => $q->where('s.persona_id', $user->persona_id))
            ->orderByDesc('r.created_at')
            ->select([
                'r.id',
                's.referencia',
                'p.nombre as persona_nombre',
                'p.apellido as persona_apellido',
                'r.monto_total',
                'r.estado as estado_reserva',
            ])
            ->paginate(10)
            ->withQueryString()
            ->through(function ($f) {
                $resumen = $this->resumenPagos((int) $f->id);

                return [
                    'id' => $f->id,
                    'referencia' => $f->referencia,
                    'solicitante' => trim($f->persona_nombre.' '.$f->persona_apellido),
                    'monto_total' => number_format((float) $f->monto_total, 2, '.', ','),
                    'forma' => $resumen['forma'],
                    'pagado' => $resumen['pagado'],
                    'vencidas' => $resumen['vencidas'],
                    'estado_reserva' => $f->estado_reserva,
                ];
            });

        return Inertia::render('panel/pagos/Index', [
            'usuario' => $this->datosUsuario($user),
            'filas' => $filas,
            'alertasDeudores' => $verTodas ? \App\Support\DeudasCuotas::alertas() : [],
            'puedeVerDeudores' => $verTodas,
        ]);
    }
    public function show(Request $request, Reserva $reserva): Response
    {
        $user = $request->user();
        $this->marcarVencidas();

        $solicitud = DB::table('solicitudes as s')
            ->join('personas as p', 'p.id', '=', 's.persona_id')
            ->where('s.id', $reserva->solicitud_id)
            ->first(['s.referencia', 's.persona_id', 'p.nombre', 'p.apellido']);

        abort_unless(
            $user->esSecretariaGeneral() || (int) $solicitud->persona_id === (int) $user->persona_id,
            403
        );
        abort_unless($reserva->tipo_prestamo === 'cobrado', 404);

        $pago = Pago::where('reserva_id', $reserva->id)->first();
        $cuotas = $pago
            ? Cuota::where('pago_id', $pago->id)->orderBy('nro_cuota')->get()
            : collect();

        $total = (float) $reserva->monto_total;
        $pagado = (float) $cuotas->where('estado', 'pagada')->sum('monto');
        $activa = in_array($reserva->estado, ['pendiente', 'confirmada'], true);
        $esSecretaria = $user->esSecretariaGeneral();
        $cuota1Pagada = $cuotas->firstWhere('nro_cuota', 1)?->estado === 'pagada';

        return Inertia::render('panel/pagos/Show', [
            'usuario' => $this->datosUsuario($user),
            'reserva' => [
                'id' => $reserva->id,
                'referencia' => $solicitud->referencia,
                'solicitante' => trim($solicitud->nombre.' '.$solicitud->apellido),
                'estado' => $reserva->estado,
                'monto_total' => number_format($total, 2, '.', ','),
            ],
            'pago' => $pago ? [
                'forma_pago' => $pago->forma_pago === 'contado' ? 'Contado' : 'Crédito en 2 cuotas',
                'fecha' => Carbon::parse($pago->fecha)->format('d/m/Y'),
            ] : null,
            'cuotas' => $cuotas->map(fn (Cuota $c) => [
                'id' => $c->id,
                'nro' => (int) $c->nro_cuota,
                'monto' => number_format((float) $c->monto, 2, '.', ','),
                'monto_numero' => round((float) $c->monto, 2),
                'vencimiento' => $c->fecha_vencimiento->format('d/m/Y'),
                'vencimiento_iso' => $c->fecha_vencimiento->format('Y-m-d'),
                'fecha_pago' => $c->fecha_pago?->format('d/m/Y H:i'),
                'estado' => $c->estado,
                'nro_comprobante' => $c->nro_comprobante,
                'url' => $c->comprobante ? asset('storage/'.$c->comprobante) : null,
                'medio_pago' => $c->medio_pago,
                'puede_qr' => $activa
                    && in_array($c->estado, ['pendiente', 'vencida'], true)
                    && ((int) $c->nro_cuota !== 2 || $cuota1Pagada),
                'url_generar_qr' => route('panel.cuotas.qr.generar', $c->id),
            ])->values(),
            'resumen' => [
                'pagado' => number_format($pagado, 2, '.', ','),
                'saldo' => number_format($total - $pagado, 2, '.', ','),
            ],
            'puedeRegistrarPago' => $esSecretaria && $activa && $pago === null,
            'puedeCobrar' => $esSecretaria && $activa && $pago !== null,
            'puedeEditarPlan' => $esSecretaria && $activa && $pago !== null && $pago->forma_pago === 'credito_2_cuotas',
        ]);
    }

    public function registrarPago(RegistrarPagoRequest $request, Reserva $reserva): RedirectResponse
    {
        abort_unless($request->user()->esSecretariaGeneral(), 403);
        $forma = $request->validated('forma_pago');

        try {
            DB::transaction(function () use ($reserva, $forma) {
                $reserva = Reserva::whereKey($reserva->id)->lockForUpdate()->firstOrFail();

                abort_unless($reserva->tipo_prestamo === 'cobrado', 422, 'Esta reserva es gratuita y no tiene pagos.');
                abort_unless(in_array($reserva->estado, ['pendiente', 'confirmada'], true), 422, 'La reserva no está activa.');

                if (Pago::where('reserva_id', $reserva->id)->exists()) {
                    throw ValidationException::withMessages([
                        'forma_pago' => 'Esta reserva ya tiene un pago registrado.',
                    ]);
                }

                $total = (float) $reserva->monto_total;

                $pago = Pago::create([
                    'forma_pago' => $forma,
                    'monto_total' => $total,
                    'fecha' => now(),
                    'reserva_id' => $reserva->id,
                ]);

                if ($forma === 'contado') {
                    Cuota::create([
                        'nro_cuota' => 1,
                        'monto' => $total,
                        'fecha_vencimiento' => now()->toDateString(),
                        'fecha_pago' => now(),
                        'estado' => 'pagada',
                        'pago_id' => $pago->id,
                    ]);

                    $reserva->update(['estado' => 'confirmada']);

                    return;
                }

                $mitad = round($total / 2, 2);

                Cuota::create([
                    'nro_cuota' => 1,
                    'monto' => $mitad,
                    'fecha_vencimiento' => now()->addDays(self::DIAS_CUOTA_1)->toDateString(),
                    'estado' => 'pendiente',
                    'pago_id' => $pago->id,
                ]);

                Cuota::create([
                    'nro_cuota' => 2,
                    'monto' => round($total - $mitad, 2),
                    'fecha_vencimiento' => now()->addDays(self::DIAS_CUOTA_2)->toDateString(),
                    'estado' => 'pendiente',
                    'pago_id' => $pago->id,
                ]);
            });
        } catch (QueryException) {
            return back()->withErrors([
                'forma_pago' => 'No se pudo registrar el pago. Intenta de nuevo.',
            ]);
        }

        return redirect("/panel/pagos/{$reserva->id}");
    }

    public function cobrar(RegistrarCuotaRequest $request, Reserva $reserva): RedirectResponse
    {
        abort_unless($request->user()->esSecretariaGeneral(), 403);
        $datos = $request->validated();

        $pago = Pago::where('reserva_id', $reserva->id)->first();
        abort_unless($pago, 422, 'Esta reserva todavía no tiene un pago registrado.');

        try {
            DB::transaction(function () use ($request, $reserva, $pago, $datos) {
                abort_unless(in_array($reserva->estado, ['pendiente', 'confirmada'], true), 422, 'La reserva no está activa.');

                $cuota = Cuota::whereKey($datos['cuota_id'])->lockForUpdate()->firstOrFail();
                abort_unless((int) $cuota->pago_id === (int) $pago->id, 422, 'La cuota no pertenece a esta reserva.');

                if ($cuota->estado === 'pagada') {
                    throw ValidationException::withMessages([
                        'cuota_id' => 'Esta cuota ya está pagada.',
                    ]);
                }

                if ((int) $cuota->nro_cuota === 2) {
                    $primera = Cuota::where('pago_id', $pago->id)->where('nro_cuota', 1)->first();

                    if (! $primera || $primera->estado !== 'pagada') {
                        throw ValidationException::withMessages([
                            'cuota_id' => 'Primero debe pagarse la cuota 1.',
                        ]);
                    }
                }

                $comprobante = $request->hasFile('comprobante')
                    ? $request->file('comprobante')->store('comprobantes_pago', 'public')
                    : null;

                $cuota->update([
                    'estado' => 'pagada',
                    'fecha_pago' => now(),
                    'nro_comprobante' => $datos['nro_comprobante'] ?? null,
                    'comprobante' => $comprobante,
                ]);

                Reserva::whereKey($reserva->id)
                    ->where('estado', 'pendiente')
                    ->update(['estado' => 'confirmada']);
            });
        } catch (QueryException) {
            return back()->withErrors([
                'cuota_id' => 'No se pudo registrar el cobro. Intenta de nuevo.',
            ]);
        }

        return redirect("/panel/pagos/{$reserva->id}");
    }

    private function marcarVencidas(): void
    {
        Cuota::where('estado', 'pendiente')
            ->where('fecha_vencimiento', '<', now()->toDateString())
            ->update(['estado' => 'vencida']);
    }

    private function resumenPagos(int $reservaId): array
    {
        $pago = Pago::where('reserva_id', $reservaId)->first();

        if (! $pago) {
            return ['forma' => 'Sin pago', 'pagado' => '0.00', 'vencidas' => 0];
        }

        $cuotas = Cuota::where('pago_id', $pago->id)->get();

        return [
            'forma' => $pago->forma_pago === 'contado' ? 'Contado' : 'Crédito 2 cuotas',
            'pagado' => number_format((float) $cuotas->where('estado', 'pagada')->sum('monto'), 2, '.', ','),
            'vencidas' => $cuotas->where('estado', 'vencida')->count(),
        ];
    }

    private function datosUsuario(User $usuario): array
    {
        return [
            'name' => $usuario->name,
            'rol' => $usuario->rol?->descripcion,
        ];
    }
}