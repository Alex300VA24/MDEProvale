<?php

namespace Database\Seeders;

use App\Models\Rol;
use App\Models\Module;
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
        $operationalRoles = [
            'Rol 1' => [
                'description' => 'Acceso a Inicio, Socios y Beneficiarios, Comités y Resoluciones, Ayuda y chatbot',
                'modules' => ['socios-beneficiarios', 'club-madres', 'reconocimientos', 'reportes'],
            ],
            'Rol 2' => [
                'description' => 'Acceso a Inicio, Productos y Pecosas, Transacciones y Repartición, Responsables y Raciones, Ayuda y chatbot',
                'modules' => ['productos', 'pecosas', 'movimientos', 'responsables-raciones', 'reportes'],
            ],
            'Rol 3' => [
                'description' => 'Acceso a todos los módulos operativos excepto Sistema, además de Inicio, Ayuda y chatbot',
                'modules' => [
                    'socios-beneficiarios',
                    'club-madres',
                    'reconocimientos',
                    'productos',
                    'pecosas',
                    'movimientos',
                    'responsables-raciones',
                    'reportes',
                ],
            ],
        ];

        $roles = [
            'Administrador' => 'Acceso completo al sistema',
            'Usuario Principal' => 'Acceso a todos los módulos excepto Responsables y Raciones, Reportes y Sistema',
            'Usuario Básico' => 'Acceso a un solo módulo',
            Rol::PRESIDENT => 'Acceso exclusivo al portal de consultas del comité asignado',
        ];

        foreach ($operationalRoles as $title => $preset) {
            $roles[$title] = $preset['description'];
        }

        foreach ($roles as $title => $description) {
            Rol::updateOrCreate(
                ['title' => $title],
                ['description' => $description, 'is_active' => true]
            );
        }

        foreach ($operationalRoles as $title => $preset) {
            $permissions = Module::query()
                ->whereIn('slug', $preset['modules'])
                ->pluck('id')
                ->mapWithKeys(fn ($moduleId) => [$moduleId => [
                    'can_view' => true,
                    'can_create' => true,
                    'can_edit' => true,
                    'can_delete' => true,
                ]])
                ->all();

            Rol::where('title', $title)->firstOrFail()->modules()->sync($permissions);
        }
    }
}
