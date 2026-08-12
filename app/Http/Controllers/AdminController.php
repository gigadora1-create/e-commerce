<?php



namespace App\Http\Controllers;

use App\Models\User;
use App\Models\Customer;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Spatie\Permission\Models\Role;
use Spatie\Permission\PermissionRegistrar;

class AdminController extends Controller
{
    public function __construct()
    {
        $this->middleware(['auth', 'super.admin']);
    }

    public function index()
    {
        $users = User::all();
        return view('Admin.index', compact('users'));
    }

    public function edit($id)
    {
        $roles = Role::all();
        $usuario = User::findOrFail($id);
        $customers = Customer::query()
            ->orderBy('is_warehouse_client')
            ->orderBy('name')
            ->get();
        $selectedCustomerIds = $usuario->customerAccesses()
            ->pluck('customers.customer_id')
            ->map(fn ($id) => (int) $id)
            ->all();

        return view('Admin.edit', compact('usuario', 'roles', 'customers', 'selectedCustomerIds'));
    }

    public function update(Request $request, $id)
    {
        $usuario = User::findOrFail($id);

        $validated = $request->validate([
            'roles' => ['nullable', 'array'],
            'roles.*' => ['nullable', 'string'],
            'customer_ids' => ['nullable', 'array'],
            'customer_ids.*' => [
                'integer',
                Rule::exists('customers', 'customer_id'),
            ],
        ]);

        $rolesToSync = collect($validated['roles'] ?? [])
            ->map(function ($value) {
                if ($value === null || $value === '') {
                    return null;
                }

                if (is_numeric($value)) {
                    return Role::query()->whereKey((int) $value)->value('name');
                }

                return trim((string) $value);
            })
            ->filter()
            ->unique()
            ->values()
            ->all();

        $usuario->syncRoles($rolesToSync);
        $usuario->customerAccesses()->sync($validated['customer_ids'] ?? []);
        app(PermissionRegistrar::class)->forgetCachedPermissions();
        return redirect()->route('admin.edit', $id)->with('info', 'Roles y clientes asignados correctamente');
    }
}
