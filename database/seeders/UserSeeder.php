<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class UserSeeder extends Seeder
{
    public function run()
    {
        DB::table('users')->insert([
            'names' => 'Larri Rodrigo',
            'father_surname' => 'Estrada',
            'mother_surname' => 'León',
            'username' => 'lestradal',
            'password' => bcrypt('admin'),
            'dni' => '71086437',
            'cui' => '9',
            'email' => 'lestradal@example.com',
            'rol_id' => 1,
            'state_id' => 1,
            'created_at' => now(),
            'updated_at' => now(),
        ]);
        
        DB::table('users')->insert([
            'names' => 'Miguel Angel',
            'father_surname' => 'Perez',
            'mother_surname' => 'Vega',
            'username' => 'mvegape',
            'password' => bcrypt('admin'),
            'dni' => '74283707',
            'cui' => '1',
            'email' => 'mvegape@example.com',
            'rol_id' => 2,
            'state_id' => 1,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        DB::table('users')->insert([
            'names' => 'Usuario',
            'father_surname' => 'Basico',
            'mother_surname' => 'Test',
            'username' => 'usuario1',
            'password' => bcrypt('admin'),
            'dni' => '12345678',
            'cui' => '2',
            'email' => 'usuario1@example.com',
            'rol_id' => 3,
            'state_id' => 1,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $modules = DB::table('modules')->get();
        $moduleIds = $modules->pluck('id')->toArray();

        $adminPermissions = [];
        foreach ($moduleIds as $moduleId) {
            $adminPermissions[$moduleId] = [
                'can_view' => true,
                'can_create' => true,
                'can_edit' => true,
                'can_delete' => true,
            ];
        }
        DB::table('module_rol')->insert(array_map(function($moduleId) use ($adminPermissions) {
            return [
                'module_id' => $moduleId,
                'rol_id' => 1,
                'can_view' => true,
                'can_create' => true,
                'can_edit' => true,
                'can_delete' => true,
                'created_at' => now(),
                'updated_at' => now(),
            ];
        }, $moduleIds));

        $excludedModules = [7, 8];
        $mainUserPermissions = [];
        foreach ($moduleIds as $moduleId) {
            if (!in_array($moduleId, $excludedModules)) {
                $mainUserPermissions[$moduleId] = [
                    'can_view' => true,
                    'can_create' => true,
                    'can_edit' => true,
                    'can_delete' => true,
                ];
            }
        }
        foreach ($mainUserPermissions as $moduleId => $perms) {
            DB::table('module_rol')->insert([
                'module_id' => $moduleId,
                'rol_id' => 2,
                'can_view' => true,
                'can_create' => true,
                'can_edit' => true,
                'can_delete' => true,
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }

        DB::table('module_rol')->insert([
            'module_id' => 1,
            'rol_id' => 3,
            'can_view' => true,
            'can_create' => true,
            'can_edit' => true,
            'can_delete' => false,
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }
}