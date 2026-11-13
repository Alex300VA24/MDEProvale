<?php

namespace Tests\Feature\Web;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;
use Tests\Traits\SeedsBaseData;

/**
 * Pruebas funcionales de TODOS los endpoints de generacion de reportes/PDF
 * que consume el dashboard (fichas, actas, padrones, comprobantes, reparticion,
 * control interno y generador de reportes).
 */
class ReportesPdfTest extends TestCase
{
    use RefreshDatabase;
    use SeedsBaseData;

    private const YEAR = 2026;
    private const MONTH = 4;

    private int $pecosaId;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seedBaseData();
        $this->seedModules();
        $this->seedCatalogs();
        $this->seedPecosa();
    }

    private function seedModules(): void
    {
        $slugs = [
            'productos', 'pecosas', 'movimientos', 'club-madres',
            'evaluacion-nutricional', 'reportes', 'responsables-raciones',
        ];

        foreach ($slugs as $order => $slug) {
            $moduleId = DB::table('modules')->where('slug', $slug)->value('id')
                ?? DB::table('modules')->insertGetId([
                    'name' => $slug,
                    'slug' => $slug,
                    'description' => $slug,
                    'icon' => 'fa-box',
                    'route' => $slug,
                    'order' => $order + 2,
                    'is_active' => true,
                ]);

            DB::table('module_rol')->where(['module_id' => $moduleId, 'rol_id' => 1])->delete();
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

    private function seedCatalogs(): void
    {
        $now = now();

        DB::table('states')->insert(['id' => 2, 'title' => 'Vigente', 'abbreviation' => 'VIG']);
        DB::table('uoms')->insert(['id' => 1, 'title' => 'UNIDAD', 'created_at' => $now, 'updated_at' => $now]);

        DB::table('products')->insert([
            'id' => 1, 'title' => 'Arroz', 'abbreviation' => 'ARROZ', 'code' => 'P001',
            'state_id' => 2, 'uom_id' => 1, 'created_at' => $now, 'updated_at' => $now,
        ]);

        DB::table('detail_products')->insert([
            'id' => 1, 'product_id' => 1, 'quantity' => 100, 'unit_price' => 5.00,
            'start_date' => '2026-01-15', 'end_date' => '2027-01-15',
            'created_at' => $now, 'updated_at' => $now,
        ]);

        DB::table('places')->insert(['id' => 1, 'code' => 'Z001', 'title' => 'Zona Central', 'created_at' => $now, 'updated_at' => $now]);
        DB::table('sectors')->insert(['id' => 1, 'title' => 'Sector 1', 'created_at' => $now, 'updated_at' => $now]);
        DB::table('place_sectors')->insert(['id' => 1, 'place_id' => 1, 'sector_id' => 1, 'created_at' => $now, 'updated_at' => $now]);
        DB::table('type_premises')->insert(['id' => 1, 'title' => 'Local Comunal', 'created_at' => $now, 'updated_at' => $now]);

        DB::table('resolutions')->insert([
            'id' => 1, 'document' => 'RES-001', 'date_document' => '2024-01-15 10:00:00',
            'date_start' => '2024-01-15', 'date_end' => '2099-12-31', 'state_id' => 2,
            'created_at' => $now, 'updated_at' => $now,
        ]);

        DB::table('associations')->insert([
            'id' => 1, 'code' => 'CDM', 'name' => 'Comité Demo', 'company_name' => 'Comité Demo SAC',
            'address' => 'Av. Demo 123', 'resolution_id' => 1, 'state_id' => 2,
            'place_sector_id' => 1, 'type_premises_id' => 1,
            'created_at' => $now, 'updated_at' => $now,
        ]);

        foreach ([1 => ['María', 'F'], 2 => ['Juan', 'M'], 3 => ['Ana', 'F']] as $id => [$names, $gender]) {
            DB::table('people')->insert([
                'id' => $id, 'names' => $names, 'father_lastname' => 'Apellido', 'mother_lastname' => 'Materno',
                'dni' => '1234567' . $id, 'gender' => $gender, 'birthdate' => $id === 3 ? '2022-01-01' : '1990-01-01',
                'address' => 'Calle 1', 'place_sector_id' => 1,
                'created_at' => $now, 'updated_at' => $now,
            ]);
        }

        DB::table('partners')->insert([
            'id' => 1, 'person_id' => 1, 'association_id' => 1, 'state_id' => 1,
            'date_begin' => '2025-01-01', 'created_at' => $now, 'updated_at' => $now,
        ]);

        DB::table('directives')->insert([
            'id' => 1, 'resolution_id' => 1, 'partner_id' => 1, 'position_id' => 1,
            'state_id' => 2, 'created_at' => $now, 'updated_at' => $now,
        ]);

        DB::table('responsibles')->insert([
            ['id' => 1, 'person_id' => 2, 'type' => 'chief', 'active' => true, 'created_at' => $now, 'updated_at' => $now],
            ['id' => 2, 'person_id' => 3, 'type' => 'storekeeper', 'active' => true, 'created_at' => $now, 'updated_at' => $now],
        ]);

        DB::table('relationships')->updateOrInsert(['id' => 1], ['title' => 'HIJO(A)', 'created_at' => $now, 'updated_at' => $now]);
        DB::table('beneficiaries')->insert([
            'id' => 1, 'person_id' => 3, 'partner_id' => 1, 'relationship_id' => 1,
            'created_at' => $now, 'updated_at' => $now,
        ]);
        DB::table('type_benefits')->insert([
            'id' => 1, 'title' => 'Beneficio 1', 'abbreviation' => 'BEN', 'min_age' => 0, 'max_age' => 99,
            'priority' => 1, 'created_at' => $now, 'updated_at' => $now,
        ]);
        DB::table('beneficiary_histories')->insert([
            'id' => 1, 'beneficiary_id' => 1, 'weight' => 12.5, 'height' => 90, 'hmg' => 10,
            'date_begin' => '2025-01-01', 'date_end' => null, 'type_benefit_id' => 1, 'state_id' => 1,
            'created_at' => $now, 'updated_at' => $now,
        ]);

        DB::table('raciones')->insert([
            'id' => 1, 'year' => (string) self::YEAR, 'month_start' => 1, 'month_end' => 12,
            'racion_leche_militros' => 410, 'racion_hojuelas_gramos' => 50, 'active' => true,
            'created_at' => $now, 'updated_at' => $now,
        ]);

        DB::table('type_transactions')->insert([
            ['id' => 1, 'title' => 'Salida', 'created_at' => $now, 'updated_at' => $now],
            ['id' => 2, 'title' => 'Ingreso', 'created_at' => $now, 'updated_at' => $now],
        ]);

        // Ingreso en el periodo bajo prueba: habilita repartición y exportaciones.
        DB::table('transactions')->insert([
            'quantity' => 100, 'unit_price' => 5, 'total_price' => 500, 'detail_product_id' => 1,
            'type_transaction_id' => 2, 'transaction_date' => sprintf('%04d-%02d-15', self::YEAR, self::MONTH),
            'product_name' => 'Arroz', 'uom_title' => 'UNIDAD', 'created_at' => $now, 'updated_at' => $now,
        ]);
    }

    private function seedPecosa(): void
    {
        $response = $this->actingAs($this->adminUser())->postJson('/api/dashboard/productos-pecosas/pecosas', [
            'pecosa_number' => 'PEC-001',
            'observation' => 'Entrega programada',
            'delivery_date' => sprintf('%04d-%02d-10', self::YEAR, self::MONTH),
            'chief_id' => 1,
            'storekeeper_id' => 2,
            'managing_partner_id' => 1,
            'state_id' => 2,
            'association_id' => 1,
            'details' => [['detail_product_id' => 1, 'quantity' => 2]],
        ]);
        $response->assertCreated();
        $this->pecosaId = (int) $response->json('data.id');
    }

    private function assertPdf($response): void
    {
        $response->assertOk();
        $this->assertStringContainsString('application/pdf', (string) $response->headers->get('content-type'));
        $content = $response->getContent() ?: $response->streamedContent();
        $this->assertStringStartsWith('%PDF', $content);
    }

    private function asAdmin(string $url)
    {
        return $this->actingAs($this->adminUser())->get($url);
    }

    // ==================== SOCIOS Y BENEFICIARIOS ====================

    public function test_ficha_de_socio_se_genera_en_pdf(): void
    {
        $this->assertPdf($this->asAdmin(route('socios-beneficiarios.socios.ficha', 1)));
    }

    public function test_ficha_de_beneficiario_en_blanco_se_genera_en_pdf(): void
    {
        $this->assertPdf($this->asAdmin(route('socios-beneficiarios.beneficiarios.imprimir')));
    }

    public function test_padron_de_beneficiarios_sin_comite_responde_422(): void
    {
        $this->asAdmin(route('socios-beneficiarios.beneficiarios.padron'))->assertStatus(422);
    }

    public function test_padron_de_beneficiarios_se_genera_en_pdf(): void
    {
        $this->assertPdf($this->asAdmin(route('socios-beneficiarios.beneficiarios.padron', [
            'association_id' => 1, 'month' => self::MONTH, 'year' => self::YEAR,
        ])));
    }

    public function test_acta_de_evaluacion_nutricional_se_genera_en_pdf(): void
    {
        $this->assertPdf($this->asAdmin(route('socios-beneficiarios.evaluacion-nutricional.acta', 1)));
    }

    public function test_acta_de_evaluacion_nutricional_de_comite_inexistente_da_404(): void
    {
        $this->asAdmin(route('socios-beneficiarios.evaluacion-nutricional.acta', 999))->assertNotFound();
    }

    // ==================== COMITES ====================

    public function test_actas_y_padron_de_comites_se_generan_en_pdf(): void
    {
        $this->assertPdf($this->asAdmin(route('club-reconocimientos.club.padron', ['month' => self::MONTH, 'year' => self::YEAR])));
        $this->assertPdf($this->asAdmin(route('club-reconocimientos.acta-renuncia')));
        $this->assertPdf($this->asAdmin(route('club-reconocimientos.acta-reuniones')));
        $this->assertPdf($this->asAdmin(route('club-reconocimientos.acta-supervision')));
    }

    // ==================== PECOSAS ====================

    public function test_comprobante_y_pecosa_completa_se_generan_en_pdf(): void
    {
        $this->assertPdf($this->asAdmin(route('productos-pecosas.pecosas.comprobante', $this->pecosaId)));
        $this->assertPdf($this->asAdmin(route('productos-pecosas.pecosas.completa', $this->pecosaId)));
    }

    public function test_comprobante_de_pecosa_inexistente_da_404(): void
    {
        $this->asAdmin(route('productos-pecosas.pecosas.comprobante', 9999))->assertNotFound();
        $this->asAdmin(route('productos-pecosas.pecosas.completa', 9999))->assertNotFound();
    }

    public function test_pdf_mensual_de_pecosas(): void
    {
        $this->assertPdf($this->asAdmin(route('productos-pecosas.pecosas.pdf-mensual', [
            'year' => self::YEAR, 'month' => self::MONTH,
        ])));
    }

    public function test_pdf_mensual_sin_pecosas_en_el_periodo_da_404(): void
    {
        $this->asAdmin(route('productos-pecosas.pecosas.pdf-mensual', ['year' => self::YEAR, 'month' => 12]))
            ->assertNotFound()
            ->assertJsonPath('message', 'No hay pecosas registradas para el periodo seleccionado.');
    }

    public function test_pdf_mensual_valida_parametros(): void
    {
        $this->actingAs($this->adminUser())
            ->getJson(route('productos-pecosas.pecosas.pdf-mensual', ['year' => 1999, 'month' => 13]))
            ->assertStatus(422)
            ->assertJsonValidationErrors(['year', 'month']);
    }

    public function test_acta_de_supervision_de_pecosas_se_genera_en_pdf(): void
    {
        $this->assertPdf($this->asAdmin(route('productos-pecosas.pecosas.acta-supervision')));
    }

    public function test_programacion_de_entrega_se_genera_en_pdf(): void
    {
        $this->assertPdf($this->asAdmin(route('productos-pecosas.pecosas.programacion-entrega', [
            'year' => self::YEAR, 'month' => self::MONTH,
        ])));
    }

    public function test_programacion_de_entrega_rechaza_periodos_sin_registro_o_sin_ingreso(): void
    {
        $this->asAdmin(route('productos-pecosas.pecosas.programacion-entrega', ['year' => 2026, 'month' => 2]))
            ->assertNotFound();
        $this->asAdmin(route('productos-pecosas.pecosas.programacion-entrega', ['year' => 2026, 'month' => 9]))
            ->assertStatus(422);
    }

    public function test_verificar_vigencia_responde_json(): void
    {
        $this->actingAs($this->adminUser())
            ->postJson(route('productos-pecosas.pecosas.verificar-vigencia'), [
                'association_id' => 1,
                'delivery_date' => sprintf('%04d-%02d-20', self::YEAR, self::MONTH + 1),
            ])
            ->assertOk()
            ->assertJsonStructure(['supersede']);

        $this->actingAs($this->adminUser())
            ->postJson(route('productos-pecosas.pecosas.verificar-vigencia'), ['association_id' => 999])
            ->assertStatus(422)
            ->assertJsonValidationErrors(['association_id', 'delivery_date']);
    }

    // ==================== REPARTICION ====================

    public function test_reparticion_pdf_y_tabla_se_generan(): void
    {
        $this->assertPdf($this->asAdmin(route('movimientos.reparticion', ['year' => self::YEAR, 'month' => self::MONTH])));
        $this->assertPdf($this->asAdmin(route('movimientos.reparticion-con-firma', ['year' => self::YEAR, 'month' => self::MONTH])));
        $this->asAdmin(route('movimientos.reparticion-tabla', ['year' => self::YEAR, 'month' => self::MONTH]))
            ->assertRedirect(route('dashboard'));
    }

    public function test_reparticion_sin_ingreso_o_sin_registro_se_rechaza(): void
    {
        $this->asAdmin(route('movimientos.reparticion', ['year' => self::YEAR, 'month' => 9]))->assertStatus(422);
        $this->asAdmin(route('movimientos.reparticion', ['year' => 2026, 'month' => 1]))->assertNotFound();
        $this->asAdmin(route('movimientos.reparticion-con-firma', ['year' => self::YEAR, 'month' => 9]))
            ->assertStatus(422);
        $this->asAdmin(route('movimientos.reparticion-con-firma', ['year' => self::YEAR, 'month' => 13]))
            ->assertStatus(422);
    }

    public function test_exportaciones_de_distribucion_en_pdf_y_excel(): void
    {
        $period = ['year' => self::YEAR, 'month' => self::MONTH];

        foreach (['reparto', 'cargo', 'fiscalizacion'] as $document) {
            $this->assertPdf($this->asAdmin(route('movimientos.distribucion.export', $period + ['document' => $document, 'format' => 'pdf'])));

            $excel = $this->asAdmin(route('movimientos.distribucion.export', $period + ['document' => $document, 'format' => 'xlsx']));
            $excel->assertOk();
            $this->assertStringContainsString('spreadsheetml', (string) $excel->headers->get('content-type'));
        }

        $this->assertPdf($this->asAdmin(route('movimientos.distribucion.export', $period + ['document' => 'acta', 'format' => 'pdf'])));
    }

    public function test_exportacion_de_acta_solo_admite_pdf(): void
    {
        $this->asAdmin(route('movimientos.distribucion.export', [
            'year' => self::YEAR, 'month' => self::MONTH, 'document' => 'acta', 'format' => 'xlsx',
        ]))->assertStatus(422);
    }

    public function test_exportacion_valida_documento_y_periodo(): void
    {
        $this->asAdmin(route('movimientos.distribucion.export', [
            'year' => self::YEAR, 'month' => self::MONTH, 'document' => 'inexistente', 'format' => 'pdf',
        ]))->assertStatus(302);

        $this->asAdmin(route('movimientos.distribucion.export', [
            'year' => self::YEAR, 'month' => 9, 'document' => 'reparto', 'format' => 'pdf',
        ]))->assertStatus(422);
    }

    // ==================== CONTROL INTERNO ====================

    public function test_tarjeta_de_control_devuelve_datos_precargados(): void
    {
        foreach (['milk', 'oat'] as $product) {
            $this->actingAs($this->adminUser())
                ->getJson('/api/dashboard/movimientos/tarjeta-control?' . http_build_query([
                    'product' => $product, 'year' => self::YEAR, 'month' => self::MONTH,
                ]))
                ->assertOk();
        }

        $this->actingAs($this->adminUser())
            ->getJson('/api/dashboard/movimientos/tarjeta-control?product=otro&year=2026&month=4')
            ->assertStatus(422)
            ->assertJsonValidationErrors(['product']);
    }

    public function test_ficha_y_tarjeta_de_control_se_generan_en_pdf(): void
    {
        foreach (['milk', 'oat'] as $product) {
            $ficha = $this->actingAs($this->adminUser())->post(route('movimientos.ficha-control'), [
                'product' => $product,
                'payload' => json_encode([
                    'items' => [['label' => 'Envase integro', 'value' => 'si']],
                    'extras' => [['label' => 'Otro', 'value' => 'no']],
                    'weighing' => 'si',
                ]),
            ]);
            $this->assertPdf($ficha);

            $tarjeta = $this->actingAs($this->adminUser())->post(route('movimientos.tarjeta-control'), [
                'product' => $product,
                'payload' => json_encode([
                    'folio' => '001',
                    'month' => 'Abril 2026',
                    'rows' => [['date' => '2026-04-10', 'time' => '09:00', 'in' => '100', 'out' => '', 'by' => 'Juan']],
                ]),
            ]);
            $this->assertPdf($tarjeta);
        }
    }

    public function test_ficha_y_tarjeta_de_control_validan_payload(): void
    {
        $this->actingAs($this->adminUser())
            ->post(route('movimientos.ficha-control'), ['product' => 'milk', 'payload' => 'no-json'])
            ->assertStatus(422);

        $this->actingAs($this->adminUser())
            ->post(route('movimientos.tarjeta-control'), ['product' => 'x'])
            ->assertStatus(302);
    }

    // ==================== GENERADOR DE REPORTES ====================

    public function test_generador_de_reportes_produce_pdf_por_entidad(): void
    {
        foreach (['comites', 'beneficiarios', 'productos', 'pecosas', 'movimientos'] as $entidad) {
            $this->assertPdf($this->asAdmin(route('reportes.generar', ['entidades' => [$entidad]])));
        }
    }

    public function test_generador_de_reportes_combina_entidades_columnas_filtros_y_agrupacion(): void
    {
        $this->assertPdf($this->asAdmin(route('reportes.generar', [
            'entidades' => ['comites', 'beneficiarios', 'pecosas', 'movimientos'],
            'columnas' => ['comites' => ['code', 'name'], 'pecosas' => ['pecosa_number', 'estado']],
            'filtros' => [
                'comites' => ['place_sector_id' => 1],
                'pecosas' => ['date_from' => '2026-01-01', 'date_to' => '2026-12-31', 'association_id' => 1],
                'movimientos' => ['type_transaction_id' => 2],
            ],
            'agrupar_por' => ['comites' => 'sector', 'movimientos' => 'tipo'],
        ])));
    }

    public function test_generador_de_reportes_exige_una_entidad_valida(): void
    {
        $this->actingAs($this->adminUser())
            ->getJson(route('reportes.generar'))
            ->assertStatus(422)
            ->assertJsonPath('message', 'Selecciona al menos una entidad para el reporte.');

        $this->actingAs($this->adminUser())
            ->getJson(route('reportes.generar', ['entidades' => ['inexistente']]))
            ->assertStatus(422);
    }

    public function test_reporte_de_pecosas_queda_registrado_como_documento_verificable(): void
    {
        $this->assertPdf($this->asAdmin(route('reportes.generar', ['entidades' => ['pecosas']])));

        $this->assertDatabaseHas('verified_documents', ['type' => \App\Models\VerifiedDocument::TYPE_PECOSA_REGISTER]);
    }

    // ==================== API AUXILIARES DE REPORTES ====================

    public function test_siguiente_numero_de_pecosa(): void
    {
        $this->actingAs($this->adminUser())
            ->getJson('/api/dashboard/productos-pecosas/pecosas/next-number')
            ->assertOk()
            ->assertJsonStructure(['pecosa_number']);
    }

    public function test_actualizar_pecosa_sin_enviar_numero_conserva_el_existente(): void
    {
        $number = DB::table('pecosas')->where('id', $this->pecosaId)->value('pecosa_number');

        $this->actingAs($this->adminUser())
            ->putJson('/api/dashboard/productos-pecosas/pecosas/' . $this->pecosaId, [
                'delivery_date' => sprintf('%04d-%02d-10', self::YEAR, self::MONTH),
                'chief_id' => 1,
                'storekeeper_id' => 2,
                'managing_partner_id' => 1,
                'state_id' => 2,
                'association_id' => 1,
                'details' => [['detail_product_id' => 1, 'quantity' => 4]],
            ])
            ->assertOk();

        $this->assertDatabaseHas('pecosas', ['id' => $this->pecosaId, 'pecosa_number' => $number]);
        $this->assertDatabaseHas('detail_pecosas', ['pecosa_id' => $this->pecosaId, 'quantity' => 4]);
    }

    public function test_evaluacion_nutricional_lista_comites_y_beneficiarios(): void
    {
        $this->actingAs($this->adminUser())
            ->getJson('/api/dashboard/evaluacion-nutricional/options')
            ->assertOk()
            ->assertJsonStructure(['associations']);

        $this->actingAs($this->adminUser())
            ->getJson('/api/dashboard/evaluacion-nutricional/1/beneficiarios')
            ->assertOk()
            ->assertJsonPath('association.code', 'CDM')
            ->assertJsonStructure(['rows']);
    }

    // ==================== ACCESO ====================
}
