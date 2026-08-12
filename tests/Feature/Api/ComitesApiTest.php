<?php

namespace Tests\Feature\Api;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;
use Tests\Traits\SeedsBaseData;

class ComitesApiTest extends TestCase
{
    use RefreshDatabase;
    use SeedsBaseData;

    private const BASE = '/api/dashboard/club-madres';

    protected function setUp(): void
    {
        parent::setUp();
        $this->seedBaseData();
        $this->seedModuleData();
    }

    private function seedModuleData(): void
    {
        $now = now();

        // Estado Inhabilitado (el seed base crea 'Activo' con id 1)
        DB::table('states')->insert([
            'id' => 2,
            'title' => 'Inhabilitado',
            'abbreviation' => 'I',
        ]);

        $moduleId = DB::table('modules')->insertGetId([
            'name' => 'Comités',
            'slug' => 'club-madres',
            'description' => 'Club de Madres y Reconocimientos',
            'icon' => 'fa-people-group',
            'route' => 'club-madres',
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

        DB::table('rols')->insert([
            'id' => 2,
            'title' => 'Operador',
            'description' => 'Sin acceso al módulo de comités',
        ]);

        DB::table('users')->insert([
            'id' => 2,
            'names' => 'Sin',
            'father_surname' => 'Acceso',
            'mother_surname' => 'Prueba',
            'username' => 'sinacceso',
            'email' => 'sinacceso@example.com',
            'dni' => '00000002',
            'cui' => '0',
            'state_id' => 1,
            'rol_id' => 2,
            'password' => bcrypt('password'),
        ]);

        DB::table('places')->insert(['id' => 1, 'code' => 'Z001', 'title' => 'Zona Central', 'created_at' => $now, 'updated_at' => $now]);
        DB::table('sectors')->insert(['id' => 1, 'title' => 'Sector 1', 'created_at' => $now, 'updated_at' => $now]);
        DB::table('place_sectors')->insert(['id' => 1, 'place_id' => 1, 'sector_id' => 1, 'created_at' => $now, 'updated_at' => $now]);
        DB::table('type_premises')->insert(['id' => 1, 'title' => 'Local Comunal', 'created_at' => $now, 'updated_at' => $now]);

        DB::table('positions')->insert(['id' => 1, 'title' => 'PRESIDENTA', 'created_at' => $now, 'updated_at' => $now]);

        DB::table('resolutions')->insert([
            [
                'id' => 1,
                'document' => 'RES-001',
                'date_document' => '2024-01-15 10:00:00',
                'date_start' => '2024-01-15',
                'date_end' => '2026-12-31',
                'state_id' => 1,
                'created_at' => $now,
                'updated_at' => $now,
            ],
            [
                'id' => 2,
                'document' => 'RES-002',
                'date_document' => '2025-02-20 10:00:00',
                'date_start' => '2025-02-20',
                'date_end' => '2026-12-31',
                'state_id' => 1,
                'created_at' => $now,
                'updated_at' => $now,
            ],
        ]);

        DB::table('associations')->insert([
            [
                'id' => 1,
                'code' => 'CDM',
                'name' => 'Comité Demo',
                'company_name' => 'Comité Demo SAC',
                'address' => 'Av. Demo 123',
                'resolution_id' => 1,
                'state_id' => 1,
                'place_sector_id' => 1,
                'type_premises_id' => 1,
                'created_at' => $now,
                'updated_at' => $now,
            ],
            [
                'id' => 2,
                'code' => 'CDM2',
                'name' => 'Comité Secundario',
                'company_name' => 'Comité Secundario SAC',
                'address' => 'Av. Secundaria 456',
                'resolution_id' => 2,
                'state_id' => 2,
                'place_sector_id' => 1,
                'type_premises_id' => 1,
                'created_at' => $now,
                'updated_at' => $now,
            ],
        ]);

        // Historial: el comité 1 también tuvo la resolución 2
        DB::table('resolution_associations')->insert([
            'resolution_id' => 2,
            'association_id' => 1,
            'created_at' => $now,
            'updated_at' => $now,
        ]);

        DB::table('people')->insert([
            [
                'id' => 1,
                'names' => 'María',
                'father_lastname' => 'Apellido',
                'mother_lastname' => 'Materno',
                'dni' => '12345671',
                'gender' => 'F',
                'birthdate' => '1990-01-01',
                'address' => 'Calle 1',
                'place_sector_id' => 1,
                'created_at' => $now,
                'updated_at' => $now,
            ],
            [
                'id' => 2,
                'names' => 'Juan',
                'father_lastname' => 'Apellido',
                'mother_lastname' => 'Materno',
                'dni' => '12345672',
                'gender' => 'M',
                'birthdate' => '1991-01-01',
                'address' => 'Calle 2',
                'place_sector_id' => 1,
                'created_at' => $now,
                'updated_at' => $now,
            ],
        ]);

        DB::table('partners')->insert([
            [
                'id' => 1,
                'person_id' => 1,
                'association_id' => 1,
                'state_id' => 1,
                'date_begin' => $now->toDateString(),
                'created_at' => $now,
                'updated_at' => $now,
            ],
            [
                'id' => 2,
                'person_id' => 2,
                'association_id' => 2,
                'state_id' => 1,
                'date_begin' => $now->toDateString(),
                'created_at' => $now,
                'updated_at' => $now,
            ],
        ]);

        // Presidenta vigente del comité 1
        DB::table('directives')->insert([
            'id' => 1,
            'resolution_id' => 1,
            'partner_id' => 1,
            'position_id' => 1,
            'state_id' => 1,
            'date_start' => $now->toDateString(),
            'created_at' => $now,
            'updated_at' => $now,
        ]);

        DB::table('responsibles')->insert([
            'id' => 1,
            'person_id' => 2,
            'type' => 'chief',
            'active' => true,
            'created_at' => $now,
            'updated_at' => $now,
        ]);
    }

    private function userWithAccess(): \App\Models\User
    {
        return $this->adminUser();
    }

    private function clubPayload(string $code = 'CDM3', string $companyName = 'Comité Nuevo SAC'): array
    {
        return [
            'code' => $code,
            'name' => 'Comité Nuevo',
            'company_name' => $companyName,
            'address' => 'Av. Test 123',
            'phone' => '999999999',
            'resolution_id' => 2,
            'place_sector_id' => 1,
            'type_premises_id' => 1,
            'observation' => null,
        ];
    }

    private function reconocimientoPayload(string $document = 'RES-003'): array
    {
        return [
            'document' => $document,
            'date_document' => '2026-01-10 09:00:00',
            'date_start' => '2026-01-10',
            'date_end' => '2026-12-31',
            'state_id' => 1,
        ];
    }

    // ==================== AUTENTICACIÓN / AUTORIZACIÓN ====================

    public function test_unauthenticated_request_is_rejected(): void
    {
        $this->getJson(self::BASE . '/clubs')
            ->assertStatus(401);
    }

    public function test_user_without_module_access_gets_403(): void
    {
        $this->actingAs(\App\Models\User::findOrFail(2))
            ->getJson(self::BASE . '/clubs')
            ->assertStatus(403);
    }

    // ==================== COMITÉS ====================

    public function test_clubs_endpoint_returns_json_collection(): void
    {
        $this->actingAs($this->userWithAccess())
            ->getJson(self::BASE . '/clubs')
            ->assertOk()
            ->assertJsonStructure([
                'data' => ['*' => [
                    'id', 'code', 'name', 'company_name', 'address', 'state_id',
                    'resolution_id', 'place_sector_id', 'type_premises_id',
                    'state', 'resolution', 'place_sector', 'type_premises',
                    'president_partner_id', 'president_name', 'latest_resolution', 'all_resolutions',
                ]],
                'links' => [],
                'meta' => [],
            ]);
    }

    public function test_clubs_endpoint_includes_president_and_resolution_history(): void
    {
        $this->actingAs($this->userWithAccess())
            ->getJson(self::BASE . '/clubs')
            ->assertOk()
            ->assertJsonPath('data.1.code', 'CDM')
            ->assertJsonPath('data.1.president_name', 'María Apellido')
            ->assertJsonPath('data.1.president_partner_id', 1)
            ->assertJsonPath('data.1.latest_resolution.document', 'RES-002')
            ->assertJsonCount(2, 'data.1.all_resolutions');
    }

    public function test_clubs_endpoint_filters_by_state_and_vigencia(): void
    {
        $this->actingAs($this->userWithAccess())
            ->getJson(self::BASE . '/clubs?state_id=2')
            ->assertOk()
            ->assertJsonPath('meta.total', 1)
            ->assertJsonPath('data.0.code', 'CDM2');

        $this->actingAs($this->userWithAccess())
            ->getJson(self::BASE . '/clubs?vigencia=vencido')
            ->assertOk()
            ->assertJsonPath('meta.total', 0);
    }

    public function test_clubs_options_endpoint(): void
    {
        $this->actingAs($this->userWithAccess())
            ->getJson(self::BASE . '/clubs/options')
            ->assertOk()
            ->assertJsonStructure([
                'states' => ['*' => ['id', 'title', 'abbreviation']],
                'place_sectors' => ['*' => ['id', 'place', 'sector']],
                'type_premises' => ['*' => ['id', 'title']],
                'resolutions' => ['*' => ['id', 'document']],
            ]);
    }

    public function test_club_show_returns_partners_and_resolutions(): void
    {
        $this->actingAs($this->userWithAccess())
            ->getJson(self::BASE . '/clubs/1')
            ->assertOk()
            ->assertJsonPath('data.code', 'CDM')
            ->assertJsonPath('data.president_name', 'María Apellido')
            ->assertJsonPath('data.partners.0.name', 'María Apellido Materno')
            ->assertJsonCount(2, 'data.all_resolutions');
    }

    public function test_store_club_creates_inhabilitado_club(): void
    {
        $this->actingAs($this->userWithAccess())
            ->postJson(self::BASE . '/clubs', $this->clubPayload())
            ->assertStatus(201)
            ->assertJsonPath('data.code', 'CDM3')
            ->assertJsonPath('data.company_name', 'Comité Nuevo SAC')
            ->assertJsonPath('data.phone', '999999999')
            ->assertJsonPath('data.state_id', 2);

        $this->assertDatabaseHas('associations', [
            'code' => 'CDM3',
            'company_name' => 'Comité Nuevo SAC',
            'phone' => '999999999',
            'state_id' => 2,
        ]);
    }

    public function test_store_club_rejects_without_company_name(): void
    {
        $payload = $this->clubPayload();
        unset($payload['company_name']);

        $this->actingAs($this->userWithAccess())
            ->postJson(self::BASE . '/clubs', $payload)
            ->assertStatus(422)
            ->assertJsonValidationErrors('company_name');
    }

    public function test_store_club_rejects_without_code(): void
    {
        $payload = $this->clubPayload();
        unset($payload['code']);

        $this->actingAs($this->userWithAccess())
            ->postJson(self::BASE . '/clubs', $payload)
            ->assertStatus(422)
            ->assertJsonValidationErrors('code');
    }

    public function test_store_club_rejects_duplicate_code(): void
    {
        $this->actingAs($this->userWithAccess())
            ->postJson(self::BASE . '/clubs', $this->clubPayload('CDM'))
            ->assertStatus(422)
            ->assertJsonValidationErrors('code');
    }

    public function test_update_club_updates_company_name_and_phone(): void
    {
        $longCompanyName = 'Razón Social del Comité con nombre más largo de ciento cincuenta caracteres para validar el fix de la longitud máxima del campo company_name';

        $this->actingAs($this->userWithAccess())
            ->putJson(self::BASE . '/clubs/1', [
                'company_name' => $longCompanyName,
                'phone' => '987654321',
            ])
            ->assertOk()
            ->assertJsonPath('data.company_name', $longCompanyName)
            ->assertJsonPath('data.phone', '987654321');

        $this->assertDatabaseHas('associations', [
            'id' => 1,
            'company_name' => $longCompanyName,
            'phone' => '987654321',
        ]);
    }

    public function test_destroy_club_without_references_deletes(): void
    {
        $response = $this->actingAs($this->userWithAccess())
            ->postJson(self::BASE . '/clubs', $this->clubPayload('CDM3'))
            ->assertStatus(201);
        $clubId = $response->json('data.id');

        $this->actingAs($this->userWithAccess())
            ->deleteJson(self::BASE . '/clubs/' . $clubId)
            ->assertStatus(204);

        $this->assertDatabaseMissing('associations', ['id' => $clubId]);
    }

    public function test_destroy_club_rejects_when_has_partners(): void
    {
        $this->actingAs($this->userWithAccess())
            ->deleteJson(self::BASE . '/clubs/1')
            ->assertStatus(422)
            ->assertJsonPath('message', 'No se puede eliminar: el comité tiene socios, pecosas o historial de resoluciones asociado');

        $this->assertDatabaseHas('associations', ['id' => 1]);
    }

    public function test_destroy_club_rejects_when_has_pecosas(): void
    {
        $response = $this->actingAs($this->userWithAccess())
            ->postJson(self::BASE . '/clubs', $this->clubPayload('CDM3'))
            ->assertStatus(201);
        $clubId = $response->json('data.id');

        DB::table('pecosas')->insert([
            'pecosa_number' => 'PEC-001',
            'delivery_date' => now(),
            'chief_id' => 1,
            'storekeeper_id' => 1,
            'managing_partner_id' => null,
            'president_id' => null,
            'state_id' => 1,
            'association_id' => $clubId,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $this->actingAs($this->userWithAccess())
            ->deleteJson(self::BASE . '/clubs/' . $clubId)
            ->assertStatus(422)
            ->assertJsonPath('message', 'No se puede eliminar: el comité tiene socios, pecosas o historial de resoluciones asociado');

        $this->assertDatabaseHas('associations', ['id' => $clubId]);
    }

    public function test_destroy_club_rejects_when_has_resolution_history(): void
    {
        $response = $this->actingAs($this->userWithAccess())
            ->postJson(self::BASE . '/clubs', $this->clubPayload('CDM3'))
            ->assertStatus(201);
        $clubId = $response->json('data.id');

        DB::table('resolution_associations')->insert([
            'resolution_id' => 1,
            'association_id' => $clubId,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $this->actingAs($this->userWithAccess())
            ->deleteJson(self::BASE . '/clubs/' . $clubId)
            ->assertStatus(422);

        $this->assertDatabaseHas('associations', ['id' => $clubId]);
    }

    // ==================== RECONOCIMIENTOS ====================

    public function test_reconocimientos_endpoint_returns_json_collection(): void
    {
        $this->actingAs($this->userWithAccess())
            ->getJson(self::BASE . '/reconocimientos')
            ->assertOk()
            ->assertJsonStructure([
                'data' => ['*' => ['id', 'document', 'date_document', 'date_start', 'date_end', 'state_id', 'state']],
                'links' => [],
                'meta' => [],
            ])
            ->assertJsonCount(2, 'data');
    }

    public function test_reconocimientos_options_endpoint(): void
    {
        $this->actingAs($this->userWithAccess())
            ->getJson(self::BASE . '/reconocimientos/options')
            ->assertOk()
            ->assertJsonStructure([
                'states' => ['*' => ['id', 'title', 'abbreviation']],
                'years' => [],
            ])
            ->assertJsonPath('years.0', 2025);
    }

    public function test_store_reconocimiento_creates(): void
    {
        $this->actingAs($this->userWithAccess())
            ->postJson(self::BASE . '/reconocimientos', $this->reconocimientoPayload())
            ->assertStatus(201)
            ->assertJsonPath('data.document', 'RES-003');

        $this->assertDatabaseHas('resolutions', ['document' => 'RES-003']);
    }

    public function test_update_reconocimiento_updates(): void
    {
        $this->actingAs($this->userWithAccess())
            ->putJson(self::BASE . '/reconocimientos/1', ['document' => 'RES-001-UPD'])
            ->assertOk()
            ->assertJsonPath('data.document', 'RES-001-UPD');

        $this->assertDatabaseHas('resolutions', ['id' => 1, 'document' => 'RES-001-UPD']);
    }

    public function test_destroy_reconocimiento_without_references_deletes(): void
    {
        $response = $this->actingAs($this->userWithAccess())
            ->postJson(self::BASE . '/reconocimientos', $this->reconocimientoPayload('RES-003'))
            ->assertStatus(201);
        $resolutionId = $response->json('data.id');

        $this->actingAs($this->userWithAccess())
            ->deleteJson(self::BASE . '/reconocimientos/' . $resolutionId)
            ->assertStatus(204);

        $this->assertDatabaseMissing('resolutions', ['id' => $resolutionId]);
    }

    public function test_destroy_reconocimiento_rejects_when_has_associations(): void
    {
        $this->actingAs($this->userWithAccess())
            ->deleteJson(self::BASE . '/reconocimientos/1')
            ->assertStatus(422)
            ->assertJsonPath('message', 'No se puede eliminar la resolución: tiene comités o directivas asociadas');

        $this->assertDatabaseHas('resolutions', ['id' => 1]);
    }

    // ==================== ASIGNAR PRESIDENTA ====================

    public function test_asignar_presidenta_habilita_comite(): void
    {
        $this->actingAs($this->userWithAccess())
            ->postJson(self::BASE . '/clubs/2/asignar-presidenta', ['partner_id' => 2])
            ->assertOk()
            ->assertJsonPath('data.state_id', 1)
            ->assertJsonPath('data.president_partner_id', 2)
            ->assertJsonPath('data.president_name', 'Juan Apellido');

        $this->assertDatabaseHas('directives', [
            'resolution_id' => 2,
            'partner_id' => 2,
            'position_id' => 1,
            'state_id' => 1,
        ]);

        $this->assertDatabaseHas('associations', ['id' => 2, 'state_id' => 1]);
    }

    public function test_asignar_presidenta_rejects_partner_of_other_club(): void
    {
        $this->actingAs($this->userWithAccess())
            ->postJson(self::BASE . '/clubs/1/asignar-presidenta', ['partner_id' => 2])
            ->assertStatus(422)
            ->assertJsonPath('message', 'La socia seleccionada no pertenece a este comité');

        $this->assertDatabaseMissing('directives', ['partner_id' => 2, 'resolution_id' => 1]);
    }
}
