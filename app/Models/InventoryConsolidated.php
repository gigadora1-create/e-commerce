<?php


namespace App\Models;
use Illuminate\Database\Eloquent\Model;
class InventoryConsolidated extends Model
{
    protected $table = 'vw_inventory_consolidated';
    public $timestamps = false;

    public function itemLocations()
    {
        return $this->hasMany(ItemLocation::class, 'inventory_id');
    }
}
