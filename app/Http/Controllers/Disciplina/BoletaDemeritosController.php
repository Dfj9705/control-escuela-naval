<?php

namespace App\Http\Controllers\Disciplina;

use App\Http\Controllers\Controller;
use App\Models\SancionDisciplinaria;
use App\Services\Disciplina\GenerarBoletaDemeritos;
use Illuminate\Http\Response;

class BoletaDemeritosController extends Controller
{
    public function __invoke(
        SancionDisciplinaria $sancion,
        GenerarBoletaDemeritos $generador
    ): Response {
        abort_unless(
            auth()->user()?->can('sanciones_disciplinarias.imprimir'),
            403,
            'No tiene autorización para imprimir boletas.'
        );

        $pdf = $generador->generar($sancion);

        return response($pdf, 200, [
            'Content-Type' => 'application/pdf',
            'Content-Disposition' =>
                'inline; filename="boleta-demeritos-' .
                $sancion->id . '.pdf"',
            'Cache-Control' => 'private, no-store, max-age=0',
            'X-Content-Type-Options' => 'nosniff',
        ]);
    }
}
