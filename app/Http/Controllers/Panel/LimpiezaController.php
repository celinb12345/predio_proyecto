<?php

namespace App\Http\Controllers\Panel;

use App\Http\Controllers\Controller;
use App\Http\Requests\Panel\AsignarLimpiezaRequest;
use App\Http\Requests\Panel\PersonalLimpiezaRequest;
use App\Models\Limpieza;
use App\Models\PersonalLimpieza;
use App\Models\Reserva;
use Illuminate\Database\QueryException;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;

class LimpiezaController extends Controller
{
    public function storePersonal(PersonalLimpiezaRequest $request): RedirectResponse
    {
        $this->soloSecretaria($request);

        $personal = PersonalLimpieza::create([
            ...$request->validated(),
            'activo' => true,
        ]);

        return redirect('/panel/entrega')
            ->with('exito', "Personal de limpieza agregado: {$personal->apellido}, {$personal->nombre}.");
    }

    public function cambiarActivo(Request $request, PersonalLimpieza $personal): RedirectResponse
    {
        $this->soloSecretaria($request);

        $personal->update(['activo' => ! $personal->activo]);

        $nombre = "{$personal->apellido}, {$personal->nombre}";

        return redirect('/panel/entrega')
            ->with('exito', $personal->activo
                ? "{$nombre} quedó activo para asignarle limpiezas."
                : "{$nombre} quedó desactivado.");
    }

    public function asignar(AsignarLimpiezaRequest $request, Reserva $reserva): RedirectResponse
    {
        $this->soloSecretaria($request);
        abort_if($reserva->estado === 'cancelada', 422, 'La reserva está cancelada.');
        abort_if(Limpieza::where('reserva_id', $reserva->id)->exists(), 422, 'Esta reserva ya tiene una limpieza asignada.');

        $datos = $request->validated();

        $activo = PersonalLimpieza::where('id', $datos['personal_limpieza_id'])
            ->where('activo', true)
            ->exists();

        if (! $activo) {
            throw ValidationException::withMessages([
                'personal_limpieza_id' => 'La persona seleccionada no está activa.',
            ]);
        }

        try {
            Limpieza::create([
                'fecha' => $datos['fecha'],
                'estado' => 'asignada',
                'reserva_id' => $reserva->id,
                'personal_limpieza_id' => (int) $datos['personal_limpieza_id'],
            ]);
        } catch (QueryException) {
            return back()->withErrors([
                'personal_limpieza_id' => 'No se pudo asignar la limpieza. Intenta de nuevo.',
            ]);
        }

        return redirect("/panel/entrega/{$reserva->id}")
            ->with('exito', 'Limpieza asignada. Queda pendiente hasta que se marque como realizada.');
    }

    public function marcarRealizada(Request $request, Reserva $reserva): RedirectResponse
    {
        $this->soloSecretaria($request);

        $limpieza = Limpieza::where('reserva_id', $reserva->id)->first();
        abort_unless($limpieza, 422, 'Esta reserva no tiene limpieza asignada.');
        abort_unless($limpieza->estado === 'asignada', 422, 'La limpieza ya no está pendiente.');

        $limpieza->update(['estado' => 'realizada']);

        return redirect("/panel/entrega/{$reserva->id}")
            ->with('exito', 'Limpieza marcada como realizada.');
    }

    private function soloSecretaria(Request $request): void
    {
        abort_unless($request->user()->esSecretariaGeneral(), 403);
    }
}