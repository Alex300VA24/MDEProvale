<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    private const GROUP = 'socios-beneficiarios';

    private const CHILDREN = [
        ['registro-socios-beneficiarios', 'Registro de Socios y Beneficiarios', 'Gestión de socios, beneficiarios, fichas y documentos familiares', 'fa-people-roof', 1],
        ['padrones', 'Padrones', 'Generación, cierre, revisión e historial de padrones mensuales', 'fa-clipboard-list', 2],
    ];

    public function up(): void
    {
        $groupId = DB::table('modules')->where('slug', self::GROUP)->value('id');
        if (! $groupId) {
            return;
        }

        DB::transaction(function () use ($groupId) {
            DB::table('modules')->where('id', $groupId)->update([
                'name' => 'Socios y Beneficiarios',
                'description' => 'Registro familiar y gestión histórica de padrones',
                'updated_at' => now(),
            ]);

            $legacyPermissions = DB::table('module_rol')->where('module_id', $groupId)->get();

            foreach (self::CHILDREN as [$slug, $name, $description, $icon, $order]) {
                DB::table('modules')->updateOrInsert(
                    ['slug' => $slug],
                    [
                        'parent_id' => $groupId,
                        'name' => $name,
                        'description' => $description,
                        'icon' => $icon,
                        'route' => 'socios-beneficiarios',
                        'order' => $order,
                        'is_active' => true,
                        'updated_at' => now(),
                        'created_at' => now(),
                    ]
                );

                $childId = DB::table('modules')->where('slug', $slug)->value('id');
                foreach ($legacyPermissions as $permission) {
                    DB::table('module_rol')->insertOrIgnore([
                        'module_id' => $childId,
                        'rol_id' => $permission->rol_id,
                        'can_view' => $permission->can_view,
                        'can_create' => $permission->can_create,
                        'can_edit' => $permission->can_edit,
                        'can_delete' => $permission->can_delete,
                        'created_at' => now(),
                        'updated_at' => now(),
                    ]);
                }
            }

            // Los grupos no guardan permisos propios; estos se derivan de sus hijos.
            DB::table('module_rol')->where('module_id', $groupId)->delete();
        });
    }

    public function down(): void
    {
        $groupId = DB::table('modules')->where('slug', self::GROUP)->value('id');
        if (! $groupId) {
            return;
        }

        DB::transaction(function () use ($groupId) {
            $childIds = DB::table('modules')
                ->whereIn('slug', array_column(self::CHILDREN, 0))
                ->pluck('id');

            DB::table('module_rol')->whereIn('module_id', $childIds)->get()
                ->groupBy('rol_id')
                ->each(function ($permissions, $rolId) use ($groupId) {
                    DB::table('module_rol')->updateOrInsert(
                        ['module_id' => $groupId, 'rol_id' => $rolId],
                        [
                            'can_view' => (bool) $permissions->max('can_view'),
                            'can_create' => (bool) $permissions->max('can_create'),
                            'can_edit' => (bool) $permissions->max('can_edit'),
                            'can_delete' => (bool) $permissions->max('can_delete'),
                            'created_at' => now(),
                            'updated_at' => now(),
                        ]
                    );
                });

            DB::table('modules')->whereIn('id', $childIds)->delete();
            DB::table('modules')->where('id', $groupId)->update([
                'description' => 'Gestión de socios y beneficiarios',
                'updated_at' => now(),
            ]);
        });
    }
};
