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

        if (Schema::hasColumn('customers', 'email')) {
            DB::statement("UPDATE customers SET email = '' WHERE email IS NULL");
            DB::statement('ALTER TABLE customers MODIFY email VARCHAR(255) NOT NULL');
        }

        if (Schema::hasColumn('customers', 'phone')) {
            DB::statement("UPDATE customers SET phone = '' WHERE phone IS NULL");
            DB::statement('ALTER TABLE customers MODIFY phone VARCHAR(255) NOT NULL');
        }

        Schema::table('customers', function (Blueprint $table) {
            if (Schema::hasColumn('customers', 'city')) {
                $table->dropColumn('city');
            }
        });
    }

    public function down(): void
    {
        if (!Schema::hasTable('customers')) {
            return;
        }

        Schema::table('customers', function (Blueprint $table) {
            if (!Schema::hasColumn('customers', 'city')) {
                $table->string('city', 255)->nullable()->after('address');
            }
        });

        if (Schema::hasColumn('customers', 'email')) {
            DB::statement('ALTER TABLE customers MODIFY email VARCHAR(255) NULL');
        }

        if (Schema::hasColumn('customers', 'phone')) {
            DB::statement('ALTER TABLE customers MODIFY phone VARCHAR(255) NULL');
        }
    }
};
