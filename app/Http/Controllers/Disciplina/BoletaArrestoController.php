<?php

namespace App\Http\Controllers\Disciplina;

use App\Http\Controllers\Controller;
use App\Models\SancionDisciplinaria;
use App\Services\Disciplina\GenerarBoletaArresto;
use Illuminate\Http\Response;

class BoletaArrestoController extends Controller
{
    public function __invoke(
        SancionDisciplinaria $sancion,
        GenerarBoletaArresto $generador
    ): Response {
        abort_unless(
            auth()->user()?->can('sanciones_disciplinarias.imprimir'),
            403
        );

        abort_unless(
            auth()->user()?->can('sanciones_disciplinarias.ver'),
            403
        );

        abort_unless(
            $sancion->tipo_sancion === SancionDisciplinaria::TIPO_HORAS,
            404
        );

        abort_if(
            $sancion->estado === SancionDisciplinaria::ESTADO_ANULADA,
            403
        );

        $pdf = $generador->generar($sancion);

        $nombre = sprintf(
            'boleta-arresto-%06d.pdf',
            $sancion->id
        );

        return response($pdf, 200, [
            'Content-Type' => 'application/pdf',
            'Content-Disposition' => 'inline; filename="' . $nombre . '"',
            'Cache-Control' => 'private, no-store, no-cache, must-revalidate',
            'X-Content-Type-Options' => 'nosniff',
        ]);
    }
}
