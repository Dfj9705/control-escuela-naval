<?php

namespace App\Filament\Resources\MeritoDisciplinarioResource\Pages;

use App\Filament\Resources\MeritoDisciplinarioResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

class ListMeritoDisciplinarios extends ListRecords
{
    protected static string $resource = MeritoDisciplinarioResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make(),
        ];
    }
}
