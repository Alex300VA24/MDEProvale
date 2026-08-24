<?php

namespace Tests\Feature\Api;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;
use Tests\Traits\SeedsBaseData;

class SistemaApiTest extends TestCase
{
    use RefreshDatabase;
    use SeedsBaseData;

    private const BASE = '/api/dashboard/sistema';

    protected function setUp(): void
    {
        parent::setUp();
        $this->seedBaseData();
        $this->seedSistemaModule();
    }

    private function seedSistemaModule(): void
    {
        $moduleId = DB::table('modules')->insertGetId([
            'name' => 'Sistema',
            'slug' => 'sistema',
            'description' => 'Sistema',
            'icon' => 'fa-cogs',
            'route' => 'sistema',
            'order' => 4,
            'is_active' => true,
        ]);

        DB::table('module_rol')->insert([
            'module_id' => $moduleId,
            'rol_id' => 1,
            'can_view' => true,
            'can_create' => true,
            'can_edit' => true,
            'can_delete' => true,
        ]);
    }

    private function createBasicUser(bool $withSistemaAccess = false): User
    {
        $rolId = DB::table('rols')->insertGetId(['title' => 'Basico', 'description' => 'Usuario básico']);

        if ($withSistemaAccess) {
            DB::table('module_rol')->insert([
                'module_id' => DB::table('modules')->where('slug', 'sistema')->value('id'),
                'rol_id' => $rolId,
                'can_view' => true,
                'can_create' => true,
                'can_edit' => true,
                'can_delete' => true,
            ]);
        }

        $userId = DB::table('users')->insertGetId([
            'names' => 'Basico', 'father_surname' => 'User', 'mother_surname' => 'Test', 'username' => 'basico',
            'email' => 'basico@example.com', 'dni' => '00000003', 'cui' => '0', 'state_id' => 1, 'rol_id' => $rolId,
            'password' => bcrypt('password'),
        ]);

        return User::find($userId);
    }

    // ==================== USUARIOS ====================

    public function test_usuarios_endpoint_returns_json_collection(): void
    {
        $this->actingAs($this->adminUser())
            ->getJson(self::BASE . '/usuarios')
            ->assertOk()
            ->assertJsonStructure(['data', 'roles', 'estados'])
            ->assertJsonCount(1, 'data');
    }

    public function test_store_usuario_creates_user(): void
    {
        $this->actingAs($this->adminUser())
            ->postJson(self::BASE . '/usuarios', [
                'names' => 'Nuevo', 'father_surname' => 'Usuario', 'mother_surname' => 'Test',
                'username' => 'nuevo', 'email' => 'nuevo@example.com', 'dni' => '00000004', 'cui' => '0',
                'rol_id' => 1, 'state_id' => 1, 'password' => 'password123!',
            ])
            ->assertStatus(201)
            ->assertJsonPath('data.username', 'nuevo');

        $this->assertDatabaseHas('users', ['username' => 'nuevo']);
    }

    public function test_update_usuario_updates_fields(): void
    {
        $other = $this->createBasicUser();

        $this->actingAs($this->adminUser())
            ->putJson(self::BASE . "/usuarios/{$other->id}", [
                'names' => 'Cambiado', 'father_surname' => $other->father_surname, 'mother_surname' => $other->mother_surname,
                'username' => $other->username, 'email' => $other->email, 'dni' => $other->dni,
                'rol_id' => $other->rol_id, 'state_id' => 1,
            ])
            ->assertOk()
            ->assertJsonPath('data.names', 'Cambiado');
    }

    public function test_update_usuario_rejects_changing_own_role(): void
    {
        $admin = $this->adminUser();
        $basicRolId = DB::table('rols')->insertGetId(['title' => 'Otro', 'description' => 'Otro rol']);

        $this->actingAs($admin)
            ->putJson(self::BASE . "/usuarios/{$admin->id}", [
                'names' => $admin->names, 'father_surname' => $admin->father_surname, 'mother_surname' => $admin->mother_surname,
                'username' => $admin->username, 'email' => $admin->email, 'dni' => $admin->dni,
                'rol_id' => $basicRolId, 'state_id' => 1,
            ])
            ->assertStatus(422)
            ->assertJsonValidationErrors('rol_id');

        $this->assertDatabaseHas('users', ['id' => $admin->id, 'rol_id' => 1]);
    }

    public function test_update_usuario_allows_changing_other_fields_for_self(): void
    {
        $admin = $this->adminUser();

        $this->actingAs($admin)
            ->putJson(self::BASE . "/usuarios/{$admin->id}", [
                'names' => 'Admin Editado', 'father_surname' => $admin->father_surname, 'mother_surname' => $admin->mother_surname,
                'username' => $admin->username, 'email' => $admin->email, 'dni' => $admin->dni,
                'rol_id' => $admin->rol_id, 'state_id' => 1,
            ])
            ->assertOk()
            ->assertJsonPath('data.names', 'Admin Editado');
    }

    public function test_destroy_usuario_deletes_other_user(): void
    {
        $other = $this->createBasicUser();

        $this->actingAs($this->adminUser())
            ->deleteJson(self::BASE . "/usuarios/{$other->id}")
            ->assertStatus(204);

        $this->assertDatabaseMissing('users', ['id' => $other->id]);
    }

    public function test_destroy_usuario_rejects_self_delete(): void
    {
        $admin = $this->adminUser();

        $this->actingAs($admin)
            ->deleteJson(self::BASE . "/usuarios/{$admin->id}")
            ->assertStatus(403);

        $this->assertDatabaseHas('users', ['id' => $admin->id]);
    }

    public function test_reset_user_password_restores_to_dni(): void
    {
        $other = $this->createBasicUser();

        $this->actingAs($this->adminUser())
            ->postJson(self::BASE . "/usuarios/{$other->id}/reset-password")
            ->assertOk();

        $this->assertTrue(\Illuminate\Support\Facades\Hash::check($other->dni, $other->fresh()->password));
    }

    // ==================== ROLES ====================

    public function test_roles_endpoint_returns_json_collection(): void
    {
        $this->actingAs($this->adminUser())
            ->getJson(self::BASE . '/roles')
            ->assertOk()
            ->assertJsonPath('data.0.title', 'Administrador')
            ->assertJsonPath('data.0.users_count', 1)
            ->assertJsonPath('data.0.is_protected', true);
    }

    public function test_store_rol_syncs_modules(): void
    {
        $moduleId = DB::table('modules')->where('slug', 'sistema')->value('id');

        $response = $this->actingAs($this->adminUser())
            ->postJson(self::BASE . '/roles', [
                'title' => 'Supervisor',
                'description' => 'Rol de prueba',
                'modules' => [
                    $moduleId => ['can_view' => true, 'can_create' => false, 'can_edit' => false, 'can_delete' => false],
                ],
            ])
            ->assertStatus(201)
            ->assertJsonPath('data.title', 'Supervisor');

        $rolId = $response->json('data.id');
        $this->assertDatabaseHas('module_rol', ['rol_id' => $rolId, 'module_id' => $moduleId, 'can_view' => 1]);
    }

    public function test_destroy_rol_rejects_protected_admin_role(): void
    {
        $this->actingAs($this->adminUser())
            ->deleteJson(self::BASE . '/roles/1')
            ->assertStatus(422)
            ->assertJsonPath('message', 'No se puede eliminar el rol Administrador: es el rol base del sistema.');
    }

    public function test_destroy_rol_rejects_when_has_users(): void
    {
        $rolId = DB::table('rols')->insertGetId(['title' => 'ConUsuarios', 'description' => null]);
        DB::table('users')->insert([
            'names' => 'X', 'father_surname' => 'Y', 'mother_surname' => 'Z', 'username' => 'xyz',
            'email' => 'xyz@example.com', 'dni' => '00000005', 'cui' => '0', 'state_id' => 1, 'rol_id' => $rolId,
            'password' => bcrypt('password'),
        ]);

        $this->actingAs($this->adminUser())
            ->deleteJson(self::BASE . "/roles/{$rolId}")
            ->assertStatus(422)
            ->assertJsonPath('message', 'No se puede eliminar el rol porque tiene usuarios asociados.');
    }

    public function test_destroy_rol_deletes_when_unused(): void
    {
        $rolId = DB::table('rols')->insertGetId(['title' => 'SinUso', 'description' => null]);

        $this->actingAs($this->adminUser())
            ->deleteJson(self::BASE . "/roles/{$rolId}")
            ->assertStatus(204);

        $this->assertDatabaseMissing('rols', ['id' => $rolId]);
    }

    // ==================== MÓDULOS ====================

    public function test_modulos_endpoint_returns_json_collection(): void
    {
        $response = $this->actingAs($this->adminUser())
            ->getJson(self::BASE . '/modulos')
            ->assertOk();

        $sistema = collect($response->json('data'))->firstWhere('slug', 'sistema');
        $this->assertNotNull($sistema);
        $this->assertTrue($sistema['is_protected']);
    }

    public function test_destroy_modulo_rejects_protected_sistema_module(): void
    {
        $moduleId = DB::table('modules')->where('slug', 'sistema')->value('id');

        $this->actingAs($this->adminUser())
            ->deleteJson(self::BASE . "/modulos/{$moduleId}")
            ->assertStatus(422)
            ->assertJsonPath('message', 'No se puede eliminar el módulo Sistema: es requerido para administrar usuarios, roles y módulos.');
    }

    public function test_destroy_modulo_rejects_when_assigned_to_roles(): void
    {
        $moduleId = DB::table('modules')->insertGetId([
            'name' => 'Otro', 'slug' => 'otro', 'description' => null, 'icon' => 'fa-box', 'route' => 'otro', 'order' => 9, 'is_active' => true,
        ]);
        DB::table('module_rol')->insert([
            'module_id' => $moduleId, 'rol_id' => 1, 'can_view' => true, 'can_create' => true, 'can_edit' => true, 'can_delete' => true,
        ]);

        $this->actingAs($this->adminUser())
            ->deleteJson(self::BASE . "/modulos/{$moduleId}")
            ->assertStatus(422)
            ->assertJsonPath('message', 'No se puede eliminar el módulo porque está asignado a uno o más roles.');
    }

    public function test_destroy_modulo_deletes_when_unused(): void
    {
        $moduleId = DB::table('modules')->insertGetId([
            'name' => 'Huerfano', 'slug' => 'huerfano', 'description' => null, 'icon' => 'fa-box', 'route' => 'huerfano', 'order' => 9, 'is_active' => true,
        ]);

        $this->actingAs($this->adminUser())
            ->deleteJson(self::BASE . "/modulos/{$moduleId}")
            ->assertStatus(204);

        $this->assertDatabaseMissing('modules', ['id' => $moduleId]);
    }

    // ==================== NOTIFICACIONES ====================

    public function test_notifications_endpoints_available_to_any_authenticated_user(): void
    {
        $basic = $this->createBasicUser();

        $this->actingAs($basic)
            ->getJson(self::BASE . '/notifications')
            ->assertOk();

        $this->actingAs($basic)
            ->getJson(self::BASE . '/notifications/unread-count')
            ->assertOk()
            ->assertJsonStructure(['count', 'label']);

        $this->actingAs($basic)
            ->postJson(self::BASE . '/notifications/mark-seen')
            ->assertOk();
    }

    public function test_notifications_scoped_to_own_for_non_admin(): void
    {
        $basic = $this->createBasicUser();
        \App\Models\Notification::createPasswordResetRequest($basic);
        \App\Models\Notification::createPasswordResetRequest($this->adminUser());

        $this->actingAs($basic)
            ->getJson(self::BASE . '/notifications')
            ->assertOk()
            ->assertJsonCount(1, 'data');

        $this->actingAs($this->adminUser())
            ->getJson(self::BASE . '/notifications')
            ->assertOk()
            ->assertJsonCount(2, 'data');
    }

    public function test_approve_notification_resets_password_to_dni(): void
    {
        $basic = $this->createBasicUser();
        $notification = \App\Models\Notification::createPasswordResetRequest($basic);

        $this->actingAs($this->adminUser())
            ->postJson(self::BASE . "/notifications/{$notification->id}/approve")
            ->assertOk()
            ->assertJsonPath('data.status', 'approved');

        $this->assertTrue(\Illuminate\Support\Facades\Hash::check($basic->dni, $basic->fresh()->password));
    }

    public function test_reject_notification(): void
    {
        $basic = $this->createBasicUser();
        $notification = \App\Models\Notification::createPasswordResetRequest($basic);

        $this->actingAs($this->adminUser())
            ->postJson(self::BASE . "/notifications/{$notification->id}/reject")
            ->assertOk()
            ->assertJsonPath('data.status', 'rejected');
    }

    // ==================== AUTORIZACIÓN ====================

    public function test_usuarios_endpoint_requires_sistema_module_access(): void
    {
        $basic = $this->createBasicUser();

        $this->actingAs($basic)
            ->getJson(self::BASE . '/usuarios')
            ->assertStatus(403);
    }

    public function test_module_permissions_block_backend_actions_independently(): void
    {
        $rolId = DB::table('rols')->insertGetId(['title' => 'Solo lectura', 'description' => null]);
        DB::table('module_rol')->insert([
            'module_id' => DB::table('modules')->where('slug', 'sistema')->value('id'),
            'rol_id' => $rolId,
            'can_view' => true,
            'can_create' => false,
            'can_edit' => false,
            'can_delete' => false,
        ]);
        $userId = DB::table('users')->insertGetId([
            'names' => 'Lector', 'father_surname' => 'Sistema', 'mother_surname' => 'Test', 'username' => 'lector',
            'email' => 'lector@example.com', 'dni' => '00000009', 'cui' => '0', 'state_id' => 1, 'rol_id' => $rolId,
            'password' => bcrypt('password'),
        ]);
        $user = User::findOrFail($userId);

        $this->actingAs($user)->getJson(self::BASE . '/usuarios')->assertOk();
        $this->actingAs($user)->postJson(self::BASE . '/usuarios', [])->assertStatus(403);
        $this->actingAs($user)->putJson(self::BASE . "/usuarios/{$user->id}", [])->assertStatus(403);
        $this->actingAs($user)->deleteJson(self::BASE . "/usuarios/{$user->id}")->assertStatus(403);
    }

    public function test_inactive_module_blocks_non_admin_access(): void
    {
        $user = $this->createBasicUser(true);
        DB::table('modules')->where('slug', 'sistema')->update(['is_active' => false]);

        $this->actingAs($user)->getJson(self::BASE . '/usuarios')->assertStatus(403);
    }

    public function test_role_sync_clears_action_permissions_when_view_is_disabled(): void
    {
        $moduleId = DB::table('modules')->where('slug', 'sistema')->value('id');
        $response = $this->actingAs($this->adminUser())->postJson(self::BASE . '/roles', [
            'title' => 'Sin vista',
            'modules' => [
                $moduleId => ['can_view' => false, 'can_create' => true, 'can_edit' => true, 'can_delete' => true],
            ],
        ])->assertCreated();

        $this->assertDatabaseHas('module_rol', [
            'rol_id' => $response->json('data.id'),
            'module_id' => $moduleId,
            'can_view' => false,
            'can_create' => false,
            'can_edit' => false,
            'can_delete' => false,
        ]);
    }

    public function test_unauthenticated_request_gets_401(): void
    {
        $this->getJson(self::BASE . '/usuarios')->assertStatus(401);
    }
}
