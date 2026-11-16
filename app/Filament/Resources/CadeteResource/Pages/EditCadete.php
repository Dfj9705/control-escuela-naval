<?php

namespace App\Filament\Resources\CadeteResource\Pages;

use App\Filament\Resources\CadeteResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditCadete extends EditRecord
{
    protected static string $resource = CadeteResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\DeleteAction::make(),
        ];
    }
}
