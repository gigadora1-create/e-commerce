<?php
 
namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Statistic extends Model
{
    use HasFactory;

    protected $table = 'maintenances';

    protected $fillable = [
        'notification',
        'internal_code',
        'date_of_purchase',
        'month_of_coat',
        'date_of_coat',
        'responsible',
        'Post',
        'observations',
        'maintenance_number',
        'state',

    ];
}
