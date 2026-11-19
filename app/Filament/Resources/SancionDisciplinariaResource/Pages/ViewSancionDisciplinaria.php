<?php

namespace App\Filament\Resources\SancionDisciplinariaResource\Pages;

use App\Filament\Resources\SancionDisciplinariaResource;
use App\Models\SancionDisciplinaria;
use Filament\Actions;
use Filament\Resources\Pages\ViewRecord;

class ViewSancionDisciplinaria extends ViewRecord
{
    protected static string $resource =
        SancionDisciplinariaResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\Action::make('imprimirBoleta')
                ->label('Imprimir boleta')
                ->icon('heroicon-o-printer')
                ->color('primary')
                ->visible(
                    fn(): bool =>
                        auth()->user()?->can('sanciones_disciplinarias.imprimir')
                        && $this->record->tipo_sancion ===
                        SancionDisciplinaria::TIPO_HORAS
                        && $this->record->estado !==
                        SancionDisciplinaria::ESTADO_ANULADA
                )
                ->url(
                    fn(): string =>
                        route('disciplina.boleta-arresto', [
                            'sancion' => $this->record->id,
                        ])
                )
                ->openUrlInNewTab(),
        ];
    }
}
