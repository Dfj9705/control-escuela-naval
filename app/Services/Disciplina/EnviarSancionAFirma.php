<?php

namespace App\Services\Disciplina;

use App\Models\SancionDisciplinaria;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class EnviarSancionAFirma
{
    public function handle(int $sancionId): SancionDisciplinaria
    {
        abort_unless(
            auth()->user()?->can('sanciones_disciplinarias.enviar_firma'),
            403,
            'No tiene autorización para enviar sanciones a firma.'
        );

        return DB::transaction(function () use ($sancionId) {
            $sancion = SancionDisciplinaria::query()
                ->lockForUpdate()
                ->findOrFail($sancionId);

            if (!$sancion->puedeEnviarseAFirma()) {
                throw ValidationException::withMessages([
                    'estado' => 'La sanción no puede enviarse a firma desde su estado actual.',
                ]);
            }

            $sancion->estado =
                SancionDisciplinaria::ESTADO_PENDIENTE_FIRMA;

            $sancion->save();

            return $sancion->refresh();
        });
    }
}
