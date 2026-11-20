<?php

use Illuminate\Database\Migrations\Migration;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Spatie\Permission\PermissionRegistrar;

return new class extends Migration
{
    public function up(): void
    {
        app(PermissionRegistrar::class)->forgetCachedPermissions();

        $readOnlyPermissions = Permission::query()
            ->whereIn('name', [
                'dashboard.view',
                'inventories.view',
                'inventory.outputs.view',
            ])
            ->where('guard_name', 'web')
            ->get();
        $managedPermissionNames = array_keys(config('role_permissions.permissions', []));

        foreach (['USUARIO BOGOTA CONSULTAS', 'BOGOTA CONSULTA'] as $roleName) {
            $role = Role::where('name', $roleName)->where('guard_name', 'web')->first();

            if (!$role) {
                continue;
            }

            $preservedPermissions = $role->permissions()
                ->whereNotIn('permissions.name', $managedPermissionNames)
                ->get();

            $role->syncPermissions($preservedPermissions->merge($readOnlyPermissions));
        }

        app(PermissionRegistrar::class)->forgetCachedPermissions();
    }

    public function down(): void
    {
        // Keep the role read-only to prevent accidental restoration of write access.
    }
};
