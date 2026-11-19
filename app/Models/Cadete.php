<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Carbon\CarbonInterface;
class Cadete extends Model
{
    use HasFactory;

    protected $fillable = [
        'persona_id',
        'promocion_id',
        'grado_id',
        'fecha_ingreso',
        'estado',
    ];

    protected function casts(): array
    {
        return [
            'fecha_ingreso' => 'date',
        ];
    }

    public function persona(): BelongsTo
    {
        return $this->belongsTo(Persona::class);
    }

    public function grado(): BelongsTo
    {
        return $this->belongsTo(Grado::class);
    }

    public function promocion(): BelongsTo
    {
        return $this->belongsTo(Promocion::class);
    }

    public function asignacionesSemestre(): HasMany
    {
        return $this->hasMany(AsignacionSemestre::class);
    }

    public function asignacionActual(): HasOne
    {
        return $this->hasOne(AsignacionSemestre::class)
            ->whereHas('periodoAcademico', function ($query) {
                $query->where('activo', true);
            });
    }

    public function asignacionesPeloton(): HasMany
    {
        return $this->hasMany(CadetePeloton::class);
    }


    public function pelotonEnFecha(
        CarbonInterface $fecha
    ): ?CadetePeloton {
        $dia = $fecha->toDateString();

        return $this->asignacionesPeloton()
            ->whereDate('fecha_inicio', '<=', $dia)
            ->where(function ($query) use ($dia) {
                $query
                    ->whereNull('fecha_fin')
                    ->orWhereDate('fecha_fin', '>=', $dia);
            })
            ->with('peloton')
            ->first();
    }

}
