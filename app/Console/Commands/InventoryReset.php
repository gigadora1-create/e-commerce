<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use App\Models\ItemLocation;

class InventoryReset extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'inventory:reset';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Truncate all inventory related tables to reset stock to zero';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        if (!$this->confirm('This will DELETE ALL INVENTORY DATA. Are you sure?')) {
            return;
        }

        DB::statement('SET FOREIGN_KEY_CHECKS=0;');

        $tables = [
            'inventories',
            'inventory_outputs',
            'inventory_movements',
            'picking_reservations'
        ];

        foreach ($tables as $table) {
            DB::table($table)->truncate();
            $this->info("Truncated {$table}");
        }

        // Reset item_locations quantity
        DB::table('item_locations')->update(['current_quantity' => 0]);
        $this->info("Reset item_locations quantity to 0");

        DB::statement('SET FOREIGN_KEY_CHECKS=1;');

        $this->info('Inventory reset complete.');
    }
}
