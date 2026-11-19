<?php

namespace App\Services\Disciplina;

use App\Models\Cadete;
use App\Models\FaltaDisciplinaria;
use App\Models\Persona;
use App\Models\SancionDisciplinaria;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class RegistrarSancionDisciplinaria
{
    public function handle(
        array $datos,
        User $usuario
    ): SancionDisciplinaria {

        if (!$usuario->can('sanciones_disciplinarias.crear')) {
            throw ValidationException::withMessages([
                'usuario' => 'No tiene permiso para registrar sanciones.',
            ]);
        }

        $datos = validator($datos, [
            'cadete_id' => ['required', 'integer', 'exists:cadetes,id'],
            'falta_disciplinaria_id' => [
                'required',
                'integer',
                'exists:faltas_disciplinarias,id',
            ],
            'responsable_id' => [
                'required',
                'integer',
                'exists:personas,id',
            ],
            'fecha_falta' => ['required', 'date', 'before_or_equal:today'],
            'descripcion_hechos' => ['nullable', 'string', 'max:10000'],
        ])->validate();

        return DB::transaction(function () use ($datos, $usuario) {

            $cadete = Cadete::query()
                ->whereKey($datos['cadete_id'])
                ->firstOrFail();

            if ($cadete->estado !== 'activo') {
                throw ValidationException::withMessages([
                    'cadete_id' => 'El cadete no está activo.',
                ]);
            }

            $falta = FaltaDisciplinaria::query()
                ->whereKey($datos['falta_disciplinaria_id'])
                ->firstOrFail();

            if (!$falta->activo) {
                throw ValidationException::withMessages([
                    'falta_disciplinaria_id' =>
                        'La falta seleccionada no está habilitada.',
                ]);
            }

            if ($falta->version_reglamento !== '2026') {
                throw ValidationException::withMessages([
                    'falta_disciplinaria_id' =>
                        'La falta no corresponde al catálogo vigente configurado.',
                ]);
            }

            if ($falta->tipo === 'gravisima') {
                throw ValidationException::withMessages([
                    'falta_disciplinaria_id' =>
                        'Las faltas gravísimas requieren un procedimiento separado.',
                ]);
            }

            if (!in_array($falta->tipo, ['leve', 'grave'], true)) {
                throw ValidationException::withMessages([
                    'falta_disciplinaria_id' =>
                        'Tipo de falta no admitido en este procedimiento.',
                ]);
            }

            if (
                !in_array(
                    $falta->tipo_sancion,
                    [
                        SancionDisciplinaria::TIPO_HORAS,
                        SancionDisciplinaria::TIPO_DEMERITOS,
                    ],
                    true
                )
            ) {
                throw ValidationException::withMessages([
                    'falta_disciplinaria_id' =>
                        'El tipo de sanción requiere revisión.',
                ]);
            }

            if ($falta->cantidad_base === null) {
                throw ValidationException::withMessages([
                    'falta_disciplinaria_id' =>
                        'La falta no tiene una cantidad base definida.',
                ]);
            }

            $responsable = Persona::query()
                ->whereKey($datos['responsable_id'])
                ->firstOrFail();


            $sancion = new SancionDisciplinaria([
                'cadete_id' => $cadete->id,
                'falta_disciplinaria_id' => $falta->id,
                'responsable_id' => $responsable->id,
                'fecha_falta' => $datos['fecha_falta'],
                'descripcion_hechos' =>
                    $datos['descripcion_hechos'] ?? null,
            ]);

            $sancion->registrado_por = $usuario->id;
            $sancion->cantidad_aplicada = $falta->cantidad_base;
            $sancion->tipo_sancion = $falta->tipo_sancion;
            $sancion->estado = SancionDisciplinaria::ESTADO_REGISTRADA;

            $sancion->save();

            return $sancion;

        });
    }
}
