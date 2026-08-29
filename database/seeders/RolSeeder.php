<?php

namespace Database\Seeders;

use App\Models\Rol;
use Illuminate\Database\Seeder;

class RolSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        $roles = [
            'Administrador' => 'Acceso completo al sistema',
            'Usuario Principal' => 'Acceso a todos los módulos excepto Responsables y Raciones, Reportes y Sistema',
            'Usuario Básico' => 'Acceso a un solo módulo',
            Rol::PRESIDENT => 'Acceso exclusivo al portal de consultas del comité asignado',
        ];

        foreach ($roles as $title => $description) {
            Rol::updateOrCreate(
                ['title' => $title],
                ['description' => $description, 'is_active' => true]
            );
        }
    }
}
