<?php

namespace App\Filament\Resources\PeriodoAcademicoResource\Pages;

use App\Filament\Resources\PeriodoAcademicoResource;
use App\Services\PeriodoAcademicoService;
use Filament\Resources\Pages\EditRecord;
use Illuminate\Database\Eloquent\Model;

class EditPeriodoAcademico extends EditRecord
{
    protected static string $resource = PeriodoAcademicoResource::class;

    protected function handleRecordUpdate(
        Model $record,
        array $data
    ): Model {
        return app(PeriodoAcademicoService::class)
            ->guardar($data, $record);
    }

    protected function getHeaderActions(): array
    {
        return [];
    }
}
