<?php

namespace App\Observers;

use App\Models\Inventory;
use App\Helpers\ItemLocationStockHelper;

class InventoryObserver
{
    public function saved(Inventory $inventory)
    {
        if ($inventory->item_id && $inventory->location_id) {
            ItemLocationStockHelper::sync($inventory->item_id, $inventory->location_id);
        }
    }

    public function deleted(Inventory $inventory)
    {
        if ($inventory->item_id && $inventory->location_id) {
            ItemLocationStockHelper::sync($inventory->item_id, $inventory->location_id);
        }
    }
}
