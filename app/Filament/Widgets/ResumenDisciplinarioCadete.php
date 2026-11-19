<?php

namespace App\Filament\Widgets;

use App\Models\Cadete;
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

        return [
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
                ->icon('heroicon-o-clipboard-document-list')
                ->color('info'),
        ];
    }
}
