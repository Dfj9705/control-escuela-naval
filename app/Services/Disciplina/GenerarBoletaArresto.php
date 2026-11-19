<?php

namespace App\Services\Disciplina;

use App\Models\SancionDisciplinaria;
use Illuminate\Support\Facades\View;
use Illuminate\Validation\ValidationException;
use Mpdf\HTMLParserMode;
use Mpdf\Mpdf;

class GenerarBoletaArresto
{
    public function generar(SancionDisciplinaria $sancion): string
    {
        // Validar el tipo de sanción
        if (
            $sancion->tipo_sancion !==
            SancionDisciplinaria::TIPO_HORAS
        ) {
            throw ValidationException::withMessages([
                'sancion' => 'La sanción no corresponde a horas de arresto.',
            ]);
        }

        if ($sancion->estado === SancionDisciplinaria::ESTADO_ANULADA) {
            throw ValidationException::withMessages([
                'sancion' => 'No se puede imprimir una sanción anulada.',
            ]);
        }

        // Consultar las relaciones necesarias
        $sancion->load([
            'cadete.persona',
            'cadete.grado',
            'faltaDisciplinaria',
            'responsable',
            'registradoPor',
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

        $asignacionPeloton = $sancion->cadete->pelotonEnFecha(
            $sancion->fecha_falta
        );

        $peloton = $asignacionPeloton?->peloton?->nombre
            ?? 'No determinado';

        $escudo = public_path(
            'images/eng_logo.png'
        );
        // Renderizar la plantilla HTML
        $html = View::make(
            'pdf.disciplina.boleta-arresto',
            [
                'sancion' => $sancion,
                'escudo' => is_file($escudo) ? $escudo : null,
                'semestre' => $semestre,
                'peloton' => $peloton,
            ]
        )->render();


        // Configurar mPDF
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
            'Boleta de arresto No. ' . $sancion->id
        );

        $mpdf->SetAuthor('Escuela Naval de Guatemala');

        $css = file_get_contents(
            resource_path('css/pdf/disciplina.css')
        );

        $mpdf->WriteHTML(
            $css,
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

        // Devolver el PDF como contenido binario
        return $mpdf->Output('', 'S');
    }
}
