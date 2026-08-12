<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Role;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\PermissionRegistrar;

class RolePermissionSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        app(PermissionRegistrar::class)->forgetCachedPermissions();

        // Crear permisos si no existen
        $permissions = [
            'biometrico' => Permission::firstOrCreate(['name' => 'biometrico']),
            'passwords.index' => Permission::firstOrCreate(['name' => 'passwords.index']),
            'password.create' => Permission::firstOrCreate(['name' => 'password.create']),
            'SUPER_ADMIN' => Permission::firstOrCreate(['name' => 'SUPER_ADMIN']),
            'UBICACION' => Permission::firstOrCreate(['name' => 'UBICACION']),
            'BOGOTA' => Permission::firstOrCreate(['name' => 'BOGOTA']),
            'BOGOTA PRINCIPAL' => Permission::firstOrCreate(['name' => 'BOGOTA PRINCIPAL']),
            'BOGOTA SECUNDARIA' => Permission::firstOrCreate(['name' => 'BOGOTA SECUNDARIA']),
            'CALI' => Permission::firstOrCreate(['name' => 'CALI']),
            'MEDELLIN' => Permission::firstOrCreate(['name' => 'MEDELLIN']),
            'BARRANQUILLA' => Permission::firstOrCreate(['name' => 'BARRANQUILLA']),
            'QUIBDO' => Permission::firstOrCreate(['name' => 'QUIBDO']),
            'SKYONE' => Permission::firstOrCreate(['name' => 'SKYONE']),
            'CARGOSMART' => Permission::firstOrCreate(['name' => 'CARGOSMART']),
            'USUARIO_CLIENTE' => Permission::firstOrCreate(['name' => 'USUARIO_CLIENTE']),
            'warehouse.view' => Permission::firstOrCreate(['name' => 'warehouse.view']),
            'warehouse.manage' => Permission::firstOrCreate(['name' => 'warehouse.manage']),
            'warehouse.export' => Permission::firstOrCreate(['name' => 'warehouse.export']),
        ];

        // Crear roles si no existen
        $roles = [
            'ADMINISTRADOR' => Role::firstOrCreate(['name' => 'ADMINISTRADOR']),
            'SUPERADMIN' => Role::firstOrCreate(['name' => 'SUPERADMIN']),
            'USUARIO' => Role::firstOrCreate(['name' => 'USUARIO']),
            'BIOMETRICO' => Role::firstOrCreate(['name' => 'BIOMETRICO']),
            'BODEGA' => Role::firstOrCreate(['name' => 'BODEGA']),
        ];

        // Asignar permisos a los roles

        // Admin y superadmin tienen acceso a todo lo que exista en la tabla
        $allPermissions = Permission::all();
        $roles['ADMINISTRADOR']->syncPermissions($allPermissions);
        $roles['SUPERADMIN']->syncPermissions($allPermissions);

        // El rol BIOMETRICO solo tiene acceso a la funcionalidad biométrica
        $roles['BIOMETRICO']->syncPermissions([$permissions['biometrico']]);

        // El rol USUARIO solo tiene los permisos que le autorices
        $roles['USUARIO']->syncPermissions([$permissions['passwords.index']]);

        // Rol de bodega para el nuevo modulo de guias y ubicaciones
        $roles['BODEGA']->syncPermissions([
            $permissions['warehouse.view'],
            $permissions['warehouse.manage'],
            $permissions['warehouse.export'],
        ]);
    }
}
