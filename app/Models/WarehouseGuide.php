<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class WarehouseGuide extends Model
{
    use HasFactory;

    public const STATUS_ACTIVE = 'ACTIVE';
    public const STATUS_EXITED = 'EXITED';

    protected $table = 'warehouse_guides';

    protected $fillable = [
        'guide',
        'national_guide',
        'customer',
        'warehouse',
        'status',
        'entry_at',
        'exit_at',
        'entry_source',
        'entry_user_id',
        'exit_user_id',
        'current_location_id',
        'current_location_code',
        'current_location_name',
        'notes',
    ];

    protected $casts = [
        'entry_at' => 'datetime',
        'exit_at' => 'datetime',
    ];

    protected $appends = [
        'status_label',
        'status_badge_class',
        'current_location_label',
        'duration_label',
        'is_active',
    ];

    public function currentLocation()
    {
        return $this->belongsTo(WarehouseLocation::class, 'current_location_id', 'location_id');
    }

    public function entryUser()
    {
        return $this->belongsTo(User::class, 'entry_user_id');
    }

    public function exitUser()
    {
        return $this->belongsTo(User::class, 'exit_user_id');
    }

    public function movements()
    {
        return $this->hasMany(WarehouseGuideMovement::class, 'warehouse_guide_id')
            ->orderBy('performed_at');
    }

    public function scopeForCustomer($query, string $customer)
    {
        return $query->where('customer', $customer);
    }

    public function scopeActive($query)
    {
        return $query->whereNull('exit_at');
    }

    public function scopeExited($query)
    {
        return $query->whereNotNull('exit_at');
    }

    public function getIsActiveAttribute(): bool
    {
        return is_null($this->exit_at);
    }

    public function getStatusLabelAttribute(): string
    {
        if ($this->status === self::STATUS_EXITED || $this->exit_at) {
            return 'Salida registrada';
        }

        if (($this->current_location_code ?? null) === 'ALMACENAMIENTO') {
            return 'En almacenamiento';
        }

        return 'Ubicada';
    }

    public function getStatusBadgeClassAttribute(): string
    {
        if ($this->status === self::STATUS_EXITED || $this->exit_at) {
            return 'bg-secondary';
        }

        if (($this->current_location_code ?? null) === 'ALMACENAMIENTO') {
            return 'bg-warning text-dark';
        }

        return 'bg-success';
    }

    public function getCurrentLocationLabelAttribute(): string
    {
        if ($this->currentLocation) {
            return trim(($this->currentLocation->code ?? 'N/A') . ' - ' . ($this->currentLocation->name ?? 'Sin nombre'));
        }

        if ($this->current_location_code) {
            return trim($this->current_location_code . ' - ' . ($this->current_location_name ?? 'Sin nombre'));
        }

        return 'Sin ubicacion';
    }

    public function getDurationMinutesAttribute(): int
    {
        if (!$this->entry_at) {
            return 0;
        }

        $end = $this->exit_at ?: now();

        return (int) $this->entry_at->diffInMinutes($end);
    }

    public function getDurationLabelAttribute(): string
    {
        $minutes = max(0, (int) $this->duration_minutes);
        $days = intdiv($minutes, 1440);
        $hours = intdiv($minutes % 1440, 60);
        $mins = $minutes % 60;

        $parts = [];

        if ($days > 0) {
            $parts[] = $days . 'd';
        }

        if ($hours > 0 || $days > 0) {
            $parts[] = str_pad((string) $hours, 2, '0', STR_PAD_LEFT) . 'h';
        }

        $parts[] = str_pad((string) $mins, 2, '0', STR_PAD_LEFT) . 'm';

        return implode(' ', $parts);
    }
}
