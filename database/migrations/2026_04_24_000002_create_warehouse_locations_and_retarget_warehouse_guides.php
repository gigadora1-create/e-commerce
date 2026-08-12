<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasTable('warehouse_locations')) {
            Schema::create('warehouse_locations', function (Blueprint $table) {
                $table->id('location_id');
                $table->string('code', 30);
                $table->string('customer', 100)->index();
                $table->string('name', 255);
                $table->string('warehouse', 100)->index();
                $table->string('description', 1000)->nullable();
                $table->boolean('is_active')->default(true)->index();
                $table->boolean('is_storage')->default(false)->index();
                $table->timestamps();

                $table->unique(['customer', 'code']);
                $table->index(['customer', 'warehouse']);
            });
        }

        if (Schema::hasTable('warehouse_guides')) {
            Schema::table('warehouse_guides', function (Blueprint $table) {
                try {
                    $table->dropForeign(['current_location_id']);
                } catch (\Throwable $e) {
                    // Foreign key may already be absent on partially migrated installs.
                }
            });

            Schema::table('warehouse_guides', function (Blueprint $table) {
                $table->foreign('current_location_id')
                    ->references('location_id')
                    ->on('warehouse_locations')
                    ->nullOnDelete();
            });
        }

        if (Schema::hasTable('warehouse_guide_movements')) {
            Schema::table('warehouse_guide_movements', function (Blueprint $table) {
                try {
                    $table->dropForeign(['from_location_id']);
                } catch (\Throwable $e) {
                    // Ignored on fresh/partially modified databases.
                }

                try {
                    $table->dropForeign(['to_location_id']);
                } catch (\Throwable $e) {
                    // Ignored on fresh/partially modified databases.
                }
            });

            Schema::table('warehouse_guide_movements', function (Blueprint $table) {
                $table->foreign('from_location_id')
                    ->references('location_id')
                    ->on('warehouse_locations')
                    ->nullOnDelete();

                $table->foreign('to_location_id')
                    ->references('location_id')
                    ->on('warehouse_locations')
                    ->nullOnDelete();
            });
        }
    }

    public function down(): void
    {
        if (Schema::hasTable('warehouse_guide_movements')) {
            Schema::table('warehouse_guide_movements', function (Blueprint $table) {
                try {
                    $table->dropForeign(['from_location_id']);
                } catch (\Throwable $e) {
                }

                try {
                    $table->dropForeign(['to_location_id']);
                } catch (\Throwable $e) {
                }
            });

            Schema::table('warehouse_guide_movements', function (Blueprint $table) {
                $table->foreign('from_location_id')
                    ->references('location_id')
                    ->on('locations')
                    ->nullOnDelete();

                $table->foreign('to_location_id')
                    ->references('location_id')
                    ->on('locations')
                    ->nullOnDelete();
            });
        }

        if (Schema::hasTable('warehouse_guides')) {
            Schema::table('warehouse_guides', function (Blueprint $table) {
                try {
                    $table->dropForeign(['current_location_id']);
                } catch (\Throwable $e) {
                }
            });

            Schema::table('warehouse_guides', function (Blueprint $table) {
                $table->foreign('current_location_id')
                    ->references('location_id')
                    ->on('locations')
                    ->nullOnDelete();
            });
        }

        Schema::dropIfExists('warehouse_locations');
    }
};
