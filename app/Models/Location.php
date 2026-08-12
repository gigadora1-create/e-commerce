<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Location extends Model
{
    use HasFactory;

    protected $primaryKey = 'location_id';

    protected $fillable = [
        'code',
        'customer',
        'name',
        'warehouse',
        'description',
        'is_active',
        'is_storage',
    ];

    protected $casts = [
        'is_active' => 'boolean',
        'is_storage' => 'boolean',
    ];

    public function inventories()
    {
        return $this->hasMany(Inventory::class, 'location_id', 'location_id');
    }


    // Scope para ubicaciones activas
    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    // Scope para filtrar por código
    public function scopeByCode($query, $code)
    {
        return $query->where('code', $code);
    }
        public function items()
    {
        return $this->belongsToMany(Item::class, 'item_locations', 'location_id', 'item_id')
                    ->withPivot('inventory_id', 'quantity', 'assigned_at')
                    ->withTimestamps();
    }

    // Relación con ciudad
    public function city()
    {
        return $this->belongsTo(City::class, 'city_id');
    }

    // Relación con item_locations
    public function itemLocations()
    {
        return $this->hasMany(ItemLocation::class, 'location_id');
    }

    public function warehouseGuides()
    {
        return $this->hasMany(WarehouseGuide::class, 'current_location_id', 'location_id');
    }
    
}
