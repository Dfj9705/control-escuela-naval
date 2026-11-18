<?php

namespace App\Filament\Resources\FaltaDisciplinariaResource\Pages;

use App\Filament\Resources\FaltaDisciplinariaResource;
use Filament\Resources\Pages\ListRecords;

class ListFaltaDisciplinarias extends ListRecords
{
    protected static string $resource = FaltaDisciplinariaResource::class;

    protected function getHeaderActions(): array
    {
        return [];
    }
}
