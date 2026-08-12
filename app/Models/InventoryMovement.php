<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class InventoryMovement extends Model
{
    protected $fillable = [
        'source_inventory_id',
        'destination_inventory_id',
        'sku',
        'quantity',
        'source_location',
        'destination_location',
        'warehouse',
        'customer',
        'movement_type',
        'notes',
        'user_id',
        'movement_date'
    ];

    protected $casts = [
        'quantity' => 'integer',
        'movement_date' => 'datetime'
    ];

    /**
     * Relación con inventario origen
     */
    public function sourceInventory(): BelongsTo
    {
        return $this->belongsTo(Inventory::class, 'source_inventory_id');
    }

    /**
     * Relación con inventario destino
     */
    public function destinationInventory(): BelongsTo
    {
        return $this->belongsTo(Inventory::class, 'destination_inventory_id');
    }

    /**
     * Usuario que realizó el movimiento
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}