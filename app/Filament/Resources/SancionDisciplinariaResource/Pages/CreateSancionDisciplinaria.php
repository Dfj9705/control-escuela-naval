<?php

namespace App\Filament\Resources\SancionDisciplinariaResource\Pages;

use App\Filament\Resources\SancionDisciplinariaResource;
use App\Models\SancionDisciplinaria;
use App\Services\Disciplina\RegistrarSancionDisciplinaria;
use Filament\Resources\Pages\CreateRecord;
use Illuminate\Database\Eloquent\Model;

class CreateSancionDisciplinaria extends CreateRecord
{
    protected static string $resource =
        SancionDisciplinariaResource::class;

    protected function handleRecordCreation(
        array $data
    ): Model {
        return app(RegistrarSancionDisciplinaria::class)
            ->handle(
                $data,
                auth()->user()
            );
    }

    protected function getCreatedNotificationTitle(): ?string
    {
        return 'Sanción disciplinaria registrada correctamente';
    }

    protected function getRedirectUrl(): string
    {
        return static::getResource()::getUrl('index');
    }
}
