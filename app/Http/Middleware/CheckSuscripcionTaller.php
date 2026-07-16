<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class CheckSuscripcionTaller
{
    public function handle(Request $request, Closure $next)
    {
        // 1. Verificamos si el usuario está logueado[cite: 14]
        if (Auth::check()) {
            $user = Auth::user();

            // 2. EXCEPCIÓN MAESTRA (CORREGIDA): Solo tú (CEO Autonix) eres inmune a los bloqueos de taller.
            if ($user->email === 'admin@autonix.com.mx') {
                return $next($request);
            }

            // 3. Verificamos si el taller del usuario está inactivo (apagado)[cite: 14]
            $taller = $user->taller;
            if ($taller && $taller->activo == false) {

                // Lo expulsamos (cerramos su sesión)[cite: 14]
                Auth::logout();

                // Redirigimos a la pantalla de login con un mensaje de error[cite: 14]
                return redirect(filament()->getLoginUrl())->withErrors([
                    'email' => 'El acceso a tu taller ha sido suspendido. Por favor, contacta a soporte de Autonix.',
                ]);
            }
        }

        // Si todo está en orden, lo dejamos pasar a la pantalla que solicitó[cite: 14]
        return $next($request);
    }
}
