<?php

namespace App\Filament\Resources\MeritoDisciplinarioResource\Pages;

use App\Filament\Resources\MeritoDisciplinarioResource;
use App\Models\MeritoDisciplinario;
use Filament\Actions;
use Filament\Resources\Pages\CreateRecord;

class CreateMeritoDisciplinario extends CreateRecord
{
    protected static string $resource = MeritoDisciplinarioResource::class;


    protected function mutateFormDataBeforeCreate(array $data): array
    {
        $data['registrado_por'] = auth()->id();

        $data['estado'] = MeritoDisciplinario::ESTADO_VIGENTE;

        return $data;
    }

}
