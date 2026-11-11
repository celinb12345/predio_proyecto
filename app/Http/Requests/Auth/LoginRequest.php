<?php

namespace App\Http\Requests\Auth;

use App\Models\User;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\ValidationException;

class LoginRequest extends FormRequest
{
    private const MAX_INTENTOS = 5;
    private const MINUTOS_BLOQUEO = 15;

    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'email' => ['required', 'string', 'email'],
            'password' => ['required', 'string'],
            'remember' => ['boolean'],
        ];
    }

    public function authenticate(): User
    {
        $datos = $this->validated();

        $usuario = User::where('email', $datos['email'])->first();

        // No revelamos si el correo existe o no.
        if (! $usuario) {
            $this->fallo('Las credenciales no coinciden.');
        }

        if (! $usuario->activo) {
            $this->fallo('Tu cuenta está desactivada. Comunícate con Secretaría General.');
        }

        if ($usuario->bloqueado_hasta && $usuario->bloqueado_hasta->isFuture()) {
            $this->fallo('Cuenta bloqueada temporalmente por intentos fallidos. Intenta de nuevo a las '
                .$usuario->bloqueado_hasta->format('H:i').'.');
        }

        if (! Hash::check($datos['password'], $usuario->password)) {
            $intentos = $usuario->intentos_fallidos + 1;
            $bloquear = $intentos >= self::MAX_INTENTOS;

            $usuario->forceFill([
                'intentos_fallidos' => $bloquear ? 0 : $intentos,
                'bloqueado_hasta' => $bloquear ? now()->addMinutes(self::MINUTOS_BLOQUEO) : null,
            ])->save();

            $this->fallo($bloquear
                ? 'Demasiados intentos fallidos. La cuenta quedó bloqueada '.self::MINUTOS_BLOQUEO.' minutos.'
                : 'Las credenciales no coinciden.');
        }

        $usuario->forceFill([
            'intentos_fallidos' => 0,
            'bloqueado_hasta' => null,
            'ultimo_acceso' => now(),
        ])->save();

        Auth::login($usuario, $this->boolean('remember'));

        return $usuario;
    }

    private function fallo(string $mensaje): never
    {
        throw ValidationException::withMessages(['email' => $mensaje]);
    }
}