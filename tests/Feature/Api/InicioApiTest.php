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

    private function seedKpiData(): void
    {
        $now = now();

        // Estado adicional "Inhabilitado" para verificar que solo cuenta lo Activo.
        DB::table('states')->insert(['id' => 2, 'title' => 'Inhabilitado', 'abbreviation' => 'I']);

        DB::table('places')->insert(['id' => 1, 'code' => 'Z001', 'title' => 'Zona Central', 'created_at' => $now, 'updated_at' => $now]);
        DB::table('sectors')->insert(['id' => 1, 'title' => 'Sector 1', 'created_at' => $now, 'updated_at' => $now]);
        DB::table('place_sectors')->insert(['id' => 1, 'place_id' => 1, 'sector_id' => 1, 'created_at' => $now, 'updated_at' => $now]);
        DB::table('type_premises')->insert(['id' => 1, 'title' => 'Local Comunal', 'created_at' => $now, 'updated_at' => $now]);
        DB::table('resolutions')->insert(['id' => 1, 'document' => 'RES-001', 'state_id' => 1, 'created_at' => $now, 'updated_at' => $now]);

        // 2 comités: 1 activo, 1 inhabilitado.
        DB::table('associations')->insert([
            ['id' => 1, 'code' => 'CDM1', 'name' => 'Comité Activo', 'company_name' => 'Comité Activo SAC', 'address' => 'Av. 1', 'resolution_id' => 1, 'state_id' => 1, 'place_sector_id' => 1, 'type_premises_id' => 1, 'created_at' => $now, 'updated_at' => $now],
            ['id' => 2, 'code' => 'CDM2', 'name' => 'Comité Inhabilitado', 'company_name' => 'Comité Inhabilitado SAC', 'address' => 'Av. 2', 'resolution_id' => 1, 'state_id' => 2, 'place_sector_id' => 1, 'type_premises_id' => 1, 'created_at' => $now, 'updated_at' => $now],
        ]);

        DB::table('people')->insert([
            ['id' => 1, 'names' => 'María', 'father_lastname' => 'Apellido', 'mother_lastname' => 'Materno', 'dni' => '12345671', 'gender' => 'F', 'birthdate' => '1990-01-01', 'address' => 'Calle 1', 'place_sector_id' => 1, 'created_at' => $now, 'updated_at' => $now],
            ['id' => 2, 'names' => 'Ana', 'father_lastname' => 'Apellido', 'mother_lastname' => 'Materno', 'dni' => '12345672', 'gender' => 'F', 'birthdate' => '1988-01-01', 'address' => 'Calle 2', 'place_sector_id' => 1, 'created_at' => $now, 'updated_at' => $now],
            ['id' => 3, 'names' => 'Luis', 'father_lastname' => 'Apellido', 'mother_lastname' => 'Materno', 'dni' => '12345673', 'gender' => 'M', 'birthdate' => '2015-01-01', 'address' => 'Calle 3', 'place_sector_id' => 1, 'created_at' => $now, 'updated_at' => $now],
        ]);

        // 2 socios: 1 activo, 1 inhabilitado.
        DB::table('partners')->insert([
            ['id' => 1, 'person_id' => 1, 'association_id' => 1, 'state_id' => 1, 'date_begin' => $now->copy()->subYear()->toDateString(), 'date_end' => null, 'created_at' => $now, 'updated_at' => $now],
            ['id' => 2, 'person_id' => 2, 'association_id' => 1, 'state_id' => 2, 'date_begin' => $now->copy()->subYear()->toDateString(), 'date_end' => null, 'created_at' => $now, 'updated_at' => $now],
        ]);

        DB::table('relationships')->insert(['id' => 1, 'title' => 'HIJO(A)', 'created_at' => $now, 'updated_at' => $now]);
        DB::table('beneficiaries')->insert(['id' => 1, 'person_id' => 3, 'partner_id' => 1, 'relationship_id' => 1, 'created_at' => $now, 'updated_at' => $now]);

        DB::table('type_benefits')->insert(['id' => 1, 'title' => 'Beneficio 1', 'abbreviation' => 'BEN', 'min_age' => 0, 'max_age' => 99, 'priority' => 1, 'created_at' => $now, 'updated_at' => $now]);
        DB::table('beneficiary_histories')->insert([
            'id' => 1, 'beneficiary_id' => 1, 'weight' => 12.5, 'height' => 90, 'hmg' => 10,
            'date_begin' => $now->copy()->subYear()->toDateString(), 'date_end' => null, 'type_benefit_id' => 1, 'state_id' => 1, 'created_at' => $now, 'updated_at' => $now,
        ]);

        DB::table('uoms')->insert(['id' => 1, 'title' => 'UNIDAD', 'created_at' => $now, 'updated_at' => $now]);
        DB::table('products')->insert([
            ['id' => 1, 'title' => 'Arroz', 'abbreviation' => 'ARROZ', 'code' => 'P001', 'state_id' => 1, 'uom_id' => 1, 'created_at' => $now, 'updated_at' => $now],
            ['id' => 2, 'title' => 'Aceite', 'abbreviation' => 'ACE', 'code' => 'P002', 'state_id' => 1, 'uom_id' => 1, 'created_at' => $now, 'updated_at' => $now],
        ]);
        // Arroz: stock bajo (5 <= 10). Aceite: stock alto (100 > 10).
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

        DB::table('raciones')->insert([
            'year' => (string) $now->year, 'racion_hojuelas_gramos' => 50, 'racion_leche_militros' => 410, 'active' => true, 'created_at' => $now, 'updated_at' => $now,
        ]);
    }

    public function test_kpis_endpoint_returns_expected_counts(): void
    {
        $this->seedKpiData();

        $this->actingAs($this->adminUser())
            ->getJson(self::BASE . '/kpis')
            ->assertOk()
            ->assertJsonPath('socios_activos', 1)
            ->assertJsonPath('beneficiarios_activos', 1)
            ->assertJsonPath('comites_activos', 1)
            ->assertJsonPath('productos_stock_critico', 1)
            ->assertJsonPath('pecosas_mes_actual', 1)
            ->assertJsonPath('racion_activa.year', now()->year)
            ->assertJsonPath('racion_activa.racion_leche_militros', 410.0);
    }

    public function test_kpis_returns_null_racion_when_none_configured(): void
    {
        $this->actingAs($this->adminUser())
            ->getJson(self::BASE . '/kpis')
            ->assertOk()
            ->assertJsonPath('racion_activa', null)
            ->assertJsonPath('socios_activos', 0);
    }

    public function test_kpis_available_to_any_authenticated_user_without_module_access(): void
    {
        $rolId = DB::table('rols')->insertGetId(['title' => 'SinModulos', 'description' => null]);
        $userId = DB::table('users')->insertGetId([
            'names' => 'Sin', 'father_surname' => 'Modulos', 'mother_surname' => 'Test', 'username' => 'sinmodulos',
            'email' => 'sinmodulos@example.com', 'dni' => '00000009', 'cui' => '0', 'state_id' => 1, 'rol_id' => $rolId,
            'password' => bcrypt('password'),
        ]);

        $this->actingAs(\App\Models\User::find($userId))
            ->getJson(self::BASE . '/kpis')
            ->assertOk();
    }

    public function test_unauthenticated_request_gets_401(): void
    {
        $this->getJson(self::BASE . '/kpis')->assertStatus(401);
    }
}
