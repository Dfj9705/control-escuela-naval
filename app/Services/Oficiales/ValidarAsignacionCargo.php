<?php

namespace App\Services\Oficiales;

use App\Models\AsignacionCargoOficial;
use Carbon\Carbon;
use Illuminate\Validation\ValidationException;

class ValidarAsignacionCargo
{
    public function validar(
        int $oficialId,
        string $tipoAsignacion,
        string $fechaInicio,
        ?string $fechaFin = null,
        ?int $ignorarAsignacionId = null
    ): void {
        $tiposPermitidos = [
            AsignacionCargoOficial::TIPO_TITULAR,
            AsignacionCargoOficial::TIPO_TEMPORAL,
        ];

        if (!in_array($tipoAsignacion, $tiposPermitidos, true)) {
            throw ValidationException::withMessages([
                'tipo_asignacion' => 'El tipo de asignación no es válido.',
            ]);
        }

        try {
            $inicio = Carbon::createFromFormat('!Y-m-d', $fechaInicio);
            $fin = $fechaFin !== null
                ? Carbon::createFromFormat('!Y-m-d', $fechaFin)
                : null;

            if (
                $inicio->format('Y-m-d') !== $fechaInicio ||
                ($fin && $fin->format('Y-m-d') !== $fechaFin)
            ) {
                throw new \InvalidArgumentException();
            }
        } catch (\Throwable $e) {
            throw ValidationException::withMessages([
                'fecha_inicio' => 'Las fechas deben tener el formato AAAA-MM-DD y ser válidas.',
            ]);
        }

        if ($fin && $fin->lt($inicio)) {
            throw ValidationException::withMessages([
                'fecha_fin' => 'La fecha final no puede ser anterior a la fecha inicial.',
            ]);
        }

        if (
            $tipoAsignacion === AsignacionCargoOficial::TIPO_TEMPORAL
            && $fin === null
        ) {
            throw ValidationException::withMessages([
                'fecha_fin' => 'Las sustituciones temporales requieren una fecha de finalización.',
            ]);
        }

        $query = AsignacionCargoOficial::query()
            ->where('oficial_id', $oficialId)
            ->where('tipo_asignacion', $tipoAsignacion)
            ->where(function ($query) use ($fechaInicio, $fechaFin) {
                $query->where(function ($query) use ($fechaFin) {
                    if ($fechaFin !== null) {
                        $query->whereDate('fecha_inicio', '<=', $fechaFin);
                    }
                });

                // La condición completa de superposición se aplica abajo.
            });

        if ($ignorarAsignacionId !== null) {
            $query->whereKeyNot($ignorarAsignacionId);
        }

        // Dos períodos se superponen cuando:
        // existente.inicio <= nuevo.fin (o nuevo.fin es NULL)
        // Y existente.fin >= nuevo.inicio (o existente.fin es NULL).
        $query = AsignacionCargoOficial::query()
            ->where('oficial_id', $oficialId)
            ->where('tipo_asignacion', $tipoAsignacion)
            ->when(
                $ignorarAsignacionId !== null,
                fn($query) => $query->where('id', '!=', $ignorarAsignacionId)
            )
            ->when(
                $fechaFin !== null,
                fn($query) => $query->whereDate('fecha_inicio', '<=', $fechaFin)
            )
            ->where(function ($query) use ($fechaInicio) {
                $query->whereNull('fecha_fin')
                    ->orWhereDate('fecha_fin', '>=', $fechaInicio);
            });

        if ($query->exists()) {
            throw ValidationException::withMessages([
                'fecha_inicio' => $tipoAsignacion === AsignacionCargoOficial::TIPO_TITULAR
                    ? 'El oficial ya tiene un cargo titular asignado durante ese período.'
                    : 'El oficial ya tiene una sustitución temporal durante ese período.',
            ]);
        }
    }
}
