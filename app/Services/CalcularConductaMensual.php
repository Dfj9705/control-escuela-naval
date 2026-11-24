<?php

namespace App\Services;

use App\Models\Cadete;
use App\Models\MeritoDisciplinario;
use App\Models\SancionDisciplinaria;
use Carbon\Carbon;

class CalcularConductaMensual
{
    public function calcular(
        Cadete $cadete,
        int $anio,
        int $mes
    ): array {
        $inicio = Carbon::create($anio, $mes, 1)->startOfMonth();
        $fin = $inicio->copy()->endOfMonth();

        $sanciones = $cadete->sancionesDisciplinarias()
            ->where(
                'estado',
                SancionDisciplinaria::ESTADO_CONFIRMADA
            )
            ->whereBetween('fecha_falta', [
                $inicio->toDateString(),
                $fin->toDateString(),
            ])
            ->selectRaw(
                'tipo_sancion, COALESCE(SUM(cantidad_aplicada), 0) AS total'
            )
            ->groupBy('tipo_sancion')
            ->pluck('total', 'tipo_sancion');

        $horas = (int) (
            $sanciones[SancionDisciplinaria::TIPO_HORAS] ?? 0
        );

        $demeritos = (int) (
            $sanciones[SancionDisciplinaria::TIPO_DEMERITOS] ?? 0
        );

        $meritos = (int) $cadete->meritosDisciplinarios()
            ->where(
                'estado',
                MeritoDisciplinario::ESTADO_VIGENTE
            )
            ->whereBetween('fecha_merito', [
                $inicio->toDateString(),
                $fin->toDateString(),
            ])
            ->sum('cantidad');

        return [
            'anio' => $anio,
            'mes' => $mes,
            ...$this->calcularDesdeTotales(
                $horas,
                $demeritos,
                $meritos
            ),
        ];
    }


    public function calcularDesdeTotales(
        int $horas,
        int $demeritos,
        int $meritos
    ): array {
        $meritosAplicados = min($meritos, $demeritos);

        $demeritosNetos = max(
            0,
            $demeritos - $meritosAplicados
        );

        $nota = 100 - $horas - (2 * $demeritosNetos);

        return [
            'horas' => $horas,
            'demeritos' => $demeritos,
            'meritos' => $meritos,
            'meritos_aplicados' => $meritosAplicados,
            'demeritos_netos' => $demeritosNetos,
            'nota' => $nota,
        ];
    }

}
