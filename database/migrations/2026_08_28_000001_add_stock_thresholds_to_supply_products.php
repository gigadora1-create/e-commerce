<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('supply_products', function (Blueprint $table) {
            if (!Schema::hasColumn('supply_products', 'minimum_stock')) {
                $table->unsignedInteger('minimum_stock')->default(5)->after('reserved_stock');
            }

            if (!Schema::hasColumn('supply_products', 'medium_stock')) {
                $table->unsignedInteger('medium_stock')->default(15)->after('minimum_stock');
            }
        });
    }

    public function down(): void
    {
        Schema::table('supply_products', function (Blueprint $table) {
            $columns = [];

            if (Schema::hasColumn('supply_products', 'medium_stock')) {
                $columns[] = 'medium_stock';
            }

            if (Schema::hasColumn('supply_products', 'minimum_stock')) {
                $columns[] = 'minimum_stock';
            }

            if ($columns !== []) {
                $table->dropColumn($columns);
            }
        });
    }
};
