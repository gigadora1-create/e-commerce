<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Receipt extends Model
{
    // Indica el nombre de la tabla asociada al modelo
    protected $table = 'products';

    // Indica los atributos que se pueden asignar masivamente
    protected $fillable = [
// Lista los nombres de los campos de la tabla que quieres usar en la vista receipt
        'Serial',
        'Internal_Code',
        'Equipment_Type',
        'Make_Model',
        'Storage',
        'Processor',
        'Ram',
        'License_Windows',
        'License_Office',
        'Date_Purchase',
        'Assigned_Process',
        'Post',
        'Responsible',
        'Corporate_Mail',
        'Corporate_Line',
        'Deliver_date',
        'Accessories',
        'Observations',
    ];

}