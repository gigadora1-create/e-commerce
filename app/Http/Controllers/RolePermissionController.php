<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Spatie\Permission\PermissionRegistrar;

class RolePermissionController extends Controller
{
    public function __construct()
    {
        $this->middleware(['auth', 'super.admin']);
    }

    public function index(Request $request)
    {
        $catalog = config('role_permissions.permissions', []);
        $roles = Role::with('permissions')->where('guard_name', 'web')->orderBy('name')->get();
        $permissions = Permission::query()
            ->where('guard_name', 'web')
            ->whereIn('name', array_keys($catalog))
            ->get()
            ->sortBy(fn (Permission $permission) => $catalog[$permission->name]['label'] ?? $permission->name)
            ->values();

        $selectedRole = $roles->firstWhere('id', (int) $request->input('role')) ?: $roles->first();
        $permissionGroups = $permissions->groupBy(
            fn (Permission $permission) => $catalog[$permission->name]['group'] ?? 'other'
        );

        return view('role_permission.index', compact('roles', 'permissions', 'selectedRole', 'permissionGroups'));
    }

    public function assignPermissions(Request $request, int $roleId)
    {
        $managedPermissionIds = Permission::query()
            ->where('guard_name', 'web')
            ->whereIn('name', array_keys(config('role_permissions.permissions', [])))
            ->pluck('id')
            ->all();

        $request->validate([
            'permissions' => 'nullable|array',
            'permissions.*' => 'integer|in:' . implode(',', $managedPermissionIds),
        ]);

        $role = Role::where('guard_name', 'web')->findOrFail($roleId);
        $selectedPermissions = Permission::whereIn('id', $request->input('permissions', []))->get();
        $preservedPermissions = $role->permissions()
            ->whereNotIn('permissions.id', $managedPermissionIds)
            ->get();

        // Legacy and customer-scope permissions are not managed by this screen.
        $role->syncPermissions($preservedPermissions->merge($selectedPermissions));

        app(PermissionRegistrar::class)->forgetCachedPermissions();

        return redirect()->route('role_permissions.index', ['role' => $role->id])
            ->with('success', 'Permisos actualizados para ' . $role->name . '.');
    }
}
