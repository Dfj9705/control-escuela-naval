<?php

namespace App\Filament\Resources\PelotonResource\Pages;

use App\Filament\Resources\PelotonResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

class ListPelotons extends ListRecords
{
    protected static string $resource = PelotonResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make(),
        ];
    }
}
