<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Str;

class RestrictSupplyRequesterOnlyAccess
{
    public function handle(Request $request, Closure $next)
    {
        if (!Auth::check()) {
            return $next($request);
        }

        $user = $request->user();

        if (!$user || !method_exists($user, 'isSupplyRequesterOnly') || !$user->isSupplyRequesterOnly()) {
            return $next($request);
        }

        $routeName = $request->route()?->getName();
        $allowedRoutes = [
            'logout',
            'two-factor.send-code',
            'two-factor.show-code-form',
            'two-factor.verify-code',
            'customer.context.index',
            'customer.context.store',
            'customer.context.clear',
        ];

        if ($routeName && (Str::startsWith($routeName, 'supplies.issues.') || in_array($routeName, $allowedRoutes, true))) {
            return $next($request);
        }

        if ((!session()->has('selected_customers') || count(session('selected_customers', [])) === 0) && !session()->has('selected_customer')) {
            return redirect()
                ->route('customer.context.index')
                ->with('warning', 'Selecciona un cliente para continuar.');
        }

        if ($request->expectsJson()) {
            return response()->json([
                'message' => 'Tu acceso esta restringido al modulo Proveeduria.',
            ], 403);
        }

        return redirect()
            ->route('supplies.issues.index')
            ->with('warning', 'Tu acceso esta restringido al modulo Proveeduria.');
    }
}
