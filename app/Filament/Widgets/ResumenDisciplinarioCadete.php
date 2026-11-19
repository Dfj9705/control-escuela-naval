<?php

namespace App\Filament\Widgets;

use App\Models\Cadete;
use App\Models\PeriodoAcademico;
use Filament\Widgets\StatsOverviewWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;

class ResumenDisciplinarioCadete extends StatsOverviewWidget
{
    public ?Cadete $record = null;


    protected function getStats(): array
    {
        if (!$this->record) {
            return [];
        }

        $cadete = $this->record;

        // Indicadores históricos.
        $stats = [
            Stat::make(
                'Sanciones confirmadas',
                $cadete->totalSancionesConfirmadas()
            )
                ->icon('heroicon-o-check-circle')
                ->color('success'),

            Stat::make(
                'Sanciones pendientes',
                $cadete->totalSancionesPendientes()
            )
                ->icon('heroicon-o-clock')
                ->color('warning'),

            Stat::make(
                'Sanciones anuladas',
                $cadete->totalSancionesAnuladas()
            )
                ->icon('heroicon-o-x-circle')
                ->color('danger'),

            Stat::make(
                'Deméritos confirmados',
                $cadete->totalDemeritosConfirmados()
            )
                ->description('Acumulado histórico')
                ->icon('heroicon-o-clipboard-document-list')
                ->color('info'),
        ];

        // Buscar el período académico activo.
        $periodo = PeriodoAcademico::query()
            ->where('activo', true)
            ->first();

        if (!$periodo) {
            return $stats;
        }

        // Obtener la situación disciplinaria del cadete.
        $situacion = $cadete->situacionDemeritosEnPeriodo(
            $periodo
        );

        if ($situacion === null) {
            return $stats;
        }


        $asignacion = $cadete->asignacionesSemestre()
            ->where('periodo_academico_id', $periodo->id)
            ->with('semestre')
            ->first();

        $alerta = $asignacion?->semestre?->alertaDemeritos(
            $situacion['acumulados']
        );


        $acumulados = $situacion['acumulados'];
        $limite = $situacion['limite'];
        $restantes = $situacion['restantes'];
        $excedidos = $situacion['excedidos'];

        $alcanzoLimite = $acumulados >= $limite;

        $stats[] = Stat::make(
            'Deméritos del período',
            $acumulados
        )
            ->description(
                $situacion['semestre'] .
                ' — ' . $situacion['porcentaje'] . '% del límite'
            )
            ->icon('heroicon-o-chart-bar')
            ->color($alcanzoLimite ? 'danger' : 'info');

        $stats[] = Stat::make(
            'Límite reglamentario',
            $limite
        )
            ->description(
                'Máximo para ' . $situacion['semestre']
            )
            ->icon('heroicon-o-scale')
            ->color('gray');

        $stats[] = Stat::make(
            'Deméritos restantes',
            $restantes
        )
            ->description(
                $excedidos > 0
                ? "Límite excedido por {$excedidos} deméritos"
                : (
                    $alcanzoLimite
                    ? 'Límite reglamentario alcanzado'
                    : 'Antes de alcanzar el límite'
                )
            )
            ->icon(
                $alcanzoLimite
                ? 'heroicon-o-exclamation-triangle'
                : 'heroicon-o-shield-check'
            )
            ->color(
                $alcanzoLimite ? 'danger' : 'success'
            );


        if ($alerta !== null) {
            $stats[] = Stat::make(
                'Alerta disciplinaria',
                $alerta['nivel'] . '%'
            )
                ->description(
                    'Corresponde: ' . $alerta['responsable']
                )
                ->descriptionIcon('heroicon-o-exclamation-triangle')
                ->icon('heroicon-o-bell-alert')
                ->color($alerta['color']);
        }


        return $stats;
    }

}
