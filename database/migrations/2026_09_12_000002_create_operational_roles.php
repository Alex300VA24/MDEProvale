<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    private const PRESETS = [
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

    public function up(): void
    {
        if (!Schema::hasTable('rols') || !Schema::hasTable('modules') || !Schema::hasTable('module_rol')) {
            return;
        }

        // En una instalación nueva las tablas aún no tienen catálogos: los
        // seeders crearán primero Administrador con ID 1 y luego estos roles.
        if (!DB::table('rols')->where('title', 'Administrador')->exists()) {
            return;
        }

        DB::transaction(function () {
            foreach (self::PRESETS as $title => $preset) {
                DB::table('rols')->updateOrInsert(
                    ['title' => $title],
                    [
                        'description' => $preset['description'],
                        'is_active' => true,
                        'created_at' => now(),
                        'updated_at' => now(),
                    ]
                );

                $rolId = DB::table('rols')->where('title', $title)->value('id');
                $moduleIds = DB::table('modules')
                    ->whereIn('slug', $preset['modules'])
                    ->pluck('id');

                DB::table('module_rol')->where('rol_id', $rolId)->delete();

                if ($moduleIds->isEmpty()) {
                    continue;
                }

                $now = now();
                DB::table('module_rol')->insert($moduleIds->map(fn ($moduleId) => [
                    'module_id' => $moduleId,
                    'rol_id' => $rolId,
                    'can_view' => true,
                    'can_create' => true,
                    'can_edit' => true,
                    'can_delete' => true,
                    'created_at' => $now,
                    'updated_at' => $now,
                ])->all());
            }
        });
    }

    public function down(): void
    {
        if (!Schema::hasTable('rols') || !Schema::hasTable('module_rol')) {
            return;
        }

        DB::transaction(function () {
            foreach (array_keys(self::PRESETS) as $title) {
                $rolId = DB::table('rols')->where('title', $title)->value('id');
                if (!$rolId) {
                    continue;
                }

                $hasUsers = Schema::hasTable('users')
                    && DB::table('users')->where('rol_id', $rolId)->exists();

                if (!$hasUsers) {
                    DB::table('module_rol')->where('rol_id', $rolId)->delete();
                    DB::table('rols')->where('id', $rolId)->delete();
                }
            }
        });
    }
};
