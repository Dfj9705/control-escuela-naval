<?php

namespace App\Filament\Resources\SancionDisciplinariaResource\Pages;

use App\Filament\Resources\SancionDisciplinariaResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditSancionDisciplinaria extends EditRecord
{
    protected static string $resource = SancionDisciplinariaResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\DeleteAction::make(),
        ];
    }
}
