<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Customer extends Model
{
    use HasFactory;

    protected $table = 'customers';
    protected $primaryKey = 'customer_id';
    public $incrementing = true;
    protected $fillable = ['name', 'email', 'phone', 'address', 'is_warehouse_client'];

    protected $casts = [
        'is_warehouse_client' => 'boolean',
    ];

    public function inventories()
    {
        return $this->hasMany(Inventory::class, 'customer_id', 'customer_id');
    }

    public function authorizedUsers()
    {
        return $this->belongsToMany(
            User::class,
            'customer_user_accesses',
            'customer_id',
            'user_id',
            'customer_id',
            'id'
        )->withTimestamps();
    }
}
