<?php

namespace App\Services\Disciplina;

use App\Models\PeriodoAcademico;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Cell\Coordinate;
use PhpOffice\PhpSpreadsheet\Cell\DataType;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use PhpOffice\PhpSpreadsheet\Style\Border;
use PhpOffice\PhpSpreadsheet\Style\Fill;
use PhpOffice\PhpSpreadsheet\Worksheet\Drawing;
use PhpOffice\PhpSpreadsheet\Worksheet\PageSetup;

class GenerarReporteConductaExcel
{
    public function generar(
        PeriodoAcademico $periodo,
        array $meses,
        array $calificaciones,
        ?string $semestre = null
    ): Spreadsheet {
        $excel = new Spreadsheet();

        $excel->getProperties()
            ->setCreator('Escuela Naval de Guatemala')
            ->setTitle('Calificación de conducta')
            ->setSubject('Control disciplinario');

        $hoja = $excel->getActiveSheet();

        $hoja->setTitle('Calificación de conducta');

        $excel->getDefaultStyle()
            ->getFont()
            ->setName('Arial')
            ->setSize(10);

        // Determinar la última columna dinámicamente.
        $ultimaColumna = Coordinate::stringFromColumnIndex(
            count($meses) + 4
        );

        // Determinar si el reporte es provisional.
        $provisional = $periodo->fecha_fin
            ->copy()
            ->endOfDay()
            ->gte(now());

        // Encabezado institucional.
        $encabezados = [
            1 => 'ESCUELA NAVAL DE GUATEMALA',
            2 => 'MARINA DE LA DEFENSA NACIONAL',
            3 => 'CUERPO DE GUARDIAMARINAS Y CADETES NAVALES',
            5 => 'CALIFICACIÓN MENSUAL Y SEMESTRAL DE CONDUCTA',
            6 => $periodo->anio . ' - Período ' . $periodo->numero,
            7 => 'Del ' . $periodo->fecha_inicio->format('d/m/Y')
                . ' al ' . $periodo->fecha_fin->format('d/m/Y'),
            8 => $semestre ?? 'Todos los semestres',
        ];

        foreach ($encabezados as $fila => $texto) {
            $hoja->mergeCells("B{$fila}:{$ultimaColumna}{$fila}");

            $hoja->setCellValue("B{$fila}", $texto);

            $hoja->getStyle("B{$fila}:{$ultimaColumna}{$fila}")
                ->getAlignment()
                ->setHorizontal(Alignment::HORIZONTAL_CENTER);
        }

        $hoja->getStyle("B1:{$ultimaColumna}1")
            ->getFont()
            ->setBold(true)
            ->setSize(15)
            ->getColor()
            ->setRGB('16324F');

        $hoja->getStyle("B5:{$ultimaColumna}5")
            ->getFont()
            ->setBold(true)
            ->setSize(13);

        $hoja->getRowDimension(1)->setRowHeight(25);
        $hoja->getRowDimension(5)->setRowHeight(25);

        // Escudo institucional.
        $escudo = public_path('images/eng_logo.png');

        if (is_file($escudo)) {
            $imagen = new Drawing();

            $imagen->setName('Escudo Escuela Naval');
            $imagen->setPath($escudo);
            $imagen->setHeight(65);
            $imagen->setCoordinates('A1');
            $imagen->setOffsetX(4);
            $imagen->setOffsetY(5);
            $imagen->setWorksheet($hoja);
        }

        $fila = 10;

        // Aviso de reporte provisional.
        if ($provisional) {
            $hoja->mergeCells("A{$fila}:{$ultimaColumna}{$fila}");

            $hoja->setCellValue(
                "A{$fila}",
                'REPORTE PROVISIONAL — Período académico aún no finalizado.'
            );

            $hoja->getStyle("A{$fila}:{$ultimaColumna}{$fila}")
                ->applyFromArray([
                    'font' => [
                        'bold' => true,
                        'color' => ['rgb' => '92400E'],
                    ],
                    'fill' => [
                        'fillType' => Fill::FILL_SOLID,
                        'startColor' => ['rgb' => 'FEF3C7'],
                    ],
                    'alignment' => [
                        'horizontal' => Alignment::HORIZONTAL_CENTER,
                    ],
                ]);

            $hoja->getRowDimension($fila)->setRowHeight(22);

            $fila += 2;
        }

        // Agrupar cadetes por semestre.
        $grupos = collect($calificaciones)
            ->groupBy('semestre');

        foreach ($grupos as $nombreSemestre => $cadetes) {

            // Encabezado del semestre.
            $hoja->mergeCells("A{$fila}:{$ultimaColumna}{$fila}");

            $hoja->setCellValueExplicit(
                "A{$fila}",
                mb_strtoupper($nombreSemestre),
                DataType::TYPE_STRING
            );

            $hoja->getStyle("A{$fila}:{$ultimaColumna}{$fila}")
                ->applyFromArray([
                    'font' => [
                        'bold' => true,
                        'size' => 11,
                        'color' => ['rgb' => '16324F'],
                    ],
                    'fill' => [
                        'fillType' => Fill::FILL_SOLID,
                        'startColor' => ['rgb' => 'E2E8F0'],
                    ],
                ]);

            $hoja->getRowDimension($fila)->setRowHeight(24);

            $fila++;

            // Encabezados de la tabla.
            $filaEncabezado = $fila;

            $columnas = [
                'No.',
                'Grado',
                'Nombre del cadete',
            ];

            foreach ($meses as $mes) {
                $columnas[] = mb_strtoupper($mes['nombre']);
            }

            $columnas[] = 'PROMEDIO';

            $hoja->fromArray(
                $columnas,
                null,
                "A{$fila}"
            );

            $hoja->getStyle("A{$fila}:{$ultimaColumna}{$fila}")
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

            $hoja->getRowDimension($fila)->setRowHeight(28);

            $fila++;

            $primeraFilaDatos = $fila;

            // Registros de cadetes.
            foreach ($cadetes->values() as $indice => $registro) {

                $hoja->setCellValue(
                    "A{$fila}",
                    $indice + 1
                );

                $hoja->setCellValueExplicit(
                    "B{$fila}",
                    (string) $registro['grado'],
                    DataType::TYPE_STRING
                );

                $hoja->setCellValueExplicit(
                    "C{$fila}",
                    (string) $registro['nombre'],
                    DataType::TYPE_STRING
                );

                // Las calificaciones comienzan en la columna D.
                $indiceColumna = 4;

                foreach ($meses as $mes) {

                    $columna = Coordinate::stringFromColumnIndex(
                        $indiceColumna
                    );

                    $resultado = $registro['calificaciones'][
                        $mes['clave']
                    ] ?? null;

                    if ($resultado !== null) {
                        $hoja->setCellValue(
                            "{$columna}{$fila}",
                            $resultado['nota']
                        );
                    }

                    $indiceColumna++;
                }

                // Promedio calculado por el sistema.
                if ($registro['promedio'] !== null) {
                    $hoja->setCellValue(
                        "{$ultimaColumna}{$fila}",
                        $registro['promedio']
                    );
                }

                // Alternar colores de las filas.
                if ($indice % 2 !== 0) {
                    $hoja->getStyle(
                        "A{$fila}:{$ultimaColumna}{$fila}"
                    )
                        ->getFill()
                        ->setFillType(Fill::FILL_SOLID)
                        ->getStartColor()
                        ->setRGB('F1F5F9');
                }

                $fila++;
            }

            $ultimaFilaDatos = $fila - 1;

            // Bordes de la tabla.
            $hoja->getStyle(
                "A{$filaEncabezado}:{$ultimaColumna}{$ultimaFilaDatos}"
            )
                ->getBorders()
                ->getAllBorders()
                ->setBorderStyle(Border::BORDER_THIN)
                ->getColor()
                ->setRGB('CBD5E1');

            // Centrar las calificaciones.
            $hoja->getStyle(
                "D{$primeraFilaDatos}:{$ultimaColumna}{$ultimaFilaDatos}"
            )
                ->getAlignment()
                ->setHorizontal(Alignment::HORIZONTAL_CENTER);

            // Destacar la columna de promedio.
            $hoja->getStyle(
                "{$ultimaColumna}{$primeraFilaDatos}:"
                . "{$ultimaColumna}{$ultimaFilaDatos}"
            )
                ->applyFromArray([
                    'font' => [
                        'bold' => true,
                        'color' => ['rgb' => '16324F'],
                    ],
                    'fill' => [
                        'fillType' => Fill::FILL_SOLID,
                        'startColor' => ['rgb' => 'E2E8F0'],
                    ],
                    'numberFormat' => [
                        'formatCode' => '0.00',
                    ],
                ]);

            // Separación entre semestres.
            $fila += 2;
        }

        // Ancho de columnas.
        $hoja->getColumnDimension('A')->setWidth(12);
        $hoja->getColumnDimension('B')->setWidth(25);
        $hoja->getColumnDimension('C')->setWidth(45);

        for ($i = 4; $i <= count($meses) + 4; $i++) {
            $columna = Coordinate::stringFromColumnIndex($i);

            $hoja->getColumnDimension($columna)
                ->setWidth(15);
        }

        // Información complementaria.
        $notas = [
            'La calificación considera las horas de sanción, '
            . 'los deméritos confirmados y los méritos vigentes aplicables.',
        ];

        if ($provisional) {
            $notas[] = 'El promedio es provisional. '
                . 'Los meses futuros no se contabilizan.';
        }

        if (count($meses) !== 6) {
            $notas[] = 'El período comprende ' . count($meses)
                . ' meses calendario. El promedio requiere validación '
                . 'antes de utilizarse como calificación semestral oficial.';
        }

        $notas[] = 'Fecha de generación: '
            . now()->format('d/m/Y H:i');

        foreach ($notas as $nota) {
            $hoja->mergeCells("A{$fila}:{$ultimaColumna}{$fila}");

            $hoja->setCellValue("A{$fila}", $nota);

            $hoja->getStyle("A{$fila}:{$ultimaColumna}{$fila}")
                ->getFont()
                ->setSize(9)
                ->getColor()
                ->setRGB('64748B');

            $hoja->getStyle("A{$fila}:{$ultimaColumna}{$fila}")
                ->getAlignment()
                ->setWrapText(true);

            $hoja->getRowDimension($fila)->setRowHeight(28);

            $fila++;
        }

        // Configuración de visualización.
        $hoja->setShowGridlines(false);
        $hoja->freezePane('D1');

        // Configuración de impresión.
        $hoja->getPageSetup()
            ->setOrientation(PageSetup::ORIENTATION_LANDSCAPE)
            ->setPaperSize(PageSetup::PAPERSIZE_LETTER)
            ->setFitToWidth(1)
            ->setFitToHeight(0)
            ->setPrintArea(
                "A1:{$ultimaColumna}" . ($fila - 1)
            );

        $hoja->getHeaderFooter()
            ->setOddFooter(
                '&LEscuela Naval de Guatemala&RPágina &P de &N'
            );

        return $excel;
    }
}
