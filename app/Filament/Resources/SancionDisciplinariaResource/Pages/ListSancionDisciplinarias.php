<?php

namespace App\Filament\Resources\SancionDisciplinariaResource\Pages;

use App\Filament\Resources\SancionDisciplinariaResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

class ListSancionDisciplinarias extends ListRecords
{
    protected static string $resource = SancionDisciplinariaResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make(),
        ];
    }
}
