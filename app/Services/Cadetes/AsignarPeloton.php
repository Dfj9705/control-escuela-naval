<?php

namespace App\Services\Cadetes;

use App\Models\Cadete;
use App\Models\CadetePeloton;
use App\Models\Peloton;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class AsignarPeloton
{
    public function handle(
        int $cadeteId,
        int $pelotonId,
        string $fechaInicio
    ): CadetePeloton {
        $fecha = Carbon::createFromFormat('!Y-m-d', $fechaInicio);
        abort_unless(
            auth()->user()?->can('cadete_pelotones.asignar'),
            403,
            'No tiene autorización para asignar pelotones.'
        );
        if (!$fecha || $fecha->format('Y-m-d') !== $fechaInicio) {
            throw ValidationException::withMessages([
                'fecha_inicio' => 'La fecha de inicio no es válida.',
            ]);
        }

        return DB::transaction(function () use ($cadeteId, $pelotonId, $fecha) {
            // Bloqueamos el cadete para serializar sus cambios
            // de pelotón, incluso si no tiene asignaciones.
            $cadete = Cadete::query()
                ->lockForUpdate()
                ->findOrFail($cadeteId);

            if ($cadete->estado !== 'activo') {
                throw ValidationException::withMessages([
                    'cadete_id' => 'El cadete no está activo.',
                ]);
            }

            $peloton = Peloton::query()
                ->where('activo', true)
                ->find($pelotonId);

            if (!$peloton) {
                throw ValidationException::withMessages([
                    'peloton_id' => 'El pelotón no está disponible.',
                ]);
            }

            $inicio = $fecha->toDateString();

            // Primera versión: solo permite agregar cambios
            // posteriores al inicio de la última asignación.
            $ultima = CadetePeloton::query()
                ->where('cadete_id', $cadete->id)
                ->orderByDesc('fecha_inicio')
                ->orderByDesc('id')
                ->first();

            if ($ultima) {
                if ($ultima->fecha_fin !== null) {
                    throw ValidationException::withMessages([
                        'fecha_inicio' =>
                            'La última asignación ya está cerrada. '
                            . 'Revisa el historial antes de continuar.',
                    ]);
                }

                if (
                    $fecha->lessThanOrEqualTo(
                        $ultima->fecha_inicio
                    )
                ) {
                    throw ValidationException::withMessages([
                        'fecha_inicio' =>
                            'La nueva fecha debe ser posterior '
                            . 'al inicio de la asignación anterior.',
                    ]);
                }

                if ($ultima->peloton_id === $peloton->id) {
                    throw ValidationException::withMessages([
                        'peloton_id' =>
                            'El cadete ya pertenece a este pelotón.',
                    ]);
                }

                // Cerramos el período anterior el día previo.
                $ultima->fecha_fin = $fecha->copy()
                    ->subDay()
                    ->toDateString();

                $ultima->save();
            }

            return CadetePeloton::create([
                'cadete_id' => $cadete->id,
                'peloton_id' => $peloton->id,
                'fecha_inicio' => $inicio,
                'fecha_fin' => null,
            ]);
        });
    }
}
