<?php

namespace Database\Seeders;

use App\Models\Semestre;
use Illuminate\Database\Seeder;

class SemestreSeeder extends Seeder
{
    public function run(): void
    {
        $semestres = [
            1 => 'Primer semestre',
            2 => 'Segundo semestre',
            3 => 'Tercer semestre',
            4 => 'Cuarto semestre',
            5 => 'Quinto semestre',
            6 => 'Sexto semestre',
            7 => 'Séptimo semestre',
            8 => 'Octavo semestre',
        ];

        foreach ($semestres as $numero => $nombre) {
            Semestre::firstOrCreate(
                ['numero' => $numero],
                [
                    'nombre' => $nombre,
                    'activo' => true,
                ]
            );
        }
    }
}
