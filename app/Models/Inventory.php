<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;

class Inventory extends Model
{
    use HasFactory;

    protected $primaryKey = 'id';

    protected $fillable = [
        'id',
        'inventory_id',
        'item_id',
        'warehouse',
        'localizacion',
        'item_description',
        'sku',
        'status',
        'entry_document',
        'retention_substatus',
        'batch',
        'expiry_date',
        'item_condition',
        'entry_date',
        'commerce',
        'quantity',
        'location_id',
        'value',
        'type',
        'observations',
        'customer',
        'document_path',
        'city_id'
    ];

    protected $casts = [
        'quantity' => 'integer',
        'expiry_date' => 'date',
        'entry_date' => 'date',
        'value' => 'decimal:2'
    ];

    protected static function boot()
    {
        parent::boot();
    }

    public function location()
    {
        return $this->belongsTo(Location::class, 'location_id');
    }

    public function item()
    {
        return $this->belongsTo(Item::class, 'item_id', 'item_id');
    }

    public function itemLocations()
    {
        return $this->hasMany(ItemLocation::class, 'item_id', 'item_id');
    }

    public function outputs()
    {
        return $this->hasMany(InventoryOutput::class, 'inventory_id', 'inventory_id');
    }

    public function reservations()
    {
        return $this->hasMany(PickingReservation::class);
    }

    public function activeReservations()
    {
        return $this->hasMany(PickingReservation::class)
            ->whereHas('pickingOrder', function($q) {
                $q->whereIn('status', ['pending', 'in_progress']);
            });
    }

    public function decreaseQuantity($quantity)
    {
        $this->quantity -= $quantity;
        $this->save();
    }

    public function getTotalQuantityAttribute()
    {
        return $this->quantity;
    }

    public function getTotalOutputQuantityAttribute()
    {
        return $this->outputs()->sum('quantity');
    }

    public function getCurrentStockAttribute()
    {
        return $this->total_quantity - $this->total_output_quantity;
    }

    public static function getInventoryEntries()
    {
        return self::select(
            DB::raw('DATE(entry_date) as date'),
            DB::raw('SUM(quantity) as total_quantity'),
            DB::raw('COUNT(*) as total_entries')
        )
        ->groupBy('date')
        ->orderBy('date', 'desc')
        ->get();
    }

    public static function getWarehouses()
    {
        return self::select('warehouse', DB::raw('SUM(quantity) as total_quantity'))
            ->groupBy('warehouse')
            ->get();
    }

    public static function getProducts()
    {
        return self::select('item_description', DB::raw('SUM(quantity) as total_quantity'))
            ->groupBy('item_description')
            ->get();
    }

    public static function getProductsWithItems()
    {
        return self::join('items', 'inventories.item_id', '=', 'items.item_id')
            ->select(
                'inventories.item_description',
                'items.name as item_name',
                'items.sku',
                DB::raw('SUM(inventories.quantity) as total_quantity')
            )
            ->groupBy('inventories.item_id', 'inventories.item_description', 'items.name', 'items.sku')
            ->get();
    }

    public function scopeByItem($query, $itemId)
    {
        return $query->where('item_id', $itemId);
    }

    public function scopeWithItem($query)
    {
        return $query->with('item');
    }
}