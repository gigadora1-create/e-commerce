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

        $isSupplyRequesterOnly = $user && method_exists($user, 'isSupplyRequesterOnly') && $user->isSupplyRequesterOnly();
        $isSupplyAdminOnly = $user && method_exists($user, 'isSupplyAdminOnly') && $user->isSupplyAdminOnly();

        if (!$isSupplyRequesterOnly && !$isSupplyAdminOnly) {
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

        $canAccessSupplyRoute = $routeName && (
            ($isSupplyAdminOnly && Str::startsWith($routeName, 'supplies.'))
            || ($isSupplyRequesterOnly && Str::startsWith($routeName, 'supplies.issues.'))
        );

        if ($canAccessSupplyRoute || ($routeName && in_array($routeName, $allowedRoutes, true))) {
            return $next($request);
        }

        if ($request->expectsJson()) {
            return response()->json([
                'message' => 'Tu acceso esta restringido al modulo Proveeduria.',
            ], 403);
        }

        return redirect()
            ->route($isSupplyAdminOnly ? 'supplies.index' : 'supplies.issues.index')
            ->with('warning', 'Tu acceso esta restringido al modulo Proveeduria.');
    }
}
