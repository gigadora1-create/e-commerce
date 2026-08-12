<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class SupplyStockMovement extends Model
{
    use HasFactory;

    protected $fillable = [
        'supply_product_id',
        'user_id',
        'movement_type',
        'quantity',
        'stock_on_hand_after',
        'reserved_stock_after',
        'reference_type',
        'reference_id',
        'notes',
    ];

    public function product()
    {
        return $this->belongsTo(SupplyProduct::class, 'supply_product_id');
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }
}
