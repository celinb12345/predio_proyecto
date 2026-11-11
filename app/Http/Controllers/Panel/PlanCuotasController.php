<?php

namespace App\Http\Controllers\Panel;

use App\Http\Controllers\Controller;
use App\Http\Requests\Panel\PlanCuotasRequest;
use App\Models\Cuota;
use App\Models\Pago;
use App\Models\Reserva;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class PlanCuotasController extends Controller
{
    public function update(PlanCuotasRequest $request, Reserva $reserva): RedirectResponse
    {
        abort_unless($request->user()->esSecretariaGeneral(), 403);

        $editadas = collect($request->validated('cuotas'))
            ->keyBy(fn (array $fila) => (int) $fila['id']);

        DB::transaction(function () use ($reserva, $editadas) {
            $actual = Reserva::whereKey($reserva->id)->lockForUpdate()->firstOrFail();

            abort_unless($actual->tipo_prestamo === 'cobrado', 422, 'Esta reserva es gratuita y no tiene cuotas.');
            abort_unless(
                in_array($actual->estado, ['pendiente', 'confirmada'], true),
                422,
                'La reserva no está activa.'
            );

            $pago = Pago::where('reserva_id', $actual->id)->first();
            abort_unless(
                $pago && $pago->forma_pago === 'credito_2_cuotas',
                422,
                'Solo se puede editar el plan de un pago en cuotas.'
            );

            $cuotas = Cuota::where('pago_id', $pago->id)->lockForUpdate()->get();

            // Toda cuota enviada debe pertenecer a este pago, no estar pagada y tener una fecha válida
            foreach ($editadas as $id => $fila) {
                $cuota = $cuotas->firstWhere('id', $id);

                abort_unless($cuota !== null, 422, 'Una de las cuotas no pertenece a esta reserva.');

                if ($cuota->estado === 'pagada') {
                    throw ValidationException::withMessages([
                        'cuotas' => "La cuota {$cuota->nro_cuota} ya está pagada y no se puede editar.",
                    ]);
                }

                if (Carbon::parse($fila['fecha_vencimiento'])->startOfDay()->lt(Carbon::today())) {
                    throw ValidationException::withMessages([
                        'cuotas' => "La fecha límite de la cuota {$cuota->nro_cuota} no puede ser anterior a hoy.",
                    ]);
                }
            }

            // Valores finales de todas las cuotas: las que no se enviaron conservan los suyos
            $finales = $cuotas->map(function (Cuota $cuota) use ($editadas) {
                $fila = $editadas->get($cuota->id);

                return [
                    'cuota' => $cuota,
                    'monto' => $fila
                        ? round((float) $fila['monto'], 2)
                        : round((float) $cuota->monto, 2),
                    'fecha' => $fila
                        ? Carbon::parse($fila['fecha_vencimiento'])->startOfDay()
                        : Carbon::parse($cuota->fecha_vencimiento)->startOfDay(),
                ];
            });

            $sumaCuotas = round((float) $finales->sum('monto'), 2);
            $montoTotal = round((float) $actual->monto_total, 2);

            if (abs($sumaCuotas - $montoTotal) > 0.001) {
                throw ValidationException::withMessages([
                    'cuotas' => 'La suma de las cuotas debe ser Bs '.number_format($montoTotal, 2, '.', ',')
                        .'. Ahora suma Bs '.number_format($sumaCuotas, 2, '.', ',').'.',
                ]);
            }

            $cuota1 = $finales->first(fn ($f) => (int) $f['cuota']->nro_cuota === 1);
            $cuota2 = $finales->first(fn ($f) => (int) $f['cuota']->nro_cuota === 2);

            if ($cuota1 && $cuota2 && ! $cuota2['fecha']->gt($cuota1['fecha'])) {
                throw ValidationException::withMessages([
                    'cuotas' => 'La fecha de la cuota 2 debe ser posterior a la fecha de la cuota 1.',
                ]);
            }

            foreach ($finales as $f) {
                $cuota = $f['cuota'];

                if (! $editadas->has($cuota->id)) {
                    continue;
                }

                $cuota->monto = $f['monto'];
                $cuota->fecha_vencimiento = $f['fecha']->toDateString();

                // Si se le puso una fecha futura, una cuota vencida vuelve a estar pendiente
                if ($cuota->estado === 'vencida') {
                    $cuota->estado = 'pendiente';
                }

                $cuota->save();
            }
        });

        return redirect("/panel/pagos/{$reserva->id}")
            ->with('exito', 'El plan de pagos se actualizó correctamente.');
    }
}