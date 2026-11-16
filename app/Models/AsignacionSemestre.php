<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class AsignacionSemestre extends Model
{
    protected $table = 'asignaciones_semestre';

    protected $fillable = [
        'cadete_id',
        'periodo_academico_id',
        'semestre_id',
    ];

    public function cadete(): BelongsTo
    {
        return $this->belongsTo(Cadete::class);
    }

    public function periodoAcademico(): BelongsTo
    {
        return $this->belongsTo(PeriodoAcademico::class);
    }

    public function semestre(): BelongsTo
    {
        return $this->belongsTo(Semestre::class);
    }
}
