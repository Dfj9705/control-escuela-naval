<?php

declare(strict_types=1);

namespace Database\Seeders;

use App\Models\FaltaDisciplinaria;
use Illuminate\Database\Seeder;
use RuntimeException;

class FaltasDisciplinariasSeeder extends Seeder
{
    private const VERSION = '2026';

    private const HEADER = [
        'tipo',
        'numeral',
        'categoria',
        'descripcion',
        'tipo_sancion',
        'cantidad_base',
        'activo',
    ];

    private const RULES = [
        'leve' => ['total' => 203, 'section' => 'VI', 'sanction' => 'horas', 'active' => '1'],
        'grave' => ['total' => 147, 'section' => 'VII', 'sanction' => 'demeritos', 'active' => '1'],
        'gravisima' => ['total' => 44, 'section' => 'VIII', 'sanction' => 'especial', 'active' => '0'],
    ];

    public function run(): void
    {
        $catalog = $this->readCatalog(database_path('seeders/data/faltas_disciplinarias_2026.csv'));
        $connection = (new FaltaDisciplinaria())->getConnection();

        FaltaDisciplinaria::withoutEvents(function () use ($connection, $catalog): void {
            $connection->transaction(function () use ($catalog): void {
                foreach ($catalog as $type => $entries) {
                    foreach ($entries as $number => $attributes) {
                        FaltaDisciplinaria::updateOrCreate(
                            [
                                'version_reglamento' => self::VERSION,
                                'tipo' => $type,
                                'numeral' => $number,
                            ],
                            $attributes,
                        );
                    }
                }
            });
        });

        $counts = array_map('count', $catalog);
        $this->command?->info(sprintf(
            'Catálogo %s: %d registros procesados (%d leves, %d graves y %d gravísimas inactivas).',
            self::VERSION,
            array_sum($counts),
            $counts['leve'],
            $counts['grave'],
            $counts['gravisima'],
        ));
    }

    private function readCatalog(string $path): array
    {
        if (!is_file($path)) {
            throw new RuntimeException("Archivo CSV inexistente: {$path}");
        }

        $stream = @fopen($path, 'rb');
        if ($stream === false) {
            throw new RuntimeException("No se pudo abrir el archivo CSV: {$path}");
        }

        $catalog = array_fill_keys(array_keys(self::RULES), []);
        try {
            $header = fgetcsv($stream, 0, ',', '"', '');
            if (is_array($header) && str_starts_with($header[0] ?? '', "\xEF\xBB\xBF")) {
                $header[0] = substr($header[0], 3);
            }
            if ($header !== self::HEADER) {
                throw new RuntimeException('Encabezado CSV inválido. Se esperaba: ' . implode(',', self::HEADER));
            }

            $record = 1;
            while (($row = fgetcsv($stream, 0, ',', '"', '')) !== false) {
                $record++;
                if (count($row) !== count(self::HEADER) || !mb_check_encoding($row, 'UTF-8')) {
                    throw new RuntimeException("Registro CSV {$record}: columnas o codificación inválidas.");
                }

                [$type, $number, $category, $description, $sanction, $quantity, $active] = $row;
                $rule = self::RULES[$type] ?? null;
                if (
                    $rule === null || !preg_match('/\A[1-9][0-9]{0,2}\z/', $number)
                    || (int) $number > $rule['total']
                ) {
                    throw new RuntimeException("Registro CSV {$record}: clasificación o numeral inválido.");
                }

                $number = (int) $number;
                if (isset($catalog[$type][$number])) {
                    throw new RuntimeException("Registro CSV {$record}: clave duplicada {$type}/{$number}.");
                }
                if (
                    trim($category) === '' || mb_strlen($category, 'UTF-8') > 150
                    || trim($description) === '' || strlen($description) > 65535
                    || str_contains($description, "\0") || str_contains($category, "\0")
                ) {
                    throw new RuntimeException("Registro CSV {$record}: categoría o descripción inválida.");
                }
                if ($sanction !== $rule['sanction'] || $active !== $rule['active']) {
                    throw new RuntimeException("Registro CSV {$record}: tipo de sanción o estado inválido.");
                }
                if ($type === 'gravisima') {
                    if ($quantity !== '' || $category !== 'FALTAS GRAVÍSIMAS') {
                        throw new RuntimeException("Registro CSV {$record}: categoría o cantidad gravísima inválida.");
                    }
                } elseif (!preg_match('/\A[1-9][0-9]{0,4}\z/', $quantity) || (int) $quantity > 65535) {
                    throw new RuntimeException("Registro CSV {$record}: cantidad base inválida.");
                }

                $reference = "Tabla de Sanciones Disciplinarias 2026, sección {$rule['section']}, numeral {$number}.";
                if ($type === 'leve' && $number === 130) {
                    if (
                        $description !== 'Fusil sucio.' || $quantity !== '8'
                        || $category !== 'EN SU ARMAMENTO Y EQUIPO'
                    ) {
                        throw new RuntimeException("Registro CSV {$record}: la fila provisional 130 difiere del reglamento.");
                    }
                    $reference = 'Tabla de Sanciones Disciplinarias 2026, sección VI, pág. 17, '
                        . 'numeral impreso 1 entre 129 y 131. Clave 130 provisional, pendiente de confirmación institucional.';
                }

                $catalog[$type][$number] = [
                    'categoria' => $category,
                    'descripcion' => $description,
                    'tipo_sancion' => $sanction,
                    'cantidad_base' => $quantity === '' ? null : (int) $quantity,
                    'fundamento_legal' => $reference,
                    'activo' => $active === '1',
                ];
            }

            if (!feof($stream)) {
                throw new RuntimeException("Error al leer el archivo CSV: {$path}");
            }
        } finally {
            fclose($stream);
        }

        foreach (self::RULES as $type => $rule) {
            $missing = array_diff(range(1, $rule['total']), array_keys($catalog[$type]));
            if ($missing !== []) {
                throw new RuntimeException("Numerales faltantes en {$type}: " . implode(', ', $missing));
            }
        }

        return $catalog;
    }
}
