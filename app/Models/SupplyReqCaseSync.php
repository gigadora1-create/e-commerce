<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class SupplyReqCaseSync extends Model
{
    use HasFactory;

    public const STATUS_PENDING = 'pending';
    public const STATUS_SYNCED = 'synced';
    public const STATUS_FAILED = 'failed';
    public const STATUS_NOT_CONFIGURED = 'not_configured';

    protected $fillable = [
        'supply_request_id',
        'status',
        'external_case_id',
        'attempts',
        'last_attempt_at',
        'synced_at',
        'request_payload',
        'response_payload',
        'last_error',
    ];

    protected $casts = [
        'last_attempt_at' => 'datetime',
        'synced_at' => 'datetime',
        'request_payload' => 'array',
        'response_payload' => 'array',
    ];

    public function supplyRequest()
    {
        return $this->belongsTo(SupplyRequest::class, 'supply_request_id');
    }
}
