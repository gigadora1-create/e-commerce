<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class WarehouseGuideMovement extends Model
{
    use HasFactory;

    protected $table = 'warehouse_guide_movements';

    public $timestamps = false;

    protected $fillable = [
        'warehouse_guide_id',
        'action',
        'national_guide',
        'from_location_id',
        'from_location_code',
        'from_location_name',
        'to_location_id',
        'to_location_code',
        'to_location_name',
        'performed_by',
        'performed_at',
        'notes',
    ];

    protected $casts = [
        'performed_at' => 'datetime',
    ];

    protected $appends = [
        'action_label',
        'action_badge_class',
        'from_location_label',
        'to_location_label',
    ];

    public function guide()
    {
        return $this->belongsTo(WarehouseGuide::class, 'warehouse_guide_id');
    }

    public function fromLocation()
    {
        return $this->belongsTo(WarehouseLocation::class, 'from_location_id', 'location_id');
    }

    public function toLocation()
    {
        return $this->belongsTo(WarehouseLocation::class, 'to_location_id', 'location_id');
    }

    public function user()
    {
        return $this->belongsTo(User::class, 'performed_by');
    }

    public function getActionLabelAttribute(): string
    {
        return match ($this->action) {
            'ENTRY' => 'Ingreso',
            'MOVE' => 'Movimiento',
            'EXIT' => 'Salida',
            default => strtoupper((string) $this->action),
        };
    }

    public function getActionBadgeClassAttribute(): string
    {
        return match ($this->action) {
            'ENTRY' => 'bg-primary',
            'MOVE' => 'bg-info text-dark',
            'EXIT' => 'bg-danger',
            default => 'bg-secondary',
        };
    }

    public function getFromLocationLabelAttribute(): string
    {
        if ($this->fromLocation) {
            return trim(($this->fromLocation->code ?? 'N/A') . ' - ' . ($this->fromLocation->name ?? 'Sin nombre'));
        }

        if ($this->from_location_code) {
            return trim($this->from_location_code . ' - ' . ($this->from_location_name ?? 'Sin nombre'));
        }

        if ($this->action === 'ENTRY') {
            return 'Sin origen internacional';
        }

        return 'Sin origen';
    }

    public function getToLocationLabelAttribute(): string
    {
        if ($this->toLocation) {
            return trim(($this->toLocation->code ?? 'N/A') . ' - ' . ($this->toLocation->name ?? 'Sin nombre'));
        }

        if ($this->to_location_code) {
            return trim($this->to_location_code . ' - ' . ($this->to_location_name ?? 'Sin nombre'));
        }

        if ($this->action === 'EXIT') {
            return $this->national_guide
                ? 'Transportadora - ' . $this->national_guide
                : 'Transportadora';
        }

        return 'Sin destino';
    }
}
