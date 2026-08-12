<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class PickingDetail extends Model
{
    protected $fillable = [
        'picking_order_id', 'inventory_id', 'sku', 'item_description', 'location_code', 'location_name',
        'warehouse', 'order_number','customer', 'batch', 'expiry_date', 'quantity_requested', 'quantity_picked'
    ];

    public function pickingOrder()
    {
        return $this->belongsTo(PickingOrder::class);
    }

    public function inventory()
    {
        return $this->belongsTo(Inventory::class);
    }
}