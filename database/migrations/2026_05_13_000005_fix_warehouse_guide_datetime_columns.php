<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('warehouse_guides')) {
            DB::statement('ALTER TABLE `warehouse_guides` MODIFY `entry_at` DATETIME NOT NULL');
            DB::statement('ALTER TABLE `warehouse_guides` MODIFY `exit_at` DATETIME NULL DEFAULT NULL');
            DB::statement('ALTER TABLE `warehouse_guides` MODIFY `created_at` DATETIME NULL DEFAULT NULL');
            DB::statement('ALTER TABLE `warehouse_guides` MODIFY `updated_at` DATETIME NULL DEFAULT NULL');
        }

        if (Schema::hasTable('warehouse_guide_movements')) {
            DB::statement('ALTER TABLE `warehouse_guide_movements` MODIFY `performed_at` DATETIME NOT NULL');
        }
    }

    public function down(): void
    {
        if (Schema::hasTable('warehouse_guides')) {
            DB::statement('ALTER TABLE `warehouse_guides` MODIFY `entry_at` TIMESTAMP NOT NULL');
            DB::statement('ALTER TABLE `warehouse_guides` MODIFY `exit_at` TIMESTAMP NULL DEFAULT NULL');
            DB::statement('ALTER TABLE `warehouse_guides` MODIFY `created_at` TIMESTAMP NULL DEFAULT NULL');
            DB::statement('ALTER TABLE `warehouse_guides` MODIFY `updated_at` TIMESTAMP NULL DEFAULT NULL');
        }

        if (Schema::hasTable('warehouse_guide_movements')) {
            DB::statement('ALTER TABLE `warehouse_guide_movements` MODIFY `performed_at` TIMESTAMP NOT NULL');
        }
    }
};
