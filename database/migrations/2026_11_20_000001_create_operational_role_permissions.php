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

        $permissions = collect(config('role_permissions.permissions'))
            ->keys()
            ->map(fn (string $name) => Permission::findOrCreate($name, 'web'));

        foreach (['ADMINISTRADOR', 'SUPERADMIN', 'SUPER_ADMIN'] as $roleName) {
            $role = Role::where('name', $roleName)->where('guard_name', 'web')->first();

            if ($role) {
                $role->givePermissionTo($permissions);
            }
        }

        $legacyLocationRoles = Role::permission('UBICACION')->where('guard_name', 'web')->get();
        $legacyLocationPermissions = $permissions->whereIn('name', ['locations.view', 'locations.manage']);

        foreach ($legacyLocationRoles as $role) {
            $role->givePermissionTo($legacyLocationPermissions);
        }

        app(PermissionRegistrar::class)->forgetCachedPermissions();
    }

    public function down(): void
    {
        // Permissions remain available because roles can already use them in production.
    }
};
