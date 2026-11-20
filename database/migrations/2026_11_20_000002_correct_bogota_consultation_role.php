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

        $role = Role::where('name', 'USUARIO BOGOTA CONSULTAS')->where('guard_name', 'web')->first();

        if (!$role) {
            return;
        }

        $managedPermissionNames = array_keys(config('role_permissions.permissions', []));
        $preservedPermissions = $role->permissions()
            ->whereNotIn('permissions.name', $managedPermissionNames)
            ->get();
        $readOnlyPermissions = Permission::query()
            ->whereIn('name', [
                'dashboard.view',
                'inventories.view',
                'inventory.outputs.view',
            ])
            ->where('guard_name', 'web')
            ->get();

        $role->syncPermissions($preservedPermissions->merge($readOnlyPermissions));

        app(PermissionRegistrar::class)->forgetCachedPermissions();
    }

    public function down(): void
    {
        // Permission changes are intentionally retained to avoid restoring write access.
    }
};
