<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasTable('supply_clients')) {
            return;
        }

        Schema::table('supply_clients', function (Blueprint $table) {
            if (!Schema::hasColumn('supply_clients', 'address')) {
                $table->string('address', 255)->nullable()->after('name');
            }

            if (!Schema::hasColumn('supply_clients', 'city')) {
                $table->string('city', 255)->nullable()->after('address');
            }
        });

        DB::statement("UPDATE supply_clients SET address = 'SIN DIRECCIÓN' WHERE address IS NULL OR TRIM(address) = ''");
        DB::statement("UPDATE supply_clients SET city = 'SIN CIUDAD' WHERE city IS NULL OR TRIM(city) = ''");
        DB::statement('ALTER TABLE supply_clients MODIFY address VARCHAR(255) NOT NULL');
        DB::statement('ALTER TABLE supply_clients MODIFY city VARCHAR(255) NOT NULL');
    }

    public function down(): void
    {
        if (!Schema::hasTable('supply_clients')) {
            return;
        }

        Schema::table('supply_clients', function (Blueprint $table) {
            if (Schema::hasColumn('supply_clients', 'city')) {
                $table->dropColumn('city');
            }

            if (Schema::hasColumn('supply_clients', 'address')) {
                $table->dropColumn('address');
            }
        });
    }
};
