<?php

namespace App\Models;
use Illuminate\Database\Eloquent\Model;
class InventoryDetailed extends Model
{
    protected $table = 'vw_inventory_detailed';
    public $timestamps = false;

    public function itemLocations()
    {
        return $this->hasMany(ItemLocation::class, 'inventory_id');
    }
}
