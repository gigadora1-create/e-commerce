<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('supply_req_case_syncs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('supply_issue_request_id')
                ->unique()
                ->constrained('supply_issue_requests')
                ->cascadeOnDelete();
            $table->string('status', 32)->default('pending')->index();
            $table->unsignedBigInteger('external_case_id')->nullable()->unique();
            $table->unsignedInteger('attempts')->default(0);
            $table->timestamp('last_attempt_at')->nullable();
            $table->timestamp('synced_at')->nullable();
            $table->json('request_payload')->nullable();
            $table->json('response_payload')->nullable();
            $table->text('last_error')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('supply_req_case_syncs');
    }
};
