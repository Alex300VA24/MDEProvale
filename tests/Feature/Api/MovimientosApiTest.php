<?php

namespace Tests\Feature\Api;

use App\Models\Transaction;
use App\Services\StockService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;
use Tests\Traits\SeedsBaseData;

class MovimientosApiTest extends TestCase
{
    use RefreshDatabase;
    use SeedsBaseData;

    private const BASE = '/api/dashboard/movimientos';

    protected function setUp(): void
    {
        parent::setUp();
        $this->seedBaseData();
        $this->seedMovimientosModule();
        $this->seedStoreData();
    }

    private function seedMovimientosModule(): void
    {
        foreach (['movimientos' => 'Movimientos', 'pecosas' => 'Pecosas'] as $slug => $name) {
            $moduleId = DB::table('modules')->insertGetId([
                'name' => $name,
                'slug' => $slug,
                'description' => $name,
                'icon' => 'fa-box',
                'route' => $slug,
                'order' => 2,
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
    }

    private function seedStoreData(): void
    {
        $now = now();

        DB::table('uoms')->insert(['id' => 1, 'title' => 'UNIDAD', 'created_at' => $now, 'updated_at' => $now]);

        DB::table('products')->insert([
            [
                'id' => 1,
                'title' => 'Arroz',
                'abbreviation' => 'ARROZ',
                'code' => 'P001',
                'state_id' => 1,
                'uom_id' => 1,
                'created_at' => $now,
                'updated_at' => $now,
            ],
            [
                'id' => 2,
                'title' => 'Aceite',
                'abbreviation' => 'ACE',
                'code' => 'P002',
                'state_id' => 1,
                'uom_id' => 1,
                'created_at' => $now,
                'updated_at' => $now,
            ],
        ]);

        // Lote vigente de Arroz (100 uds) para las salidas FIFO
        DB::table('detail_products')->insert([
            'id' => 1,
            'product_id' => 1,
            'quantity' => 100,
            'unit_price' => 5.00,
            'start_date' => $now->copy()->subDays(30)->toDateString(),
            'end_date' => $now->copy()->addDays(30)->toDateString(),
            'created_at' => $now,
            'updated_at' => $now,
        ]);

        DB::table('type_transactions')->insert([
            ['id' => 1, 'title' => 'Salida', 'created_at' => $now, 'updated_at' => $now],
            ['id' => 2, 'title' => 'Ingreso', 'created_at' => $now, 'updated_at' => $now],
        ]);
    }

    private function seedReparticionContext(): void
    {
        $now = now();

        DB::table('places')->insert(['id' => 1, 'code' => 'Z001', 'title' => 'Zona Central', 'created_at' => $now, 'updated_at' => $now]);
        DB::table('sectors')->insert(['id' => 1, 'title' => 'Sector 1', 'created_at' => $now, 'updated_at' => $now]);
        DB::table('place_sectors')->insert(['id' => 1, 'place_id' => 1, 'sector_id' => 1, 'created_at' => $now, 'updated_at' => $now]);
        DB::table('type_premises')->insert(['id' => 1, 'title' => 'Local Comunal', 'created_at' => $now, 'updated_at' => $now]);

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

        DB::table('people')->insert([
            ['id' => 1, 'names' => 'María', 'father_lastname' => 'Apellido', 'mother_lastname' => 'Materno', 'dni' => '12345671', 'gender' => 'F', 'birthdate' => '1990-01-01', 'address' => 'Calle 1', 'place_sector_id' => 1, 'created_at' => $now, 'updated_at' => $now],
            ['id' => 2, 'names' => 'Juan', 'father_lastname' => 'Apellido', 'mother_lastname' => 'Materno', 'dni' => '12345672', 'gender' => 'M', 'birthdate' => '1985-05-05', 'address' => 'Calle 2', 'place_sector_id' => 1, 'created_at' => $now, 'updated_at' => $now],
            ['id' => 3, 'names' => 'Ana', 'father_lastname' => 'Apellido', 'mother_lastname' => 'Materno', 'dni' => '12345673', 'gender' => 'F', 'birthdate' => '2000-01-01', 'address' => 'Calle 3', 'place_sector_id' => 1, 'created_at' => $now, 'updated_at' => $now],
        ]);

        DB::table('partners')->insert([
            'id' => 1,
            'person_id' => 1,
            'association_id' => 1,
            'state_id' => 1,
            'date_begin' => $now->copy()->subYears(2)->toDateString(),
            'date_end' => null,
            'created_at' => $now,
            'updated_at' => $now,
        ]);

        DB::table('responsibles')->insert([
            ['id' => 1, 'person_id' => 2, 'type' => 'chief', 'active' => true, 'created_at' => $now, 'updated_at' => $now],
            ['id' => 2, 'person_id' => 3, 'type' => 'storekeeper', 'active' => true, 'created_at' => $now, 'updated_at' => $now],
        ]);

        DB::table('relationships')->insert(['id' => 1, 'title' => 'HIJO(A)', 'created_at' => $now, 'updated_at' => $now]);
        // NOTA: no se seedea aquí ninguna fila en `pecosas` — cada test que la necesite
        // (p.ej. verificar el bloqueo de eliminación de una salida ligada a una pecosa)
        // debe insertarla explícitamente, porque otros tests de este archivo crean
        // una pecosa "PEC-001" real vía la API y chocarían con un número duplicado.

        DB::table('beneficiaries')->insert([
            'id' => 1,
            'person_id' => 3,
            'partner_id' => 1,
            'relationship_id' => 1,
            'created_at' => $now,
            'updated_at' => $now,
        ]);

        DB::table('type_benefits')->insert([
            'id' => 1,
            'title' => 'Beneficio 1',
            'abbreviation' => 'BEN',
            'min_age' => 0,
            'max_age' => 99,
            'priority' => 1,
            'created_at' => $now,
            'updated_at' => $now,
        ]);

        DB::table('beneficiary_histories')->insert([
            'id' => 1,
            'beneficiary_id' => 1,
            'weight' => 12.50,
            'height' => 90.00,
            'hmg' => 10.00,
            'date_begin' => $now->copy()->subYear()->toDateString(),
            'date_end' => null,
            'type_benefit_id' => 1,
            'state_id' => 1,
            'created_at' => $now,
            'updated_at' => $now,
        ]);

        DB::table('raciones')->insert([
            'id' => 1,
            'year' => (string) $now->year,
            'month_start' => 1,
            'month_end' => 12,
            'racion_leche_militros' => 410,
            'racion_hojuelas_gramos' => 50,
            'active' => true,
            'created_at' => $now,
            'updated_at' => $now,
        ]);
    }

    private function seedExistingPecosa(): void
    {
        $now = now();

        DB::table('pecosas')->insert([
            'id' => 1,
            'pecosa_number' => 'PEC-001',
            'delivery_date' => $now->toDateTimeString(),
            'chief_id' => 1,
            'storekeeper_id' => 2,
            'state_id' => 1,
            'association_id' => 1,
            'created_at' => $now,
            'updated_at' => $now,
        ]);
    }

    // ==================== LISTADO / OPTIONS ====================

    public function test_transactions_list_paginates(): void
    {
        $this->actingAs($this->adminUser())
            ->getJson(self::BASE . '/transactions?per_page=2')
            ->assertOk()
            ->assertJsonStructure([
                'data' => [],
                'links' => ['first', 'last', 'prev', 'next'],
                'meta' => ['current_page', 'from', 'last_page', 'per_page', 'to', 'total', 'path'],
            ]);
    }

    public function test_options_returns_types_products_and_detail_products(): void
    {
        $this->actingAs($this->adminUser())
            ->getJson(self::BASE . '/transactions/options')
            ->assertOk()
            ->assertJsonStructure([
                'types' => ['*' => ['id', 'title']],
                'products' => ['*' => ['id', 'title', 'abbreviation']],
                'detail_products' => ['*' => ['id', 'product_id', 'product_title', 'uom_title', 'unit_price', 'quantity', 'used_quantity', 'available_stock']],
            ])
            ->assertJsonCount(2, 'types')
            ->assertJsonPath('detail_products.0.available_stock', 100.0);
    }

    // ==================== INGRESOS ====================

    public function test_store_ingreso_creates_lote_and_transaction(): void
    {
        $this->actingAs($this->adminUser())
            ->postJson(self::BASE . '/transactions', [
                'type_transaction_id' => 2,
                'product_id' => 2,
                'quantity' => 10,
                'unit_price' => 5.00,
                'document_number' => 'ING-001',
                'transaction_date' => now()->toDateString(),
                'start_date' => now()->toDateString(),
                'end_date' => now()->addYear()->toDateString(),
            ])
            ->assertStatus(201)
            ->assertJsonPath('data.type.title', 'Ingreso')
            ->assertJsonPath('data.quantity', 10.0)
            ->assertJsonPath('data.total_price', 50.0);

        $this->assertDatabaseHas('detail_products', [
            'product_id' => 2,
            'quantity' => 10,
            'unit_price' => 5.00,
        ]);
        $this->assertDatabaseHas('transactions', [
            'type_transaction_id' => 2,
            'quantity' => 10,
            'total_price' => 50,
            'document_number' => 'ING-001',
        ]);
    }

    public function test_store_ingreso_requires_product(): void
    {
        $this->actingAs($this->adminUser())
            ->postJson(self::BASE . '/transactions', [
                'type_transaction_id' => 2,
                'quantity' => 10,
                'unit_price' => 5.00,
                'transaction_date' => now()->toDateString(),
            ])
            ->assertStatus(422)
            ->assertJsonValidationErrors('product_id');
    }

    public function test_update_ingreso_recalculates_lote_and_total(): void
    {
        $txId = $this->createIngreso(10);

        $loteId = DB::table('transactions')->where('id', $txId)->value('detail_product_id');

        // Consume 5 del lote generado por el ingreso
        $this->actingAs($this->adminUser())
            ->postJson(self::BASE . '/transactions', [
                'type_transaction_id' => 1,
                'detail_product_id' => $loteId,
                'quantity' => 5,
                'unit_price' => 5.00,
                'transaction_date' => now()->toDateString(),
            ])
            ->assertStatus(201);

        // No se puede reducir por debajo de lo consumido (5)
        $this->actingAs($this->adminUser())
            ->putJson(self::BASE . '/transactions/' . $txId, [
                'quantity' => 3,
                'unit_price' => 5.00,
                'transaction_date' => now()->toDateString(),
            ])
            ->assertStatus(422);

        // Subir cantidad recalcula lote y total
        $this->actingAs($this->adminUser())
            ->putJson(self::BASE . '/transactions/' . $txId, [
                'quantity' => 7,
                'unit_price' => 4.00,
                'transaction_date' => now()->toDateString(),
                'start_date' => now()->toDateString(),
                'end_date' => now()->addYear()->toDateString(),
            ])
            ->assertOk()
            ->assertJsonPath('data.quantity', 7.0)
            ->assertJsonPath('data.total_price', 28.0);

        $this->assertDatabaseHas('detail_products', ['id' => $loteId, 'quantity' => 7, 'unit_price' => 4.00]);
        $this->assertDatabaseHas('transactions', ['id' => $txId, 'quantity' => 7, 'total_price' => 28]);
    }

    public function test_delete_ingreso_without_consumption_deletes_lote(): void
    {
        $txId = $this->createIngreso(10);

        $this->actingAs($this->adminUser())
            ->deleteJson(self::BASE . '/transactions/' . $txId)
            ->assertStatus(204);

        $this->assertDatabaseMissing('transactions', ['id' => $txId]);
    }

    public function test_delete_ingreso_with_consumption_rejected(): void
    {
        $txId = $this->createIngreso(10);

        $loteId = DB::table('transactions')->where('id', $txId)->value('detail_product_id');

        $this->actingAs($this->adminUser())
            ->postJson(self::BASE . '/transactions', [
                'type_transaction_id' => 1,
                'detail_product_id' => $loteId,
                'quantity' => 5,
                'unit_price' => 5.00,
                'transaction_date' => now()->toDateString(),
            ])
            ->assertStatus(201);

        $this->actingAs($this->adminUser())
            ->deleteJson(self::BASE . '/transactions/' . $txId)
            ->assertStatus(422)
            ->assertJsonPath('message', 'No se puede eliminar: el lote generado por este ingreso ya tiene salidas/pecosas asociadas.');

        $this->assertDatabaseHas('transactions', ['id' => $txId]);
        $this->assertDatabaseHas('detail_products', ['id' => $loteId]);
    }

    // ==================== SALIDAS ====================

    public function test_store_salida_deducts_stock_and_links_transaction(): void
    {
        $response = $this->actingAs($this->adminUser())
            ->postJson(self::BASE . '/transactions', [
                'type_transaction_id' => 1,
                'detail_product_id' => 1,
                'quantity' => 10,
                'unit_price' => 5.00,
                'transaction_date' => now()->toDateString(),
            ])
            ->assertStatus(201)
            ->assertJsonPath('data.type.title', 'Salida');

        $txId = $response->json('data.id');

        $this->assertDatabaseHas('product_stocks', [
            'detail_product_id' => 1,
            'transaction_id' => $txId,
            'quantity' => 10,
        ]);

        $this->assertEquals(90, app(StockService::class)->getAvailableStockByDetailProduct(1));
    }

    public function test_update_salida_recalculates_stock(): void
    {
        $txId = $this->createSalida(10);

        $this->actingAs($this->adminUser())
            ->putJson(self::BASE . '/transactions/' . $txId, [
                'quantity' => 30,
                'unit_price' => 5.00,
                'transaction_date' => now()->toDateString(),
            ])
            ->assertOk()
            ->assertJsonPath('data.quantity', 30.0);

        $this->assertEquals(70, app(StockService::class)->getAvailableStockByDetailProduct(1));

        $this->actingAs($this->adminUser())
            ->putJson(self::BASE . '/transactions/' . $txId, [
                'quantity' => 5,
                'unit_price' => 5.00,
                'transaction_date' => now()->toDateString(),
            ])
            ->assertOk();

        $this->assertEquals(95, app(StockService::class)->getAvailableStockByDetailProduct(1));
    }

    public function test_update_salida_rejects_insufficient_stock(): void
    {
        $txId = $this->createSalida(10);

        $this->actingAs($this->adminUser())
            ->putJson(self::BASE . '/transactions/' . $txId, [
                'quantity' => 500,
                'unit_price' => 5.00,
                'transaction_date' => now()->toDateString(),
            ])
            ->assertStatus(422);

        // El descuento original no debe haberse perdido ante un error
        $this->assertEquals(90, app(StockService::class)->getAvailableStockByDetailProduct(1));
    }

    public function test_delete_salida_reverts_stock(): void
    {
        $txId = $this->createSalida(10);

        $this->actingAs($this->adminUser())
            ->deleteJson(self::BASE . '/transactions/' . $txId)
            ->assertStatus(204);

        $this->assertEquals(100, app(StockService::class)->getAvailableStockByDetailProduct(1));
        $this->assertDatabaseMissing('transactions', ['id' => $txId]);
        $this->assertDatabaseMissing('product_stocks', ['transaction_id' => $txId]);
    }

    public function test_delete_salida_linked_to_pecosa_rejected(): void
    {
        $this->seedReparticionContext();
        $this->seedExistingPecosa();

        $response = $this->actingAs($this->adminUser())
            ->postJson(self::BASE . '/transactions', [
                'type_transaction_id' => 1,
                'detail_product_id' => 1,
                'quantity' => 5,
                'unit_price' => 5.00,
                'document_number' => 'PEC-001',
                'transaction_date' => now()->toDateString(),
            ])
            ->assertStatus(201);

        $txId = $response->json('data.id');

        $this->actingAs($this->adminUser())
            ->deleteJson(self::BASE . '/transactions/' . $txId)
            ->assertStatus(422)
            ->assertJsonPath('message', 'No se puede eliminar: el movimiento pertenece a una Pecosa. Elimine la Pecosa en el módulo de Pecosas.');

        $this->assertDatabaseHas('transactions', ['id' => $txId]);
    }

    // ==================== PECOSA -> STOCK LINK ====================

    public function test_pecosa_salida_links_transaction_to_stock(): void
    {
        $this->seedReparticionContext();

        $this->actingAs($this->adminUser())
            ->postJson('/api/dashboard/productos-pecosas/pecosas', [
                'pecosa_number' => 'PEC-001',
                'observation' => 'Entrega programada',
                'delivery_date' => now()->toDateString(),
                'chief_id' => 1,
                'storekeeper_id' => 2,
                'managing_partner_id' => 1,
                'state_id' => 1,
                'association_id' => 1,
                'details' => [
                    ['detail_product_id' => 1, 'quantity' => 2],
                ],
            ])
            ->assertStatus(201);

        $pecosa = DB::table('pecosas')->where('pecosa_number', 'PEC-001')->first();

        $transactionId = DB::table('transactions')->where('document_number', 'PEC-001')->value('id');

        // El stock descontado por la pecosa queda enlazado a su transacción
        $this->assertDatabaseHas('product_stocks', [
            'pecosa_id' => $pecosa->id,
            'transaction_id' => $transactionId,
            'quantity' => 2,
        ]);
    }

    // ==================== REPARTICIÓN ====================

    public function test_reparticion_returns_404_without_racion(): void
    {
        $this->actingAs($this->adminUser())
            ->getJson(self::BASE . '/reparticion?year=2027&month=3')
            ->assertStatus(404)
            ->assertJsonPath('message', 'No hay ración configurada para el período 3/2027. Configure las raciones en Responsables y Raciones.');
    }

    public function test_periods_before_march_2026_report_that_no_record_exists(): void
    {
        $this->seedReparticionContext();
        $this->seedIngresoForPeriod(2026, 2);

        $this->actingAs($this->adminUser())
            ->getJson(self::BASE . '/reparticion?year=2026&month=2')
            ->assertStatus(404)
            ->assertJsonPath('code', 'PERIOD_RECORD_NOT_FOUND')
            ->assertJsonPath(
                'message',
                'No se encuentra registro de la repartición para febrero de 2026. Los registros disponibles comienzan en marzo de 2026.'
            );
    }

    public function test_reparticion_requires_an_ingreso_in_the_same_period(): void
    {
        $this->seedReparticionContext();
        $year = now()->year;

        $this->actingAs($this->adminUser())
            ->getJson(self::BASE . '/reparticion?year=' . $year . '&month=10')
            ->assertStatus(422)
            ->assertJsonPath('code', 'INGRESO_REQUIRED')
            ->assertJsonPath(
                'message',
                "La repartición de octubre de {$year} aún no está habilitada. Registre al menos un ingreso de producto en ese período para generar, guardar o exportar la repartición."
            );

        $this->seedIngresoForPeriod($year, 11);

        $this->actingAs($this->adminUser())
            ->getJson(self::BASE . '/reparticion?year=' . $year . '&month=10')
            ->assertStatus(422)
            ->assertJsonPath('code', 'INGRESO_REQUIRED');

        $this->seedIngresoForPeriod($year, 10);

        $this->actingAs($this->adminUser())
            ->getJson(self::BASE . '/reparticion?year=' . $year . '&month=10')
            ->assertOk();
    }

    public function test_reparticion_recognizes_imported_lots_as_ingreso_in_march_and_august(): void
    {
        $this->seedReparticionContext();

        foreach ([3, 8] as $month) {
            DB::table('detail_products')->where('id', 1)->update([
                'start_date' => sprintf('2026-%02d-01', $month),
                'end_date' => date('Y-m-t', strtotime(sprintf('2026-%02d-01', $month))),
            ]);

            $this->actingAs($this->adminUser())
                ->getJson(self::BASE . '/reparticion?year=2026&month=' . $month)
                ->assertOk();
        }
    }

    public function test_reparticion_save_and_exports_are_blocked_without_ingreso(): void
    {
        $this->seedReparticionContext();
        $year = now()->year;

        $this->actingAs($this->adminUser())
            ->putJson(self::BASE . '/reparticion', [
                'year' => $year,
                'month' => 10,
                'service_days' => 31,
                'milk_grams_per_beneficiary' => 410,
                'oat_grams_per_beneficiary' => 50,
                'milk_can_grams' => 410,
                'oat_bag_grams' => 1000,
                'milk_cans_per_box' => 48,
                'oat_kg_per_sack' => 30,
                'assignments' => [],
            ])
            ->assertStatus(422)
            ->assertJsonPath('code', 'INGRESO_REQUIRED');

        $this->actingAs($this->adminUser())
            ->get(route('movimientos.distribucion.export', [
                'year' => $year,
                'month' => 10,
                'document' => 'cargo',
                'format' => 'pdf',
            ]))
            ->assertStatus(422);
    }

    public function test_reparticion_computes_rations_and_totals(): void
    {
        $this->seedReparticionContext();

        $year = now()->year;
        $days = (int) date('t', strtotime("$year-3-01"));
        $this->seedIngresoForPeriod($year, 3);

        $this->actingAs($this->adminUser())
            ->getJson(self::BASE . '/reparticion?year=' . $year . '&month=3')
            ->assertOk()
            ->assertJsonPath('total_beneficiarios', 1)
            ->assertJsonPath('days_in_month', $days)
            ->assertJsonPath('associations.0.beneficiarios', 1)
            ->assertJsonPath('associations.0.leche_litros', $days)
            ->assertJsonPath('associations.0.leche_tarros', $days % 48)
            ->assertJsonPath('associations.0.hojuelas_kg', (int) round(($days * 50) / 1000))
            ->assertJsonPath('pdf_url', route('movimientos.distribucion.export', [
                'year' => $year,
                'month' => 3,
                'document' => 'reparto',
                'format' => 'pdf',
            ]));
    }

    // ==================== HELPERS ====================

    private function createIngreso(int $quantity): int
    {
        $response = $this->actingAs($this->adminUser())
            ->postJson(self::BASE . '/transactions', [
                'type_transaction_id' => 2,
                'product_id' => 2,
                'quantity' => $quantity,
                'unit_price' => 5.00,
                'transaction_date' => now()->toDateString(),
                'start_date' => now()->toDateString(),
                'end_date' => now()->addYear()->toDateString(),
            ])
            ->assertStatus(201);

        return (int) $response->json('data.id');
    }

    private function seedIngresoForPeriod(int $year, int $month): void
    {
        DB::table('transactions')->insert([
            'quantity' => 100,
            'unit_price' => 5,
            'total_price' => 500,
            'detail_product_id' => 1,
            'type_transaction_id' => 2,
            'transaction_date' => sprintf('%04d-%02d-15', $year, $month),
            'product_name' => 'Arroz',
            'uom_title' => 'UNIDAD',
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }

    private function createSalida(int $quantity): int
    {
        $response = $this->actingAs($this->adminUser())
            ->postJson(self::BASE . '/transactions', [
                'type_transaction_id' => 1,
                'detail_product_id' => 1,
                'quantity' => $quantity,
                'unit_price' => 5.00,
                'transaction_date' => now()->toDateString(),
            ])
            ->assertStatus(201);

        return (int) $response->json('data.id');
    }
}
