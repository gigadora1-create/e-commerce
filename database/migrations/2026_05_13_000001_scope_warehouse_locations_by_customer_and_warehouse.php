<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasTable('warehouse_locations')) {
            return;
        }

        Schema::table('warehouse_locations', function (Blueprint $table) {
            try {
                $table->dropUnique(['customer', 'code']);
            } catch (\Throwable $e) {
                try {
                    $table->dropUnique('warehouse_locations_customer_code_unique');
                } catch (\Throwable $ignored) {
                }
            }

            $table->unique(['customer', 'warehouse', 'code'], 'warehouse_locations_customer_warehouse_code_unique');
        });
    }

    public function down(): void
    {
        if (!Schema::hasTable('warehouse_locations')) {
            return;
        }

        Schema::table('warehouse_locations', function (Blueprint $table) {
            try {
                $table->dropUnique('warehouse_locations_customer_warehouse_code_unique');
            } catch (\Throwable $e) {
            }

            $table->unique(['customer', 'code']);
        });
    }
};
