<?php

namespace App\Filament\Resources\PeriodoAcademicoResource\Pages;

use App\Filament\Resources\PeriodoAcademicoResource;
use App\Services\PeriodoAcademicoService;
use Filament\Resources\Pages\CreateRecord;
use Illuminate\Database\Eloquent\Model;

class CreatePeriodoAcademico extends CreateRecord
{
    protected static string $resource = PeriodoAcademicoResource::class;

    protected function handleRecordCreation(array $data): Model
    {
        return app(PeriodoAcademicoService::class)->guardar($data);
    }
}
