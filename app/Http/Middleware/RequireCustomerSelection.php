<?php

namespace App\Http\Middleware;

use App\Services\CustomerAccessService;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Str;

class RequireCustomerSelection
{
    public function handle(Request $request, Closure $next)
    {
        if (!Auth::check()) {
            return $next($request);
        }

        if ((bool) config('auth.two_factor_enabled', true) && !session('two_factor_verified')) {
            return $next($request);
        }

        $routeName = $request->route()?->getName();
        $allowedRoutes = [
            'login',
            'logout',
            'two-factor.send-code',
            'two-factor.show-code-form',
            'two-factor.verify-code',
            'customer.context.index',
            'customer.context.store',
            'customer.context.clear',
        ];

        if ($routeName && in_array($routeName, $allowedRoutes, true)) {
            return $next($request);
        }

        if ($routeName && Str::startsWith($routeName, [
            'permissions.',
            'roles.',
            'profiles.',
            'admin.',
            'role_permissions.',
            'warehouse.',
            'supplies.',
        ])) {
            return $next($request);
        }

        $selectedCustomers = session('selected_customers', []);

        if (!empty($selectedCustomers)) {
            if (!$this->selectionMatchesNormalContext($request, $selectedCustomers)) {
                return $this->redirectToCustomerContext($request);
            }

            session(['selected_customer' => $selectedCustomers[0]]);
            return $next($request);
        }

        $selectedCustomer = session('selected_customer');

        if (!empty($selectedCustomer)) {
            if (!$this->selectionMatchesNormalContext($request, [$selectedCustomer])) {
                return $this->redirectToCustomerContext($request);
            }

            session(['selected_customers' => [$selectedCustomer]]);
            return $next($request);
        }

        return $this->redirectToCustomerContext($request);
    }

    private function selectionMatchesNormalContext(Request $request, array $selectedCustomers): bool
    {
        $allowedCustomers = app(CustomerAccessService::class)
            ->availableCustomers($request->user())
            ->pluck('name')
            ->map(fn ($name) => (string) $name)
            ->all();

        if (empty($allowedCustomers)) {
            return false;
        }

        foreach ($selectedCustomers as $customer) {
            if (!in_array((string) $customer, $allowedCustomers, true)) {
                return false;
            }
        }

        return true;
    }

    private function redirectToCustomerContext(Request $request)
    {
        $request->session()->forget(['selected_customer', 'selected_customers']);
        session(['url.intended' => $request->fullUrl()]);

        return redirect()
            ->route('customer.context.index')
            ->with('warning', 'Selecciona un cliente normal para continuar.');
    }
}
