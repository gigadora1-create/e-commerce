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

        $adminPermission = Permission::findOrCreate('supplies.admin', 'web');
        $requestPermission = Permission::findOrCreate('supplies.request', 'web');

        Role::findOrCreate('PROVEEDURIA_ADMIN', 'web')
            ->givePermissionTo([$adminPermission, $requestPermission]);
        Role::findOrCreate('PROVEEDURIA_USUARIO', 'web')
            ->givePermissionTo($requestPermission);

        app(PermissionRegistrar::class)->forgetCachedPermissions();
    }

    public function down(): void
    {
        // Permissions are shared with existing supply records and must remain intact.
    }
};
