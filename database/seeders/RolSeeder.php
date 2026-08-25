<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class RolSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        DB::table('rols')->insert([
            'title' => 'Administrador',
            'description' => 'Acceso completo al sistema',
            'is_active' => true,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        DB::table('rols')->insert([
            'title' => 'Usuario Principal',
            'description' => 'Acceso a todos los módulos excepto Responsables y Raciones, Reportes y Sistema',
            'is_active' => true,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        DB::table('rols')->insert([
            'title' => 'Usuario Básico',
            'description' => 'Acceso a un solo módulo',
            'is_active' => true,
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }
}
