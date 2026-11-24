<?php

namespace App\Filament\Resources\MeritoDisciplinarioResource\Pages;

use App\Filament\Resources\MeritoDisciplinarioResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditMeritoDisciplinario extends EditRecord
{
    protected static string $resource = MeritoDisciplinarioResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\DeleteAction::make(),
        ];
    }
}
