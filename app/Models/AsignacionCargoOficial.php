<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class AsignacionCargoOficial extends Model
{
    public const TIPO_TITULAR = 'titular';
    public const TIPO_TEMPORAL = 'temporal';

    protected $table = 'asignaciones_cargo_oficial';

    protected $fillable = [
        'oficial_id',
        'cargo_id',
        'tipo_asignacion',
        'fecha_inicio',
        'fecha_fin',
        'observaciones',
    ];

    protected function casts(): array
    {
        return [
            'fecha_inicio' => 'date',
            'fecha_fin' => 'date',
        ];
    }

    public function oficial(): BelongsTo
    {
        return $this->belongsTo(Oficial::class);
    }

    public function cargo(): BelongsTo
    {
        return $this->belongsTo(Cargo::class);
    }
}
