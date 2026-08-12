<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class InventoryOutput extends Model
{
    use HasFactory;

    protected $primaryKey = 'id';
    public $incrementing = true;
    protected $keyType = 'int';
    protected $fillable = [
        'inventory_id',
        'item_id',
        'item_name',
        'localizacion',
        'batch',
        'expiry_date',   
        'guide',
        'quantity',
        'declared_value',
        'status',
        'location_id',
        'warehouse',
        'customer',
        'user_id',
        'created_at',
    ];

    protected $casts = [
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
        'declared_value' => 'decimal:2',
        'quantity' => 'integer',
    ];

    /**
     * Relación con el usuario que creó la salida
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function items()
    {
        return $this->hasMany(InventoryOutputItem::class, 'inventory_output_id');
    }

    /**
     * Relación con el inventario
     */
    public function inventory(): BelongsTo
    {
        return $this->belongsTo(Inventory::class, 'inventory_id', 'id');
    }

    /**
     * Relación con el item
     */
    public function item(): BelongsTo
    {
        return $this->belongsTo(Item::class, 'item_id', 'item_id'); // Cambiar a item_id como clave local y foránea
    }

    /**
     * Relación con la ubicación
     */
    public function location(): BelongsTo
    {
        return $this->belongsTo(Location::class, 'location_id', 'location_id'); // Nueva relación con Location
    }

    /**
     * Scope para filtrar por cliente
     */
    public function scopeForCustomer($query, $customer)
    {
        return $query->where('customer', $customer);
    }

    /**
     * Scope para filtrar por bodega
     */
    public function scopeForWarehouse($query, $warehouse)
    {
        return $query->where('warehouse', $warehouse);
    }

    /**
     * Scope para filtrar por rango de fechas
     */
    public function scopeDateRange($query, $startDate, $endDate)
    {
        return $query->whereBetween('created_at', [$startDate, $endDate]);
    }

    /**
     * Accessor para formatear el valor declarado
     */
    public function getFormattedDeclaredValueAttribute()
    {
        return number_format($this->declared_value, 2, ',', '.');
    }

    /**
     * Accessor para obtener el estado con formato
     */
    public function getStatusBadgeAttribute()
    {
        $badges = [
            'bueno' => '<span class="badge bg-success">Bueno</span>',
            'malo' => '<span class="badge bg-danger">Malo</span>',
        ];

        return $badges[strtolower($this->status)] ?? '<span class="badge bg-secondary">Desconocido</span>';
    }
}
