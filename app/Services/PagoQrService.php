<?php

namespace App\Services;

use App\Models\Cuota;
use App\Models\Pago;
use App\Models\Reserva;
use DomainException;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

class PagoQrService
{
    private const DIAS_CUOTA_1 = 30;
    private const DIAS_CUOTA_2 = 60;

    public function __construct(private PagoFacilService $pagofacil) {}

    /** Crea el plan (contado o 2 cuotas) con cuotas pendientes, listas para cobrarse por QR. */
    public function crearPlan(int $reservaId, string $forma): Pago
    {
        return DB::transaction(function () use ($reservaId, $forma) {
            $reserva = Reserva::whereKey($reservaId)->lockForUpdate()->firstOrFail();

            abort_unless($reserva->tipo_prestamo === 'cobrado', 422, 'Esta reserva es gratuita y no tiene pagos.');
            abort_unless(in_array($reserva->estado, ['pendiente', 'confirmada'], true), 422, 'La reserva no está activa.');

            if (Pago::where('reserva_id', $reserva->id)->exists()) {
                throw ValidationException::withMessages([
                    'forma_pago' => 'Esta reserva ya tiene un plan de pago.',
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
                    'estado' => 'pendiente',
                    'pago_id' => $pago->id,
                ]);

                return $pago;
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

            return $pago;
        });
    }

    /** Devuelve el QR vigente de la cuota; si no hay uno o venció, genera uno nuevo. */
    public function generarQr(int $cuotaId, Reserva $reserva): array
    {
        return DB::transaction(function () use ($cuotaId, $reserva) {
            $cuota = Cuota::whereKey($cuotaId)->lockForUpdate()->firstOrFail();
            $this->validarCuotaCobrable($cuota, $reserva);

            // Si ya hay un QR vigente se reutiliza, para no generar varios por la misma cuota
            if ($cuota->qr_estado === 'pendiente' && $cuota->qr_imagen && $cuota->qr_expira_en?->isFuture()) {
                return $this->respuesta($cuota);
            }

            $cliente = $this->datosCliente($reserva);
            $idEmpresa = 'PF'.$cuota->id.'-'.Str::upper(Str::random(12));

            $qr = $this->pagofacil->generarQr([
                'cliente_nombre' => $cliente['nombre'],
                'documento_tipo' => 1,
                'documento_id' => $cliente['documento'],
                'telefono' => $cliente['telefono'],
                'email' => $cliente['email'],
                'id_empresa' => $idEmpresa,
                'codigo_cliente' => 'PERSONA-'.$cliente['persona_id'],
                'monto' => (float) $cuota->monto,
                'detalle' => [[
                    'producto' => 'Reserva de predio N° '.$reserva->id.' - Cuota '.$cuota->nro_cuota,
                    'cantidad' => 1,
                    'precio' => (float) $cuota->monto,
                ]],
            ]);

            $cuota->update([
                'medio_pago' => 'qr',
                'qr_estado' => 'pendiente',
                'qr_id_empresa' => $idEmpresa,
                'qr_id_transaccion' => $qr['transaction_id'],
                'qr_imagen' => $qr['qr_base64'],
                'qr_expira_en' => $qr['expira_en'],
                'qr_pagado_en' => null,
            ]);

            return $this->respuesta($cuota->refresh());
        });
    }

    /**
     * Consulta a PagoFácil el estado real de la cuota y actualiza el registro.
     * Devuelve: pagado, pendiente, vencido o rechazado.
     */
    public function verificarPago(int $cuotaId): string
    {
        $cuota = Cuota::whereKey($cuotaId)->firstOrFail();

        if ($cuota->estado === 'pagada') {
            return 'pagado';
        }

        if ($cuota->qr_estado !== 'pendiente' || ! $cuota->qr_id_empresa) {
            return $cuota->qr_estado ?? 'pendiente';
        }

        $estado = $this->pagofacil->consultarEstado($cuota->qr_id_empresa);

        if ($this->pagofacil->estaPagado($estado)) {
            $this->confirmarCobro($cuota->id);

            return 'pagado';
        }

        if ($cuota->qr_expira_en?->isPast()) {
            $cuota->update(['qr_estado' => 'vencido']);

            return 'vencido';
        }

        return 'pendiente';
    }

    /** Lo usa el callback: devuelve el id de la cuota, o null si el pedido no existe. */
    public function confirmarPorIdEmpresa(string $idEmpresa): ?int
    {
        $cuota = Cuota::where('qr_id_empresa', $idEmpresa)->first();

        if (! $cuota) {
            return null;
        }

        // El cuerpo del callback no se confía: se verifica con PagoFácil
        $this->verificarPago($cuota->id);

        return $cuota->id;
    }

    /** Marca la cuota como pagada por QR y confirma la reserva. Es idempotente. */
    public function confirmarCobro(int $cuotaId): void
    {
        DB::transaction(function () use ($cuotaId) {
            $cuota = Cuota::whereKey($cuotaId)->lockForUpdate()->firstOrFail();

            // Si ya estaba cobrada (caja o callback repetido), no se vuelve a registrar
            if ($cuota->estado === 'pagada') {
                return;
            }

            $cuota->update([
                'estado' => 'pagada',
                'fecha_pago' => now(),
                'medio_pago' => 'qr',
                'qr_estado' => 'pagado',
                'qr_pagado_en' => now(),
            ]);

            $pago = Pago::findOrFail($cuota->pago_id);

            Reserva::whereKey($pago->reserva_id)
                ->where('estado', 'pendiente')
                ->update(['estado' => 'confirmada']);
        });
    }

    private function validarCuotaCobrable(Cuota $cuota, Reserva $reserva): void
    {
        $pago = Pago::find($cuota->pago_id);

        if (! $pago || (int) $pago->reserva_id !== (int) $reserva->id) {
            throw new DomainException('La cuota no pertenece a esta reserva.');
        }

        if (! in_array($reserva->estado, ['pendiente', 'confirmada'], true)) {
            throw new DomainException('La reserva no está activa.');
        }

        if (! in_array($cuota->estado, ['pendiente', 'vencida'], true)) {
            throw new DomainException('Esta cuota ya está pagada o no se puede cobrar.');
        }

        if ((int) $cuota->nro_cuota === 2) {
            $primera = Cuota::where('pago_id', $cuota->pago_id)->where('nro_cuota', 1)->first();

            if (! $primera || $primera->estado !== 'pagada') {
                throw new DomainException('Primero debe pagarse la cuota 1.');
            }
        }
    }

      private function datosCliente(Reserva $reserva): array
    {
        $fila = DB::table('solicitudes as s')
            ->join('personas as p', 'p.id', '=', 's.persona_id')
            ->leftJoin('users as u', 'u.persona_id', '=', 'p.id')
            ->where('s.id', $reserva->solicitud_id)
            ->first(['s.persona_id', 'p.nombre', 'p.apellido', 'p.ci', 'p.celular', 'u.email']);

        if (! $fila) {
            throw new DomainException('No se encontró el solicitante de la reserva.');
        }

        return [
            'persona_id' => $fila->persona_id,
            'nombre' => trim($fila->nombre.' '.$fila->apellido),
            'documento' => (string) ($fila->ci ?? '0'),
            'telefono' => (string) ($fila->celular ?? '0'),
            'email' => (string) ($fila->email ?? ''),
        ];
    }

    private function respuesta(Cuota $cuota): array
    {
        return [
            'qr_imagen' => $cuota->qr_imagen,
            'expira_en' => $cuota->qr_expira_en->format('Y-m-d H:i:s'),
            'url_estado' => route('panel.cuotas.qr.estado', $cuota),
        ];
    }
}