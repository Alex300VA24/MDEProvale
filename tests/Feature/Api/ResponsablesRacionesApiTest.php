<?php

namespace Tests\Feature\Api;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;
use Tests\Traits\SeedsBaseData;

class ResponsablesRacionesApiTest extends TestCase
{
    use RefreshDatabase;
    use SeedsBaseData;

    private const BASE = '/api/dashboard/responsables-raciones';

    protected function setUp(): void
    {
        parent::setUp();
        $this->seedBaseData();
        $this->seedResponsablesRacionesModule();
        $this->seedPeople();
    }

    private function seedResponsablesRacionesModule(): void
    {
        $moduleId = DB::table('modules')->insertGetId([
            'name' => 'Responsables y Raciones',
            'slug' => 'responsables-raciones',
            'description' => 'Gestión de responsables del programa y raciones por año',
            'icon' => 'fa-sliders',
            'route' => 'responsables-raciones',
            'order' => 3,
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

    private function seedPeople(): void
    {
        $now = now();

        DB::table('places')->insert(['id' => 1, 'code' => 'Z001', 'title' => 'Zona Central', 'created_at' => $now, 'updated_at' => $now]);
        DB::table('sectors')->insert(['id' => 1, 'title' => 'Sector 1', 'created_at' => $now, 'updated_at' => $now]);
        DB::table('place_sectors')->insert(['id' => 1, 'place_id' => 1, 'sector_id' => 1, 'created_at' => $now, 'updated_at' => $now]);

        DB::table('people')->insert([
            ['id' => 1, 'names' => 'María', 'father_lastname' => 'Apellido', 'mother_lastname' => 'Materno', 'dni' => '12345671', 'gender' => 'F', 'birthdate' => '1990-01-01', 'address' => 'Calle 1', 'place_sector_id' => 1, 'created_at' => $now, 'updated_at' => $now],
            ['id' => 2, 'names' => 'Juan', 'father_lastname' => 'Apellido', 'mother_lastname' => 'Materno', 'dni' => '12345672', 'gender' => 'M', 'birthdate' => '1985-05-05', 'address' => 'Calle 2', 'place_sector_id' => 1, 'created_at' => $now, 'updated_at' => $now],
        ]);
    }

    // ==================== RESPONSABLES ====================

    public function test_responsibles_endpoint_returns_chief_storekeeper_and_people(): void
    {
        $this->actingAs($this->adminUser())
            ->getJson(self::BASE . '/responsibles')
            ->assertOk()
            ->assertJsonStructure(['chief', 'storekeeper', 'people'])
            ->assertJsonPath('chief', null)
            ->assertJsonPath('storekeeper', null)
            ->assertJsonCount(2, 'people');
    }

    public function test_update_responsible_assigns_chief_and_deactivates_previous(): void
    {
        $this->actingAs($this->adminUser())
            ->putJson(self::BASE . '/responsibles/chief', ['person_id' => 1])
            ->assertOk()
            ->assertJsonPath('data.type', 'chief')
            ->assertJsonPath('data.person_id', 1);

        $this->actingAs($this->adminUser())
            ->putJson(self::BASE . '/responsibles/chief', ['person_id' => 2])
            ->assertOk()
            ->assertJsonPath('data.person_id', 2);

        $this->assertDatabaseHas('responsibles', ['person_id' => 1, 'type' => 'chief', 'active' => 0]);
        $this->assertDatabaseHas('responsibles', ['person_id' => 2, 'type' => 'chief', 'active' => 1]);
    }

    public function test_update_responsible_rejects_invalid_type(): void
    {
        $this->actingAs($this->adminUser())
            ->putJson(self::BASE . '/responsibles/manager', ['person_id' => 1])
            ->assertStatus(404);
    }

    public function test_update_responsible_requires_valid_person(): void
    {
        $this->actingAs($this->adminUser())
            ->putJson(self::BASE . '/responsibles/storekeeper', ['person_id' => 999])
            ->assertStatus(422)
            ->assertJsonValidationErrors('person_id');
    }

    // ==================== RACIONES ====================

    public function test_raciones_endpoint_returns_json_collection(): void
    {
        DB::table('raciones')->insert([
            'year' => '2026', 'racion_hojuelas_gramos' => 50, 'racion_leche_militros' => 410, 'active' => true, 'created_at' => now(), 'updated_at' => now(),
        ]);

        $this->actingAs($this->adminUser())
            ->getJson(self::BASE . '/raciones')
            ->assertOk()
            ->assertJsonCount(1, 'data')
            ->assertJsonPath('data.0.year', 2026);
    }

    public function test_store_racion_creates_racion(): void
    {
        $this->actingAs($this->adminUser())
            ->postJson(self::BASE . '/raciones', [
                'year' => 2027,
                'racion_hojuelas_gramos' => 50,
                'racion_leche_militros' => 410,
            ])
            ->assertStatus(201)
            ->assertJsonPath('data.year', 2027)
            ->assertJsonPath('data.active', true);

        $this->assertDatabaseHas('raciones', ['year' => 2027]);
    }

    public function test_store_racion_rejects_duplicate_year(): void
    {
        DB::table('raciones')->insert([
            'year' => '2026', 'racion_hojuelas_gramos' => 50, 'racion_leche_militros' => 410, 'active' => true, 'created_at' => now(), 'updated_at' => now(),
        ]);

        $this->actingAs($this->adminUser())
            ->postJson(self::BASE . '/raciones', [
                'year' => 2026,
                'racion_hojuelas_gramos' => 60,
                'racion_leche_militros' => 420,
            ])
            ->assertStatus(422)
            ->assertJsonValidationErrors('year');
    }

    public function test_update_racion_updates_values_but_not_year(): void
    {
        $id = DB::table('raciones')->insertGetId([
            'year' => '2026', 'racion_hojuelas_gramos' => 50, 'racion_leche_militros' => 410, 'active' => true, 'created_at' => now(), 'updated_at' => now(),
        ]);

        $this->actingAs($this->adminUser())
            ->putJson(self::BASE . "/raciones/{$id}", [
                'racion_hojuelas_gramos' => 55,
                'racion_leche_militros' => 415,
            ])
            ->assertOk()
            ->assertJsonPath('data.racion_hojuelas_gramos', 55.0)
            ->assertJsonPath('data.year', 2026);

        $this->assertDatabaseHas('raciones', ['id' => $id, 'racion_hojuelas_gramos' => 55, 'year' => '2026']);
    }

    public function test_destroy_racion_deletes(): void
    {
        $id = DB::table('raciones')->insertGetId([
            'year' => '2026', 'racion_hojuelas_gramos' => 50, 'racion_leche_militros' => 410, 'active' => true, 'created_at' => now(), 'updated_at' => now(),
        ]);

        $this->actingAs($this->adminUser())
            ->deleteJson(self::BASE . "/raciones/{$id}")
            ->assertStatus(204);

        $this->assertDatabaseMissing('raciones', ['id' => $id]);
    }

    public function test_destroy_racion_honors_role_delete_permission(): void
    {
        $rolId = DB::table('rols')->insertGetId(['title' => 'Basico', 'description' => 'Usuario básico']);
        DB::table('module_rol')->insert([
            'module_id' => DB::table('modules')->where('slug', 'responsables-raciones')->value('id'),
            'rol_id' => $rolId,
            'can_view' => true,
            'can_create' => true,
            'can_edit' => true,
            'can_delete' => true,
        ]);
        $userId = DB::table('users')->insertGetId([
            'names' => 'No', 'father_surname' => 'Admin', 'mother_surname' => 'User', 'username' => 'noadmin',
            'email' => 'noadmin@example.com', 'dni' => '00000002', 'cui' => '0', 'state_id' => 1, 'rol_id' => $rolId,
            'password' => bcrypt('password'),
        ]);

        $id = DB::table('raciones')->insertGetId([
            'year' => '2026', 'racion_hojuelas_gramos' => 50, 'racion_leche_militros' => 410, 'active' => true, 'created_at' => now(), 'updated_at' => now(),
        ]);

        $this->actingAs(\App\Models\User::find($userId))
            ->deleteJson(self::BASE . "/raciones/{$id}")
            ->assertNoContent();

        $this->assertDatabaseMissing('raciones', ['id' => $id]);
    }

    public function test_unauthenticated_request_gets_401(): void
    {
        $this->getJson(self::BASE . '/raciones')->assertStatus(401);
    }
}
