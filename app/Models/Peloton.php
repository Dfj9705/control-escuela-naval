<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Peloton extends Model
{
    protected $table = 'pelotones';

    protected $fillable = [
        'nombre',
        'activo',
    ];

    protected $casts = [
        'activo' => 'boolean',
    ];
}
