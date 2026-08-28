<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('supply_req_case_syncs', function (Blueprint $table) {
            $table->dropForeign(['supply_issue_request_id']);
            $table->dropUnique(['supply_issue_request_id']);
            $table->dropColumn('supply_issue_request_id');
            $table->foreignId('supply_request_id')->unique()->after('id')
                ->constrained('supply_requests')->cascadeOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('supply_req_case_syncs', function (Blueprint $table) {
            $table->dropForeign(['supply_request_id']);
            $table->dropUnique(['supply_request_id']);
            $table->dropColumn('supply_request_id');
            $table->foreignId('supply_issue_request_id')->unique()->after('id')
                ->constrained('supply_issue_requests')->cascadeOnDelete();
        });
    }
};
