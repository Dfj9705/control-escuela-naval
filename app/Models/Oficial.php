<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Oficial extends Model
{
    use HasFactory;

    protected $table = 'oficiales';
    protected $fillable = [
        'persona_id',
        'grado_id',
        'estado',
    ];

    public function persona(): BelongsTo
    {
        return $this->belongsTo(Persona::class);
    }

    public function grado(): BelongsTo
    {
        return $this->belongsTo(Grado::class);
    }


    public function asignacionesCargo(): HasMany
    {
        return $this->hasMany(AsignacionCargoOficial::class);
    }

    public function asignacionesTitulares(): HasMany
    {
        return $this->asignacionesCargo()
            ->where(
                'tipo_asignacion',
                AsignacionCargoOficial::TIPO_TITULAR
            );
    }

    public function asignacionesTemporales(): HasMany
    {
        return $this->asignacionesCargo()
            ->where(
                'tipo_asignacion',
                AsignacionCargoOficial::TIPO_TEMPORAL
            );
    }

}
