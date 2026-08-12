<?php

namespace Tests\Feature\Api;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;
use Tests\Traits\SeedsBaseData;

class ProductosPecosasApiTest extends TestCase
{
    use RefreshDatabase;
    use SeedsBaseData;

    private const BASE = '/api/dashboard/productos-pecosas';

    protected function setUp(): void
    {
        parent::setUp();
        $this->seedBaseData();
        $this->seedModuleData();
    }

    private function seedModuleData(): void
    {
        $moduleIds = [];
        foreach (['productos' => 'Productos', 'pecosas' => 'Pecosas'] as $slug => $name) {
            $moduleIds[] = DB::table('modules')->insertGetId([
                'name' => $name,
                'slug' => $slug,
                'description' => $name,
                'icon' => 'fa-box',
                'route' => $slug,
                'order' => 2,
                'is_active' => true,
            ]);
        }

        foreach ($moduleIds as $moduleId) {
            DB::table('module_rol')->insert([
                'module_id' => $moduleId,
                'rol_id' => 1,
                'can_view' => true,
                'can_create' => true,
                'can_edit' => true,
                'can_delete' => true,
            ]);
        }

        $now = now();

        DB::table('uoms')->insert(['id' => 1, 'title' => 'UNIDAD', 'created_at' => $now, 'updated_at' => $now]);

        DB::table('products')->insert([
            'id' => 1,
            'title' => 'Arroz',
            'abbreviation' => 'ARROZ',
            'code' => 'P001',
            'state_id' => 1,
            'uom_id' => 1,
            'created_at' => $now,
            'updated_at' => $now,
        ]);

        DB::table('detail_products')->insert([
            [
                'id' => 1,
                'product_id' => 1,
                'quantity' => 10,
                'unit_price' => 10.00,
                'start_date' => $now->copy()->subDays(30)->toDateString(),
                'end_date' => $now->copy()->addDays(30)->toDateString(),
                'created_at' => $now,
                'updated_at' => $now,
            ],
            [
                'id' => 2,
                'product_id' => 1,
                'quantity' => 5,
                'unit_price' => 12.00,
                'start_date' => $now->copy()->subDays(30)->toDateString(),
                'end_date' => $now->copy()->addDays(30)->toDateString(),
                'created_at' => $now,
                'updated_at' => $now,
            ],
        ]);

        DB::table('places')->insert(['id' => 1, 'code' => 'Z001', 'title' => 'Zona Central', 'created_at' => $now, 'updated_at' => $now]);
        DB::table('sectors')->insert(['id' => 1, 'title' => 'Sector 1', 'created_at' => $now, 'updated_at' => $now]);
        DB::table('place_sectors')->insert(['id' => 1, 'place_id' => 1, 'sector_id' => 1, 'created_at' => $now, 'updated_at' => $now]);
        DB::table('type_premises')->insert(['id' => 1, 'title' => 'Local Comunal', 'created_at' => $now, 'updated_at' => $now]);

        DB::table('positions')->insert(['id' => 1, 'title' => 'PRESIDENTA', 'created_at' => $now, 'updated_at' => $now]);

        DB::table('resolutions')->insert([
            'id' => 1,
            'document' => 'RES-001',
            'state_id' => 1,
            'created_at' => $now,
            'updated_at' => $now,
        ]);

        DB::table('associations')->insert([
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
        ]);

        foreach ([1 => ['María', 'F'], 2 => ['Juan', 'M'], 3 => ['Ana', 'F']] as $id => [$names, $gender]) {
            DB::table('people')->insert([
                'id' => $id,
                'names' => $names,
                'father_lastname' => 'Apellido',
                'mother_lastname' => 'Materno',
                'dni' => '1234567' . $id,
                'gender' => $gender,
                'birthdate' => '1990-01-01',
                'address' => 'Calle 1',
                'place_sector_id' => 1,
                'created_at' => $now,
                'updated_at' => $now,
            ]);
        }

        DB::table('partners')->insert([
            'id' => 1,
            'person_id' => 1,
            'association_id' => 1,
            'state_id' => 1,
            'date_begin' => $now->toDateString(),
            'created_at' => $now,
            'updated_at' => $now,
        ]);

        DB::table('directives')->insert([
            'id' => 1,
            'resolution_id' => 1,
            'partner_id' => 1,
            'position_id' => 1,
            'state_id' => 1,
            'created_at' => $now,
            'updated_at' => $now,
        ]);

        DB::table('responsibles')->insert([
            ['id' => 1, 'person_id' => 2, 'type' => 'chief', 'active' => true, 'created_at' => $now, 'updated_at' => $now],
            ['id' => 2, 'person_id' => 3, 'type' => 'storekeeper', 'active' => true, 'created_at' => $now, 'updated_at' => $now],
        ]);

        DB::table('type_transactions')->insert(['id' => 1, 'title' => 'Salida', 'created_at' => $now, 'updated_at' => $now]);
    }

    private function userWithAccess(): \App\Models\User
    {
        return $this->adminUser();
    }

    private function pecosaPayload(string $number = 'PEC-001', int $quantity = 2): array
    {
        return [
            'pecosa_number' => $number,
            'observation' => 'Entrega programada',
            'delivery_date' => now()->toDateString(),
            'chief_id' => 1,
            'storekeeper_id' => 2,
            'managing_partner_id' => 1,
            'state_id' => 1,
            'association_id' => 1,
            'details' => [
                ['detail_product_id' => 1, 'quantity' => $quantity],
            ],
        ];
    }

    // ==================== PRODUCTOS ====================

    public function test_products_endpoint_returns_json_collection(): void
    {
        $this->actingAs($this->userWithAccess())
            ->getJson(self::BASE . '/products')
            ->assertOk()
            ->assertJsonStructure([
                'data' => ['*' => ['id', 'title', 'abbreviation', 'code', 'uom_id', 'state_id', 'uom', 'state', 'stock']],
                'links' => [],
                'meta' => [],
            ]);
    }

    public function test_products_options_endpoint(): void
    {
        $this->actingAs($this->userWithAccess())
            ->getJson(self::BASE . '/products/options')
            ->assertOk()
            ->assertJsonStructure([
                'states' => ['*' => ['id', 'title']],
                'uoms' => ['*' => ['id', 'title']],
            ]);
    }

    public function test_store_product_creates_product(): void
    {
        $this->actingAs($this->userWithAccess())
            ->postJson(self::BASE . '/products', [
                'title' => 'Aceite',
                'abbreviation' => 'ACE',
                'code' => 'P002',
                'state_id' => 1,
                'uom_id' => 1,
            ])
            ->assertStatus(201)
            ->assertJsonPath('data.title', 'Aceite')
            ->assertJsonPath('data.code', 'P002');

        $this->assertDatabaseHas('products', ['code' => 'P002']);
    }

    public function test_store_product_rejects_duplicate_code(): void
    {
        $this->actingAs($this->userWithAccess())
            ->postJson(self::BASE . '/products', [
                'title' => 'Arroz Duplicado',
                'abbreviation' => 'ARROZ2',
                'code' => 'P001',
                'state_id' => 1,
                'uom_id' => 1,
            ])
            ->assertStatus(422);
    }

    public function test_update_product_updates(): void
    {
        $this->actingAs($this->userWithAccess())
            ->putJson(self::BASE . '/products/1', ['title' => 'Arroz Integral'])
            ->assertOk()
            ->assertJsonPath('data.title', 'Arroz Integral');

        $this->assertDatabaseHas('products', ['id' => 1, 'title' => 'Arroz Integral']);
    }

    public function test_destroy_product_without_references_deletes(): void
    {
        $this->actingAs($this->userWithAccess())
            ->deleteJson(self::BASE . '/products/1')
            ->assertStatus(204);

        $this->assertDatabaseMissing('products', ['id' => 1]);
        $this->assertDatabaseMissing('detail_products', ['product_id' => 1]);
    }

    public function test_destroy_product_rejects_when_referenced_by_pecosa(): void
    {
        $this->actingAs($this->userWithAccess())
            ->postJson(self::BASE . '/pecosas', $this->pecosaPayload())
            ->assertStatus(201);

        $this->actingAs($this->userWithAccess())
            ->deleteJson(self::BASE . '/products/1')
            ->assertStatus(422)
            ->assertJsonPath('message', 'No se puede eliminar: el producto tiene detalles/stock asociado');

        $this->assertDatabaseHas('products', ['id' => 1]);
    }

    // ==================== DETALLE PRODUCTOS ====================

    public function test_detail_products_endpoint_paginates(): void
    {
        $now = now();
        $today = $now->toDateString();

        // 20 filas adicionales (ids 3..22) => 22 en total, para forzar más de una página
        $rows = [];
        for ($i = 3; $i <= 22; $i++) {
            $rows[] = [
                'id' => $i,
                'product_id' => 1,
                'quantity' => 1,
                'unit_price' => 1.00,
                'start_date' => $today,
                'end_date' => $now->copy()->addDays(30)->toDateString(),
                'created_at' => $now,
                'updated_at' => $now,
            ];
        }
        DB::table('detail_products')->insert($rows);

        $page1 = $this->actingAs($this->userWithAccess())
            ->getJson(self::BASE . '/products/detail-products?per_page=15&page=1')
            ->assertOk()
            ->assertJsonPath('meta.total', 22)
            ->assertJsonPath('meta.per_page', 15)
            ->assertJsonPath('meta.current_page', 1)
            ->assertJsonCount(15, 'data')
            ->assertJsonStructure([
                'data' => ['*' => ['id', 'product_id', 'product_title', 'unit_price', 'available_stock', 'active']],
                'links' => [],
                'meta' => ['links' => []],
            ]);

        $page2 = $this->actingAs($this->userWithAccess())
            ->getJson(self::BASE . '/products/detail-products?per_page=15&page=2')
            ->assertOk()
            ->assertJsonPath('meta.current_page', 2)
            ->assertJsonCount(7, 'data');

        $page1Ids = collect($page1->json('data'))->pluck('id')->all();
        $page2Ids = collect($page2->json('data'))->pluck('id')->all();

        $this->assertNotEmpty($page1Ids, 'La página 1 debe devolver registros.');
        $this->assertNotEmpty($page2Ids, 'La página 2 debe devolver registros.');
        $this->assertNotSame($page1Ids, $page2Ids, 'La página 2 debe devolver resultados distintos a la página 1.');
        $this->assertEmpty(array_intersect($page1Ids, $page2Ids), 'Las páginas no deben compartir registros.');
    }

    public function test_detail_products_endpoint_filters_by_search_and_period(): void
    {
        $this->actingAs($this->userWithAccess())
            ->getJson(self::BASE . '/products/detail-products?per_page=15&search=Arroz')
            ->assertOk()
            ->assertJsonPath('meta.total', 2);

        $this->actingAs($this->userWithAccess())
            ->getJson(self::BASE . '/products/detail-products?per_page=15&search=Inexistente')
            ->assertOk()
            ->assertJsonPath('meta.total', 0);

        $this->actingAs($this->userWithAccess())
            ->getJson(self::BASE . '/products/detail-products?per_page=15&periodo=vencido')
            ->assertOk()
            ->assertJsonPath('meta.total', 0);
    }

    // ==================== PECOSAS ====================

    public function test_pecosas_endpoint_returns_json_collection(): void
    {
        $this->actingAs($this->userWithAccess())
            ->getJson(self::BASE . '/pecosas')
            ->assertOk()
            ->assertJsonStructure([
                'data' => ['*' => ['id', 'pecosa_number', 'delivery_date', 'observation', 'association', 'state', 'detail_pecosas']],
                'links' => [],
                'meta' => [],
            ]);
    }

    public function test_pecosas_options_endpoint_returns_modal_data(): void
    {
        $this->actingAs($this->userWithAccess())
            ->getJson(self::BASE . '/pecosas/options')
            ->assertOk()
            ->assertJsonStructure([
                'states' => ['*' => ['id', 'title', 'abbreviation']],
                'associations' => ['*' => ['id', 'name', 'code']],
                'responsibles' => ['*' => ['id', 'type', 'name', 'dni']],
                'detail_products' => ['*' => ['id', 'product_id', 'product_title', 'unit_price', 'available_stock', 'active']],
            ]);
    }

    public function test_pecosas_options_association_has_president(): void
    {
        $this->actingAs($this->userWithAccess())
            ->getJson(self::BASE . '/pecosas/options')
            ->assertOk()
            ->assertJsonPath('associations.0.president_partner_id', 1)
            ->assertJsonPath('associations.0.president_name', 'María Apellido');
    }

    public function test_store_pecosa_deducts_stock_and_creates_relations(): void
    {
        $response = $this->actingAs($this->userWithAccess())
            ->postJson(self::BASE . '/pecosas', $this->pecosaPayload())
            ->assertStatus(201)
            ->assertJsonPath('data.pecosa_number', 'PEC-001')
            ->assertJsonPath('data.president_name', 'María Apellido Materno')
            ->assertJsonPath('data.chief_name', 'Juan Apellido Materno')
            ->assertJsonPath('data.storekeeper_name', 'Ana Apellido Materno')
            ->assertJsonPath('data.detail_pecosas.0.quantity', 2)
            ->assertJsonPath('data.detail_pecosas.0.product_name', 'Arroz');

        $pecosaId = $response->json('data.id');

        $this->assertDatabaseHas('pecosas', ['pecosa_number' => 'PEC-001']);
        $this->assertDatabaseHas('detail_pecosas', ['pecosa_id' => $pecosaId, 'quantity' => 2]);
        $this->assertDatabaseHas('product_stocks', ['pecosa_id' => $pecosaId, 'quantity' => 2]);
        $this->assertDatabaseHas('transactions', ['document_number' => 'PEC-001', 'quantity' => 2]);

        $this->assertSame(2, (int) DB::table('product_stocks')->where('pecosa_id', $pecosaId)->sum('quantity'));
    }

    public function test_store_pecosa_rejects_insufficient_stock(): void
    {
        $this->actingAs($this->userWithAccess())
            ->postJson(self::BASE . '/pecosas', $this->pecosaPayload('PEC-002', 99))
            ->assertStatus(422)
            ->assertJsonStructure(['message']);
    }

    public function test_update_pecosa_reverts_and_recalculates_stock(): void
    {
        $response = $this->actingAs($this->userWithAccess())
            ->postJson(self::BASE . '/pecosas', $this->pecosaPayload('PEC-001', 2))
            ->assertStatus(201);
        $pecosaId = $response->json('data.id');

        $this->actingAs($this->userWithAccess())
            ->putJson(self::BASE . '/pecosas/' . $pecosaId, [
                'pecosa_number' => 'PEC-001',
                'observation' => 'Actualizada',
                'delivery_date' => now()->toDateString(),
                'chief_id' => 1,
                'storekeeper_id' => 2,
                'managing_partner_id' => 1,
                'state_id' => 1,
                'association_id' => 1,
                'details' => [
                    ['detail_product_id' => 1, 'quantity' => 4],
                    ['detail_product_id' => 2, 'quantity' => 1],
                ],
            ])
            ->assertOk()
            ->assertJsonPath('data.observation', 'Actualizada')
            ->assertJsonCount(2, 'data.detail_pecosas');

        $this->assertSame(5, (int) DB::table('product_stocks')
            ->where('pecosa_id', $pecosaId)
            ->sum('quantity'));

        $this->assertDatabaseHas('detail_pecosas', ['pecosa_id' => $pecosaId, 'detail_product_id' => 1, 'quantity' => 4]);
        $this->assertDatabaseHas('detail_pecosas', ['pecosa_id' => $pecosaId, 'detail_product_id' => 2, 'quantity' => 1]);
    }

    public function test_destroy_pecosa_reverts_stock_and_deletes_relations(): void
    {
        $response = $this->actingAs($this->userWithAccess())
            ->postJson(self::BASE . '/pecosas', $this->pecosaPayload())
            ->assertStatus(201);
        $pecosaId = $response->json('data.id');

        $this->actingAs($this->userWithAccess())
            ->deleteJson(self::BASE . '/pecosas/' . $pecosaId)
            ->assertStatus(204);

        $this->assertDatabaseMissing('pecosas', ['id' => $pecosaId]);
        $this->assertDatabaseMissing('detail_pecosas', ['pecosa_id' => $pecosaId]);
        $this->assertDatabaseMissing('product_stocks', ['pecosa_id' => $pecosaId]);
        $this->assertDatabaseMissing('transactions', ['document_number' => 'PEC-001']);
    }
}
