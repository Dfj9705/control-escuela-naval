<?php

namespace App\Filament\Resources\UserResource\Pages;

use App\Filament\Resources\UserResource;
use App\Support\UserRoleGuard;
use Filament\Resources\Pages\EditRecord;

class EditUser extends EditRecord
{
    protected static string $resource = UserResource::class;

    protected function beforeSave(): void
    {
        UserRoleGuard::validate(
            $this->form->getState()['roles'] ?? [],
            $this->record
        );
    }

    protected function getHeaderActions(): array
    {
        return [];
    }
}
