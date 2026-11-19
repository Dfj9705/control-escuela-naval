<?php

namespace App\Services\Disciplina;

use App\Models\SancionDisciplinaria;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\ValidationException;

class AdjuntarBoletaFirmada
{
    public function handle(
        int $sancionId,
        UploadedFile $archivo
    ): SancionDisciplinaria {
        abort_unless(
            auth()->user()?->can('sanciones_disciplinarias.adjuntar'),
            403,
            'No tiene autorización para adjuntar boletas.'
        );

        if (!$archivo->isValid()) {
            throw ValidationException::withMessages([
                'boleta_firmada' => 'El archivo cargado no es válido.',
            ]);
        }

        validator(
            ['archivo' => $archivo],
            [
                'archivo' => [
                    'required',
                    'file',
                    'mimes:pdf',
                    'max:10240',
                ],
            ]
        )->validate();

        $ruta = null;

        try {
            return DB::transaction(function () use ($sancionId, $archivo, &$ruta) {
                $sancion = SancionDisciplinaria::query()
                    ->lockForUpdate()
                    ->findOrFail($sancionId);

                if (
                    $sancion->estado !==
                    SancionDisciplinaria::ESTADO_PENDIENTE_FIRMA
                ) {
                    throw ValidationException::withMessages([
                        'estado' => 'La sanción no está pendiente de firma.',
                    ]);
                }

                if (!empty($sancion->boleta_firmada)) {
                    throw ValidationException::withMessages([
                        'boleta_firmada' => 'Esta sanción ya tiene una boleta firmada.',
                    ]);
                }

                $ruta = $archivo->store(
                    'disciplina/boletas-firmadas',
                    'local'
                );

                if ($ruta === false) {
                    throw ValidationException::withMessages([
                        'boleta_firmada' => 'No fue posible guardar el documento.',
                    ]);
                }

                $sancion->boleta_firmada = $ruta;

                $sancion->estado =
                    SancionDisciplinaria::ESTADO_PENDIENTE_CONFIRMACION;

                $sancion->save();

                return $sancion->refresh();
            });
        } catch (\Throwable $e) {
            if ($ruta !== null) {
                Storage::disk('local')->delete($ruta);
            }

            throw $e;
        }
    }
}
