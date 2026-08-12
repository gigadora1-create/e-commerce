<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;
use Spatie\Permission\Traits\HasRoles;
use App\Notifications\ResetPasswordNotification;
use App\Models\Customer;


class User extends Authenticatable
{
    use HasApiTokens, HasFactory, Notifiable,HasRoles;

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'name',
        'email',
        'address',
        'telephone',
        'password',
        'user_type',
        
        
    ];
    public function sendPasswordResetNotification($token)
    {
        $this->notify(new ResetPasswordNotification($token));
    }
    /**
     * The attributes that should be hidden for serialization.
     *
     * @var array<int, string>
     */
    protected $hidden = [
        'password',
        'remember_token',
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'email_verified_at' => 'datetime',
        'password' => 'hashed',
    ];

    public function isWarehouseOnly(): bool
    {
        return $this->hasRole('BODEGA') && $this->getRoleNames()->count() === 1;
    }

    public function isSuperAdmin(): bool
    {
        return $this->hasAnyRole(['SUPERADMIN', 'SUPER_ADMIN']);
    }

    public function isSupplyAdmin(): bool
    {
        return $this->hasRole('PROVEEDURIA_ADMIN') || $this->can('supplies.admin');
    }

    public function isSupplyRequesterOnly(): bool
    {
        return $this->hasRole('PROVEEDURIA_USUARIO')
            && !$this->isSupplyAdmin()
            && !$this->isSuperAdmin()
            && $this->getRoleNames()->count() === 1;
    }

    public function customerAccesses()
    {
        return $this->belongsToMany(
            Customer::class,
            'customer_user_accesses',
            'user_id',
            'customer_id',
            'id',
            'customer_id'
        )->withTimestamps();
    }
    
}
