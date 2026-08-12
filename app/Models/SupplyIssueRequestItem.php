<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class SupplyIssueRequestItem extends Model
{
    use HasFactory;

    protected $fillable = [
        'supply_issue_request_id',
        'supply_product_id',
        'requested_quantity',
        'reserved_quantity',
        'delivered_quantity',
        'available_quantity_at_request',
    ];

    public function issueRequest()
    {
        return $this->belongsTo(SupplyIssueRequest::class, 'supply_issue_request_id');
    }

    public function product()
    {
        return $this->belongsTo(SupplyProduct::class, 'supply_product_id');
    }
}
