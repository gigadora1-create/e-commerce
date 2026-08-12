<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasTable('warehouse_guides')) {
            Schema::create('warehouse_guides', function (Blueprint $table) {
                $table->id();
                $table->string('guide', 30);
                $table->string('customer', 100)->index();
                $table->string('warehouse', 100)->index();
                $table->string('status', 30)->default('ACTIVE')->index();
                $table->timestamp('entry_at')->index();
                $table->timestamp('exit_at')->nullable()->index();
                $table->string('entry_source', 20)->default('manual');
                $table->unsignedBigInteger('entry_user_id')->nullable()->index();
                $table->unsignedBigInteger('exit_user_id')->nullable()->index();
                $table->foreignId('current_location_id')->nullable()->constrained('locations', 'location_id')->nullOnDelete();
                $table->string('current_location_code', 50)->nullable();
                $table->string('current_location_name', 150)->nullable();
                $table->text('notes')->nullable();
                $table->timestamps();

                $table->unique(['customer', 'guide']);
                $table->index(['customer', 'status']);
                $table->index(['customer', 'warehouse']);
                $table->index(['customer', 'current_location_id']);
            });
        } else {
            Schema::table('warehouse_guides', function (Blueprint $table) {
                $table->unique(['customer', 'guide']);
                $table->index(['customer', 'status']);
                $table->index(['customer', 'warehouse']);
                $table->index(['customer', 'current_location_id']);
                $table->index('entry_at');
                $table->index('exit_at');
                $table->foreign('current_location_id')->references('location_id')->on('locations')->nullOnDelete();
            });
        }

        if (!Schema::hasTable('warehouse_guide_movements')) {
            Schema::create('warehouse_guide_movements', function (Blueprint $table) {
                $table->id();
                $table->foreignId('warehouse_guide_id')->constrained('warehouse_guides')->cascadeOnDelete();
                $table->string('action', 20)->index();
                $table->foreignId('from_location_id')->nullable()->constrained('locations', 'location_id')->nullOnDelete();
                $table->string('from_location_code', 50)->nullable();
                $table->string('from_location_name', 150)->nullable();
                $table->foreignId('to_location_id')->nullable()->constrained('locations', 'location_id')->nullOnDelete();
                $table->string('to_location_code', 50)->nullable();
                $table->string('to_location_name', 150)->nullable();
                $table->unsignedBigInteger('performed_by')->nullable()->index();
                $table->timestamp('performed_at')->index();
                $table->text('notes')->nullable();
            });
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('warehouse_guide_movements');
        Schema::dropIfExists('warehouse_guides');
    }
};
