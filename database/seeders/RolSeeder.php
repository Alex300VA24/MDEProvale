<?php

namespace Database\Seeders;

use App\Models\Rol;
use App\Models\Module;
use App\Support\ModuleCatalog;
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
                'description' => 'Acceso a Inicio, Socios y Beneficiarios, Comités y Resoluciones, Supervisión, Evaluación Nutricional, Ayuda y chatbot',
                'modules' => [
                    ModuleCatalog::REGISTRO_SOCIOS_BENEFICIARIOS,
                    ModuleCatalog::PADRONES,
                    ModuleCatalog::EVALUACION_NUTRICIONAL,
                    ModuleCatalog::COMITES,
                    ModuleCatalog::RESOLUCIONES,
                    ModuleCatalog::SUPERVISION_COMITES,
                    ModuleCatalog::REPORTES,
                    ModuleCatalog::PERFIL,
                    ModuleCatalog::AYUDA,
                ],
            ],
            'Rol 2' => [
                'description' => 'Acceso a Inicio, Productos y Programación de Entrega, Pecosas y Repartición, Responsables y Raciones, Ayuda y chatbot',
                'modules' => [
                    ModuleCatalog::PRODUCTOS,
                    ModuleCatalog::REGISTRO_INGRESO,
                    ModuleCatalog::PROGRAMACION_ENTREGA,
                    ModuleCatalog::PECOSAS,
                    ModuleCatalog::REPARTICION,
                    ModuleCatalog::RESPONSABLES_RACIONES,
                    ModuleCatalog::REPORTES,
                    ModuleCatalog::PERFIL,
                    ModuleCatalog::AYUDA,
                ],
            ],
            'Rol 3' => [
                'description' => 'Acceso a todos los módulos operativos excepto Sistema, además de Inicio, Ayuda y chatbot',
                'modules' => ModuleCatalog::operationalLeaves(),
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
