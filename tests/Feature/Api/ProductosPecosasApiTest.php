<?php

namespace Tests\Feature\Api;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use App\Models\Pecosa;
use App\Services\PecosaService;
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
            ->postJson(self::BASE . '/pecosas', $this->pecosaPayload())
            ->assertCreated();

        $this->actingAs($this->userWithAccess())
            ->getJson(self::BASE . '/pecosas')
            ->assertOk()
            ->assertJsonStructure([
                'data' => ['*' => [
                    'id', 'pecosa_number', 'delivery_date', 'observation',
                    'association_id', 'state_id', 'managing_partner_id', 'chief_id', 'storekeeper_id',
                    'association', 'state', 'managing_partner', 'chief', 'storekeeper', 'detail_pecosas',
                ]],
                'links' => [],
                'meta' => [],
            ])
            ->assertJsonPath('data.0.association_id', 1)
            ->assertJsonPath('data.0.state_id', 1)
            ->assertJsonPath('data.0.managing_partner_id', 1)
            ->assertJsonPath('data.0.chief_id', 1)
            ->assertJsonPath('data.0.storekeeper_id', 2)
            ->assertJsonPath('data.0.chief.person.full_name', 'Juan Apellido Materno')
            ->assertJsonPath('data.0.storekeeper.person.full_name', 'Ana Apellido Materno')
            ->assertJsonPath('data.0.detail_pecosas.0.product.title', 'Arroz');
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

    public function test_legacy_pecosa_does_not_expose_default_responsibles_as_historical_data(): void
    {
        DB::table('pecosas')->insert([
            'pecosa_number' => 'LEG-0001',
            'delivery_date' => now()->toDateString(),
            'chief_id' => 1,
            'storekeeper_id' => 2,
            'state_id' => 1,
            'association_id' => 1,
            'chief_name' => null,
            'storekeeper_name' => null,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $this->actingAs($this->userWithAccess())
            ->getJson(self::BASE . '/pecosas')
            ->assertOk()
            ->assertJsonPath('data.0.pecosa_number', 'LEG-0001')
            ->assertJsonPath('data.0.chief_name', null)
            ->assertJsonPath('data.0.storekeeper_name', null)
            ->assertJsonPath('data.0.chief_id', null)
            ->assertJsonPath('data.0.storekeeper_id', null)
            ->assertJsonPath('data.0.chief', null)
            ->assertJsonPath('data.0.storekeeper', null);
    }

    public function test_historical_responsibles_are_returned_instead_of_current_people(): void
    {
        DB::table('pecosas')->insert([
            'pecosa_number' => 'HST0001',
            'delivery_date' => now()->toDateString(),
            'chief_id' => 1,
            'storekeeper_id' => 2,
            'state_id' => 1,
            'association_id' => 1,
            'chief_name' => 'Jefe Histórico',
            'chief_dni' => '12345678',
            'storekeeper_name' => 'Almacenera Histórica',
            'storekeeper_dni' => '87654321',
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $this->actingAs($this->userWithAccess())
            ->getJson(self::BASE . '/pecosas')
            ->assertOk()
            ->assertJsonPath('data.0.chief.person.full_name', 'Jefe Histórico')
            ->assertJsonPath('data.0.chief.person.dni', '12345678')
            ->assertJsonPath('data.0.storekeeper.person.full_name', 'Almacenera Histórica')
            ->assertJsonPath('data.0.storekeeper.person.dni', '87654321');
    }

    public function test_comprobante_uses_pecosa_snapshots_instead_of_template_defaults_or_current_people(): void
    {
        $this->actingAs($this->userWithAccess())
            ->postJson(self::BASE . '/pecosas', $this->pecosaPayload('PDF-001'))
            ->assertCreated();

        DB::table('people')->where('id', 2)->update(['names' => 'Jefe Actual']);
        DB::table('people')->where('id', 3)->update(['names' => 'Almacenera Actual']);

        $pecosa = Pecosa::where('pecosa_number', 'PDF-001')->firstOrFail();
        $pecosa->load('detailPecosas');
        $method = new \ReflectionMethod(PecosaService::class, 'buildComprobanteData');
        $method->setAccessible(true);
        $data = $method->invoke(app(PecosaService::class), $pecosa);

        $this->assertSame('PDF-001', $data['numero_orden']);
        $this->assertSame('Z001', $data['zona']);
        $this->assertSame('CDM', $data['comite']);
        $this->assertSame('Comité Demo', $data['domicilio']);
        $this->assertSame('María Apellido Materno', $data['solicitante_nombre']);
        $this->assertSame('Juan Apellido Materno', $data['encargado_almacen']);
        $this->assertSame('Ana Apellido Materno', $data['control']);
        $this->assertSame('Arroz (ARROZ)', $data['articulos'][0]['descripcion']);
        $this->assertSame('S/. 20.00', $data['total_general']);

        $html = view('comprobante_salida', ['articulos' => []])->render();
        $this->assertStringNotContainsString('MARIA NELLY RODRIGUEZ LOYOLA', $html);
        $this->assertStringNotContainsString('SANTA RITA DE CASA', $html);
        $this->assertStringNotContainsString('MEZCLAS PARA YOGURES', $html);
    }

    public function test_legacy_comprobante_uses_document_relations_but_never_current_responsibles(): void
    {
        $pecosaId = DB::table('pecosas')->insertGetId([
            'pecosa_number' => 'PDF-002',
            'delivery_date' => now()->toDateString(),
            'chief_id' => 1,
            'storekeeper_id' => 2,
            'state_id' => 1,
            'association_id' => 1,
            'chief_name' => null,
            'storekeeper_name' => null,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        DB::table('detail_pecosas')->insert([
            'pecosa_id' => $pecosaId,
            'detail_product_id' => 1,
            'quantity' => 3,
            'unit_price' => 10,
            'subtotal' => 30,
            'priority' => 1,
            'product_name' => null,
            'product_abbreviation' => null,
            'uom_title' => null,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $pecosa = Pecosa::findOrFail($pecosaId);
        $pecosa->load(['detailPecosas.detailProduct.product.uom', 'association.placeSector.place']);
        $method = new \ReflectionMethod(PecosaService::class, 'buildComprobanteData');
        $method->setAccessible(true);
        $data = $method->invoke(app(PecosaService::class), $pecosa);

        $this->assertSame('Comité Demo', $data['domicilio']);
        $this->assertSame('CDM', $data['comite']);
        $this->assertSame('Z001', $data['zona']);
        $this->assertSame('Arroz (ARROZ)', $data['articulos'][0]['descripcion']);
        $this->assertSame('UNIDAD', $data['articulos'][0]['unidad']);
        $this->assertSame('', $data['encargado_almacen']);
        $this->assertSame('', $data['control']);
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
