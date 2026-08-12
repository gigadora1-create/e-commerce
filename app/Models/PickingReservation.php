<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class PickingReservation extends Model
{
    protected $fillable = ['picking_order_id', 'inventory_id', 'quantity_reserved'];

    public function pickingOrder()
    {
        return $this->belongsTo(PickingOrder::class);
    }

    public function inventory()
    {
        return $this->belongsTo(Inventory::class);
    }
}