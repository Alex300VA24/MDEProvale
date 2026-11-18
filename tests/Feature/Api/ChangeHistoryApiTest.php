<?php

namespace Tests\Feature\Api;

use App\Models\AuditLog;
use App\Models\Association;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;
use Tests\Traits\SeedsBaseData;

class ChangeHistoryApiTest extends TestCase
{
    use RefreshDatabase;
    use SeedsBaseData;

    private const BASE = '/api/dashboard/sistema/change-history';

    private function admin(): User
    {
        $this->seedBaseData();
        $moduleId = DB::table('modules')->insertGetId([
            'name' => 'Sistema', 'slug' => 'sistema', 'description' => 'Sistema',
            'icon' => 'fa-cogs', 'route' => 'sistema', 'order' => 4, 'is_active' => true,
        ]);
        DB::table('module_rol')->insert([
            'module_id' => $moduleId, 'rol_id' => 1,
            'can_view' => true, 'can_create' => true, 'can_edit' => true, 'can_delete' => true,
        ]);

        return User::where('rol_id', 1)->firstOrFail();
    }

    public function test_model_changes_are_logged_with_user_and_field_diff(): void
    {
        $admin = $this->admin();
        $this->actingAs($admin);

        $user = User::factory()->create(['rol_id' => 1, 'state_id' => $admin->state_id]);
        $user->update(['names' => 'Nombre Nuevo', 'password' => bcrypt('secreto-123')]);

        $created = AuditLog::where('action', 'created')->where('auditable_id', $user->id)->first();
        $this->assertNotNull($created);
        $this->assertSame($admin->id, $created->user_id);

        $updated = AuditLog::where('action', 'updated')->where('auditable_id', $user->id)->firstOrFail();
        $fields = $updated->changes()->pluck('new_value', 'field');
        $this->assertSame('Nombre Nuevo', $fields['names']);
        $this->assertArrayNotHasKey('password', $fields->all());
    }

    public function test_list_filters_and_detail_endpoint(): void
    {
        $admin = $this->admin();
        $this->actingAs($admin);
        $user = User::factory()->create(['rol_id' => 1, 'state_id' => $admin->state_id]);
        $user->update(['names' => 'Otro']);

        $this->getJson(self::BASE.'?action=updated&module=usuarios')
            ->assertOk()
            ->assertJsonPath('data.0.action', 'updated');

        $log = AuditLog::where('action', 'updated')->firstOrFail();
        $this->getJson(self::BASE.'/'.$log->id)
            ->assertOk()
            ->assertJsonPath('data.changes.0.field', 'names');

        $this->getJson(self::BASE.'/options')->assertOk()->assertJsonStructure(['data' => ['modules', 'actions', 'users']]);
    }

    public function test_requires_sistema_module_access(): void
    {
        $this->seedBaseData();
        $rolId = DB::table('rols')->insertGetId(['title' => 'Basico', 'description' => 'x']);
        $user = User::factory()->create(['rol_id' => $rolId]);

        $this->actingAs($user)->getJson(self::BASE)->assertForbidden();
    }
}
