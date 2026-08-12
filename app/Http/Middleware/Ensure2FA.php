<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class Ensure2FA
{
    public function handle(Request $request, Closure $next)
    {
        $routeName = $request->route()?->getName();

        $publicRoutes = [
            'login',
            'login.action',
            'password.request',
            'password.email',
            'password.reset',
            'password.update',
            'two-factor.show-code-form',
            'two-factor.verify-code',
            'two-factor.send-code',
            'logout',
        ];

        if (!Auth::check()) {
            if ($routeName && in_array($routeName, $publicRoutes, true)) {
                return $next($request);
            }

            return redirect()->route('login');
        }

        if (!(bool) config('auth.two_factor_enabled', true)) {
            $request->session()->put('two_factor_verified', true);
            $request->session()->forget([
                'two_factor_code',
                'two_factor_code_expires',
                'pending_2fa',
            ]);

            return $next($request);
        }

        if (!session('two_factor_verified')) {
            $allowedRoutes = $publicRoutes;

            if (!$routeName || !in_array($routeName, $allowedRoutes, true)) {
                return redirect()->route('two-factor.show-code-form')
                    ->with('error', 'Debes completar la verificación de doble factor para continuar.');
            }
        }

        return $next($request);
    }
}
