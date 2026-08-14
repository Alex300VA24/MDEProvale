<?php

namespace Tests\Feature\Api;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;
use Tests\Traits\SeedsBaseData;

class SociosBeneficiariosApiTest extends TestCase
{
    use RefreshDatabase;
    use SeedsBaseData;

    private const BASE = '/api/dashboard/socios-beneficiarios';

    protected function setUp(): void
    {
        parent::setUp();
        $this->seedBaseData();
    }

    private function userWithAccess(): \App\Models\User
    {
        return $this->adminUser();
    }

    public function test_partners_endpoint_returns_json_collection(): void
    {
        $this->actingAs($this->userWithAccess())
            ->getJson(self::BASE . '/partners')
            ->assertOk()
            ->assertJsonStructure([
                'data' => ['*' => ['id', 'name', 'date_begin', 'person', 'association', 'state', 'beneficiaries_count']],
                'links' => [],
                'meta' => [],
            ]);
    }

    public function test_partners_endpoint_filters_by_search(): void
    {
        $this->actingAs($this->userWithAccess())
            ->getJson(self::BASE . '/partners?search=zzzz&association_id=1&state_id=1')
            ->assertOk()
            ->assertJsonStructure(['data' => [], 'meta' => []]);
    }

    public function test_partners_options_endpoint(): void
    {
        $this->actingAs($this->userWithAccess())
            ->getJson(self::BASE . '/partners/options')
            ->assertOk()
            ->assertJsonStructure([
                'associations' => [],
                'states' => [],
                'people' => [],
                'all_people' => [],
                'relationships' => [],
                'place_sectors' => [],
                'type_benefits' => [],
                'reason_disqualifications' => [],
            ]);
    }

    public function test_personas_endpoint_returns_json_collection(): void
    {
        $this->actingAs($this->userWithAccess())
            ->getJson(self::BASE . '/personas')
            ->assertOk()
            ->assertJsonStructure([
                'data' => ['*' => ['id', 'names', 'father_lastname', 'mother_lastname', 'dni', 'age_formatted']],
                'links' => [],
                'meta' => [],
            ]);
    }

    public function test_personas_options_endpoint(): void
    {
        $this->actingAs($this->userWithAccess())
            ->getJson(self::BASE . '/personas/options')
            ->assertOk()
            ->assertJsonStructure(['place_sectors' => []]);
    }

    public function test_beneficiarios_endpoint_returns_json_collection(): void
    {
        $this->actingAs($this->userWithAccess())
            ->getJson(self::BASE . '/beneficiarios')
            ->assertOk()
            ->assertJsonStructure([
                'data' => ['*' => ['id', 'name', 'person', 'partner', 'relationship']],
                'links' => [],
                'meta' => [],
            ]);
    }

    public function test_beneficiarios_options_endpoint(): void
    {
        $this->actingAs($this->userWithAccess())
            ->getJson(self::BASE . '/beneficiarios/options')
            ->assertOk()
            ->assertJsonStructure(['partners' => [], 'relationships' => []]);
    }

    // ==================== FIXTURES ====================

    private function seedAssociation(): int
    {
        $now = now();
        \Illuminate\Support\Facades\DB::table('places')->insert(['id' => 1, 'code' => 'Z001', 'title' => 'Zona Central', 'created_at' => $now, 'updated_at' => $now]);
        \Illuminate\Support\Facades\DB::table('sectors')->insert(['id' => 1, 'title' => 'Sector 1', 'created_at' => $now, 'updated_at' => $now]);
        \Illuminate\Support\Facades\DB::table('place_sectors')->insert(['id' => 1, 'place_id' => 1, 'sector_id' => 1, 'created_at' => $now, 'updated_at' => $now]);
        \Illuminate\Support\Facades\DB::table('type_premises')->insert(['id' => 1, 'title' => 'Local Comunal', 'created_at' => $now, 'updated_at' => $now]);
        \Illuminate\Support\Facades\DB::table('resolutions')->insert(['id' => 1, 'document' => 'RES-001', 'state_id' => 1, 'created_at' => $now, 'updated_at' => $now]);

        return \Illuminate\Support\Facades\DB::table('associations')->insertGetId([
            'code' => 'CDM1', 'name' => 'Comité Central', 'company_name' => 'Comité Central SAC',
            'address' => 'Av. 1', 'resolution_id' => 1, 'state_id' => 1, 'place_sector_id' => 1,
            'type_premises_id' => 1, 'created_at' => $now, 'updated_at' => $now,
        ]);
    }

    private function seedPerson(array $overrides = []): int
    {
        return \Illuminate\Support\Facades\DB::table('people')->insertGetId(array_merge([
            'names' => 'Sandro', 'father_lastname' => 'Cardenas', 'mother_lastname' => 'Vilca',
            'dni' => (string) random_int(10000000, 99999999),
            'gender' => 'M',
            'created_at' => now(), 'updated_at' => now(),
        ], $overrides));
    }

    // ==================== PERSONAS: CRUD ====================

    public function test_store_persona_creates_persona(): void
    {
        $this->actingAs($this->userWithAccess())
            ->postJson(self::BASE . '/personas', [
                'names' => 'Sandro',
                'father_lastname' => 'Cardenas',
                'mother_lastname' => 'Vilca',
                'dni' => '72843944',
            ])
            ->assertStatus(201)
            ->assertJsonPath('data.names', 'Sandro');

        $this->assertDatabaseHas('people', ['dni' => '72843944']);
    }

    public function test_update_persona_updates_fields_without_touching_own_dni(): void
    {
        // Regresión: la validación 'unique' de dni en edición debe ignorar el
        // propio registro sin romperse. `route('person')` resuelve al modelo
        // (route-model-binding) y concatenarlo como string invoca su __toString
        // (Laravel lo serializa a JSON), lo que corrompe la regla `unique:...`.
        $personId = $this->seedPerson(['dni' => '72843944']);

        $this->actingAs($this->userWithAccess())
            ->putJson(self::BASE . "/personas/{$personId}", [
                'names' => 'Sandro Andres',
                'dni' => '72843944',
            ])
            ->assertOk()
            ->assertJsonPath('data.names', 'Sandro Andres');

        $this->assertDatabaseHas('people', ['id' => $personId, 'names' => 'Sandro Andres', 'dni' => '72843944']);
    }

    public function test_update_persona_rejects_dni_already_used_by_another_person(): void
    {
        $this->seedPerson(['dni' => '11111111']);
        $personId = $this->seedPerson(['dni' => '22222222']);

        $this->actingAs($this->userWithAccess())
            ->putJson(self::BASE . "/personas/{$personId}", ['dni' => '11111111'])
            ->assertStatus(422);
    }

    public function test_destroy_persona_without_relations_deletes(): void
    {
        $personId = $this->seedPerson();

        $this->actingAs($this->userWithAccess())
            ->deleteJson(self::BASE . "/personas/{$personId}")
            ->assertStatus(204);

        $this->assertDatabaseMissing('people', ['id' => $personId]);
    }

    public function test_destroy_persona_rejects_when_associated_to_partner(): void
    {
        $associationId = $this->seedAssociation();
        $personId = $this->seedPerson();
        \Illuminate\Support\Facades\DB::table('partners')->insert([
            'person_id' => $personId, 'association_id' => $associationId, 'state_id' => 1,
            'date_begin' => now()->toDateString(), 'created_at' => now(), 'updated_at' => now(),
        ]);

        $this->actingAs($this->userWithAccess())
            ->deleteJson(self::BASE . "/personas/{$personId}")
            ->assertStatus(422);

        $this->assertDatabaseHas('people', ['id' => $personId]);
    }

    // ==================== SOCIOS: CRUD ====================

    public function test_store_partner_creates_socio(): void
    {
        $associationId = $this->seedAssociation();
        $personId = $this->seedPerson();

        $this->actingAs($this->userWithAccess())
            ->postJson(self::BASE . '/partners', [
                'person_id' => $personId,
                'association_id' => $associationId,
                'state_id' => 1,
                'date_begin' => now()->toDateString(),
            ])
            ->assertStatus(201)
            ->assertJsonPath('data.person.id', $personId);

        $this->assertDatabaseHas('partners', ['person_id' => $personId, 'association_id' => $associationId]);
    }

    public function test_update_partner_updates_observations(): void
    {
        $associationId = $this->seedAssociation();
        $personId = $this->seedPerson();
        $partnerId = \Illuminate\Support\Facades\DB::table('partners')->insertGetId([
            'person_id' => $personId, 'association_id' => $associationId, 'state_id' => 1,
            'date_begin' => now()->toDateString(), 'created_at' => now(), 'updated_at' => now(),
        ]);

        $this->actingAs($this->userWithAccess())
            ->putJson(self::BASE . "/partners/{$partnerId}", ['observations' => 'Actualizado'])
            ->assertOk()
            ->assertJsonPath('data.observations', 'Actualizado');

        $this->assertDatabaseHas('partners', ['id' => $partnerId, 'observations' => 'Actualizado']);
    }

    public function test_destroy_partner_deletes(): void
    {
        $associationId = $this->seedAssociation();
        $personId = $this->seedPerson();
        $partnerId = \Illuminate\Support\Facades\DB::table('partners')->insertGetId([
            'person_id' => $personId, 'association_id' => $associationId, 'state_id' => 1,
            'date_begin' => now()->toDateString(), 'created_at' => now(), 'updated_at' => now(),
        ]);

        $this->actingAs($this->userWithAccess())
            ->deleteJson(self::BASE . "/partners/{$partnerId}")
            ->assertStatus(204);

        $this->assertDatabaseMissing('partners', ['id' => $partnerId]);
    }

    // ==================== BENEFICIARIOS: CRUD ====================

    private function seedPartnerWithRelationship(): array
    {
        $associationId = $this->seedAssociation();
        $partnerPersonId = $this->seedPerson();
        $partnerId = \Illuminate\Support\Facades\DB::table('partners')->insertGetId([
            'person_id' => $partnerPersonId, 'association_id' => $associationId, 'state_id' => 1,
            'date_begin' => now()->toDateString(), 'created_at' => now(), 'updated_at' => now(),
        ]);
        $relationshipId = \Illuminate\Support\Facades\DB::table('relationships')->insertGetId([
            'title' => 'HIJO(A)', 'created_at' => now(), 'updated_at' => now(),
        ]);

        return [$partnerId, $relationshipId];
    }

    public function test_store_beneficiario_creates(): void
    {
        [$partnerId, $relationshipId] = $this->seedPartnerWithRelationship();
        $beneficiaryPersonId = $this->seedPerson();

        $this->actingAs($this->userWithAccess())
            ->postJson(self::BASE . '/beneficiarios', [
                'person_id' => $beneficiaryPersonId,
                'partner_id' => $partnerId,
                'relationship_id' => $relationshipId,
            ])
            ->assertStatus(201)
            ->assertJsonPath('data.person.id', $beneficiaryPersonId);

        $this->assertDatabaseHas('beneficiaries', ['person_id' => $beneficiaryPersonId, 'partner_id' => $partnerId]);
    }

    public function test_update_beneficiario_updates_relationship(): void
    {
        [$partnerId, $relationshipId] = $this->seedPartnerWithRelationship();
        $beneficiaryPersonId = $this->seedPerson();
        $beneficiarieId = \Illuminate\Support\Facades\DB::table('beneficiaries')->insertGetId([
            'person_id' => $beneficiaryPersonId, 'partner_id' => $partnerId, 'relationship_id' => $relationshipId,
            'created_at' => now(), 'updated_at' => now(),
        ]);
        $newRelationshipId = \Illuminate\Support\Facades\DB::table('relationships')->insertGetId([
            'title' => 'NIETO(A)', 'created_at' => now(), 'updated_at' => now(),
        ]);

        $this->actingAs($this->userWithAccess())
            ->putJson(self::BASE . "/beneficiarios/{$beneficiarieId}", [
                'person_id' => $beneficiaryPersonId,
                'partner_id' => $partnerId,
                'relationship_id' => $newRelationshipId,
            ])
            ->assertOk()
            ->assertJsonPath('data.relationship.id', $newRelationshipId);

        $this->assertDatabaseHas('beneficiaries', ['id' => $beneficiarieId, 'relationship_id' => $newRelationshipId]);
    }

    public function test_destroy_beneficiario_deletes(): void
    {
        [$partnerId, $relationshipId] = $this->seedPartnerWithRelationship();
        $beneficiaryPersonId = $this->seedPerson();
        $beneficiarieId = \Illuminate\Support\Facades\DB::table('beneficiaries')->insertGetId([
            'person_id' => $beneficiaryPersonId, 'partner_id' => $partnerId, 'relationship_id' => $relationshipId,
            'created_at' => now(), 'updated_at' => now(),
        ]);

        $this->actingAs($this->userWithAccess())
            ->deleteJson(self::BASE . "/beneficiarios/{$beneficiarieId}")
            ->assertStatus(204);

        $this->assertDatabaseMissing('beneficiaries', ['id' => $beneficiarieId]);
    }

    public function test_user_without_module_access_gets_403(): void
    {
        $user = $this->userWithAccess();
        $user->forceFill(['rol_id' => 999999]);

        $this->actingAs($user)
            ->getJson(self::BASE . '/partners')
            ->assertForbidden();
    }

    public function test_unauthenticated_request_gets_401(): void
    {
        $this->getJson(self::BASE . '/partners')
            ->assertUnauthorized();
    }
}
