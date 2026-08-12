<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('warehouse_guides') && !Schema::hasColumn('warehouse_guides', 'national_guide')) {
            Schema::table('warehouse_guides', function (Blueprint $table) {
                $table->string('national_guide', 60)->nullable()->after('guide')->index();
            });
        }

        if (Schema::hasTable('warehouse_guide_movements') && !Schema::hasColumn('warehouse_guide_movements', 'national_guide')) {
            Schema::table('warehouse_guide_movements', function (Blueprint $table) {
                $table->string('national_guide', 60)->nullable()->after('action')->index();
            });
        }
    }

    public function down(): void
    {
        if (Schema::hasTable('warehouse_guide_movements') && Schema::hasColumn('warehouse_guide_movements', 'national_guide')) {
            Schema::table('warehouse_guide_movements', function (Blueprint $table) {
                $table->dropColumn('national_guide');
            });
        }

        if (Schema::hasTable('warehouse_guides') && Schema::hasColumn('warehouse_guides', 'national_guide')) {
            Schema::table('warehouse_guides', function (Blueprint $table) {
                $table->dropColumn('national_guide');
            });
        }
    }
};
