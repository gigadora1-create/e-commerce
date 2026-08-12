<?php


namespace App\Models;
use Illuminate\Database\Eloquent\Model;
class InventoryRetention extends Model
{
    protected $table = 'vw_inventory_retentions';
    public $timestamps = false;

    public function itemLocations()
    {
        return $this->hasMany(ItemLocation::class, 'inventory_id');
    }
}
