<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;

class AuthorizeOperationalPermission
{
    public function handle(Request $request, Closure $next)
    {
        $user = $request->user();

        if (!$user || $user->isSuperAdmin()) {
            return $next($request);
        }

        $permission = $this->requiredPermission($request);

        if (!$permission || $user->can($permission)) {
            return $next($request);
        }

        $message = 'No tienes permiso para usar esta funcionalidad.';

        if ($request->expectsJson()) {
            return response()->json(['message' => $message], 403);
        }

        abort(403, $message);
    }

    private function requiredPermission(Request $request): ?string
    {
        $path = trim($request->path(), '/');
        $method = $request->method();

        if (str_starts_with($path, 'dashboard')) {
            return 'dashboard.view';
        }

        if (str_starts_with($path, 'locations')) {
            return $method === 'GET' ? 'locations.view' : 'locations.manage';
        }

        if (str_starts_with($path, 'items')) {
            return $method === 'GET' ? 'items.view' : 'items.manage';
        }

        if (str_starts_with($path, 'cities')) {
            return $method === 'GET' ? 'cities.view' : 'cities.manage';
        }

        if (str_starts_with($path, 'customers')) {
            return $method === 'GET' ? 'customers.view' : 'customers.manage';
        }

        if (str_starts_with($path, 'inventory-outputs')) {
            if (str_contains($path, 'import')) {
                return 'inventory.outputs.import';
            }

            if (str_contains($path, 'export') || str_contains($path, 'template')) {
                return 'inventory.outputs.export';
            }

            return $method === 'GET' ? 'inventory.outputs.view' : 'inventory.outputs.manage';
        }

        if (str_starts_with($path, 'inventories')) {
            if (str_contains($path, 'import')) {
                return 'inventories.import';
            }

            if (str_contains($path, 'export')) {
                return 'inventories.export';
            }

            return $method === 'GET' ? 'inventories.view' : 'inventories.manage';
        }

        if (str_starts_with($path, 'barcode')) {
            return $method === 'GET' ? 'barcode.view' : 'barcode.manage';
        }

        if (str_starts_with($path, 'picking')) {
            return $method === 'GET' ? 'picking.view' : 'picking.manage';
        }

        if (str_starts_with($path, 'send')) {
            return 'messages.manage';
        }

        return null;
    }
}
