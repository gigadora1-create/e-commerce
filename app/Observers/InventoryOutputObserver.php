<?php

namespace App\Observers;

use App\Models\InventoryOutput;
use App\Helpers\ItemLocationStockHelper;

class InventoryOutputObserver
{
    public function saved(InventoryOutput $output)
    {
        if ($output->item_id && $output->location_id) {
            ItemLocationStockHelper::sync($output->item_id, $output->location_id);
        }
    }

    public function deleted(InventoryOutput $output)
    {
        if ($output->item_id && $output->location_id) {
            ItemLocationStockHelper::sync($output->item_id, $output->location_id);
        }
    }
}
