<?php

namespace App\Http\Controllers\Panel;

use App\Http\Controllers\Controller;
use App\Models\Cuota;
use App\Models\Pago;
use App\Models\Reserva;
use App\Models\User;
use App\Services\PagoQrService;
use DomainException;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use RuntimeException;

class PagoQrController extends Controller
{
    public function __construct(private PagoQrService $qr) {}

    /** Crea el plan con cobro por QR. Lo usa la secretaría desde la reserva o el particular desde su perfil. */
    public function crearPlan(Request $request, Reserva $reserva): RedirectResponse
    {
        $usuario = $request->user();
        abort_unless($this->puedeGestionar($usuario, $reserva), 403);

        $datos = $request->validate([
            'forma_pago' => ['required', 'in:contado,credito_2_cuotas'],
        ]);

        $this->qr->crearPlan($reserva->id, $datos['forma_pago']);

        return redirect($usuario->esSecretariaGeneral()
            ? "/panel/pagos/{$reserva->id}"
            : '/panel/mis-pagos');
    }

    /** Devuelve el QR vigente de la cuota o genera uno nuevo. */
    public function generar(Request $request, Cuota $cuota): JsonResponse
    {
        $reserva = $this->reservaDeCuota($cuota);
        abort_unless($this->puedeGestionar($request->user(), $reserva), 403);

        try {
            return response()->json($this->qr->generarQr($cuota->id, $reserva));
        } catch (DomainException $e) {
            return response()->json(['mensaje' => $e->getMessage()], 422);
        } catch (RuntimeException $e) {
            return response()->json(['mensaje' => $e->getMessage()], 503);
        }
    }

    /** Consulta el estado del pago. El componente lo llama cada pocos segundos. */
    public function estado(Request $request, Cuota $cuota): JsonResponse
    {
        $reserva = $this->reservaDeCuota($cuota);
        abort_unless($this->puedeGestionar($request->user(), $reserva), 403);

        try {
            return response()->json(['estado' => $this->qr->verificarPago($cuota->id)]);
        } catch (RuntimeException) {
            return response()->json(['mensaje' => 'No se pudo consultar el pago. Intenta nuevamente.'], 503);
        }
    }

    private function reservaDeCuota(Cuota $cuota): Reserva
    {
        $pago = Pago::findOrFail($cuota->pago_id);

        return Reserva::findOrFail($pago->reserva_id);
    }

    private function puedeGestionar(User $usuario, Reserva $reserva): bool
    {
        if ($usuario->esSecretariaGeneral()) {
            return true;
        }

        if ($usuario->persona_id === null) {
            return false;
        }

        $personaId = DB::table('solicitudes')
            ->where('id', $reserva->solicitud_id)
            ->value('persona_id');

        return (int) $personaId === (int) $usuario->persona_id;
    }
}