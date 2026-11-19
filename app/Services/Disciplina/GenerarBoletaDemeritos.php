<?php

namespace App\Services\Disciplina;

use App\Models\SancionDisciplinaria;
use DB;
use Illuminate\Support\Facades\View;
use Illuminate\Validation\ValidationException;
use Mpdf\HTMLParserMode;
use Mpdf\Mpdf;
use App\Models\PeriodoAcademico;

class GenerarBoletaDemeritos
{
    public function generar(SancionDisciplinaria $sancion): string
    {
        if (
            $sancion->tipo_sancion !==
            SancionDisciplinaria::TIPO_DEMERITOS
        ) {
            throw ValidationException::withMessages([
                'sancion' => 'La sanción no corresponde a deméritos.',
            ]);
        }

        if ($sancion->estado === SancionDisciplinaria::ESTADO_ANULADA) {
            throw ValidationException::withMessages([
                'sancion' => 'No se puede imprimir una sanción anulada.',
            ]);
        }

        $sancion->load([
            'cadete.persona',
            'cadete.grado',
            'faltaDisciplinaria',
            'responsable',
        ]);

        $asignacion = $sancion->cadete
            ->asignacionesSemestre()
            ->whereHas('periodoAcademico', function ($query) use ($sancion) {
                $query
                    ->whereDate('fecha_inicio', '<=', $sancion->fecha_falta)
                    ->whereDate('fecha_fin', '>=', $sancion->fecha_falta);
            })
            ->with('semestre')
            ->first();

        $semestre = $asignacion?->semestre?->nombre
            ?? 'No determinado';

        $asignacionPeloton = $sancion->cadete
            ->pelotonEnFecha($sancion->fecha_falta);

        $peloton = $asignacionPeloton?->peloton?->nombre
            ?? 'No determinado';

        // Período académico correspondiente a la fecha de la falta.
        $periodo = PeriodoAcademico::query()
            ->whereDate('fecha_inicio', '<=', $sancion->fecha_falta)
            ->whereDate('fecha_fin', '>=', $sancion->fecha_falta)
            ->first();

        if (!$periodo) {
            throw ValidationException::withMessages([
                'periodo_academico' =>
                    'No existe un período académico para la fecha de la falta.',
            ]);
        }

        // Deméritos confirmados en el período.

        $demeritosAcumulados = DB::transaction(
            function () use ($sancion, $periodo): int {

                $registro = SancionDisciplinaria::query()
                    ->lockForUpdate()
                    ->findOrFail($sancion->id);

                // Si el acumulado ya fue guardado,
                // no lo modificamos.
                if ($registro->demeritos_acumulados_emision !== null) {
                    return (int) $registro->demeritos_acumulados_emision;
                }

                // Primera emisión: calcular el acumulado histórico.
                $acumulado = $registro->cadete
                    ->demeritosPreviosASancion($registro, $periodo);

                // Guardar el acumulado y la fecha de emisión.
                $registro->demeritos_acumulados_emision = $acumulado;
                $registro->demeritos_emitida_at = now();

                $registro->save();

                return $acumulado;
            }
        );


        $escudo = public_path('images/eng_logo.png');

        $html = View::make(
            'pdf.disciplina.boleta-demeritos',
            [
                'sancion' => $sancion,
                'escudo' => is_file($escudo) ? $escudo : null,
                'semestre' => $semestre,
                'peloton' => $peloton,
                'periodo' => $periodo,
                'demeritosAcumulados' => $demeritosAcumulados,
            ]
        )->render();

        $mpdf = new Mpdf([
            'mode' => 'utf-8',
            'format' => 'Letter',
            'orientation' => 'P',
            'margin_left' => 15,
            'margin_right' => 15,
            'margin_top' => 18,
            'margin_bottom' => 25,
            'margin_footer' => 10,
            'tempDir' => storage_path('app/mpdf'),
        ]);

        $mpdf->SetTitle(
            'Boleta de deméritos No. ' . $sancion->id
        );

        $mpdf->SetAuthor('Escuela Naval de Guatemala');

        $css = file_get_contents(
            resource_path('css/pdf/disciplina.css')
        );

        $mpdf->WriteHTML(
            $css,
            HTMLParserMode::HEADER_CSS
        );

        $mpdf->WriteHTML(
            $html,
            HTMLParserMode::HTML_BODY
        );

        return $mpdf->Output('', 'S');
    }
}
