<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class SupplyRequestItem extends Model
{
    use HasFactory;

    protected $fillable = [
        'supply_request_id',
        'supply_product_id',
        'requested_quantity',
        'received_quantity',
        'missing_quantity',
        'observation',
    ];

    public function supplyRequest()
    {
        return $this->belongsTo(SupplyRequest::class, 'supply_request_id');
    }

    public function product()
    {
        return $this->belongsTo(SupplyProduct::class, 'supply_product_id');
    }
}
