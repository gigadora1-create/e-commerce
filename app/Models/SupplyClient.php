<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class SupplyClient extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'address',
        'city',
        'contact_name',
        'contact_phone',
        'notes',
        'is_active',
    ];

    protected $casts = [
        'is_active' => 'boolean',
    ];

    public function supplyRequests()
    {
        return $this->hasMany(SupplyRequest::class);
    }

    public function issueRequests()
    {
        return $this->hasMany(SupplyIssueRequest::class);
    }

    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }
}
