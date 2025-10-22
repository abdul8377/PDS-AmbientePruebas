<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Role;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\PermissionRegistrar;

class RolesAndPermissionsSeeder extends Seeder
{
    public function run(): void
    {
        // Limpia la caché de permisos/roles
        app()[PermissionRegistrar::class]->forgetCachedPermissions();

        $guard = 'web';

        // Define permisos (ajústalos a tus necesidades)
        $permissions = [
            // Usuarios
            'usuarios.ver',
            'usuarios.crear',
            'usuarios.editar',
            'usuarios.eliminar',

            // Cursos / contenido
            'cursos.ver',
            'cursos.crear',
            'cursos.editar',
            'cursos.eliminar',

            // Reportes
            'reportes.ver',
        ];

        // Crea permisos si no existen
        foreach ($permissions as $perm) {
            Permission::firstOrCreate([
                'name'       => $perm,
                'guard_name' => $guard,
            ]);
        }

        // Crea roles
        $administrador = Role::firstOrCreate(['name' => 'ADMINISTRADOR', 'guard_name' => $guard]);
        $coordinador   = Role::firstOrCreate(['name' => 'COORDINADOR',   'guard_name' => $guard]);
        $encargado     = Role::firstOrCreate(['name' => 'ENCARGADO',     'guard_name' => $guard]);
        $estudiante    = Role::firstOrCreate(['name' => 'ESTUDIANTE',    'guard_name' => $guard]);

        // Asigna permisos por rol (ajustable)
        // Admin: todo
        $administrador->syncPermissions(Permission::pluck('name'));

        // Coordinador: gestiona cursos, ve usuarios y reportes
        $coordinador->syncPermissions([
            'cursos.ver', 'cursos.crear', 'cursos.editar', 'cursos.eliminar',
            'usuarios.ver',
            'reportes.ver',
        ]);

        // Encargado: apoyo operativo sobre cursos
        $encargado->syncPermissions([
            'cursos.ver', 'cursos.editar',
        ]);

        // Estudiante: solo lectura de cursos
        $estudiante->syncPermissions([
            'cursos.ver',
        ]);
    }
}
