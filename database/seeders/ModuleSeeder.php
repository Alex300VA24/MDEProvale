<?php

namespace Database\Seeders;

use App\Support\ModuleCatalog;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class ModuleSeeder extends Seeder
{
    public function run()
    {
        $modules = ModuleCatalog::modules();

        // Primero los módulos de primer nivel (y grupos); luego los submódulos, que ya pueden apuntar al padre.
        foreach ([false, true] as $submodulesPass) {
            foreach ($modules as $module) {
                if (($module['parent'] !== null) !== $submodulesPass) {
                    continue;
                }

                $attributes = [
                    'name' => $module['name'],
                    'description' => $module['description'],
                    'icon' => $module['icon'],
                    'route' => $module['route'],
                    'order' => $module['order'],
                    'parent_id' => $module['parent']
                        ? DB::table('modules')->where('slug', $module['parent'])->value('id')
                        : null,
                    'is_active' => true,
                    'updated_at' => now(),
                ];

                $exists = DB::table('modules')->where('slug', $module['slug'])->exists();

                DB::table('modules')->updateOrInsert(
                    ['slug' => $module['slug']],
                    $exists ? $attributes : $attributes + ['created_at' => now()]
                );
            }
        }

        // El módulo plano "movimientos" fue reemplazado por Registro de Ingreso y Repartición.
        DB::table('modules')->where('slug', ModuleCatalog::LEGACY_MOVIMIENTOS)->delete();
    }
}
