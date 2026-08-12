<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class WarehouseLocation extends Model
{
    use HasFactory;

    protected $table = 'warehouse_locations';

    protected $primaryKey = 'location_id';

    protected $fillable = [
        'code',
        'customer',
        'name',
        'warehouse',
        'description',
        'is_active',
        'is_storage',
    ];

    protected $casts = [
        'is_active' => 'boolean',
        'is_storage' => 'boolean',
    ];

    public function warehouseGuides()
    {
        return $this->hasMany(WarehouseGuide::class, 'current_location_id', 'location_id');
    }

    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    public function scopeByCode($query, string $code)
    {
        return $query->where('code', $code);
    }
}
