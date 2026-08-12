<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\ItemLocation;
use App\Helpers\ItemLocationStockHelper;

class InventoryReconcileCommand extends Command
{
    /**
     * The name and signature of the console command.
     */
    protected $signature = 'inventory:reconcile {--sku= : Solo reconciliar un SKU específico}';

    /**
     * The console command description.
     */
    protected $description = 'Recalcula y sincroniza el stock de item_locations basándose en inventories y outputs.';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $sku = $this->option('sku');
        $corrections = [];
        
        $this->info("Iniciando reconciliación de stock...");

        if ($sku) {
            $item = \App\Models\Item::where('sku', $sku)->first();
            if (!$item) {
                $this->error("SKU $sku no encontrado.");
                return;
            }
            $itemLocations = ItemLocation::where('item_id', $item->item_id)->get();
        } else {
            $itemLocations = ItemLocation::all();
        }

        $bar = $this->output->createProgressBar(count($itemLocations));
        $bar->start();

        foreach ($itemLocations as $il) {
            $result = ItemLocationStockHelper::sync($il->item_id, $il->location_id);
            if ($result) {
                $corrections[] = $result;
            }
            $bar->advance();
        }

        $bar->finish();
        $this->newLine();
        
        if (count($corrections) > 0) {
            $this->info("Se realizaron " . count($corrections) . " correcciones.");
            
            // Output data as JSON at the very end so the controller can parse it
            $this->line("REPORT_START");
            $this->line(json_encode($corrections));
            $this->line("REPORT_END");
        } else {
            $this->info("No se encontraron discrepancias. El inventario está balanceado.");
        }

        $this->info("Reconciliación completada exitosamente.");
    }
}
