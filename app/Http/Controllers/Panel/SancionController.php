<?php

namespace App\Http\Controllers\Panel;

use App\Http\Controllers\Controller;
use App\Http\Requests\Panel\SancionRequest;
use App\Models\Reserva;
use App\Models\Sancion;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;

class SancionController extends Controller
{
    public function store(SancionRequest $request, Reserva $reserva): RedirectResponse
    {
        $this->soloSecretaria($request);
        abort_if($reserva->estado === 'cancelada', 422, 'La reserva está cancelada.');

        Sancion::create([
            'motivo' => $request->validated('motivo'),
            'fecha' => now()->toDateString(),
            'estado' => 'pendiente',
            'persona_id' => DB::table('solicitudes')->where('id', $reserva->solicitud_id)->value('persona_id'),
            'reserva_id' => $reserva->id,
        ]);

        return redirect("/panel/entrega/{$reserva->id}");
    }

    public function cambiarEstado(Request $request, Sancion $sancion): RedirectResponse
    {
        $this->soloSecretaria($request);

        $datos = $request->validate([
            'estado' => ['required', Rule::in(['cumplida', 'anulada'])],
        ]);

        abort_unless($sancion->estado === 'pendiente', 422, 'Solo se pueden cambiar sanciones pendientes.');

        $sancion->update(['estado' => $datos['estado']]);

        return redirect("/panel/entrega/{$sancion->reserva_id}");
    }

    private function soloSecretaria(Request $request): void
    {
        abort_unless($request->user()->esSecretariaGeneral(), 403);
    }
}