<?php

namespace App\Services\Disciplina;

use App\Models\SancionDisciplinaria;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\ValidationException;

class ConfirmarSancionDisciplinaria
{
    public function handle(int $sancionId): SancionDisciplinaria
    {
        abort_unless(
            auth()->user()?->can('sanciones_disciplinarias.confirmar'),
            403,
            'No tiene autorización para confirmar sanciones.'
        );

        return DB::transaction(function () use ($sancionId) {
            $sancion = SancionDisciplinaria::query()
                ->lockForUpdate()
                ->findOrFail($sancionId);

            if (!$sancion->puedeConfirmarse()) {
                throw ValidationException::withMessages([
                    'estado' => 'La sanción no cumple las condiciones para confirmarse.',
                ]);
            }

            if (!Storage::disk('local')->exists($sancion->boleta_firmada)) {
                throw ValidationException::withMessages([
                    'boleta_firmada' => 'No se encontró el documento firmado.',
                ]);
            }

            $sancion->estado =
                SancionDisciplinaria::ESTADO_CONFIRMADA;

            $sancion->confirmado_por = auth()->id();
            $sancion->confirmado_at = now();

            $sancion->save();

            return $sancion->refresh();
        });
    }
}
