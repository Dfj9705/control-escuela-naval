<?php

namespace App\Services\Disciplina;

use App\Models\PeriodoAcademico;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use PhpOffice\PhpSpreadsheet\Style\Fill;
use PhpOffice\PhpSpreadsheet\Style\Border;
use PhpOffice\PhpSpreadsheet\Cell\DataType;
use PhpOffice\PhpSpreadsheet\Worksheet\Drawing;
use PhpOffice\PhpSpreadsheet\Worksheet\PageSetup;

class GenerarReporteDisciplinarioExcel
{
    public function generar(
        PeriodoAcademico $periodo,
        array $resultados,
        array $resumen,
        ?string $semestre = null
    ): Spreadsheet {
        $excel = new Spreadsheet();

        $excel->getProperties()
            ->setCreator('Escuela Naval de Guatemala')
            ->setTitle('Reporte general de sanciones disciplinarias')
            ->setSubject('Control disciplinario');

        $hoja = $excel->getActiveSheet();

        $hoja->setTitle('Acumulado disciplinario');

        // Fuente predeterminada.
        $excel->getDefaultStyle()
            ->getFont()
            ->setName('Arial')
            ->setSize(10);

        // Encabezado institucional.
        $encabezados = [
            1 => 'ESCUELA NAVAL DE GUATEMALA',
            2 => 'MARINA DE LA DEFENSA NACIONAL',
            3 => 'CUERPO DE GUARDIAMARINAS Y CADETES NAVALES',
            5 => 'REPORTE GENERAL DE SANCIONES DISCIPLINARIAS',
            6 => $periodo->anio . ' - Período ' . $periodo->numero,
            7 => 'Del ' . $periodo->fecha_inicio->format('d/m/Y')
                . ' al ' . $periodo->fecha_fin->format('d/m/Y'),
            8 => $semestre ?? 'Todos los semestres',
        ];

        foreach ($encabezados as $fila => $texto) {
            $hoja->mergeCells("A{$fila}:K{$fila}");

            $hoja->setCellValue("A{$fila}", $texto);

            $hoja->getStyle("A{$fila}:K{$fila}")
                ->getAlignment()
                ->setHorizontal(Alignment::HORIZONTAL_CENTER);
        }

        $hoja->getStyle('A1:K1')
            ->getFont()
            ->setBold(true)
            ->setSize(15)
            ->getColor()
            ->setRGB('16324F');

        $hoja->getStyle('A5:K5')
            ->getFont()
            ->setBold(true)
            ->setSize(13);

        $hoja->getRowDimension(1)->setRowHeight(24);

        // Escudo institucional.
        $escudo = public_path('images/eng_logo.png');

        if (is_file($escudo)) {
            $imagen = new Drawing();

            $imagen->setName('Escudo Escuela Naval');
            $imagen->setPath($escudo);
            $imagen->setHeight(75);
            $imagen->setCoordinates('A1');
            $imagen->setOffsetX(8);
            $imagen->setOffsetY(5);
            $imagen->setWorksheet($hoja);
        }

        // Resumen general.
        $indicadores = [
            'A' => ['Cadetes', $resumen['cadetes']],
            'C' => ['Faltas', $resumen['faltas']],
            'E' => ['Horas', $resumen['horas']],
            'G' => ['Deméritos', $resumen['demeritos']],
            'I' => ['Méritos', $resumen['meritos']],
            'K' => ['Con deméritos', $resumen['cadetes_con_demeritos']],
        ];

        foreach ($indicadores as $columna => $indicador) {
            $hoja->setCellValue($columna . '10', $indicador[0]);
            $hoja->setCellValue($columna . '11', $indicador[1]);
        }

        $hoja->getStyle('A10:K11')->applyFromArray([
            'font' => [
                'bold' => true,
                'color' => ['rgb' => '16324F'],
            ],
            'fill' => [
                'fillType' => Fill::FILL_SOLID,
                'startColor' => ['rgb' => 'E2E8F0'],
            ],
            'alignment' => [
                'horizontal' => Alignment::HORIZONTAL_CENTER,
                'vertical' => Alignment::VERTICAL_CENTER,
            ],
        ]);

        $hoja->getStyle('A10:K10')
            ->getAlignment()
            ->setWrapText(true);

        $hoja->getRowDimension(10)->setRowHeight(32);
        $hoja->getRowDimension(11)->setRowHeight(25);

        $hoja->getStyle('A11:K11')
            ->getFont()
            ->setSize(14);

        // Encabezados de la tabla.
        $filaEncabezado = 13;

        $hoja->fromArray([
            'No.',
            'Catálogo',
            'Grado',
            'Nombre del cadete',
            'Semestre',
            'Faltas',
            'Horas',
            'Deméritos',
            'Méritos',
            'Límite',
            '% del límite',
        ], null, "A{$filaEncabezado}");

        $hoja->getStyle("A{$filaEncabezado}:K{$filaEncabezado}")
            ->applyFromArray([
                'font' => [
                    'bold' => true,
                    'color' => ['rgb' => 'FFFFFF'],
                ],
                'fill' => [
                    'fillType' => Fill::FILL_SOLID,
                    'startColor' => ['rgb' => '16324F'],
                ],
                'alignment' => [
                    'horizontal' => Alignment::HORIZONTAL_CENTER,
                    'vertical' => Alignment::VERTICAL_CENTER,
                    'wrapText' => true,
                ],
            ]);

        $hoja->getRowDimension($filaEncabezado)
            ->setRowHeight(28);

        // Registros de cadetes.
        $fila = $filaEncabezado + 1;

        foreach ($resultados as $indice => $registro) {
            $hoja->setCellValue("A{$fila}", $indice + 1);

            // Conservar posibles ceros iniciales del catálogo.
            $hoja->setCellValueExplicit(
                "B{$fila}",
                (string) $registro['catalogo'],
                DataType::TYPE_STRING
            );

            $hoja->setCellValueExplicit(
                "C{$fila}",
                $registro['grado'],
                DataType::TYPE_STRING
            );

            $hoja->setCellValueExplicit(
                "D{$fila}",
                $registro['nombre'],
                DataType::TYPE_STRING
            );

            $hoja->setCellValueExplicit(
                "E{$fila}",
                $registro['semestre'],
                DataType::TYPE_STRING
            );

            $hoja->setCellValue("F{$fila}", $registro['faltas']);
            $hoja->setCellValue("G{$fila}", $registro['horas']);
            $hoja->setCellValue("H{$fila}", $registro['demeritos']);
            $hoja->setCellValue("I{$fila}", $registro['meritos']);
            $hoja->setCellValue("J{$fila}", $registro['limite']);

            if ($registro['porcentaje'] !== null) {
                $hoja->setCellValue(
                    "K{$fila}",
                    $registro['porcentaje'] / 100
                );
            }

            // Alternar color de las filas.
            if ($indice % 2 !== 0) {
                $hoja->getStyle("A{$fila}:K{$fila}")
                    ->getFill()
                    ->setFillType(Fill::FILL_SOLID)
                    ->getStartColor()
                    ->setRGB('F1F5F9');
            }

            $fila++;
        }

        $ultimaFila = $fila - 1;

        if ($ultimaFila > $filaEncabezado) {
            $hoja->getStyle("A13:K{$ultimaFila}")
                ->getBorders()
                ->getAllBorders()
                ->setBorderStyle(Border::BORDER_THIN)
                ->getColor()
                ->setRGB('CBD5E1');

            $hoja->getStyle("K14:K{$ultimaFila}")
                ->getNumberFormat()
                ->setFormatCode('0.00%');

            $hoja->getStyle("F14:K{$ultimaFila}")
                ->getAlignment()
                ->setHorizontal(Alignment::HORIZONTAL_CENTER);

            $hoja->getStyle("I14:I{$ultimaFila}")
                ->getFont()
                ->setBold(true)
                ->getColor()
                ->setRGB('166534');

            $hoja->setAutoFilter("A13:K{$ultimaFila}");
        }

        // Ancho de columnas.
        $anchos = [
            'A' => 12,
            'B' => 15,
            'C' => 22,
            'D' => 42,
            'E' => 22,
            'F' => 10,
            'G' => 12,
            'H' => 13,
            'I' => 12,
            'J' => 10,
            'K' => 17,
        ];

        foreach ($anchos as $columna => $ancho) {
            $hoja->getColumnDimension($columna)
                ->setWidth($ancho);
        }

        // Información complementaria.
        $filaNota = $ultimaFila + 3;

        $hoja->mergeCells("A{$filaNota}:K{$filaNota}");

        $hoja->setCellValue(
            "A{$filaNota}",
            'Incluye únicamente sanciones confirmadas y méritos vigentes.'
        );

        $filaNota++;

        $hoja->mergeCells("A{$filaNota}:K{$filaNota}");

        $hoja->setCellValue(
            "A{$filaNota}",
            'Fecha de generación: ' . now()->format('d/m/Y H:i')
        );

        // Configuración de visualización.
        $hoja->freezePane('D14');
        $hoja->setShowGridlines(false);

        // Configuración de impresión.
        $hoja->getPageSetup()
            ->setOrientation(PageSetup::ORIENTATION_LANDSCAPE)
            ->setPaperSize(PageSetup::PAPERSIZE_LETTER)
            ->setFitToWidth(1)
            ->setFitToHeight(0)
            ->setRowsToRepeatAtTopByStartAndEnd(13, 13)
            ->setPrintArea("A1:K{$filaNota}");

        $hoja->getHeaderFooter()
            ->setOddFooter(
                '&LEscuela Naval de Guatemala&RPágina &P de &N'
            );

        return $excel;
    }
}
