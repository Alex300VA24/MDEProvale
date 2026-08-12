<?php

namespace Tests\Traits;

use Illuminate\Support\Facades\DB;

trait SeedsBaseData
{
    /**
     * Inserta los datos base mínimos que requieren los tests que consumen la BD:
     * un estado, el rol Administrador, el módulo socios-beneficiarios con su
     * permiso module_rol y un usuario con acceso.
     */
    protected function seedBaseData(): void
    {
        DB::table('states')->insert([
            'id' => 1,
            'title' => 'Activo',
            'abbreviation' => 'A',
        ]);

        DB::table('rols')->insert([
            'id' => 1,
            'title' => 'Administrador',
            'description' => 'Acceso completo al sistema',
        ]);

        $moduleId = DB::table('modules')->insertGetId([
            'name' => 'Socios y Beneficiarios',
            'slug' => 'socios-beneficiarios',
            'description' => 'Gestión de socios y beneficiarios',
            'icon' => 'fa-users',
            'route' => 'socios-beneficiarios',
            'order' => 1,
            'is_active' => true,
        ]);

        DB::table('module_rol')->insert([
            'module_id' => $moduleId,
            'rol_id' => 1,
            'can_view' => true,
            'can_create' => true,
            'can_edit' => true,
            'can_delete' => true,
        ]);

        DB::table('users')->insert([
            'id' => 1,
            'names' => 'Test',
            'father_surname' => 'Usuario',
            'mother_surname' => 'Prueba',
            'username' => 'testadmin',
            'email' => 'testadmin@example.com',
            'dni' => '00000001',
            'cui' => '0',
            'state_id' => 1,
            'rol_id' => 1,
            'password' => bcrypt('password'),
        ]);
    }

    protected function adminUser(): \App\Models\User
    {
        return \App\Models\User::findOrFail(1);
    }
}
