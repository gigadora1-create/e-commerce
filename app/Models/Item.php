<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Item extends Model
{
    use HasFactory;

    protected $primaryKey = 'item_id';

    protected $fillable = [
        'name', 'description', 'sku', 'barcode', 'ruta'
    ];

    // Relación existente con inventarios (mantenida)
    public function inventories()
    {
        return $this->hasMany(Inventory::class, 'item_id');
    }

    // Relación con ubicaciones a través de item_locations (actualizada con inventory_id)
    public function locations()
    {
        return $this->belongsToMany(Location::class, 'item_locations', 'item_id', 'location_id')
                    ->withPivot('inventory_id', 'current_quantity', 'max_capacity', 'assigned_at')
                    ->withTimestamps();
    }

    // Obtener ubicaciones por inventario específico
    public function locationsByInventory($inventoryId)
    {
        return $this->belongsToMany(Location::class, 'item_locations', 'item_id', 'location_id')
                    ->wherePivot('inventory_id', $inventoryId)
                    ->withPivot('inventory_id', 'current_quantity', 'max_capacity', 'assigned_at')
                    ->withTimestamps();
    }

    // Obtener la ubicación principal del producto (actualizada)
    public function primaryLocation($inventoryId = null)
    {
        $query = $this->belongsToMany(Location::class, 'item_locations', 'item_id', 'location_id')
                      ->withPivot('inventory_id', 'current_quantity', 'max_capacity', 'assigned_at')
                      ->withTimestamps()
                      ->orderByPivot('assigned_at', 'desc');
        
        if ($inventoryId) {
            $query->wherePivot('inventory_id', $inventoryId);
        }
        
        return $query->limit(1);
    }

    // Obtener todas las asignaciones de ubicación (mantenida)
    public function itemLocations()
    {
        return $this->hasMany(ItemLocation::class, 'item_id');
    }

    // Obtener asignaciones por inventario específico
    public function itemLocationsByInventory($inventoryId)
    {
        return $this->hasMany(ItemLocation::class, 'item_id')
                    ->where('inventory_id', $inventoryId);
    }

    // Obtener la URL de la imagen (mantenida)
public function getImageUrlAttribute()
{
    return $this->ruta ? asset('images/' . $this->ruta) : asset('images/no-image.png');
}


    // Obtener la cantidad total en todas las ubicaciones (actualizada)
    public function getTotalInLocations($inventoryId = null)
    {
        $query = $this->itemLocations();
        
        if ($inventoryId) {
            $query->where('inventory_id', $inventoryId);
        }
        
        return $query->sum('current_quantity');
    }

    // Verificar si el producto está asignado a alguna ubicación (actualizada)
    public function hasLocations($inventoryId = null)
    {
        if ($inventoryId) {
            return $this->locationsByInventory($inventoryId)->count() > 0;
        }
        
        return $this->locations()->count() > 0;
    }

    // Obtener la ubicación actual del producto en un inventario específico
    public function getCurrentLocation($inventoryId)
    {
        return $this->locationsByInventory($inventoryId)->first();
    }

    // Verificar si el producto está asignado en un inventario específico
    public function isAssignedInInventory($inventoryId)
    {
        return $this->locationsByInventory($inventoryId)->exists();
    }

    // Obtener el historial de ubicaciones del producto
    public function getLocationHistory($inventoryId = null)
    {
        $query = $this->itemLocations()
                      ->with('location')
                      ->orderBy('assigned_at', 'desc');
        
        if ($inventoryId) {
            $query->where('inventory_id', $inventoryId);
        }
        
        return $query->get();
    }

    // Scope para productos asignados
    public function scopeAssigned($query, $inventoryId = null)
    {
        if ($inventoryId) {
            return $query->whereHas('itemLocations', function ($q) use ($inventoryId) {
                $q->where('inventory_id', $inventoryId);
            });
        }
        
        return $query->whereHas('locations');
    }

    // Scope para productos sin asignar
    public function scopeUnassigned($query, $inventoryId = null)
    {
        if ($inventoryId) {
            return $query->whereDoesntHave('itemLocations', function ($q) use ($inventoryId) {
                $q->where('inventory_id', $inventoryId);
            });
        }
        
        return $query->whereDoesntHave('locations');
    }

    // Obtener todas las ubicaciones donde está el producto con información de inventario
    public function getAllLocationAssignments()
    {
        return $this->itemLocations()
                    ->with(['location', 'inventory'])
                    ->orderBy('assigned_at', 'desc')
                    ->get();
    }

    // Verificar disponibilidad del producto en un inventario
    public function isAvailableInInventory($inventoryId)
    {
        $inventory = $this->inventories()->where('inventory_id', $inventoryId)->first();
        if (!$inventory) {
            return false;
        }
        
        $assignedQuantity = $this->getTotalInLocations($inventoryId);
        return $inventory->stock > $assignedQuantity;
    }
}
