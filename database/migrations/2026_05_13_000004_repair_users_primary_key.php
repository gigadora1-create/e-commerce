<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasTable('users') || !Schema::hasColumn('users', 'id')) {
            return;
        }

        if (!$this->hasPrimaryKey('users')) {
            $summary = DB::table('users')
                ->selectRaw('COUNT(*) as total, COUNT(DISTINCT id) as distinct_ids, SUM(CASE WHEN id IS NULL THEN 1 ELSE 0 END) as null_ids')
                ->first();

            if ((int) $summary->total !== (int) $summary->distinct_ids || (int) $summary->null_ids > 0) {
                throw new RuntimeException('No se puede reparar users.id: existen IDs duplicados o nulos.');
            }

            DB::statement('ALTER TABLE `users` ADD PRIMARY KEY (`id`)');
        }

        if (!$this->isAutoIncrement('users', 'id')) {
            $nextId = ((int) DB::table('users')->max('id')) + 1;
            DB::statement('ALTER TABLE `users` MODIFY `id` bigint unsigned NOT NULL AUTO_INCREMENT');
            DB::statement("ALTER TABLE `users` AUTO_INCREMENT = {$nextId}");
        }
    }

    public function down(): void
    {
        // This is a schema repair migration. Do not remove the users primary key on rollback.
    }

    private function hasPrimaryKey(string $table): bool
    {
        $indexes = DB::select("SHOW INDEX FROM `{$table}` WHERE Key_name = 'PRIMARY'");

        return !empty($indexes);
    }

    private function isAutoIncrement(string $table, string $column): bool
    {
        $columns = DB::select("SHOW COLUMNS FROM `{$table}` WHERE Field = ?", [$column]);

        return !empty($columns) && str_contains(strtolower((string) $columns[0]->Extra), 'auto_increment');
    }
};
