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

    public function sancionesDisciplinarias(): HasMany
    {
        return $this->hasMany(
            SancionDisciplinaria::class,
            'cadete_id'
        );
    }


    public function totalSancionesConfirmadas(): int
    {
        return $this->sancionesDisciplinarias()
            ->where(
                'estado',
                SancionDisciplinaria::ESTADO_CONFIRMADA
            )
            ->count();
    }

    public function totalSancionesPendientes(): int
    {
        return $this->sancionesDisciplinarias()
            ->whereIn('estado', [
                SancionDisciplinaria::ESTADO_REGISTRADA,
                SancionDisciplinaria::ESTADO_PENDIENTE_FIRMA,
                SancionDisciplinaria::ESTADO_PENDIENTE_CONFIRMACION,
            ])
            ->count();
    }

    public function totalSancionesAnuladas(): int
    {
        return $this->sancionesDisciplinarias()
            ->where(
                'estado',
                SancionDisciplinaria::ESTADO_ANULADA
            )
            ->count();
    }

    public function totalDemeritosConfirmados(): int
    {
        return (int) $this->sancionesDisciplinarias()
            ->where(
                'estado',
                SancionDisciplinaria::ESTADO_CONFIRMADA
            )
            ->where(
                'tipo_sancion',
                SancionDisciplinaria::TIPO_DEMERITOS
            )
            ->sum('cantidad_aplicada');
    }

    public function totalDemeritosEnPeriodo(
        PeriodoAcademico $periodo
    ): int {
        return (int) $this->sancionesDisciplinarias()
            ->where(
                'estado',
                SancionDisciplinaria::ESTADO_CONFIRMADA
            )
            ->where(
                'tipo_sancion',
                SancionDisciplinaria::TIPO_DEMERITOS
            )
            ->whereDate(
                'fecha_falta',
                '>=',
                $periodo->fecha_inicio->toDateString()
            )
            ->whereDate(
                'fecha_falta',
                '<=',
                $periodo->fecha_fin->toDateString()
            )
            ->sum('cantidad_aplicada');
    }



    public function demeritosPreviosASancion(
        SancionDisciplinaria $sancion,
        PeriodoAcademico $periodo
    ): int {
        if ($sancion->cadete_id !== $this->id) {
            throw new \InvalidArgumentException(
                'La sanción no corresponde al cadete.'
            );
        }

        return (int) $this->sancionesDisciplinarias()
            ->where(
                'estado',
                SancionDisciplinaria::ESTADO_CONFIRMADA
            )
            ->where(
                'tipo_sancion',
                SancionDisciplinaria::TIPO_DEMERITOS
            )
            ->where('id', '!=', $sancion->id)
            ->whereDate(
                'fecha_falta',
                '>=',
                $periodo->fecha_inicio->toDateString()
            )
            ->whereDate(
                'fecha_falta',
                '<',
                $sancion->fecha_falta->toDateString()
            )
            ->sum('cantidad_aplicada');
    }


    public function situacionDemeritosEnPeriodo(
        PeriodoAcademico $periodo
    ): ?array {
        // Buscar la asignación del cadete en el período.
        $asignacion = $this->asignacionesSemestre()
            ->where('periodo_academico_id', $periodo->id)
            ->with('semestre')
            ->first();

        // No asumir un semestre si no existe asignación.
        if (!$asignacion || !$asignacion->semestre) {
            return null;
        }

        $semestre = $asignacion->semestre;
        $limite = $semestre->limiteDemeritos();

        // No asumir un límite inexistente.
        if ($limite === null) {
            return null;
        }

        $acumulados = $this->totalDemeritosEnPeriodo($periodo);

        return [
            'periodo_academico_id' => $periodo->id,
            'semestre' => $semestre->nombre,
            'semestre_numero' => $semestre->numero,
            'limite' => $limite,
            'acumulados' => $acumulados,
            'restantes' => max(0, $limite - $acumulados),
            'excedidos' => max(0, $acumulados - $limite),
            'porcentaje' => round(
                ($acumulados / $limite) * 100,
                2
            ),
        ];
    }

    public function meritosDisciplinarios(): HasMany
    {
        return $this->hasMany(MeritoDisciplinario::class);
    }


}
