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
            // Usuarios
            'usuarios.ver',
            'usuarios.crear',
            'usuarios.editar',
            'usuarios.eliminar',

            // Roles
            'roles.ver',
            'roles.crear',
            'roles.editar',
            'roles.eliminar',

            // Permisos
            'permisos.ver',

            // Cadetes
            'cadetes.ver',
            'cadetes.crear',
            'cadetes.editar',
            'cadetes.eliminar',

            // Períodos académicos
            'periodos.ver',
            'periodos.crear',
            'periodos.editar',

            // Asignaciones
            'asignaciones.ver',
            'asignaciones.crear',
            'asignaciones.editar',

            // Expediente personal
            'mi_expediente.ver',

            // Catálogo disciplinario
            'faltas_disciplinarias.ver',

            // Sanciones disciplinarias
            'sanciones_disciplinarias.ver',
            'sanciones_disciplinarias.crear',
            'sanciones_disciplinarias.imprimir',
            'sanciones_disciplinarias.adjuntar',
            'sanciones_disciplinarias.confirmar',
            'sanciones_disciplinarias.anular',
            'sanciones_disciplinarias.ver_propias',

            'pelotones.ver',
            'pelotones.crear',
            'pelotones.editar',
            'pelotones.eliminar',
            'cadete_pelotones.asignar',
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

        // Cadete: consulta de su información
        Role::findByName('cadete', 'web')
            ->givePermissionTo([
                'mi_expediente.ver',
                'sanciones_disciplinarias.ver_propias',
            ]);

        // Administrador: consulta del catálogo
        Role::findByName('administrador', 'web')
            ->givePermissionTo([
                'faltas_disciplinarias.ver',
                'pelotones.ver',
                'pelotones.crear',
                'pelotones.editar',
                'cadete_pelotones.asignar',
            ]);

        // Operador: registro y gestión documental
        Role::findByName('operador_cadetes', 'web')
            ->givePermissionTo([
                'faltas_disciplinarias.ver',
                'sanciones_disciplinarias.ver',
                'sanciones_disciplinarias.crear',
                'sanciones_disciplinarias.imprimir',
                'sanciones_disciplinarias.adjuntar',
                'pelotones.ver',
                'pelotones.crear',
                'pelotones.editar',
                'cadete_pelotones.asignar',
            ]);

        Role::findByName('consulta', 'web')
            ->givePermissionTo([

                'pelotones.ver',

            ]);

        app(PermissionRegistrar::class)->forgetCachedPermissions();
    }
}
