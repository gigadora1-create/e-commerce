<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Priority extends Model
{
    use HasFactory;

    // Si la tabla tiene un nombre diferente a 'priorities', agrega esta línea
    protected $table = 'priorities';

    protected $fillable = ['name'];

    // Agrega otros métodos o relaciones si es necesario
}
