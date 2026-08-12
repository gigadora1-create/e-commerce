<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasTable('customers')) {
            return;
        }

        Schema::table('customers', function (Blueprint $table) {
            if (!Schema::hasColumn('customers', 'is_warehouse_client')) {
                $table->boolean('is_warehouse_client')->default(false)->after('address')->index();
            }
        });

        $names = [];

        if (Schema::hasTable('warehouse_locations')) {
            $names = array_merge($names, DB::table('warehouse_locations')->distinct()->pluck('customer')->all());
        }

        if (Schema::hasTable('warehouse_guides')) {
            $names = array_merge($names, DB::table('warehouse_guides')->distinct()->pluck('customer')->all());
        }

        foreach (array_unique(array_filter($names)) as $name) {
            DB::table('customers')->where('name', $name)->update(['is_warehouse_client' => true]);
        }
    }

    public function down(): void
    {
        if (!Schema::hasTable('customers') || !Schema::hasColumn('customers', 'is_warehouse_client')) {
            return;
        }

        Schema::table('customers', function (Blueprint $table) {
            $table->dropColumn('is_warehouse_client');
        });
    }
};
