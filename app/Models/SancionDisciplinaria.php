<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class SancionDisciplinaria extends Model
{
    protected $table = 'sanciones_disciplinarias';

    // Estados del procedimiento
    public const ESTADO_REGISTRADA = 'registrada';
    public const ESTADO_PENDIENTE_FIRMA = 'pendiente_firma';
    public const ESTADO_PENDIENTE_CONFIRMACION = 'pendiente_confirmacion';
    public const ESTADO_CONFIRMADA = 'confirmada';
    public const ESTADO_ANULADA = 'anulada';

    // Tipos de sanción
    public const TIPO_HORAS = 'horas';
    public const TIPO_DEMERITOS = 'demeritos';
    public const TIPO_ESPECIAL = 'especial';

    protected $fillable = [
        'cadete_id',
        'falta_disciplinaria_id',
        'responsable_id',
        'fecha_falta',
        'descripcion_hechos',
    ];

    protected $casts = [
        'fecha_falta' => 'date',
        'cantidad_aplicada' => 'integer',
        'confirmado_at' => 'datetime',
        'anulado_at' => 'datetime',
    ];

    // Cadete sancionado
    public function cadete(): BelongsTo
    {
        return $this->belongsTo(Cadete::class);
    }

    // Falta del catálogo disciplinario
    public function faltaDisciplinaria(): BelongsTo
    {
        return $this->belongsTo(FaltaDisciplinaria::class);
    }

    // Persona que impuso la sanción
    public function responsable(): BelongsTo
    {
        return $this->belongsTo(Persona::class, 'responsable_id');
    }

    // Usuario que registró la sanción
    public function registradoPor(): BelongsTo
    {
        return $this->belongsTo(User::class, 'registrado_por');
    }

    // Usuario de Comandancia que confirmó
    public function confirmadoPor(): BelongsTo
    {
        return $this->belongsTo(User::class, 'confirmado_por');
    }

    // Usuario que anuló
    public function anuladoPor(): BelongsTo
    {
        return $this->belongsTo(User::class, 'anulado_por');
    }

    public function estaConfirmada(): bool
    {
        return $this->estado === self::ESTADO_CONFIRMADA;
    }

    public function puedeConfirmarse(): bool
    {
        return $this->estado === self::ESTADO_PENDIENTE_CONFIRMACION
            && !empty($this->boleta_firmada)
            && $this->tipo_sancion !== self::TIPO_ESPECIAL;
    }

    public function puedeAnularse(): bool
    {
        return $this->estado !== self::ESTADO_ANULADA;
    }
}
