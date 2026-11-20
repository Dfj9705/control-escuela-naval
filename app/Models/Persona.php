<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\HasOne;

class Persona extends Model
{
    use HasFactory;

    protected $fillable = [
        'catalogo',
        'primer_nombre',
        'segundo_nombre',
        'primer_apellido',
        'segundo_apellido',
        'foto',
        'etnia',
        'grupo_sanguineo',
        'tipo_documento',
        'numero_documento',
        'nit',
        'telefono',
        'direccion',
        'correo_electronico',
        'fecha_nacimiento',
    ];

    protected function casts(): array
    {
        return [
            'fecha_nacimiento' => 'date',
        ];
    }

    public function getNombreCompletoAttribute(): string
    {
        return collect([
            $this->primer_nombre,
            $this->segundo_nombre,
            $this->primer_apellido,
            $this->segundo_apellido,
        ])
            ->filter(fn($valor) => filled($valor))
            ->implode(' ');
    }

    public function usuario(): HasOne
    {
        return $this->hasOne(User::class);
    }

    public function cadete(): HasOne
    {
        return $this->hasOne(Cadete::class);
    }

    public function oficial(): HasOne
    {
        return $this->hasOne(Oficial::class);
    }
}
