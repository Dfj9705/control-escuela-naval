<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Promocion extends Model
{
    protected $table = 'promociones';
    protected $fillable = [
        'numero',
        'anio_ingreso',
        'descripcion',
    ];

    public function cadetes(): HasMany
    {
        return $this->hasMany(Cadete::class);
    }
}
