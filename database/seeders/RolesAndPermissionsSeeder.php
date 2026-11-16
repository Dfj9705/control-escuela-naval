<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Spatie\Permission\PermissionRegistrar;

class RolesAndPermissionsSeeder extends Seeder
{
    public function run(): void
    {
        app(PermissionRegistrar::class)->forgetCachedPermissions();

        $permissions = [
            'usuarios.ver',
            'usuarios.crear',
            'usuarios.editar',
            'usuarios.eliminar',

            'roles.ver',
            'roles.crear',
            'roles.editar',
            'roles.eliminar',

            'permisos.ver',

            'cadetes.ver',
            'cadetes.crear',
            'cadetes.editar',
            'cadetes.eliminar',

            'periodos.ver',
            'periodos.crear',
            'periodos.editar',

            'asignaciones.ver',
            'asignaciones.crear',
            'asignaciones.editar',

            'mi_expediente.ver',
        ];

        foreach ($permissions as $permission) {
            Permission::firstOrCreate([
                'name' => $permission,
                'guard_name' => 'web',
            ]);
        }

        $roles = [
            'superadministrador',
            'administrador',
            'operador_cadetes',
            'consulta',
            'cadete',
        ];

        foreach ($roles as $role) {
            Role::firstOrCreate([
                'name' => $role,
                'guard_name' => 'web',
            ]);
        }

        Role::findByName('cadete', 'web')
            ->givePermissionTo('mi_expediente.ver');

        app(PermissionRegistrar::class)->forgetCachedPermissions();
    }
}
