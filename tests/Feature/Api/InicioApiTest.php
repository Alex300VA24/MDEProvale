<?php

namespace Tests\Feature\Api;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;
use Tests\Traits\SeedsBaseData;

class InicioApiTest extends TestCase
{
    use RefreshDatabase;
    use SeedsBaseData;

    private const BASE = '/api/dashboard/inicio';

    protected function setUp(): void
    {
        parent::setUp();
        $this->seedBaseData();
    }

    private function seedPanelData(): void
    {
        $now = now();

        DB::table('states')->insert(['id' => 2, 'title' => 'Inhabilitado', 'abbreviation' => 'I']);

        DB::table('places')->insert(['id' => 1, 'code' => 'Z001', 'title' => 'Zona Central', 'created_at' => $now, 'updated_at' => $now]);
        DB::table('sectors')->insert(['id' => 1, 'title' => 'Sector 1', 'created_at' => $now, 'updated_at' => $now]);
        DB::table('place_sectors')->insert(['id' => 1, 'place_id' => 1, 'sector_id' => 1, 'created_at' => $now, 'updated_at' => $now]);
        DB::table('type_premises')->insert(['id' => 1, 'title' => 'Local Comunal', 'created_at' => $now, 'updated_at' => $now]);
        DB::table('resolutions')->insert(['id' => 1, 'document' => 'RES-001', 'state_id' => 1, 'created_at' => $now, 'updated_at' => $now]);

        // 2 comités: solo el primero termina con beneficiarios (aparece en top_comites).
        DB::table('associations')->insert([
            ['id' => 1, 'code' => 'CDM1', 'name' => 'Comité Activo', 'company_name' => 'Comité Activo SAC', 'address' => 'Av. 1', 'resolution_id' => 1, 'state_id' => 1, 'place_sector_id' => 1, 'type_premises_id' => 1, 'created_at' => $now, 'updated_at' => $now],
            ['id' => 2, 'code' => 'CDM2', 'name' => 'Comité Inhabilitado', 'company_name' => 'Comité Inhabilitado SAC', 'address' => 'Av. 2', 'resolution_id' => 1, 'state_id' => 2, 'place_sector_id' => 1, 'type_premises_id' => 1, 'created_at' => $now, 'updated_at' => $now],
        ]);

        DB::table('people')->insert([
            ['id' => 1, 'names' => 'María', 'father_lastname' => 'Apellido', 'mother_lastname' => 'Materno', 'dni' => '12345671', 'gender' => 'F', 'birthdate' => '1990-01-01', 'address' => 'Calle 1', 'place_sector_id' => 1, 'created_at' => $now, 'updated_at' => $now],
            ['id' => 2, 'names' => 'Ana', 'father_lastname' => 'Apellido', 'mother_lastname' => 'Materno', 'dni' => '12345672', 'gender' => 'F', 'birthdate' => '1988-01-01', 'address' => 'Calle 2', 'place_sector_id' => 1, 'created_at' => $now, 'updated_at' => $now],
            ['id' => 3, 'names' => 'Luis', 'father_lastname' => 'Apellido', 'mother_lastname' => 'Materno', 'dni' => '12345673', 'gender' => 'M', 'birthdate' => '2015-01-01', 'address' => 'Calle 3', 'place_sector_id' => 1, 'created_at' => $now, 'updated_at' => $now],
        ]);

        DB::table('partners')->insert([
            ['id' => 1, 'person_id' => 1, 'association_id' => 1, 'state_id' => 1, 'date_begin' => $now->copy()->subYear()->toDateString(), 'date_end' => null, 'created_at' => $now, 'updated_at' => $now],
            ['id' => 2, 'person_id' => 2, 'association_id' => 1, 'state_id' => 2, 'date_begin' => $now->copy()->subYear()->toDateString(), 'date_end' => null, 'created_at' => $now, 'updated_at' => $now],
        ]);

        DB::table('relationships')->insert(['id' => 1, 'title' => 'HIJO(A)', 'created_at' => $now, 'updated_at' => $now]);
        DB::table('beneficiaries')->insert(['id' => 1, 'person_id' => 3, 'partner_id' => 1, 'relationship_id' => 1, 'created_at' => $now, 'updated_at' => $now]);

        DB::table('uoms')->insert(['id' => 1, 'title' => 'UNIDAD', 'created_at' => $now, 'updated_at' => $now]);
        DB::table('products')->insert([
            ['id' => 1, 'title' => 'Arroz', 'abbreviation' => 'ARROZ', 'code' => 'P001', 'state_id' => 1, 'uom_id' => 1, 'created_at' => $now, 'updated_at' => $now],
            ['id' => 2, 'title' => 'Aceite', 'abbreviation' => 'ACE', 'code' => 'P002', 'state_id' => 1, 'uom_id' => 1, 'created_at' => $now, 'updated_at' => $now],
        ]);
        DB::table('detail_products')->insert([
            ['id' => 1, 'product_id' => 1, 'quantity' => 5, 'unit_price' => 5, 'start_date' => $now->copy()->subDays(10)->toDateString(), 'end_date' => $now->copy()->addDays(10)->toDateString(), 'created_at' => $now, 'updated_at' => $now],
            ['id' => 2, 'product_id' => 2, 'quantity' => 100, 'unit_price' => 5, 'start_date' => $now->copy()->subDays(10)->toDateString(), 'end_date' => $now->copy()->addDays(10)->toDateString(), 'created_at' => $now, 'updated_at' => $now],
        ]);

        DB::table('responsibles')->insert([
            ['id' => 1, 'person_id' => 1, 'type' => 'chief', 'active' => true, 'created_at' => $now, 'updated_at' => $now],
            ['id' => 2, 'person_id' => 2, 'type' => 'storekeeper', 'active' => true, 'created_at' => $now, 'updated_at' => $now],
        ]);
        DB::table('pecosas')->insert([
            'id' => 1, 'pecosa_number' => 'PEC-001', 'delivery_date' => $now->toDateTimeString(),
            'chief_id' => 1, 'storekeeper_id' => 2, 'state_id' => 1, 'association_id' => 1, 'created_at' => $now, 'updated_at' => $now,
        ]);
    }

    public function test_panel_endpoint_returns_expected_counts(): void
    {
        $this->seedPanelData();

        $this->actingAs($this->adminUser())
            ->getJson(self::BASE . '/panel')
            ->assertOk()
            ->assertJsonPath('stats.total_socios', 2)
            ->assertJsonPath('stats.total_beneficiarios', 1)
            ->assertJsonPath('stats.total_comites', 2)
            ->assertJsonPath('stats.stock_total', 105)
            ->assertJsonPath('pecosas_por_mes.anio', now()->year)
            ->assertJsonPath('pecosas_por_mes.total_anio', 1)
            ->assertJsonPath('top_comites.0.nombre', 'Comité Activo')
            ->assertJsonPath('top_comites.0.total', 1);
    }

    public function test_panel_returns_zeroed_stats_when_no_data(): void
    {
        $this->actingAs($this->adminUser())
            ->getJson(self::BASE . '/panel')
            ->assertOk()
            ->assertJsonPath('stats.total_socios', 0)
            ->assertJsonPath('stats.total_beneficiarios', 0)
            ->assertJsonPath('stats.total_comites', 0)
            ->assertJsonPath('stats.stock_total', 0)
            ->assertJsonPath('top_comites', []);
    }

    public function test_panel_available_to_any_authenticated_user_without_module_access(): void
    {
        $rolId = DB::table('rols')->insertGetId(['title' => 'SinModulos', 'description' => null]);
        $userId = DB::table('users')->insertGetId([
            'names' => 'Sin', 'father_surname' => 'Modulos', 'mother_surname' => 'Test', 'username' => 'sinmodulos',
            'email' => 'sinmodulos@example.com', 'dni' => '00000009', 'cui' => '0', 'state_id' => 1, 'rol_id' => $rolId,
            'password' => bcrypt('password'),
        ]);

        $this->actingAs(\App\Models\User::find($userId))
            ->getJson(self::BASE . '/panel')
            ->assertOk();
    }

    public function test_unauthenticated_request_gets_401(): void
    {
        $this->getJson(self::BASE . '/panel')->assertStatus(401);
    }
}
