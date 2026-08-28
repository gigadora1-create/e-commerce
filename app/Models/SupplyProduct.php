<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class SupplyProduct extends Model
{
    use HasFactory;

    protected $fillable = [
        'catalog_number',
        'name',
        'description',
        'stock_on_hand',
        'reserved_stock',
        'minimum_stock',
        'medium_stock',
        'is_active',
    ];

    protected $casts = [
        'is_active' => 'boolean',
        'minimum_stock' => 'integer',
        'medium_stock' => 'integer',
    ];

    public function requestItems()
    {
        return $this->hasMany(SupplyRequestItem::class);
    }

    public function issueRequestItems()
    {
        return $this->hasMany(SupplyIssueRequestItem::class);
    }

    public function stockMovements()
    {
        return $this->hasMany(SupplyStockMovement::class);
    }

    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    public function getAvailableStockAttribute(): int
    {
        return max(((int) $this->stock_on_hand) - ((int) $this->reserved_stock), 0);
    }
}
