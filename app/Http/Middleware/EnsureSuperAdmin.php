<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class EnsureSuperAdmin
{
    public function handle(Request $request, Closure $next)
    {
        if (!Auth::check()) {
            return redirect()->route('login');
        }

        $user = $request->user();

        if ($user && method_exists($user, 'isSuperAdmin') && $user->isSuperAdmin()) {
            return $next($request);
        }

        if ($request->expectsJson()) {
            return response()->json([
                'message' => 'Acceso denegado. Solo SUPERADMIN puede acceder a esta sección.',
            ], 403);
        }

        abort(403, 'Acceso denegado. Solo SUPERADMIN puede acceder a esta sección.');
    }
}
