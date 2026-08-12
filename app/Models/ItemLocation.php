<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ItemLocation extends Model
{
    use HasFactory;

    protected $primaryKey = 'id';

    protected $fillable = [
        'item_id',
        'location_id',
        'location_code',
        'inventory_id',
        'quantity',
        'max_capacity',
        'current_quantity',
        'assigned_at',
    ];

    public function item()
    {
        return $this->belongsTo(Item::class, 'item_id');
    }

    public function location()
    {
        return $this->belongsTo(Location::class, 'location_id')->where('is_active', true);
    }

    public function inventory()
    {
        return $this->belongsTo(Inventory::class, 'inventory_id');
    }

    // Validar que location_id corresponda a un code válido
    public static function boot()
    {
        parent::boot();
        static::creating(function ($itemLocation) {
            $location = Location::find($itemLocation->location_id);
            if (!$location) {
                throw new \Exception('La ubicación no existe.');
            }
            $itemLocation->location_code = $location->code; // Almacenar code para referencia rápida
        });
        static::updating(function ($itemLocation) {
            $location = Location::find($itemLocation->location_id);
            if (!$location) {
                throw new \Exception('La ubicación no existe.');
            }
            $itemLocation->location_code = $location->code;
        });
    }
}