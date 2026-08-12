<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class PickingOrder extends Model
{
    protected $fillable = ['picking_code', 'warehouse', 'order_number', 'customer', 'status', 'total_items', 'total_quantity', 'user_id', 'completed_at'];

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function details()
    {
        return $this->hasMany(PickingDetail::class);
    }

    public function reservations()
    {
        return $this->hasMany(PickingReservation::class);
    }

    public static function generatePickingCode()
    {
        $date = date('Ymd');
        $rand = strtoupper(substr(md5(microtime()), 0, 4));
        return "PICK-{$date}-{$rand}";
    }
}