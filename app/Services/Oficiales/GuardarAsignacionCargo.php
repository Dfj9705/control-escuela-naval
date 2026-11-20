<?php

namespace App\Services\Oficiales;

use App\Models\AsignacionCargoOficial;
use App\Models\Oficial;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class GuardarAsignacionCargo
{
    public function __construct(
        private readonly ValidarAsignacionCargo $validador
    ) {
    }

    public function crear(array $datos): AsignacionCargoOficial
    {
        return DB::transaction(function () use ($datos) {
            $oficialId = (int) $datos['oficial_id'];

            // Serializa las asignaciones del mismo oficial.
            Oficial::query()
                ->whereKey($oficialId)
                ->lockForUpdate()
                ->firstOrFail();

            $datos = $this->prepararDatos($datos);

            // Validar estructura y fechas antes de modificar registros.
            validator($datos, [
                'oficial_id' => ['required', 'integer', 'exists:oficiales,id'],
                'cargo_id' => ['required', 'integer', 'exists:cargos,id'],
                'tipo_asignacion' => ['required', 'string'],
                'fecha_inicio' => ['required', 'date_format:Y-m-d'],
                'fecha_fin' => ['nullable', 'date_format:Y-m-d'],
            ])->validate();
            if (
                $datos['tipo_asignacion'] ===
                AsignacionCargoOficial::TIPO_TITULAR
                && !empty($datos['fecha_fin'])
            ) {
                throw ValidationException::withMessages([
                    'fecha_fin' =>
                        'Un nuevo nombramiento titular debe registrarse sin fecha de finalización.',
                ]);
            }
            // Cerrar automáticamente el cargo titular anterior.
            $this->cerrarCargoTitularAnterior($datos);

            // Validar que no existan períodos incompatibles.
            $this->validar($datos);

            return AsignacionCargoOficial::create($datos);
        });
    }

    public function actualizar(
        AsignacionCargoOficial $asignacion,
        array $datos
    ): AsignacionCargoOficial {

        return DB::transaction(function () use ($asignacion, $datos) {
            $oficialId = (int) $asignacion->oficial_id;

            // Siempre bloqueamos primero al oficial.
            Oficial::query()
                ->whereKey($oficialId)
                ->lockForUpdate()
                ->firstOrFail();

            // Después bloqueamos la asignación.
            $registro = AsignacionCargoOficial::query()
                ->whereKey($asignacion->getKey())
                ->lockForUpdate()
                ->firstOrFail();

            if ((int) $registro->oficial_id !== $oficialId) {
                throw ValidationException::withMessages([
                    'oficial_id' => 'La asignación cambió de oficial. Recarga el registro.',
                ]);
            }

            if (
                array_key_exists('oficial_id', $datos)
                && (int) $datos['oficial_id'] !== $oficialId
            ) {
                throw ValidationException::withMessages([
                    'oficial_id' => 'No se puede cambiar el oficial de una asignación existente.',
                ]);
            }

            $datos = $this->prepararDatos(
                array_merge(
                    $registro->only([
                        'oficial_id',
                        'cargo_id',
                        'tipo_asignacion',
                        'fecha_inicio',
                        'fecha_fin',
                        'observaciones',
                    ]),
                    $datos
                )
            );

            $this->validar($datos, $registro->id);

            $registro->update($datos);

            return $registro->refresh();
        });

    }

    private function prepararDatos(array $datos): array
    {
        $datos = Arr::only($datos, [
            'oficial_id',
            'cargo_id',
            'tipo_asignacion',
            'fecha_inicio',
            'fecha_fin',
            'observaciones',
        ]);

        foreach (['fecha_inicio', 'fecha_fin'] as $campo) {
            if (
                isset($datos[$campo])
                && $datos[$campo] instanceof \DateTimeInterface
            ) {
                $datos[$campo] = $datos[$campo]->format('Y-m-d');
            }
        }

        if (array_key_exists('fecha_fin', $datos) && $datos['fecha_fin'] === '') {
            $datos['fecha_fin'] = null;
        }

        return $datos;
    }

    private function validar(
        array $datos,
        ?int $ignorarAsignacionId = null
    ): void {
        validator($datos, [
            'oficial_id' => ['required', 'integer', 'exists:oficiales,id'],
            'cargo_id' => ['required', 'integer', 'exists:cargos,id'],
            'tipo_asignacion' => ['required', 'string'],
            'fecha_inicio' => ['required', 'date_format:Y-m-d'],
            'fecha_fin' => ['nullable', 'date_format:Y-m-d'],
            'observaciones' => ['nullable', 'string'],
        ])->validate();

        $this->validador->validar(
            oficialId: (int) $datos['oficial_id'],
            tipoAsignacion: $datos['tipo_asignacion'],
            fechaInicio: $datos['fecha_inicio'],
            fechaFin: $datos['fecha_fin'] ?? null,
            ignorarAsignacionId: $ignorarAsignacionId
        );
    }



    private function cerrarCargoTitularAnterior(array $datos): void
    {
        if (
            $datos['tipo_asignacion'] !==
            AsignacionCargoOficial::TIPO_TITULAR
        ) {
            return;
        }

        $fechaInicio = \Carbon\Carbon::createFromFormat(
            '!Y-m-d',
            $datos['fecha_inicio']
        );

        // No permitimos insertar nombramientos titulares
        // antes o en la fecha de inicio de uno existente.
        $existeNombramientoPosterior = AsignacionCargoOficial::query()
            ->where('oficial_id', $datos['oficial_id'])
            ->where(
                'tipo_asignacion',
                AsignacionCargoOficial::TIPO_TITULAR
            )
            ->whereDate(
                'fecha_inicio',
                '>=',
                $fechaInicio->toDateString()
            )
            ->exists();

        if ($existeNombramientoPosterior) {
            throw \Illuminate\Validation\ValidationException::withMessages([
                'fecha_inicio' =>
                    'No se puede registrar el cargo porque existe un nombramiento titular con fecha igual o posterior.',
            ]);
        }

        // Buscamos el último nombramiento titular anterior.
        $cargoAnterior = AsignacionCargoOficial::query()
            ->where('oficial_id', $datos['oficial_id'])
            ->where(
                'tipo_asignacion',
                AsignacionCargoOficial::TIPO_TITULAR
            )
            ->whereDate(
                'fecha_inicio',
                '<',
                $fechaInicio->toDateString()
            )
            ->orderByDesc('fecha_inicio')
            ->lockForUpdate()
            ->first();

        if (!$cargoAnterior) {
            return;
        }

        // Si ya finalizó antes del nuevo nombramiento,
        // conservamos su fecha histórica.
        if (
            $cargoAnterior->fecha_fin !== null &&
            $cargoAnterior->fecha_fin->lt($fechaInicio)
        ) {
            return;
        }

        // Cerramos el cargo anterior un día antes.
        $cargoAnterior->update([
            'fecha_fin' => $fechaInicio
                ->copy()
                ->subDay()
                ->toDateString(),
        ]);
    }


    public function finalizar(
        AsignacionCargoOficial $asignacion,
        string $fechaFin
    ): AsignacionCargoOficial {
        return DB::transaction(function () use ($asignacion, $fechaFin) {
            // Mantener el mismo orden de bloqueo:
            // primero el oficial, después la asignación.
            Oficial::query()
                ->whereKey($asignacion->oficial_id)
                ->lockForUpdate()
                ->firstOrFail();

            $registro = AsignacionCargoOficial::query()
                ->whereKey($asignacion->id)
                ->lockForUpdate()
                ->firstOrFail();

            if (
                $registro->tipo_asignacion !==
                AsignacionCargoOficial::TIPO_TITULAR
            ) {
                throw ValidationException::withMessages([
                    'fecha_fin' => 'Solo se pueden finalizar cargos titulares mediante esta acción.',
                ]);
            }

            if ($registro->fecha_fin !== null) {
                throw ValidationException::withMessages([
                    'fecha_fin' => 'Esta asignación ya tiene una fecha de finalización.',
                ]);
            }

            validator(
                ['fecha_fin' => $fechaFin],
                ['fecha_fin' => ['required', 'date_format:Y-m-d']]
            )->validate();

            if ($fechaFin < $registro->fecha_inicio->format('Y-m-d')) {
                throw ValidationException::withMessages([
                    'fecha_fin' => 'La fecha final no puede ser anterior a la fecha de inicio.',
                ]);
            }

            $registro->update([
                'fecha_fin' => $fechaFin,
            ]);

            return $registro->refresh();
        });
    }



}
