<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

/**
 * La migración de submódulos sobre una base que ya tiene módulos planos y roles con
 * permisos: nadie pierde ni gana acceso de más.
 */
class SubmodulesMigrationTest extends TestCase
{
    use RefreshDatabase;

    private function legacyModule(string $slug, string $name, int $order): int
    {
        return DB::table('modules')->insertGetId([
            'name' => $name, 'slug' => $slug, 'description' => $name, 'icon' => 'fa-box',
            'route' => $slug, 'order' => $order, 'is_active' => true,
        ]);
    }

    private function legacyRole(string $title, array $grants): int
    {
        $rolId = DB::table('rols')->insertGetId(['title' => $title, 'description' => $title, 'is_active' => true]);

        foreach ($grants as $slug => $flags) {
            DB::table('module_rol')->insert([
                'module_id' => DB::table('modules')->where('slug', $slug)->value('id'),
                'rol_id' => $rolId,
                'can_view' => $flags[0], 'can_create' => $flags[1], 'can_edit' => $flags[2], 'can_delete' => $flags[3],
            ]);
        }

        return $rolId;
    }

    private function runMigration(): void
    {
        $migration = require base_path('database/migrations/2026_11_17_000001_add_submodules_to_modules_table.php');
        $migration->up();
    }

    private function runPadronesMigration(): void
    {
        $migration = require base_path('database/migrations/2026_11_17_000003_add_padrones_submodule.php');
        $migration->up();
    }

    private function permission(int $rolId, string $slug): ?object
    {
        return DB::table('module_rol')
            ->join('modules', 'modules.id', '=', 'module_rol.module_id')
            ->where('module_rol.rol_id', $rolId)->where('modules.slug', $slug)
            ->first(['module_rol.can_view', 'module_rol.can_create', 'module_rol.can_edit', 'module_rol.can_delete']);
    }

    private function seedLegacy(): array
    {
        foreach ([
            'socios-beneficiarios' => 'Socios y Beneficiarios', 'club-madres' => 'Club de Madres',
            'reconocimientos' => 'Reconocimientos', 'productos' => 'Productos', 'pecosas' => 'Pecosas',
            'movimientos' => 'Movimientos', 'sistema' => 'Sistema',
        ] as $slug => $name) {
            $this->legacyModule($slug, $name, 1);
        }

        DB::table('rols')->insert(['id' => 1, 'title' => 'Administrador', 'description' => 'Admin']);
        DB::table('rols')->insert(['title' => 'Socia Presidenta', 'description' => 'Portal', 'is_active' => true]);

        return [
            // [view, create, edit, delete]
            'almacen' => $this->legacyRole('Almacén', [
                'productos' => [true, true, false, false],
                'pecosas' => [true, false, false, false],
                'movimientos' => [true, true, true, false],
            ]),
            'comites' => $this->legacyRole('Comités', [
                'club-madres' => [true, true, true, true],
                'socios-beneficiarios' => [true, false, false, false],
            ]),
            'sinmovimientos' => $this->legacyRole('Solo sistema', ['sistema' => [true, true, true, true]]),
        ];
    }

    public function test_it_builds_the_tree_and_keeps_existing_access(): void
    {
        $roles = $this->seedLegacy();

        $this->runMigration();

        // Árbol de módulos.
        $id = fn (string $slug) => DB::table('modules')->where('slug', $slug)->value('id');
        $this->assertSame($id('comites-resoluciones'), DB::table('modules')->where('slug', 'club-madres')->value('parent_id'));
        $this->assertSame($id('comites-resoluciones'), DB::table('modules')->where('slug', 'reconocimientos')->value('parent_id'));
        foreach (['productos', 'registro-ingreso', 'programacion-entrega'] as $slug) {
            $this->assertSame($id('productos-programacion'), DB::table('modules')->where('slug', $slug)->value('parent_id'), $slug);
        }
        foreach (['pecosas', 'reparticion'] as $slug) {
            $this->assertSame($id('pecosas-reparticion'), DB::table('modules')->where('slug', $slug)->value('parent_id'), $slug);
        }
        $this->assertNull(DB::table('modules')->where('slug', 'supervision-comites')->value('parent_id'));
        $this->assertSame('Comités', DB::table('modules')->where('slug', 'club-madres')->value('name'));
        $this->assertSame('Resoluciones', DB::table('modules')->where('slug', 'reconocimientos')->value('name'));

        // "movimientos" desaparece; sus permisos pasan a Registro de Ingreso y Repartición.
        $this->assertFalse(DB::table('modules')->where('slug', 'movimientos')->exists());
        foreach (['registro-ingreso', 'reparticion'] as $slug) {
            $p = $this->permission($roles['almacen'], $slug);
            $this->assertSame([1, 1, 1, 0], [(int) $p->can_view, (int) $p->can_create, (int) $p->can_edit, (int) $p->can_delete], $slug);
        }

        // Programación de Entrega hereda de Productos; Supervisión, de Comités.
        $p = $this->permission($roles['almacen'], 'programacion-entrega');
        $this->assertSame([1, 1, 0, 0], [(int) $p->can_view, (int) $p->can_create, (int) $p->can_edit, (int) $p->can_delete]);
        $p = $this->permission($roles['comites'], 'supervision-comites');
        $this->assertSame([1, 1, 1, 1], [(int) $p->can_view, (int) $p->can_create, (int) $p->can_edit, (int) $p->can_delete]);

        // Quien no tenía acceso a un módulo origen no lo recibe en el nuevo.
        $this->assertNull($this->permission($roles['comites'], 'registro-ingreso'));
        $this->assertNull($this->permission($roles['comites'], 'programacion-entrega'));
        $this->assertNull($this->permission($roles['sinmovimientos'], 'supervision-comites'));
        $this->assertNull($this->permission($roles['almacen'], 'supervision-comites'));
    }

    public function test_perfil_and_ayuda_are_granted_to_every_dashboard_role_but_not_the_president(): void
    {
        $roles = $this->seedLegacy();

        $this->runMigration();

        foreach ($roles as $rolId) {
            foreach (['perfil', 'ayuda'] as $slug) {
                $p = $this->permission($rolId, $slug);
                $this->assertNotNull($p, "{$slug} para rol {$rolId}");
                $this->assertSame([1, 0, 1, 0], [(int) $p->can_view, (int) $p->can_create, (int) $p->can_edit, (int) $p->can_delete]);
            }
        }

        $presidentId = DB::table('rols')->where('title', 'Socia Presidenta')->value('id');
        $this->assertNull($this->permission($presidentId, 'perfil'));
    }

    public function test_padrones_becomes_a_submodule_without_changing_existing_role_permissions(): void
    {
        $roles = $this->seedLegacy();
        $this->runMigration();
        $this->runPadronesMigration();

        $groupId = DB::table('modules')->where('slug', 'socios-beneficiarios')->value('id');
        $this->assertSame(
            ['registro-socios-beneficiarios', 'padrones'],
            DB::table('modules')->where('parent_id', $groupId)->orderBy('order')->pluck('slug')->all()
        );
        $this->assertNull($this->permission($roles['comites'], 'socios-beneficiarios'));

        foreach (['registro-socios-beneficiarios', 'padrones'] as $slug) {
            $permission = $this->permission($roles['comites'], $slug);
            $this->assertNotNull($permission);
            $this->assertSame(
                [1, 0, 0, 0],
                [(int) $permission->can_view, (int) $permission->can_create, (int) $permission->can_edit, (int) $permission->can_delete]
            );
        }
    }

    public function test_it_does_not_widen_access_when_run_twice(): void
    {
        $roles = $this->seedLegacy();

        $this->runMigration();
        // Un administrador ajusta el rol después de migrar...
        DB::table('module_rol')
            ->where('rol_id', $roles['almacen'])
            ->where('module_id', DB::table('modules')->where('slug', 'reparticion')->value('id'))
            ->update(['can_edit' => false]);

        // ...y la migración vuelve a ejecutarse: no debe haber duplicados ni errores.
        $this->runMigration();

        $this->assertSame(1, DB::table('module_rol')
            ->where('rol_id', $roles['almacen'])
            ->where('module_id', DB::table('modules')->where('slug', 'perfil')->value('id'))->count());
        $this->assertSame(1, DB::table('modules')->where('slug', 'registro-ingreso')->count());
    }

    public function test_it_leaves_a_fresh_install_to_the_seeder(): void
    {
        // Sin el módulo Sistema (instalación nueva) no crea catálogo: lo hace ModuleSeeder.
        $before = DB::table('modules')->count();

        $this->runMigration();

        $this->assertSame($before, DB::table('modules')->count());
    }
}
