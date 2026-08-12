<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use App\Helpers\ItemLocationStockHelper;

class InventoryDebug extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'inventory:debug {locations}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Debug stock for specific locations (comma separated)';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $locations = explode(',', $this->argument('locations'));

        foreach ($locations as $loc) {
            $this->info("LOCATION: $loc");
            $itemIds = DB::table('inventories')->where('localizacion', $loc)->pluck('item_id')->unique();
            
            if ($itemIds->isEmpty()) {
                $this->warn("  No items found.");
                continue;
            }

            foreach ($itemIds as $id) {
                $sku = DB::table('items')->where('item_id', $id)->value('sku');
                $stock = ItemLocationStockHelper::calculateCurrentStock($id, $loc);
                
                $this->line("  Item: $sku (ID: $id)");
                $this->line("    Physical: " . $stock['physical_stock']);
                $this->line("    Available: " . $stock['available']);
                $this->line("    Retained: " . $stock['retained']);
                $this->line("    Reserved: " . $stock['reserved']);
                
                if (isset($stock['debug'])) {
                     $this->line("    Debug: Entries=" . $stock['debug']['entries'] . 
                                ", Outputs=" . $stock['debug']['outputs'] . 
                                ", Returns=" . $stock['debug']['returns']);
                }
                $this->line("--------------------------------");
            }
        }
    }
}
