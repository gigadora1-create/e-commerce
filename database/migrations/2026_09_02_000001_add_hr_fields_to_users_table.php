<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->string('hr_employee_id', 64)->nullable()->unique()->after('id');
            $table->string('position')->nullable()->after('telephone');
            $table->string('process')->nullable()->after('position');
            $table->string('regional')->nullable()->after('process');
            $table->boolean('is_active')->default(true)->after('user_type');
            $table->timestamp('synced_from_hr_at')->nullable()->after('is_active');
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropUnique(['hr_employee_id']);
            $table->dropColumn([
                'hr_employee_id',
                'position',
                'process',
                'regional',
                'is_active',
                'synced_from_hr_at',
            ]);
        });
    }
};
