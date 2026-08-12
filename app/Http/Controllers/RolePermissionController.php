<?php

namespace App\Http\Controllers;

use App\Models\Customer;
use Illuminate\Http\Request;
use Spatie\Permission\Models\Role;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\PermissionRegistrar;
use Illuminate\Support\Facades\Log; // Importar el facade Log

class RolePermissionController extends Controller
{
    public function __construct()
    {
        $this->middleware(['auth', 'super.admin']);
    }

    public function index()
    {
        $roles = Role::all();
        $customerPermissionNames = Customer::query()
            ->pluck('name')
            ->filter()
            ->values()
            ->all();

        $permissions = Permission::where('guard_name', 'web')
            ->when(!empty($customerPermissionNames), function ($query) use ($customerPermissionNames) {
                $query->whereNotIn('name', $customerPermissionNames);
            })
            ->orderBy('name')
            ->get();

        if ($permissions->isEmpty()) {
            return redirect()->back()->with('error', 'No hay permisos disponibles. Por favor, crea algunos permisos primero.');
        }

        return view('role_permission.index', compact('roles', 'permissions'));
    }

    public function assignPermissions(Request $request, $roleId)
    {
        // Validar que se hayan enviado permisos
        $request->validate([
            'permissions' => 'nullable|array',
            'permissions.*' => 'exists:permissions,id',
        ]);

        // Encontrar el rol
        $role = Role::findOrFail($roleId);

        // Obtener los IDs de los permisos enviados
        $permissionIds = $request->input('permissions', []);

        // Depurar los IDs recibidos
        Log::info('Permission IDs received:', $permissionIds);

        // Si no se enviaron permisos, sincronizar con un array vacío
        if (empty($permissionIds)) {
            $role->syncPermissions([]);
        } else {
            // Buscar los permisos por ID
            $permissions = Permission::whereIn('id', $permissionIds)->get();

            // Depurar los permisos encontrados
            Log::info('Permissions found:', $permissions->toArray());

            // Sincronizar los permisos usando las instancias de Permission
            $role->syncPermissions($permissions);
        }

        app(PermissionRegistrar::class)->forgetCachedPermissions();

        return redirect()->route('role_permissions.index')
            ->with('success', 'Permisos asignados al rol exitosamente');
    }
}
