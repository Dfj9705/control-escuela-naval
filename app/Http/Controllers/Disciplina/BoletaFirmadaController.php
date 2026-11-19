<?php

namespace App\Http\Controllers\Disciplina;

use App\Http\Controllers\Controller;
use App\Models\SancionDisciplinaria;
use Symfony\Component\HttpFoundation\BinaryFileResponse;
use Illuminate\Support\Facades\Storage;

class BoletaFirmadaController extends Controller
{
    public function __invoke(
        SancionDisciplinaria $sancion
    ): BinaryFileResponse {
        abort_unless(
            auth()->user()?->can('sanciones_disciplinarias.ver'),
            403
        );

        abort_if(
            empty($sancion->boleta_firmada),
            404,
            'La sanción no tiene una boleta firmada.'
        );

        $disco = Storage::disk('local');
        $ruta = $sancion->boleta_firmada;

        // Evitar consultar archivos fuera del directorio autorizado.
        abort_unless(
            str_starts_with(
                $ruta,
                'disciplina/boletas-firmadas/'
            ),
            403
        );

        abort_unless(
            $disco->exists($ruta),
            404,
            'No se encontró el documento firmado.'
        );

        return response()->file(
            $disco->path($ruta),
            [
                'Content-Type' => 'application/pdf',
                'Content-Disposition' =>
                    'inline; filename="boleta-firmada-'
                    . $sancion->id . '.pdf"',
                'Cache-Control' => 'private, no-store, max-age=0',
                'X-Content-Type-Options' => 'nosniff',
            ]
        );
    }
}
