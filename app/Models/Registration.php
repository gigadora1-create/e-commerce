<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Registration extends Model
{
    // Indica el nombre de la tabla asociada al modelo
    protected $table = 'maintenances';

    // Indica los atributos que se pueden asignar masivamente
    protected $fillable = [
// Lista los nombres de los campos de la tabla que quieres usar en la vista receipt
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