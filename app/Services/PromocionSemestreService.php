<?php

namespace App\Services;

use App\Models\AsignacionSemestre;
use App\Models\PeriodoAcademico;
use App\Models\Semestre;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class PromocionSemestreService
{
    public function promover(
        int $periodoOrigenId,
        int $periodoDestinoId,
        array $cadetesIds
    ): int {
        return DB::transaction(function () use ($periodoOrigenId, $periodoDestinoId, $cadetesIds) {
            $cadetesIds = array_values(array_unique(
                array_map('intval', $cadetesIds)
            ));

            if (empty($cadetesIds)) {
                throw ValidationException::withMessages([
                    'cadetesSeleccionados' => 'Seleccione al menos un cadete.',
                ]);
            }

            $origen = PeriodoAcademico::findOrFail($periodoOrigenId);
            $destino = PeriodoAcademico::findOrFail($periodoDestinoId);

            $ordenOrigen = $origen->anio * 2 + $origen->numero;
            $ordenDestino = $destino->anio * 2 + $destino->numero;

            if ($ordenDestino <= $ordenOrigen) {
                throw ValidationException::withMessages([
                    'data.periodo_destino_id' =>
                        'El período de destino debe ser posterior al de origen.',
                ]);
            }

            // Bloquear los cadetes seleccionados durante la promoción.
            // Ordenar por ID reduce el riesgo de interbloqueos.

            $cadetesBloqueados = \App\Models\Cadete::query()
                ->whereIn('id', $cadetesIds)
                ->orderBy('id')
                ->lockForUpdate()
                ->pluck('id');

            if ($cadetesBloqueados->count() !== count($cadetesIds)) {
                throw ValidationException::withMessages([
                    'cadetesSeleccionados' =>
                        'Uno o más cadetes seleccionados ya no existen.',
                ]);
            }

            $asignaciones = AsignacionSemestre::query()
                ->with(['semestre', 'cadete'])
                ->where('periodo_academico_id', $periodoOrigenId)
                ->whereIn('cadete_id', $cadetesIds)
                ->get();

            if ($asignaciones->count() !== count($cadetesIds)) {
                throw ValidationException::withMessages([
                    'cadetesSeleccionados' =>
                        'Algunos cadetes no pertenecen al período de origen.',
                ]);
            }

            $semestres = Semestre::query()
                ->get()
                ->keyBy('numero');

            foreach ($asignaciones as $asignacion) {
                if ($asignacion->cadete?->estado !== 'activo') {
                    throw ValidationException::withMessages([
                        'cadetesSeleccionados' =>
                            'La selección contiene cadetes inactivos.',
                    ]);
                }

                $numeroActual = $asignacion->semestre?->numero;

                if (!$numeroActual || $numeroActual >= 8) {
                    throw ValidationException::withMessages([
                        'cadetesSeleccionados' =>
                            'La selección contiene cadetes que no pueden avanzar.',
                    ]);
                }

                $siguiente = $semestres->get($numeroActual + 1);

                if (!$siguiente || !$siguiente->activo) {
                    throw ValidationException::withMessages([
                        'cadetesSeleccionados' =>
                            'No existe un siguiente semestre activo para todos los cadetes.',
                    ]);
                }

                $existe = AsignacionSemestre::query()
                    ->where('cadete_id', $asignacion->cadete_id)
                    ->where('periodo_academico_id', $periodoDestinoId)
                    ->exists();

                if ($existe) {
                    throw ValidationException::withMessages([
                        'cadetesSeleccionados' =>
                            'Uno o más cadetes ya tienen asignación en el período de destino.',
                    ]);
                }
            }

            foreach ($asignaciones as $asignacion) {
                $siguiente = $semestres->get(
                    $asignacion->semestre->numero + 1
                );

                AsignacionSemestre::create([
                    'cadete_id' => $asignacion->cadete_id,
                    'periodo_academico_id' => $periodoDestinoId,
                    'semestre_id' => $siguiente->id,
                ]);
            }

            return $asignaciones->count();
        });
    }
}
