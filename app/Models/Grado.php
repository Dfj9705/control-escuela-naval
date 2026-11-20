<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Grado extends Model
{
    protected $fillable = [
        'nombre',
        'abreviatura',
        'activo',
    ];

    protected function casts(): array
    {
        return [
            'activo' => 'boolean',
        ];
    }

    public function cadetes(): HasMany
    {
        return $this->hasMany(Cadete::class);
    }

    public function oficiales(): HasMany
    {
        return $this->hasMany(Oficial::class);
    }
}
