<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class SupplyRequest extends Model
{
    use HasFactory;

    public const STATUS_REQUESTED = 'requested';
    public const STATUS_PARTIAL = 'audited_partial';
    public const STATUS_COMPLETE = 'audited_complete';

    protected $fillable = [
        'request_number',
        'requested_by_user_id',
        'audited_by_user_id',
        'supply_client_id',
        'status',
        'request_notes',
        'audit_notes',
        'requested_at',
        'audited_at',
        'received_by_name',
        'received_by_signature',
        'delivered_by_name',
        'delivered_by_signature',
    ];

    protected $casts = [
        'requested_at' => 'datetime',
        'audited_at' => 'datetime',
    ];

    public function items()
    {
        return $this->hasMany(SupplyRequestItem::class);
    }

    public function requestedBy()
    {
        return $this->belongsTo(User::class, 'requested_by_user_id');
    }

    public function auditedBy()
    {
        return $this->belongsTo(User::class, 'audited_by_user_id');
    }

    public function client()
    {
        return $this->belongsTo(SupplyClient::class, 'supply_client_id');
    }

    public function reqCaseSync()
    {
        return $this->hasOne(SupplyReqCaseSync::class, 'supply_request_id');
    }

    public function getStatusLabelAttribute(): string
    {
        return match ($this->status) {
            self::STATUS_COMPLETE => 'Recibido completo',
            self::STATUS_PARTIAL => 'Recibido con faltantes',
            default => 'Solicitud pendiente',
        };
    }

    public function getStatusColorAttribute(): string
    {
        return match ($this->status) {
            self::STATUS_COMPLETE => 'success',
            self::STATUS_PARTIAL => 'warning',
            default => 'secondary',
        };
    }

    public function getTotalRequestedAttribute(): int
    {
        return (int) $this->items->sum('requested_quantity');
    }

    public function getTotalReceivedAttribute(): int
    {
        return (int) $this->items->sum('received_quantity');
    }

    public function getTotalMissingAttribute(): int
    {
        return (int) $this->items->sum('missing_quantity');
    }
}
