<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('supply_clients', function (Blueprint $table) {
            $table->id();
            $table->string('name')->unique();
            $table->string('contact_name')->nullable();
            $table->string('contact_phone')->nullable();
            $table->text('notes')->nullable();
            $table->boolean('is_active')->default(true)->index();
            $table->timestamps();
        });

        Schema::table('supply_requests', function (Blueprint $table) {
            $table->foreignId('supply_client_id')
                ->nullable()
                ->after('audited_by_user_id')
                ->constrained('supply_clients')
                ->nullOnDelete();
        });

        Schema::table('supply_issue_requests', function (Blueprint $table) {
            $table->foreignId('supply_client_id')
                ->nullable()
                ->after('requested_by_user_id')
                ->constrained('supply_clients')
                ->nullOnDelete();
        });

        $defaultClients = [
            ['name' => 'DERCO FUNZA'],
            ['name' => 'CLIENTE INTERNO'],
        ];

        $now = now();

        foreach ($defaultClients as $client) {
            DB::table('supply_clients')->updateOrInsert(
                ['name' => $client['name']],
                [
                    'contact_name' => null,
                    'contact_phone' => null,
                    'notes' => null,
                    'is_active' => true,
                    'created_at' => $now,
                    'updated_at' => $now,
                ]
            );
        }
    }

    public function down(): void
    {
        Schema::table('supply_issue_requests', function (Blueprint $table) {
            $table->dropConstrainedForeignId('supply_client_id');
        });

        Schema::table('supply_requests', function (Blueprint $table) {
            $table->dropConstrainedForeignId('supply_client_id');
        });

        Schema::dropIfExists('supply_clients');
    }
};
