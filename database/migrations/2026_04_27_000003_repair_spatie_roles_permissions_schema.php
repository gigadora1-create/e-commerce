<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Spatie\Permission\PermissionRegistrar;

return new class extends Migration
{
    public function up(): void
    {
        $tableNames = config('permission.table_names');
        $columnNames = config('permission.column_names');

        if (empty($tableNames)) {
            throw new RuntimeException('Error: config/permission.php not loaded. Run [php artisan config:clear] and try again.');
        }

        $rolesTable = $tableNames['roles'] ?? 'roles';
        $permissionsTable = $tableNames['permissions'] ?? 'permissions';
        $modelHasPermissionsTable = $tableNames['model_has_permissions'] ?? 'model_has_permissions';
        $modelHasRolesTable = $tableNames['model_has_roles'] ?? 'model_has_roles';
        $roleHasPermissionsTable = $tableNames['role_has_permissions'] ?? 'role_has_permissions';
        $pivotRole = $columnNames['role_pivot_key'] ?? 'role_id';
        $pivotPermission = $columnNames['permission_pivot_key'] ?? 'permission_id';
        $modelMorphKey = $columnNames['model_morph_key'] ?? 'model_id';

        $this->repairBrokenRoleAndPermissionIds(
            $rolesTable,
            $permissionsTable,
            $roleHasPermissionsTable,
            $modelHasRolesTable,
            $modelHasPermissionsTable
        );

        $this->ensureRoleAndPermissionSchema($rolesTable, $permissionsTable);
        $this->ensurePivotTables(
            $rolesTable,
            $permissionsTable,
            $modelHasPermissionsTable,
            $modelHasRolesTable,
            $roleHasPermissionsTable,
            $pivotRole,
            $pivotPermission,
            $modelMorphKey
        );

        app(PermissionRegistrar::class)->forgetCachedPermissions();
    }

    public function down(): void
    {
        // One-way repair migration. Reversing the ID remap safely would require rebuilding
        // the role/permission pivots from historical data, which we do not have.
    }

    private function repairBrokenRoleAndPermissionIds(
        string $rolesTable,
        string $permissionsTable,
        string $roleHasPermissionsTable,
        string $modelHasRolesTable,
        string $modelHasPermissionsTable
    ): void {
        $brokenRoleIds = DB::table($rolesTable)
            ->whereIn('name', ['BODEGA', 'USUARIO', 'BIOMETRICO'])
            ->where('id', 0)
            ->exists();

        $brokenPermissionIds = DB::table($permissionsTable)
            ->whereIn('name', ['biometrico', 'warehouse.view', 'warehouse.manage', 'warehouse.export'])
            ->where('id', 0)
            ->exists();

        if (!$brokenRoleIds && !$brokenPermissionIds) {
            return;
        }

        DB::transaction(function () use (
            $rolesTable,
            $permissionsTable,
            $roleHasPermissionsTable,
            $modelHasRolesTable,
            $modelHasPermissionsTable
        ) {
            $now = now();

            $roleBaseId = ((int) DB::table($rolesTable)->max('id')) + 1;
            $permissionBaseId = ((int) DB::table($permissionsTable)->max('id')) + 1;

            $roleIdMap = [
                'BODEGA' => $roleBaseId,
                'USUARIO' => $roleBaseId + 1,
                'BIOMETRICO' => $roleBaseId + 2,
            ];

            $permissionIdMap = [
                'biometrico' => $permissionBaseId,
                'warehouse.view' => $permissionBaseId + 1,
                'warehouse.manage' => $permissionBaseId + 2,
                'warehouse.export' => $permissionBaseId + 3,
            ];

            foreach ($roleIdMap as $name => $newId) {
                DB::table($rolesTable)
                    ->where('name', $name)
                    ->update([
                        'id' => $newId,
                        'updated_at' => $now,
                    ]);
            }

            foreach ($permissionIdMap as $name => $newId) {
                DB::table($permissionsTable)
                    ->where('name', $name)
                    ->update([
                        'id' => $newId,
                        'updated_at' => $now,
                    ]);
            }

            DB::table($roleHasPermissionsTable)
                ->whereIn('role_id', [0, 1, 3])
                ->delete();

            DB::table($roleHasPermissionsTable)
                ->where('permission_id', 0)
                ->delete();

            DB::table($modelHasRolesTable)
                ->where('role_id', 0)
                ->delete();

            DB::table($modelHasPermissionsTable)
                ->where('permission_id', 0)
                ->delete();

            $allPermissionIds = DB::table($permissionsTable)
                ->pluck('id')
                ->map(function ($id) {
                    return (int) $id;
                })
                ->values()
                ->all();

            $passwordsIndexId = (int) DB::table($permissionsTable)
                ->where('name', 'passwords.index')
                ->value('id');

            $rows = [];

            foreach ($allPermissionIds as $permissionId) {
                $rows[] = [
                    'permission_id' => $permissionId,
                    'role_id' => 1,
                ];

                $rows[] = [
                    'permission_id' => $permissionId,
                    'role_id' => 3,
                ];
            }

            foreach ([
                $permissionIdMap['warehouse.view'],
                $permissionIdMap['warehouse.manage'],
                $permissionIdMap['warehouse.export'],
            ] as $permissionId) {
                $rows[] = [
                    'permission_id' => $permissionId,
                    'role_id' => $roleIdMap['BODEGA'],
                ];
            }

            if ($passwordsIndexId > 0) {
                $rows[] = [
                    'permission_id' => $passwordsIndexId,
                    'role_id' => $roleIdMap['USUARIO'],
                ];
            }

            $rows[] = [
                'permission_id' => $permissionIdMap['biometrico'],
                'role_id' => $roleIdMap['BIOMETRICO'],
            ];

            DB::table($roleHasPermissionsTable)->insert($rows);
        });
    }

    private function ensureRoleAndPermissionSchema(string $rolesTable, string $permissionsTable): void
    {
        if (!Schema::hasTable($rolesTable)) {
            Schema::create($rolesTable, function (Blueprint $table) {
                $table->bigIncrements('id');
                $table->string('name');
                $table->string('guard_name');
                $table->timestamps();
                $table->unique(['name', 'guard_name']);
            });
        }

        if (!$this->hasTablePrimaryKey($rolesTable)) {
            DB::statement("ALTER TABLE `{$rolesTable}` ADD PRIMARY KEY (`id`)");
        }

        if (!$this->hasTableIndex($rolesTable, 'roles_name_guard_name_unique')) {
            DB::statement("ALTER TABLE `{$rolesTable}` ADD UNIQUE KEY `roles_name_guard_name_unique` (`name`, `guard_name`)");
        }

        if (!$this->tableIsAutoIncrement($rolesTable)) {
            DB::statement("ALTER TABLE `{$rolesTable}` MODIFY `id` bigint unsigned NOT NULL AUTO_INCREMENT");
        }

        if (!Schema::hasTable($permissionsTable)) {
            Schema::create($permissionsTable, function (Blueprint $table) {
                $table->bigIncrements('id');
                $table->string('name');
                $table->string('guard_name');
                $table->timestamps();
                $table->unique(['name', 'guard_name']);
            });
        }

        if (!$this->hasTablePrimaryKey($permissionsTable)) {
            DB::statement("ALTER TABLE `{$permissionsTable}` ADD PRIMARY KEY (`id`)");
        }

        if (!$this->hasTableIndex($permissionsTable, 'permissions_name_guard_name_unique')) {
            DB::statement("ALTER TABLE `{$permissionsTable}` ADD UNIQUE KEY `permissions_name_guard_name_unique` (`name`, `guard_name`)");
        }

        if (!$this->tableIsAutoIncrement($permissionsTable)) {
            DB::statement("ALTER TABLE `{$permissionsTable}` MODIFY `id` bigint unsigned NOT NULL AUTO_INCREMENT");
        }

        $nextRoleId = ((int) DB::table($rolesTable)->max('id')) + 1;
        $nextPermissionId = ((int) DB::table($permissionsTable)->max('id')) + 1;

        DB::statement("ALTER TABLE `{$rolesTable}` AUTO_INCREMENT = {$nextRoleId}");
        DB::statement("ALTER TABLE `{$permissionsTable}` AUTO_INCREMENT = {$nextPermissionId}");
    }

    private function ensurePivotTables(
        string $rolesTable,
        string $permissionsTable,
        string $modelHasPermissionsTable,
        string $modelHasRolesTable,
        string $roleHasPermissionsTable,
        string $pivotRole,
        string $pivotPermission,
        string $modelMorphKey
    ): void {
        if (!Schema::hasTable($modelHasPermissionsTable)) {
            Schema::create($modelHasPermissionsTable, function (Blueprint $table) use (
                $permissionsTable,
                $pivotPermission,
                $modelMorphKey
            ) {
                $table->unsignedBigInteger($pivotPermission);
                $table->string('model_type');
                $table->unsignedBigInteger($modelMorphKey);
                $table->index([$modelMorphKey, 'model_type'], 'model_has_permissions_model_id_model_type_index');
                $table->foreign($pivotPermission)
                    ->references('id')
                    ->on($permissionsTable)
                    ->onDelete('cascade');
                $table->primary([$pivotPermission, $modelMorphKey, 'model_type'], 'model_has_permissions_permission_model_type_primary');
            });
        } elseif (!$this->hasTablePrimaryKey($modelHasPermissionsTable)) {
            DB::statement("ALTER TABLE `{$modelHasPermissionsTable}` ADD PRIMARY KEY (`{$pivotPermission}`, `{$modelMorphKey}`, `model_type`)");
        }

        if (!Schema::hasTable($modelHasRolesTable)) {
            Schema::create($modelHasRolesTable, function (Blueprint $table) use (
                $rolesTable,
                $pivotRole,
                $modelMorphKey
            ) {
                $table->unsignedBigInteger($pivotRole);
                $table->string('model_type');
                $table->unsignedBigInteger($modelMorphKey);
                $table->index([$modelMorphKey, 'model_type'], 'model_has_roles_model_id_model_type_index');
                $table->foreign($pivotRole)
                    ->references('id')
                    ->on($rolesTable)
                    ->onDelete('cascade');
                $table->primary([$pivotRole, $modelMorphKey, 'model_type'], 'model_has_roles_role_model_type_primary');
            });
        } elseif (!$this->hasTablePrimaryKey($modelHasRolesTable)) {
            DB::statement("ALTER TABLE `{$modelHasRolesTable}` ADD PRIMARY KEY (`{$pivotRole}`, `{$modelMorphKey}`, `model_type`)");
        }

        if (!Schema::hasTable($roleHasPermissionsTable)) {
            Schema::create($roleHasPermissionsTable, function (Blueprint $table) use (
                $rolesTable,
                $permissionsTable,
                $pivotRole,
                $pivotPermission
            ) {
                $table->unsignedBigInteger($pivotPermission);
                $table->unsignedBigInteger($pivotRole);
                $table->foreign($pivotPermission)
                    ->references('id')
                    ->on($permissionsTable)
                    ->onDelete('cascade');
                $table->foreign($pivotRole)
                    ->references('id')
                    ->on($rolesTable)
                    ->onDelete('cascade');
                $table->primary([$pivotPermission, $pivotRole], 'role_has_permissions_permission_id_role_id_primary');
            });
        } elseif (!$this->hasTablePrimaryKey($roleHasPermissionsTable)) {
            DB::statement("ALTER TABLE `{$roleHasPermissionsTable}` ADD PRIMARY KEY (`{$pivotPermission}`, `{$pivotRole}`)");
        }
    }

    private function hasTablePrimaryKey(string $table): bool
    {
        if (!Schema::hasTable($table)) {
            return false;
        }

        $indexes = DB::select("SHOW INDEX FROM `{$table}` WHERE Key_name = 'PRIMARY'");

        return !empty($indexes);
    }

    private function hasTableIndex(string $table, string $indexName): bool
    {
        if (!Schema::hasTable($table)) {
            return false;
        }

        $indexes = DB::select("SHOW INDEX FROM `{$table}` WHERE Key_name = ?", [$indexName]);

        return !empty($indexes);
    }

    private function tableIsAutoIncrement(string $table): bool
    {
        if (!Schema::hasTable($table)) {
            return false;
        }

        $columns = DB::select("SHOW COLUMNS FROM `{$table}` WHERE Field = 'id'");

        if (empty($columns)) {
            return false;
        }

        return str_contains(strtolower((string) ($columns[0]->Extra ?? '')), 'auto_increment');
    }
};
