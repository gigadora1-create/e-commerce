<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('customer_user_accesses')) {
            $this->ensureIndexes();
            return;
        }

        Schema::create('customer_user_accesses', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('user_id');
            $table->unsignedBigInteger('customer_id');
            $table->timestamps();

            $table->unique(['user_id', 'customer_id']);
            $table->index('user_id');
            $table->index('customer_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('customer_user_accesses');
    }

    private function ensureIndexes(): void
    {
        if (!$this->hasIndex('customer_user_accesses_user_id_customer_id_unique')) {
            DB::statement('ALTER TABLE `customer_user_accesses` ADD UNIQUE KEY `customer_user_accesses_user_id_customer_id_unique` (`user_id`, `customer_id`)');
        }

        if (!$this->hasIndex('customer_user_accesses_user_id_index')) {
            DB::statement('ALTER TABLE `customer_user_accesses` ADD KEY `customer_user_accesses_user_id_index` (`user_id`)');
        }

        if (!$this->hasIndex('customer_user_accesses_customer_id_index')) {
            DB::statement('ALTER TABLE `customer_user_accesses` ADD KEY `customer_user_accesses_customer_id_index` (`customer_id`)');
        }
    }

    private function hasIndex(string $indexName): bool
    {
        $indexes = DB::select('SHOW INDEX FROM `customer_user_accesses` WHERE Key_name = ?', [$indexName]);

        return !empty($indexes);
    }
};
