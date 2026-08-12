<?php
namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class InventoryUnified extends Model
{
    protected $table = 'vw_inventory_unified'; // Vista de BD
    public $timestamps = false; // porque es una vista
}
    