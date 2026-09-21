<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasTable('users')) {
            return;
        }

        Schema::table('users', function (Blueprint $table) {
            if (!Schema::hasColumn('users', 'theme_mode')) {
                $table->string('theme_mode', 20)->default('system');
            }

            if (!Schema::hasColumn('users', 'two_factor_enabled')) {
                $table->boolean('two_factor_enabled')->default(true);
            }
        });
    }

    public function down(): void
    {
        if (!Schema::hasTable('users')) {
            return;
        }

        Schema::table('users', function (Blueprint $table) {
            $columns = [];

            if (Schema::hasColumn('users', 'theme_mode')) {
                $columns[] = 'theme_mode';
            }

            if (Schema::hasColumn('users', 'two_factor_enabled')) {
                $columns[] = 'two_factor_enabled';
            }

            if ($columns !== []) {
                $table->dropColumn($columns);
            }
        });
    }
};
