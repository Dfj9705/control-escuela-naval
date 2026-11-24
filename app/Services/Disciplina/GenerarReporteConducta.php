<?php

namespace App\Services\Disciplina;

use App\Models\PeriodoAcademico;
use Illuminate\Support\Facades\View;
use Mpdf\HTMLParserMode;
use Mpdf\Mpdf;

class GenerarReporteConducta
{
    public function generar(
        PeriodoAcademico $periodo,
        array $meses,
        array $calificaciones,
        ?string $semestre = null
    ): string {
        $escudo = public_path('images/eng_logo.png');

        $html = View::make(
            'pdf.disciplina.reporte-conducta',
            [
                'periodo' => $periodo,
                'meses' => $meses,
                'calificaciones' => collect($calificaciones)
                    ->groupBy('semestre'),
                'semestre' => $semestre,
                'escudo' => is_file($escudo) ? $escudo : null,
                'fechaGeneracion' => now(),
                'provisional' => $periodo->fecha_fin
                    ->copy()
                    ->endOfDay()
                    ->gte(now()),
            ]
        )->render();

        $mpdf = new Mpdf([
            'mode' => 'utf-8',
            'format' => 'Letter',
            'orientation' => 'L',
            'margin_left' => 12,
            'margin_right' => 12,
            'margin_top' => 12,
            'margin_bottom' => 20,
            'margin_footer' => 8,
            'tempDir' => storage_path('app/mpdf'),
        ]);

        $mpdf->SetTitle(
            'Calificación de conducta - '
            . $periodo->anio
            . ' - Período '
            . $periodo->numero
        );

        $mpdf->SetAuthor('Escuela Naval de Guatemala');

        $mpdf->WriteHTML(
            file_get_contents(
                resource_path('css/pdf/reportes-disciplina.css')
            ),
            HTMLParserMode::HEADER_CSS
        );

        $mpdf->SetHTMLFooter('
            <div style="
                text-align: center;
                font-size: 8pt;
                color: #64748b;
                border-top: 1px solid #cbd5e1;
                padding-top: 5px;
            ">
                Escuela Naval de Guatemala |
                Sistema de Control Disciplinario |
                Página {PAGENO} de {nbpg}
            </div>
        ');

        $mpdf->WriteHTML(
            $html,
            HTMLParserMode::HTML_BODY
        );

        return $mpdf->Output('', 'S');
    }
}
