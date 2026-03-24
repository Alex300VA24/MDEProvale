<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class ModuleSeeder extends Seeder
{
    public function run()
    {
        $modules = [
            ['name' => 'Socios y Beneficiarios', 'slug' => 'socios-beneficiarios', 'description' => 'Gestión de socios y beneficiarios', 'icon' => 'fa-users', 'route' => 'socios-beneficiarios', 'order' => 1],
            ['name' => 'Club de Madres', 'slug' => 'club-madres', 'description' => 'Gestión de club de madres', 'icon' => 'fa-female', 'route' => 'club-reconocimientos', 'order' => 2],
            ['name' => 'Reconocimientos', 'slug' => 'reconocimientos', 'description' => 'Gestión de reconocimientos', 'icon' => 'fa-award', 'route' => 'club-reconocimientos', 'order' => 3],
            ['name' => 'Productos', 'slug' => 'productos', 'description' => 'Gestión de productos', 'icon' => 'fa-box', 'route' => 'productos-pecosas', 'order' => 4],
            ['name' => 'Pecosas', 'slug' => 'pecosas', 'description' => 'Gestión de pecosas', 'icon' => 'fa-file-alt', 'route' => 'productos-pecosas', 'order' => 5],
            ['name' => 'Movimientos', 'slug' => 'movimientos', 'description' => 'Gestión de movimientos', 'icon' => 'fa-exchange-alt', 'route' => 'movimientos', 'order' => 6],
            ['name' => 'Mantenimiento', 'slug' => 'mantenimiento', 'description' => 'Gestión de mantenimiento', 'icon' => 'fa-tools', 'route' => 'mantenimiento', 'order' => 7],
            ['name' => 'Reportes', 'slug' => 'reportes', 'description' => 'Reportes del sistema', 'icon' => 'fa-chart-bar', 'route' => null, 'order' => 8],
            ['name' => 'Sistema', 'slug' => 'sistema', 'description' => 'Configuración del sistema', 'icon' => 'fa-cogs', 'route' => 'sistema', 'order' => 9],
        ];

        foreach ($modules as $module) {
            DB::table('modules')->updateOrInsert(
                ['slug' => $module['slug']],
                array_merge($module, [
                    'is_active' => true,
                    'created_at' => now(),
                    'updated_at' => now(),
                ])
            );
        }
    }
}
