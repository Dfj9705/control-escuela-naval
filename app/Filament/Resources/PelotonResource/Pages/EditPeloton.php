<?php

namespace App\Filament\Resources\PelotonResource\Pages;

use App\Filament\Resources\PelotonResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditPeloton extends EditRecord
{
    protected static string $resource = PelotonResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\DeleteAction::make(),
        ];
    }
}
