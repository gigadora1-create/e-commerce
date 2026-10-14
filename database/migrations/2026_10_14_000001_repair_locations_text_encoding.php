<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        $correctDescription = 'Ubicaci' . chr(195) . chr(179)
            . 'n para productos en retenci' . chr(195) . chr(179) . 'n';

        DB::table('locations')
            ->where('code', 'PENDIENTES')
            ->where('customer', 'SKYONE')
            ->update(['description' => $correctDescription]);
    }

    public function down(): void
    {
        // Data repair: reverting would reintroduce corrupted text.
    }
};
