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
