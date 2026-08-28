<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class SupplyIssueRequest extends Model
{
    use HasFactory;

    public const STATUS_PREPARING = 'preparing';
    public const STATUS_READY = 'ready';
    public const STATUS_PENDING_SUPPORT = 'pending_support';
    public const STATUS_REJECTED = 'rejected';
    public const STATUS_CLOSED = 'closed';

    protected $fillable = [
        'request_number',
        'requested_by_user_id',
        'supply_client_id',
        'prepared_by_user_id',
        'closed_by_user_id',
        'status',
        'request_notes',
        'admin_notes',
        'requested_at',
        'ready_at',
        'closed_at',
    ];

    protected $casts = [
        'requested_at' => 'datetime',
        'ready_at' => 'datetime',
        'closed_at' => 'datetime',
    ];

    public function items()
    {
        return $this->hasMany(SupplyIssueRequestItem::class);
    }

    public function requestedBy()
    {
        return $this->belongsTo(User::class, 'requested_by_user_id');
    }

    public function client()
    {
        return $this->belongsTo(SupplyClient::class, 'supply_client_id');
    }

    public function preparedBy()
    {
        return $this->belongsTo(User::class, 'prepared_by_user_id');
    }

    public function closedBy()
    {
        return $this->belongsTo(User::class, 'closed_by_user_id');
    }

    public function getStatusLabelAttribute(): string
    {
        return match ($this->status) {
            self::STATUS_READY => 'Listo para recoger',
            self::STATUS_PENDING_SUPPORT => 'Cierre pendiente soporte',
            self::STATUS_REJECTED => 'Rechazada',
            self::STATUS_CLOSED => 'Cerrado',
            default => 'En alistamiento',
        };
    }

    public function getStatusColorAttribute(): string
    {
        return match ($this->status) {
            self::STATUS_READY => 'info',
            self::STATUS_PENDING_SUPPORT => 'primary',
            self::STATUS_REJECTED => 'danger',
            self::STATUS_CLOSED => 'success',
            default => 'warning',
        };
    }
}
