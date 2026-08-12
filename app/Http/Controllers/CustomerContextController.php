<?php

namespace App\Http\Controllers;

use App\Services\CustomerAccessService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\ValidationException;

class CustomerContextController extends Controller
{
    public function __construct(private CustomerAccessService $customerAccess)
    {
    }

    public function index(Request $request)
    {
        if (!Auth::check()) {
            return redirect()->route('login');
        }

        $user = $request->user();

        $customers = $this->customerAccess->availableCustomers($user, $request->input('search'));

        return view('customer-context.index', [
            'customers' => $customers,
            'selectedCustomers' => session('selected_customers', []),
            'search' => (string) $request->input('search', ''),
        ]);
    }

    public function store(Request $request)
    {
        if (!Auth::check()) {
            return redirect()->route('login');
        }

        if ((bool) config('auth.two_factor_enabled', true) && !session('two_factor_verified')) {
            return redirect()->route('two-factor.show-code-form');
        }

        $user = $request->user();

        $requestCustomers = $request->input('customers');
        if (empty($requestCustomers) && $request->filled('customer')) {
            $requestCustomers = [$request->input('customer')];
            $request->merge(['customers' => $requestCustomers]);
        }

        $validated = $request->validate([
            'customers' => ['required', 'array', 'min:1'],
            'customers.*' => ['exists:customers,name'],
        ]);

        foreach ($validated['customers'] as $customerName) {
            if (!$this->customerAccess->isCustomerAllowed($user, $customerName)) {
                throw ValidationException::withMessages([
                    'customers' => "No tienes permisos para seleccionar el cliente: {$customerName}",
                ]);
            }
        }

        session([
            'selected_customers' => array_values($validated['customers']),
            'selected_customer' => $validated['customers'][0],
        ]);

        return redirect()
            ->intended(route('dashboard'))
            ->with('success', 'Clientes seleccionados correctamente.');
    }

    public function clear(Request $request)
    {
        if (!Auth::check()) {
            return redirect()->route('login');
        }

        $request->session()->forget(['selected_customers', 'selected_customer']);

        if ($request->expectsJson()) {
            return response()->json(['success' => true]);
        }

        return redirect()
            ->route('customer.context.index')
            ->with('info', 'Selecciona al menos un cliente para continuar.');
    }

    public function autoSelectIfSingleAllowed(Request $request)
    {
        $user = $request->user();

        if (!$user || (method_exists($user, 'isSuperAdmin') && $user->isSuperAdmin())) {
            return false;
        }

        $customers = $this->customerAccess->availableCustomers($user);

        if ($customers->count() !== 1) {
            return false;
        }

        $customerName = (string) $customers->first()->name;
        session([
            'selected_customers' => [$customerName],
            'selected_customer' => $customerName,
        ]);

        return true;
    }
}
