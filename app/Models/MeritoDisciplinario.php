<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class MeritoDisciplinario extends Model
{
    public const ESTADO_VIGENTE = 'vigente';
    public const ESTADO_ANULADO = 'anulado';

    protected $table = 'meritos_disciplinarios';

    protected $fillable = [
        'cadete_id',
        'fecha_merito',
        'cantidad',
        'motivo',
        'responsable_id',
        'registrado_por',
        'estado',
        'anulado_por',
        'anulado_at',
        'motivo_anulacion',
    ];

    protected function casts(): array
    {
        return [
            'fecha_merito' => 'date',
            'cantidad' => 'integer',
            'anulado_at' => 'datetime',
        ];
    }

    public function cadete(): BelongsTo
    {
        return $this->belongsTo(Cadete::class);
    }

    public function responsable(): BelongsTo
    {
        return $this->belongsTo(Persona::class, 'responsable_id');
    }

    public function registradoPor(): BelongsTo
    {
        return $this->belongsTo(User::class, 'registrado_por');
    }

    public function anuladoPor(): BelongsTo
    {
        return $this->belongsTo(User::class, 'anulado_por');
    }

    public function estaVigente(): bool
    {
        return $this->estado === self::ESTADO_VIGENTE;
    }
}
