<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class CheckSuscripcionTaller
{
    public function handle(Request $request, Closure $next)
    {
// 1. Verificamos si el usuario está logueado
        if (Auth::check()) {
            $user = Auth::user();

            // 2. EXCEPCIÓN MAESTRA: Los Super Admins son inmunes a los bloqueos de taller.
            if ($user->hasRole('super_admin')) {
                return $next($request);
            }

            $taller = $user->taller;

            if ($taller) {
                // 3. Verificamos si el taller del usuario está inactivo (suspendido manualmente)
                if ($taller->activo == false) {
                    Auth::logout();
                    return redirect(filament()->getLoginUrl())->withErrors([
                        'email' => 'El acceso a tu taller ha sido suspendido. Por favor, contacta a soporte de Autonix.',
                    ]);
                }

                // 4. Verificamos si la suscripción está vencida (por fecha)
                // Asumimos que la columna en tu base de datos se llama 'vencimiento_suscripcion'
                if ($taller->vencimiento_suscripcion && now()->startOfDay()->greaterThan($taller->vencimiento_suscripcion)) {
                    Auth::logout();
                    return redirect(filament()->getLoginUrl())->withErrors([
                        'email' => 'Tu suscripción ha vencido. Te invitamos a realizar el pago para renovar tu plan y seguir disfrutando de Autonix.',
                    ]);
                }
            }
        }

        // Si todo está en orden, lo dejamos pasar a la pantalla que solicitó
        return $next($request);
    }
}
