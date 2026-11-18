<?php

namespace Tests\Feature\Api;

use App\Models\Notification;
use App\Models\State;
use App\Models\User;
use App\Support\ModuleCatalog;
use Database\Seeders\ModuleSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Route;
use Tests\TestCase;

/**
 * Módulos con submódulos: catálogo, permisos efectivos, middleware, políticas y
 * administración desde Sistema, más la paginación de notificaciones.
 */
class ModuleHierarchyTest extends TestCase
{
    use RefreshDatabase;

    private const SISTEMA = '/api/dashboard/sistema';

    private int $seq = 0;

    protected function setUp(): void
    {
        parent::setUp();

        DB::table('states')->insert(['id' => 1, 'title' => 'Activo', 'abbreviation' => State::ACTIVE]);
        DB::table('rols')->insert(['id' => 1, 'title' => 'Administrador', 'description' => 'Acceso completo']);
        DB::table('users')->insert([
            'id' => 1, 'names' => 'Admin', 'father_surname' => 'Test', 'mother_surname' => 'Test',
            'username' => 'admin', 'email' => 'admin@example.com', 'dni' => '00000001', 'cui' => '0',
            'state_id' => 1, 'rol_id' => 1, 'password' => bcrypt('password'),
        ]);

        $this->seed(ModuleSeeder::class);
    }

    private function admin(): User
    {
        return User::findOrFail(1);
    }

    /**
     * Crea un usuario cuyo rol tiene los submódulos indicados (slug => banderas).
     *
     * @param  array<int|string, string|array<string,bool>>  $grants  'slug' o 'slug' => ['can_edit' => true, ...]
     */
    private function userWith(array $grants): User
    {
        $n = ++$this->seq;
        $rolId = DB::table('rols')->insertGetId(['title' => "Rol {$n}", 'description' => 'Test', 'is_active' => true]);

        foreach ($grants as $key => $value) {
            [$slug, $flags] = is_int($key) ? [$value, []] : [$key, $value];

            DB::table('module_rol')->insert([
                'module_id' => $this->moduleId($slug),
                'rol_id' => $rolId,
                'can_view' => true,
                'can_create' => $flags['can_create'] ?? true,
                'can_edit' => $flags['can_edit'] ?? true,
                'can_delete' => $flags['can_delete'] ?? true,
            ]);
        }

        $userId = DB::table('users')->insertGetId([
            'names' => "U{$n}", 'father_surname' => 'T', 'mother_surname' => 'T', 'username' => "user{$n}",
            'email' => "user{$n}@example.com", 'dni' => sprintf('1000%04d', $n), 'cui' => '0',
            'state_id' => 1, 'rol_id' => $rolId, 'password' => bcrypt('password'),
        ]);

        return User::findOrFail($userId);
    }

    /** El middleware dejó pasar la petición (la respuesta puede ser un error de datos, nunca 401/403). */
    private function assertPassed(\Illuminate\Testing\TestResponse $response): void
    {
        $this->assertNotContains($response->getStatusCode(), [401, 403], 'El middleware bloqueó una petición permitida.');
    }

    private function moduleId(string $slug): int
    {
        return (int) DB::table('modules')->where('slug', $slug)->value('id');
    }

    // ==================== CATÁLOGO ====================

    public function test_seeder_builds_the_full_module_tree(): void
    {
        $children = fn (string $group) => DB::table('modules')
            ->where('parent_id', $this->moduleId($group))
            ->orderBy('order')->pluck('slug')->all();

        $this->assertSame(['club-madres', 'reconocimientos'], $children('comites-resoluciones'));
        $this->assertSame(['registro-socios-beneficiarios', 'padrones'], $children('socios-beneficiarios'));
        $this->assertSame(['productos', 'registro-ingreso', 'programacion-entrega'], $children('productos-programacion'));
        $this->assertSame(['pecosas', 'reparticion'], $children('pecosas-reparticion'));

        foreach (['supervision-comites', 'evaluacion-nutricional', 'responsables-raciones',
            'reportes', 'perfil', 'sistema', 'ayuda'] as $slug) {
            $this->assertNull(DB::table('modules')->where('slug', $slug)->value('parent_id'), $slug);
            $this->assertSame([], $children($slug), $slug);
        }

        $this->assertFalse(DB::table('modules')->where('slug', 'movimientos')->exists());
        $this->assertEqualsCanonicalizing(ModuleCatalog::slugs(), DB::table('modules')->pluck('slug')->all());
    }

    public function test_modules_are_listed_with_each_group_followed_by_its_submodules(): void
    {
        $slugs = collect($this->actingAs($this->admin())
            ->getJson(self::SISTEMA.'/modulos?per_page=50')
            ->assertOk()
            ->json('data'))->pluck('slug')->all();

        $this->assertSame(
            ['socios-beneficiarios', 'registro-socios-beneficiarios', 'padrones',
                'comites-resoluciones', 'club-madres', 'reconocimientos',
                'productos-programacion', 'productos', 'registro-ingreso', 'programacion-entrega',
                'pecosas-reparticion', 'pecosas', 'reparticion', 'supervision-comites'],
            array_slice($slugs, 0, 14)
        );
    }

    // ==================== PERMISOS EFECTIVOS ====================

    public function test_group_access_is_derived_from_its_submodules(): void
    {
        $user = $this->userWith(['reconocimientos']);

        $this->assertTrue($user->canModule('reconocimientos'));
        $this->assertTrue($user->canModule('comites-resoluciones'));
        $this->assertFalse($user->canModule('club-madres'));
        $this->assertFalse($user->canModule('productos-programacion'));
    }

    public function test_group_actions_are_the_union_of_its_submodules(): void
    {
        $user = $this->userWith([
            'productos' => ['can_create' => false, 'can_edit' => false, 'can_delete' => false],
            'programacion-entrega' => ['can_create' => false, 'can_edit' => true, 'can_delete' => false],
        ]);

        $this->assertTrue($user->canModule('productos-programacion', 'view'));
        $this->assertTrue($user->canModule('productos-programacion', 'edit'));
        $this->assertFalse($user->canModule('productos-programacion', 'create'));
        $this->assertFalse($user->canModule('productos', 'edit'));
        $this->assertTrue($user->canModule('programacion-entrega', 'edit'));
    }

    public function test_submodules_are_unavailable_when_their_group_is_inactive(): void
    {
        $user = $this->userWith(['reconocimientos', 'club-madres']);
        DB::table('modules')->where('slug', 'comites-resoluciones')->update(['is_active' => false]);

        $this->assertFalse($user->fresh()->canModule('reconocimientos'));
        $this->assertFalse($user->fresh()->canModule('comites-resoluciones'));
    }

    public function test_current_modules_exposes_the_tree_for_the_menu(): void
    {
        $user = $this->userWith(['reconocimientos', 'registro-ingreso', 'perfil']);

        $rows = collect($this->actingAs($user)->getJson(self::SISTEMA.'/current-modules')->assertOk()->json('data'))
            ->keyBy('slug');

        $this->assertEqualsCanonicalizing(
            ['comites-resoluciones', 'reconocimientos', 'productos-programacion', 'registro-ingreso', 'perfil'],
            $rows->keys()->all()
        );
        $this->assertTrue($rows['comites-resoluciones']['is_group']);
        $this->assertSame('comites-resoluciones', $rows['reconocimientos']['parent_slug']);
        $this->assertNull($rows['perfil']['parent_slug']);
    }

    public function test_admin_receives_every_active_module_and_group(): void
    {
        $slugs = collect($this->actingAs($this->admin())->getJson(self::SISTEMA.'/current-modules')->json('data'))
            ->pluck('slug')->all();

        $this->assertEqualsCanonicalizing(ModuleCatalog::slugs(), $slugs);
    }

    // ==================== MIDDLEWARE ====================

    public function test_resoluciones_and_comites_have_independent_permissions(): void
    {
        $onlyResoluciones = $this->userWith(['reconocimientos']);

        $this->actingAs($onlyResoluciones)
            ->getJson('/api/dashboard/club-madres/reconocimientos/options')->assertSuccessful();
        $this->actingAs($onlyResoluciones)
            ->getJson('/api/dashboard/club-madres/clubs/options')->assertForbidden();

        $onlyComites = $this->userWith(['club-madres']);

        $this->actingAs($onlyComites)
            ->getJson('/api/dashboard/club-madres/clubs/options')->assertSuccessful();
        $this->actingAs($onlyComites)
            ->getJson('/api/dashboard/club-madres/reconocimientos/options')->assertForbidden();
    }

    public function test_registro_socios_and_padrones_have_independent_permissions(): void
    {
        $registro = $this->userWith(['registro-socios-beneficiarios']);
        $this->actingAs($registro)->getJson('/api/dashboard/socios-beneficiarios/partners')->assertSuccessful();
        $this->actingAs($registro)->getJson('/api/dashboard/socios-beneficiarios/monthly-rosters')->assertForbidden();

        $padrones = $this->userWith(['padrones']);
        $this->actingAs($padrones)->getJson('/api/dashboard/socios-beneficiarios/monthly-rosters')->assertSuccessful();
        $this->actingAs($padrones)->getJson('/api/dashboard/socios-beneficiarios/partners')->assertForbidden();
        $this->actingAs($padrones)->getJson('/api/dashboard/socios-beneficiarios/partners/options')->assertSuccessful();
    }

    public function test_supervision_requires_its_own_module_not_comites(): void
    {
        $this->actingAs($this->userWith(['club-madres']))
            ->getJson('/api/dashboard/supervision-comites')->assertForbidden();

        $this->assertPassed($this->actingAs($this->userWith(['supervision-comites']))
            ->getJson('/api/dashboard/supervision-comites'));
    }

    public function test_registro_ingreso_and_reparticion_are_split(): void
    {
        $ingreso = $this->userWith(['registro-ingreso']);
        $this->actingAs($ingreso)->getJson('/api/dashboard/movimientos/transactions/options')->assertSuccessful();
        $this->actingAs($ingreso)->getJson('/api/dashboard/movimientos/reparticion')->assertForbidden();

        $reparticion = $this->userWith(['reparticion']);
        $this->actingAs($reparticion)->getJson('/api/dashboard/movimientos/transactions/options')->assertForbidden();
        $this->assertPassed($this->actingAs($reparticion)->getJson('/api/dashboard/movimientos/reparticion'));
    }

    public function test_productos_programacion_and_pecosas_are_independent(): void
    {
        $productos = $this->userWith(['productos']);
        $this->actingAs($productos)->getJson('/api/dashboard/productos-pecosas/products/options')->assertSuccessful();
        $this->actingAs($productos)->getJson('/api/dashboard/productos-pecosas/programacion-entrega')->assertForbidden();
        $this->actingAs($productos)->getJson('/api/dashboard/productos-pecosas/pecosas/options')->assertForbidden();

        $programacion = $this->userWith(['programacion-entrega']);
        $this->assertPassed($this->actingAs($programacion)->getJson('/api/dashboard/productos-pecosas/programacion-entrega'));
        $this->actingAs($programacion)->getJson('/api/dashboard/productos-pecosas/products/options')->assertForbidden();
    }

    public function test_edit_ability_is_enforced_per_submodule(): void
    {
        $readOnly = $this->userWith([
            'programacion-entrega' => ['can_create' => false, 'can_edit' => false, 'can_delete' => false],
        ]);

        $this->actingAs($readOnly)
            ->putJson('/api/dashboard/productos-pecosas/programacion-entrega', [])
            ->assertForbidden();
    }

    public function test_module_middleware_accepts_any_of_several_submodules(): void
    {
        Route::middleware(['web', 'auth', 'module:productos|registro-ingreso'])
            ->get('/__any-of', fn () => 'ok');

        $this->actingAs($this->userWith(['registro-ingreso']))->get('/__any-of')->assertOk();
        $this->actingAs($this->userWith(['productos']))->get('/__any-of')->assertOk();
        $this->actingAs($this->userWith(['pecosas']))->get('/__any-of')->assertRedirect();
    }

    public function test_module_middleware_accepts_a_group_slug(): void
    {
        Route::middleware(['web', 'auth', 'module:comites-resoluciones'])->get('/__group', fn () => 'ok');

        $this->actingAs($this->userWith(['reconocimientos']))->get('/__group')->assertOk();
        $this->actingAs($this->userWith(['pecosas']))->get('/__group')->assertRedirect();
    }

    public function test_profile_requires_the_perfil_module(): void
    {
        $this->actingAs($this->userWith(['registro-socios-beneficiarios']))->getJson('/perfil')->assertForbidden();
        $this->actingAs($this->userWith(['perfil']))->getJson('/perfil')->assertOk();

        $viewOnly = $this->userWith(['perfil' => ['can_edit' => false]]);
        $this->actingAs($viewOnly)->putJson('/perfil', [])->assertForbidden();
    }

    // ==================== POLÍTICAS ====================

    public function test_policies_follow_the_submodule_that_owns_each_resource(): void
    {
        $resoluciones = $this->userWith(['reconocimientos']);
        $this->assertTrue($resoluciones->can('create', \App\Models\Resolution::class));
        $this->assertFalse($resoluciones->can('create', \App\Models\Association::class));

        $comites = $this->userWith(['club-madres']);
        $this->assertTrue($comites->can('create', \App\Models\Association::class));
        $this->assertFalse($comites->can('create', \App\Models\Resolution::class));

        $ingreso = $this->userWith(['registro-ingreso']);
        $this->assertTrue($ingreso->can('create', \App\Models\Transaction::class));
        $this->assertFalse($this->userWith(['reparticion'])->can('create', \App\Models\Transaction::class));

        $this->assertFalse($this->userWith(['pecosas'])->can('create', \App\Models\Product::class));
        $this->assertTrue($this->userWith(['productos'])->can('create', \App\Models\Product::class));
        $this->assertTrue($this->userWith(['registro-socios-beneficiarios'])->can('create', \App\Models\Partner::class));
        $this->assertFalse($this->userWith(['padrones'])->can('create', \App\Models\Partner::class));
    }

    // ==================== ADMINISTRACIÓN DESDE SISTEMA ====================

    public function test_role_permissions_ignore_group_modules(): void
    {
        $group = $this->moduleId('comites-resoluciones');
        $leaf = $this->moduleId('reconocimientos');

        $rolId = $this->actingAs($this->admin())->postJson(self::SISTEMA.'/roles', [
            'title' => 'Con grupo',
            'modules' => [
                $group => ['can_view' => true, 'can_edit' => true],
                $leaf => ['can_view' => true],
            ],
        ])->assertCreated()->json('data.id');

        $this->assertDatabaseMissing('module_rol', ['rol_id' => $rolId, 'module_id' => $group]);
        $this->assertDatabaseHas('module_rol', ['rol_id' => $rolId, 'module_id' => $leaf, 'can_view' => 1]);
    }

    public function test_custom_module_can_be_created_under_a_top_level_module(): void
    {
        $this->actingAs($this->admin())->postJson(self::SISTEMA.'/modulos', [
            'name' => 'Nuevo', 'slug' => 'nuevo', 'order' => 9,
            'parent_id' => $this->moduleId('comites-resoluciones'),
        ])->assertCreated()->assertJsonPath('data.parent_id', $this->moduleId('comites-resoluciones'));
    }

    public function test_parent_must_be_a_top_level_module(): void
    {
        $this->actingAs($this->admin())->postJson(self::SISTEMA.'/modulos', [
            'name' => 'Nieto', 'slug' => 'nieto', 'order' => 9,
            'parent_id' => $this->moduleId('reconocimientos'),
        ])->assertUnprocessable()->assertJsonValidationErrors('parent_id');
    }

    public function test_module_cannot_be_its_own_parent_or_nest_a_group(): void
    {
        $custom = $this->moduleId('base-conocimiento');

        $this->actingAs($this->admin())->putJson(self::SISTEMA."/modulos/{$custom}", [
            'name' => 'Base', 'slug' => 'base-conocimiento', 'order' => 12, 'parent_id' => $custom,
        ])->assertUnprocessable()->assertJsonValidationErrors('parent_id');
    }

    public function test_built_in_modules_keep_their_slug_and_parent(): void
    {
        $id = $this->moduleId('reconocimientos');

        $this->actingAs($this->admin())->putJson(self::SISTEMA."/modulos/{$id}", [
            'name' => 'Resoluciones', 'slug' => 'otro-slug', 'order' => 2,
            'parent_id' => $this->moduleId('comites-resoluciones'),
        ])->assertUnprocessable()->assertJsonValidationErrors('slug');

        $this->actingAs($this->admin())->putJson(self::SISTEMA."/modulos/{$id}", [
            'name' => 'Resoluciones', 'slug' => 'reconocimientos', 'order' => 2, 'parent_id' => null,
        ])->assertUnprocessable()->assertJsonValidationErrors('parent_id');

        // Renombrar la etiqueta, reordenar y desactivar sí se permite.
        $this->actingAs($this->admin())->putJson(self::SISTEMA."/modulos/{$id}", [
            'name' => 'Resoluciones y Reconocimientos', 'slug' => 'reconocimientos', 'order' => 2,
            'parent_id' => $this->moduleId('comites-resoluciones'),
        ])->assertOk()->assertJsonPath('data.name', 'Resoluciones y Reconocimientos');
    }

    public function test_built_in_modules_and_groups_cannot_be_deleted(): void
    {
        foreach (['reconocimientos', 'comites-resoluciones', 'perfil'] as $slug) {
            $this->actingAs($this->admin())
                ->deleteJson(self::SISTEMA.'/modulos/'.$this->moduleId($slug))
                ->assertUnprocessable();
        }
    }

    public function test_custom_group_with_submodules_cannot_be_deleted(): void
    {
        $groupId = DB::table('modules')->insertGetId(['name' => 'Grupo', 'slug' => 'grupo', 'order' => 50, 'is_active' => true]);
        DB::table('modules')->insert(['name' => 'Hijo', 'slug' => 'hijo', 'order' => 1, 'is_active' => true, 'parent_id' => $groupId]);

        $this->actingAs($this->admin())
            ->deleteJson(self::SISTEMA."/modulos/{$groupId}")
            ->assertUnprocessable()
            ->assertJsonPath('message', 'No se puede eliminar el módulo porque tiene submódulos. Elimina o reasigna primero sus submódulos.');
    }

    public function test_module_resource_marks_groups_and_parents(): void
    {
        $rows = collect($this->actingAs($this->admin())->getJson(self::SISTEMA.'/modulos?per_page=50')->json('data'))->keyBy('slug');

        $this->assertTrue($rows['comites-resoluciones']['is_group']);
        $this->assertFalse($rows['reconocimientos']['is_group']);
        $this->assertSame('comites-resoluciones', $rows['reconocimientos']['parent_slug']);
        $this->assertSame('Comités y Resoluciones', $rows['reconocimientos']['parent_name']);
        $this->assertTrue($rows['reconocimientos']['is_protected']);
    }

    // ==================== PAGINACIÓN DE NOTIFICACIONES ====================

    private function seedNotifications(int $count, string $type = 'alerta_resolucion'): void
    {
        for ($i = 1; $i <= $count; $i++) {
            Notification::create([
                'type' => $type,
                'title' => "Aviso {$i}",
                'status' => Notification::STATUS_OPEN,
                'user_id' => 1,
                'requested_at' => now()->subMinutes($count - $i),
                'is_seen' => false,
            ]);
        }
    }

    public function test_notifications_are_paginated_when_a_page_is_requested(): void
    {
        $this->seedNotifications(12);

        $first = $this->actingAs($this->admin())
            ->getJson(self::SISTEMA.'/notifications?page=1&per_page=5')
            ->assertOk()
            ->assertJsonCount(5, 'data')
            ->assertJsonPath('meta.total', 12)
            ->assertJsonPath('meta.per_page', 5)
            ->assertJsonPath('meta.last_page', 3)
            ->assertJsonStructure(['data', 'links', 'meta' => ['links']]);

        // Más reciente primero.
        $this->assertSame('Aviso 12', $first->json('data.0.title'));

        $this->actingAs($this->admin())
            ->getJson(self::SISTEMA.'/notifications?page=3&per_page=5')
            ->assertOk()
            ->assertJsonCount(2, 'data')
            ->assertJsonPath('meta.current_page', 3);
    }

    public function test_notification_pages_do_not_overlap(): void
    {
        $this->seedNotifications(12);

        $ids = collect([1, 2, 3])->flatMap(fn (int $page) => $this->actingAs($this->admin())
            ->getJson(self::SISTEMA."/notifications?page={$page}&per_page=5")->json('data.*.id'));

        $this->assertCount(12, $ids);
        $this->assertCount(12, $ids->unique());
    }

    public function test_notifications_without_page_keep_the_short_list_shape(): void
    {
        $this->seedNotifications(7);

        $response = $this->actingAs($this->admin())
            ->getJson(self::SISTEMA.'/notifications?limit=100')
            ->assertOk()
            ->assertJsonCount(7, 'data');

        $this->assertArrayNotHasKey('meta', $response->json());
    }

    public function test_notification_page_size_is_clamped(): void
    {
        $this->seedNotifications(3);

        $this->actingAs($this->admin())
            ->getJson(self::SISTEMA.'/notifications?page=1&per_page=1000')
            ->assertOk()
            ->assertJsonPath('meta.per_page', 50);
    }

    public function test_paginated_notifications_stay_scoped_to_the_user(): void
    {
        // Solicitudes de otros usuarios: el administrador las ve, el resto no.
        $this->seedNotifications(6, 'password_reset');
        $basic = $this->userWith(['perfil']);
        Notification::create([
            'type' => 'password_reset', 'title' => 'Mi solicitud', 'status' => 'pending',
            'user_id' => $basic->id, 'requested_by' => $basic->id, 'requested_at' => now(), 'is_seen' => false,
        ]);

        $this->actingAs($basic)
            ->getJson(self::SISTEMA.'/notifications?page=1&per_page=10')
            ->assertOk()
            ->assertJsonPath('meta.total', 1)
            ->assertJsonPath('data.0.title', 'Mi solicitud');
    }
}
