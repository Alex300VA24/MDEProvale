<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Módulos con submódulos.
 *
 * - Agrega modules.parent_id (los permisos viven en las hojas; los grupos solo agrupan).
 * - Crea los grupos y submódulos nuevos: Registro de Ingreso, Programación de
 *   Entrega, Repartición, Supervisión de Comités, Perfil y Ayuda.
 * - Retira "movimientos": sus permisos pasan a Registro de Ingreso y Repartición.
 * - Conserva el acceso que cada rol ya tenía (Programación hereda de Productos,
 *   Supervisión hereda de Comités) y da Perfil y Ayuda a todos los roles, que
 *   hasta ahora eran visibles para cualquier usuario autenticado.
 */
return new class extends Migration
{
    /** Copia literal de App\Support\ModuleCatalog::modules() al momento de esta migración. */
    private const CATALOG = [
        ['socios-beneficiarios', 'Socios y Beneficiarios', 'Gestión de socios y beneficiarios', 'fa-users', 'socios-beneficiarios', 1, null],
        ['comites-resoluciones', 'Comités y Resoluciones', 'Comités (Club de Madres) y resoluciones de reconocimiento', 'fa-people-group', 'comites', 2, null],
        ['club-madres', 'Comités', 'Gestión de comités (Club de Madres)', 'fa-female', 'club-reconocimientos', 1, 'comites-resoluciones'],
        ['reconocimientos', 'Resoluciones', 'Gestión de resoluciones de reconocimiento', 'fa-award', 'club-reconocimientos', 2, 'comites-resoluciones'],
        ['productos-programacion', 'Productos y Programación de Entrega', 'Productos, ingresos al almacén y programación de entrega', 'fa-boxes-stacked', 'productos', 3, null],
        ['productos', 'Productos', 'Gestión de productos', 'fa-box', 'productos-pecosas', 1, 'productos-programacion'],
        ['registro-ingreso', 'Registro de Ingreso', 'Registro de ingresos de productos al almacén', 'fa-dolly', 'movimientos', 2, 'productos-programacion'],
        ['programacion-entrega', 'Programación de Entrega', 'Programación mensual de entrega por comité', 'fa-calendar-check', 'productos-pecosas', 3, 'productos-programacion'],
        ['pecosas-reparticion', 'Pecosas y Repartición', 'Pecosas y repartición mensual de raciones', 'fa-file-signature', 'pecosas', 4, null],
        ['pecosas', 'Pecosas', 'Gestión de pecosas', 'fa-file-alt', 'productos-pecosas', 1, 'pecosas-reparticion'],
        ['reparticion', 'Repartición', 'Repartición mensual de raciones por comité', 'fa-truck-ramp-box', 'movimientos', 2, 'pecosas-reparticion'],
        ['supervision-comites', 'Supervisión de Comités', 'Visitas de supervisión a los comités', 'fa-clipboard-check', 'supervision-comites', 5, null],
        ['evaluacion-nutricional', 'Evaluación Nutricional', 'Evaluación nutricional y clasificación poblacional', 'fa-heart-pulse', 'evaluacion-nutricional', 6, null],
        ['responsables-raciones', 'Responsables y Raciones', 'Gestión de responsables del programa y raciones por año', 'fa-sliders', 'responsables-raciones', 7, null],
        ['reportes', 'Reportes', 'Reportes del sistema', 'fa-chart-bar', null, 8, null],
        ['perfil', 'Perfil', 'Datos y contraseña del usuario', 'fa-user', 'perfil', 9, null],
        ['sistema', 'Sistema', 'Configuración del sistema', 'fa-cogs', 'sistema', 10, null],
        ['ayuda', 'Ayuda', 'Guías de uso del sistema', 'fa-circle-question', 'ayuda', 11, null],
        ['base-conocimiento', 'Base de Conocimiento IA', 'Documentos indexados con IA para consultas tipo RAG', 'fa-database', 'base-conocimiento', 12, null],
    ];

    /** Submódulos nuevos que heredan el permiso que el rol ya tenía en otro módulo: [nuevo, origen]. */
    private const INHERITS = [
        ['registro-ingreso', 'movimientos'],
        ['reparticion', 'movimientos'],
        ['programacion-entrega', 'productos'],
        ['supervision-comites', 'club-madres'],
    ];

    private const FOR_EVERY_ROLE = ['perfil', 'ayuda'];

    public function up(): void
    {
        if (! Schema::hasColumn('modules', 'parent_id')) {
            Schema::table('modules', function (Blueprint $table) {
                $table->foreignId('parent_id')->nullable()->after('id')
                    ->constrained('modules')->nullOnDelete();
            });
        }

        // Instalación nueva: el catálogo lo crea ModuleSeeder (ModuleCatalog).
        if (! DB::table('modules')->where('slug', 'sistema')->exists()) {
            return;
        }

        DB::transaction(function () {
            $this->upsertCatalog();
            $this->inheritPermissions();
            $this->grantToEveryRole();
            $this->retireMovimientos();
        });
    }

    public function down(): void
    {
        DB::transaction(function () {
            // Restituye "movimientos" con el permiso más amplio de sus dos submódulos.
            $now = now();
            DB::table('modules')->updateOrInsert(
                ['slug' => 'movimientos'],
                ['name' => 'Movimientos', 'description' => 'Gestión de movimientos', 'icon' => 'fa-exchange-alt',
                    'route' => 'movimientos', 'order' => 6, 'is_active' => true, 'parent_id' => null,
                    'created_at' => $now, 'updated_at' => $now]
            );
            $legacyId = DB::table('modules')->where('slug', 'movimientos')->value('id');
            $sources = DB::table('modules')->whereIn('slug', ['registro-ingreso', 'reparticion'])->pluck('id');

            DB::table('module_rol')->whereIn('module_id', $sources)->get()
                ->groupBy('rol_id')
                ->each(function ($rows, $rolId) use ($legacyId, $now) {
                    DB::table('module_rol')->updateOrInsert(
                        ['module_id' => $legacyId, 'rol_id' => $rolId],
                        [
                            'can_view' => (int) $rows->max('can_view'),
                            'can_create' => (int) $rows->max('can_create'),
                            'can_edit' => (int) $rows->max('can_edit'),
                            'can_delete' => (int) $rows->max('can_delete'),
                            'created_at' => $now,
                            'updated_at' => $now,
                        ]
                    );
                });

            $newSlugs = ['registro-ingreso', 'reparticion', 'programacion-entrega', 'supervision-comites',
                'perfil', 'ayuda', 'comites-resoluciones', 'productos-programacion', 'pecosas-reparticion'];
            DB::table('modules')->whereIn('slug', $newSlugs)->update(['parent_id' => null]);
            DB::table('modules')->whereIn('slug', $newSlugs)->delete();

            DB::table('modules')->where('slug', 'club-madres')->update(['name' => 'Club de Madres']);
            DB::table('modules')->where('slug', 'reconocimientos')->update(['name' => 'Reconocimientos']);
        });

        Schema::table('modules', function (Blueprint $table) {
            $table->dropConstrainedForeignId('parent_id');
        });
    }

    private function upsertCatalog(): void
    {
        $now = now();

        // Primero los grupos y módulos sueltos; luego las hojas, que ya pueden apuntar a su padre.
        foreach ([false, true] as $leavesPass) {
            foreach (self::CATALOG as [$slug, $name, $description, $icon, $route, $order, $parent]) {
                if (($parent !== null) !== $leavesPass) {
                    continue;
                }

                $row = [
                    'name' => $name,
                    'description' => $description,
                    'icon' => $icon,
                    'route' => $route,
                    'order' => $order,
                    'parent_id' => $parent ? DB::table('modules')->where('slug', $parent)->value('id') : null,
                    'updated_at' => $now,
                ];

                if (DB::table('modules')->where('slug', $slug)->exists()) {
                    DB::table('modules')->where('slug', $slug)->update($row);
                } else {
                    DB::table('modules')->insert($row + ['slug' => $slug, 'is_active' => true, 'created_at' => $now]);
                }
            }
        }
    }

    private function inheritPermissions(): void
    {
        $now = now();

        foreach (self::INHERITS as [$targetSlug, $sourceSlug]) {
            $targetId = DB::table('modules')->where('slug', $targetSlug)->value('id');
            $sourceId = DB::table('modules')->where('slug', $sourceSlug)->value('id');
            if (! $targetId || ! $sourceId) {
                continue;
            }

            foreach (DB::table('module_rol')->where('module_id', $sourceId)->get() as $permission) {
                DB::table('module_rol')->updateOrInsert(
                    ['module_id' => $targetId, 'rol_id' => $permission->rol_id],
                    [
                        'can_view' => $permission->can_view,
                        'can_create' => $permission->can_create,
                        'can_edit' => $permission->can_edit,
                        'can_delete' => $permission->can_delete,
                        'created_at' => $now,
                        'updated_at' => $now,
                    ]
                );
            }
        }
    }

    /** Perfil y Ayuda eran visibles para todo usuario autenticado: se conserva solo lectura. */
    private function grantToEveryRole(): void
    {
        $now = now();
        $moduleIds = DB::table('modules')->whereIn('slug', self::FOR_EVERY_ROLE)->pluck('id');

        // El rol del portal de presidentas no usa el dashboard.
        $roleIds = DB::table('rols')->where('title', '!=', 'Socia Presidenta')->pluck('id');

        foreach ($roleIds as $rolId) {
            foreach ($moduleIds as $moduleId) {
                DB::table('module_rol')->updateOrInsert(
                    ['module_id' => $moduleId, 'rol_id' => $rolId],
                    ['can_view' => true, 'can_create' => false, 'can_edit' => true, 'can_delete' => false,
                        'created_at' => $now, 'updated_at' => $now]
                );
            }
        }
    }

    private function retireMovimientos(): void
    {
        DB::table('modules')->where('slug', 'movimientos')->delete();
    }
};
