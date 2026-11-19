<?php

namespace App\Services\Disciplina;

use App\Models\SancionDisciplinaria;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class AnularSancionDisciplinaria
{
    public function handle(
        int $sancionId,
        string $motivo
    ): SancionDisciplinaria {
        abort_unless(
            auth()->user()?->can('sanciones_disciplinarias.anular'),
            403,
            'No tiene autorización para anular sanciones.'
        );

        $motivo = trim($motivo);

        if (mb_strlen($motivo) < 10) {
            throw ValidationException::withMessages([
                'motivo_anulacion' =>
                    'El motivo de anulación debe contener al menos 10 caracteres.',
            ]);
        }

        return DB::transaction(function () use ($sancionId, $motivo) {
            $sancion = SancionDisciplinaria::query()
                ->lockForUpdate()
                ->findOrFail($sancionId);

            if (!$sancion->puedeAnularse()) {
                throw ValidationException::withMessages([
                    'estado' =>
                        'Esta sanción ya fue anulada.',
                ]);
            }

            $sancion->estado =
                SancionDisciplinaria::ESTADO_ANULADA;

            $sancion->motivo_anulacion = $motivo;
            $sancion->anulado_por = auth()->id();
            $sancion->anulado_at = now();

            $sancion->save();

            return $sancion->refresh();
        });
    }
}
