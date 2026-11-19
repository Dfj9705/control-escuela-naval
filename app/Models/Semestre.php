<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class Semestre extends Model
{
    use HasFactory;


    public const LIMITES_DEMERITOS = [
        1 => 100,
        2 => 95,
        3 => 90,
        4 => 85,
        5 => 80,
        6 => 75,
        7 => 70,
        8 => 65,
    ];


    public const UMBRALES_ALERTA_DEMERITOS = [
        1 => [40 => 40, 60 => 60, 80 => 80],
        2 => [40 => 38, 60 => 57, 80 => 76],
        3 => [40 => 36, 60 => 54, 80 => 72],
        4 => [40 => 34, 60 => 51, 80 => 68],
        5 => [40 => 32, 60 => 48, 80 => 64],
        6 => [40 => 30, 60 => 45, 80 => 60],
        7 => [40 => 28, 60 => 45, 80 => 56],
        8 => [40 => 26, 60 => 39, 80 => 52],
    ];



    protected $fillable = [
        'numero',
        'nombre',
        'activo',
    ];

    protected function casts(): array
    {
        return [
            'numero' => 'integer',
            'activo' => 'boolean',
        ];
    }


    public function limiteDemeritos(): ?int
    {
        return self::LIMITES_DEMERITOS[$this->numero] ?? null;
    }


    public function alertaDemeritos(int $acumulados): ?array
    {

        $umbrales = self::UMBRALES_ALERTA_DEMERITOS[
            $this->numero
        ] ?? null;


        if ($umbrales === null) {
            return null;
        }


        if ($acumulados >= $umbrales[80]) {
            return [
                'nivel' => 80,
                'responsable' => 'Comandante de Cuerpo',
                'color' => 'danger',
                'mensaje' => 'Alerta preventiva del 80%',
            ];
        }

        if ($acumulados >= $umbrales[60]) {
            return [
                'nivel' => 60,
                'responsable' => 'Comandante de Compañía',
                'color' => 'warning',
                'mensaje' => 'Alerta preventiva del 60%',
            ];
        }

        if ($acumulados >= $umbrales[40]) {
            return [
                'nivel' => 40,
                'responsable' => 'Comandante de Pelotón',
                'color' => 'info',
                'mensaje' => 'Alerta preventiva del 40%',
            ];
        }

        return null;
    }


}
