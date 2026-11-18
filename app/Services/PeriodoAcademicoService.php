<?php

namespace App\Services;

use App\Models\PeriodoAcademico;
use Illuminate\Support\Facades\DB;

class PeriodoAcademicoService
{
    public function guardar(
        array $data,
        ?PeriodoAcademico $periodo = null
    ): PeriodoAcademico {
        return DB::transaction(function () use ($data, $periodo) {

            // Serializar las operaciones de activación
            // mediante un bloqueo sobre una fila estable.
            DB::table('periodos_academicos_bloqueo')
                ->where('id', 1)
                ->lockForUpdate()
                ->first();

            if ($data['activo'] ?? false) {
                PeriodoAcademico::query()
                    ->where('activo', true)
                    ->when(
                        $periodo,
                        fn($query) => $query->whereKeyNot($periodo->getKey())
                    )
                    ->update(['activo' => false]);
            }

            if ($periodo) {
                $periodo->update($data);

                return $periodo->refresh();
            }

            return PeriodoAcademico::create($data);
        });
    }
}
