<?php

namespace App\Filament\Resources\FaltaDisciplinariaResource\Pages;

use App\Filament\Resources\FaltaDisciplinariaResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditFaltaDisciplinaria extends EditRecord
{
    protected static string $resource = FaltaDisciplinariaResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\DeleteAction::make(),
        ];
    }
}
