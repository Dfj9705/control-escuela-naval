<?php

namespace App\Filament\Resources\OficialResource\Pages;

use App\Filament\Resources\OficialResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

class ListOficials extends ListRecords
{
    protected static string $resource = OficialResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make(),
        ];
    }
}
