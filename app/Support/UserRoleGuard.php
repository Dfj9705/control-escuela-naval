<?php

namespace App\Support;

use App\Models\User;
use Illuminate\Validation\ValidationException;
use Spatie\Permission\Models\Role;

class UserRoleGuard
{
    public static function validate(
        array $roleIds,
        ?User $user = null
    ): void {
        $roleIds = array_map('intval', $roleIds);

        $superRole = Role::findByName(
            'superadministrador',
            'web'
        );

        // Evitar que el usuario actual se quite sus privilegios.
        if (
            $user?->exists
            && $user->is(auth()->user())
            && $user->hasRole('superadministrador')
            && !in_array($superRole->id, $roleIds, true)
        ) {
            throw ValidationException::withMessages([
                'data.roles' =>
                    'No puedes quitarte tu propio rol de superadministrador.',
            ]);
        }

        // Proteger al último superadministrador.
        if (
            $user?->exists
            && $user->hasRole('superadministrador')
            && !in_array($superRole->id, $roleIds, true)
            && User::role('superadministrador')->count() <= 1
        ) {
            throw ValidationException::withMessages([
                'data.roles' =>
                    'No puedes quitar el rol al último superadministrador.',
            ]);
        }
    }
}
