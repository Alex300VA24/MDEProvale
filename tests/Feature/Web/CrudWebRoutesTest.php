<?php

namespace Tests\Feature\Web;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;
use Tests\Traits\SeedsBaseData;

/**
 * CRUD de las rutas web (no-API) de Socios/Personas/Beneficiarios, Productos,
 * Pecosas y Movimientos. Las pantallas HTML migraron al dashboard SPA, de modo
 * que las paginas GET redirigen al dashboard y las escrituras deben persistir y
 * redirigir (nunca responder 500 por vistas/rutas inexistentes).
 */
class CrudWebRoutesTest extends TestCase
{
    use RefreshDatabase;
    use SeedsBaseData;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seedBaseData();

        foreach (['productos', 'pecosas', 'movimientos'] as $order => $slug) {
            $moduleId = DB::table('modules')->where('slug', $slug)->value('id')
                ?? DB::table('modules')->insertGetId([
                    'name' => $slug, 'slug' => $slug, 'description' => $slug, 'icon' => 'fa-box',
                    'route' => $slug, 'order' => $order + 2, 'is_active' => true,
                ]);
            DB::table('module_rol')->where(['module_id' => $moduleId, 'rol_id' => 1])->delete();
            DB::table('module_rol')->insert([
                'module_id' => $moduleId, 'rol_id' => 1,
                'can_view' => true, 'can_create' => true, 'can_edit' => true, 'can_delete' => true,
            ]);
        }

        $now = now();
        DB::table('states')->insert(['id' => 2, 'title' => 'Vigente', 'abbreviation' => 'VIG']);
        DB::table('uoms')->insert(['id' => 1, 'title' => 'UNIDAD', 'created_at' => $now, 'updated_at' => $now]);
        DB::table('places')->insert(['id' => 1, 'code' => 'Z001', 'title' => 'Zona Central', 'created_at' => $now, 'updated_at' => $now]);
        DB::table('sectors')->insert(['id' => 1, 'title' => 'Sector 1', 'created_at' => $now, 'updated_at' => $now]);
        DB::table('place_sectors')->insert(['id' => 1, 'place_id' => 1, 'sector_id' => 1, 'created_at' => $now, 'updated_at' => $now]);
        DB::table('type_premises')->insert(['id' => 1, 'title' => 'Local Comunal', 'created_at' => $now, 'updated_at' => $now]);
        DB::table('resolutions')->insert(['id' => 1, 'document' => 'RES-001', 'state_id' => 2, 'created_at' => $now, 'updated_at' => $now]);
        DB::table('associations')->insert([
            'id' => 1, 'code' => 'CDM', 'name' => 'Comité Demo', 'company_name' => 'Comité Demo SAC',
            'address' => 'Av. Demo 123', 'resolution_id' => 1, 'state_id' => 2,
            'place_sector_id' => 1, 'type_premises_id' => 1, 'created_at' => $now, 'updated_at' => $now,
        ]);
        foreach ([1 => 'F', 2 => 'M', 3 => 'F'] as $id => $gender) {
            DB::table('people')->insert([
                'id' => $id, 'names' => 'Persona' . $id, 'father_lastname' => 'Apellido', 'mother_lastname' => 'Materno',
                'dni' => '1234567' . $id, 'gender' => $gender, 'birthdate' => '1990-01-01', 'address' => 'Calle 1',
                'place_sector_id' => 1, 'created_at' => $now, 'updated_at' => $now,
            ]);
        }
        DB::table('partners')->insert([
            'id' => 1, 'person_id' => 1, 'association_id' => 1, 'state_id' => 1,
            'date_begin' => '2025-01-01', 'created_at' => $now, 'updated_at' => $now,
        ]);
        DB::table('relationships')->updateOrInsert(['id' => 1], ['title' => 'HIJO(A)', 'created_at' => $now, 'updated_at' => $now]);
        DB::table('type_transactions')->insert([
            ['id' => 1, 'title' => 'Salida', 'created_at' => $now, 'updated_at' => $now],
            ['id' => 2, 'title' => 'Ingreso', 'created_at' => $now, 'updated_at' => $now],
        ]);
    }

    protected function tearDown(): void
    {
        parent::tearDown();
    }

    private function admin()
    {
        return $this->actingAs($this->adminUser());
    }

    private function dbg()
    {
        return $this->actingAs($this->adminUser());
    }

    public function test_paginas_get_de_listado_formulario_y_reportes_redirigen_al_dashboard(): void
    {
        $dashboard = route('dashboard');

        foreach ([
            route('socios-beneficiarios.index'),
            route('socios-beneficiarios.personas.index'),
            route('socios-beneficiarios.beneficiarios.index'),
            route('productos-pecosas.index'),
            route('productos-pecosas.productos.index'),
            route('productos-pecosas.productos.create'),
            route('productos-pecosas.productos.reportes'),
            route('productos-pecosas.pecosas.index'),
            route('productos-pecosas.pecosas.create'),
            route('productos-pecosas.pecosas.reportes'),
            route('productos-pecosas.productos-detalle'),
            route('movimientos.index'),
            route('movimientos.reparticion-tabla'),
        ] as $url) {
            $this->admin()->get($url)->assertRedirect($dashboard);
        }
    }

    // ==================== PERSONAS ====================

    public function test_crud_de_personas(): void
    {
        $this->admin()->post(route('socios-beneficiarios.personas.store'), [
            'names' => 'Lucia', 'father_lastname' => 'Quispe', 'mother_lastname' => 'Rojas',
            'dni' => '70000001', 'gender' => 'F', 'birthdate' => '1995-05-05',
        ])->assertRedirect(route('dashboard'));
        $this->assertDatabaseHas('people', ['dni' => '70000001', 'names' => 'Lucia']);

        $id = DB::table('people')->where('dni', '70000001')->value('id');

        $this->admin()->put(route('socios-beneficiarios.personas.update', $id), [
            'names' => 'Lucia Maria', 'father_lastname' => 'Quispe', 'mother_lastname' => 'Rojas',
            'dni' => '70000001', 'gender' => 'F',
        ])->assertRedirect(route('dashboard'));
        $this->assertDatabaseHas('people', ['id' => $id, 'names' => 'Lucia Maria']);

        $this->admin()->get(route('socios-beneficiarios.personas.index'))->assertRedirect(route('dashboard'));

        $this->admin()->delete(route('socios-beneficiarios.personas.destroy', $id))
            ->assertRedirect(route('dashboard'));
        $this->assertDatabaseMissing('people', ['id' => $id]);
    }

    public function test_persona_valida_datos_y_dni_unico(): void
    {
        $this->admin()->post(route('socios-beneficiarios.personas.store'), [])
            ->assertSessionHasErrors(['names', 'father_lastname', 'mother_lastname', 'dni']);

        $this->admin()->post(route('socios-beneficiarios.personas.store'), [
            'names' => 'X', 'father_lastname' => 'Y', 'mother_lastname' => 'Z', 'dni' => '12345671',
        ])->assertSessionHasErrors(['dni']);
    }

    public function test_persona_con_socio_no_se_puede_eliminar_y_responde_redirect(): void
    {
        $this->admin()->delete(route('socios-beneficiarios.personas.destroy', 1))
            ->assertRedirect(route('dashboard'));
        $this->assertDatabaseHas('people', ['id' => 1]);
    }

    // ==================== BENEFICIARIOS ====================

    public function test_crud_de_beneficiarios(): void
    {
        $this->admin()->post(route('socios-beneficiarios.beneficiarios.store'), [
            'person_id' => 3, 'partner_id' => 1, 'relationship_id' => 1,
        ])->assertRedirect(route('dashboard'));
        $this->assertDatabaseHas('beneficiaries', ['person_id' => 3, 'partner_id' => 1]);

        $id = DB::table('beneficiaries')->where('person_id', 3)->value('id');

        $this->admin()->put(route('socios-beneficiarios.beneficiarios.update', $id), [
            'person_id' => 2, 'partner_id' => 1, 'relationship_id' => 1,
        ])->assertRedirect(route('dashboard'));
        $this->assertDatabaseHas('beneficiaries', ['id' => $id, 'person_id' => 2]);

        $this->admin()->delete(route('socios-beneficiarios.beneficiarios.destroy', $id))
            ->assertRedirect(route('dashboard'));
        $this->assertDatabaseMissing('beneficiaries', ['id' => $id]);
    }

    public function test_beneficiario_valida_datos(): void
    {
        $this->admin()->post(route('socios-beneficiarios.beneficiarios.store'), [])
            ->assertSessionHasErrors(['person_id', 'partner_id', 'relationship_id']);
    }

    // ==================== SOCIOS ====================

    public function test_crud_de_socios_con_beneficiarios(): void
    {
        $this->admin()->post(route('socios-beneficiarios.socios.store'), [
            'person_id' => 2, 'association_id' => 1, 'state_id' => 2, 'date_begin' => '2025-02-01',
            'beneficiaries' => [['person_id' => 3, 'relationship_id' => 1]],
        ])->assertSessionHas('success')->assertRedirect(route('dashboard'));

        $partnerId = DB::table('partners')->where('person_id', 2)->value('id');
        $this->assertNotNull($partnerId);
        $this->assertDatabaseHas('beneficiaries', ['partner_id' => $partnerId, 'person_id' => 3]);

        $this->admin()->put(route('socios-beneficiarios.socios.update', $partnerId), [
            'person_id' => 2, 'association_id' => 1, 'state_id' => 2, 'date_begin' => '2025-03-01',
            'beneficiaries' => [['person_id' => 3, 'relationship_id' => 1]],
        ])->assertRedirect(route('dashboard'))->assertSessionHas('success');
        $this->assertDatabaseHas('partners', ['id' => $partnerId, 'date_begin' => '2025-03-01']);

        $this->admin()->delete(route('socios-beneficiarios.socios.destroy', $partnerId))
            ->assertRedirect(route('dashboard'));
    }

    public function test_socio_valida_datos_obligatorios(): void
    {
        $this->admin()->post(route('socios-beneficiarios.socios.store'), [])
            ->assertSessionHasErrors(['person_id', 'association_id', 'date_begin', 'beneficiaries']);
    }

    // ==================== PRODUCTOS ====================

    public function test_crud_de_productos(): void
    {
        $this->admin()->post(route('productos-pecosas.productos.store'), [
            'title' => 'Leche', 'abbreviation' => 'LEC', 'code' => 'P100', 'uom_id' => 1,
        ])->assertRedirect(route('dashboard'));
        $this->assertDatabaseHas('products', ['code' => 'P100', 'title' => 'Leche']);

        $id = DB::table('products')->where('code', 'P100')->value('id');

        $this->admin()->put(route('productos-pecosas.productos.update', $id), [
            'title' => 'Leche Evaporada', 'abbreviation' => 'LEC', 'code' => 'P100', 'uom_id' => 1,
        ])->assertRedirect(route('dashboard'));
        $this->assertDatabaseHas('products', ['id' => $id, 'title' => 'Leche Evaporada']);

        $this->admin()->get(route('productos-pecosas.productos.show', $id))->assertRedirect(route('dashboard'));
        $this->admin()->get(route('productos-pecosas.productos.edit', $id))->assertRedirect(route('dashboard'));

        $this->admin()->delete(route('productos-pecosas.productos.destroy', $id))
            ->assertRedirect(route('dashboard'));
        $this->assertDatabaseMissing('products', ['id' => $id]);
    }

    public function test_producto_ajax_responde_json(): void
    {
        $this->admin()->postJson(route('productos-pecosas.productos.store-ajax'), [
            'title' => 'Avena', 'abbreviation' => 'AVE', 'code' => 'P200', 'uom_id' => 1,
        ])->assertOk()->assertJsonPath('success', true);

        $this->admin()->postJson(route('productos-pecosas.productos.store-ajax'), [])
            ->assertStatus(422);
    }

    public function test_producto_valida_codigo_unico(): void
    {
        DB::table('products')->insert([
            'title' => 'Arroz', 'abbreviation' => 'ARR', 'code' => 'DUP', 'state_id' => 2, 'uom_id' => 1,
            'created_at' => now(), 'updated_at' => now(),
        ]);

        $this->admin()->post(route('productos-pecosas.productos.store'), [
            'title' => 'Otro', 'code' => 'DUP', 'uom_id' => 1,
        ])->assertSessionHasErrors(['code']);
    }

    // ==================== MOVIMIENTOS ====================

    private function seedLot(): void
    {
        $now = now();
        DB::table('products')->insert([
            'id' => 1, 'title' => 'Arroz', 'abbreviation' => 'ARR', 'code' => 'P001', 'state_id' => 2, 'uom_id' => 1,
            'created_at' => $now, 'updated_at' => $now,
        ]);
        DB::table('detail_products')->insert([
            'id' => 1, 'product_id' => 1, 'quantity' => 100, 'unit_price' => 5,
            'start_date' => $now->copy()->subDays(30)->toDateString(),
            'end_date' => $now->copy()->addDays(300)->toDateString(),
            'created_at' => $now, 'updated_at' => $now,
        ]);
    }

    public function test_crud_de_movimientos_ingreso_y_salida(): void
    {
        $this->seedLot();

        $this->admin()->post(route('movimientos.store'), [
            'type_transaction_id' => 2, 'product_id' => 1, 'quantity' => 10, 'unit_price' => 5,
            'transaction_date' => now()->toDateString(),
            'start_date' => now()->toDateString(), 'end_date' => now()->addYear()->toDateString(),
        ])->assertRedirect(route('dashboard'))->assertSessionHas('success');
        $this->assertDatabaseHas('transactions', ['type_transaction_id' => 2, 'quantity' => 10]);

        $this->admin()->post(route('movimientos.store'), [
            'type_transaction_id' => 1, 'detail_product_id' => 1, 'quantity' => 3, 'unit_price' => 5,
            'transaction_date' => now()->toDateString(),
        ])->assertRedirect(route('dashboard'))->assertSessionHas('success');

        $salida = DB::table('transactions')->where('type_transaction_id', 1)->first();
        $this->assertNotNull($salida);

        $this->admin()->put(route('movimientos.update', $salida->id), [
            'quantity' => 4, 'unit_price' => 5, 'transaction_date' => now()->toDateString(),
        ])->assertRedirect(route('dashboard'));

        $this->admin()->delete(route('movimientos.destroy', $salida->id))->assertRedirect(route('dashboard'));
        $this->assertDatabaseMissing('transactions', ['id' => $salida->id]);
    }

    public function test_movimiento_valida_datos(): void
    {
        $this->admin()->post(route('movimientos.store'), [])
            ->assertSessionHasErrors(['type_transaction_id', 'quantity', 'unit_price', 'transaction_date']);
    }

    public function test_salida_mayor_al_stock_no_rompe_y_informa_error(): void
    {
        $this->seedLot();

        $this->admin()->post(route('movimientos.store'), [
            'type_transaction_id' => 1, 'detail_product_id' => 1, 'quantity' => 100000, 'unit_price' => 5,
            'transaction_date' => now()->toDateString(),
        ])->assertRedirect(route('dashboard'))->assertSessionHas('error');
    }

    // ==================== PECOSAS ====================

    public function test_crud_de_pecosas(): void
    {
        $this->seedLot();
        $now = now();
        DB::table('responsibles')->insert([
            ['id' => 1, 'person_id' => 2, 'type' => 'chief', 'active' => true, 'created_at' => $now, 'updated_at' => $now],
            ['id' => 2, 'person_id' => 3, 'type' => 'storekeeper', 'active' => true, 'created_at' => $now, 'updated_at' => $now],
        ]);

        $payload = [
            'observation' => 'Entrega', 'delivery_date' => $now->toDateString(),
            'chief_id' => 1, 'storekeeper_id' => 2, 'managing_partner_id' => 1,
            'state_id' => 2, 'association_id' => 1,
            'details' => [['detail_product_id' => 1, 'quantity' => 2]],
        ];

        $this->admin()->post(route('productos-pecosas.pecosas.store'), $payload)
            ->assertRedirect(route('dashboard'))->assertSessionHas('success');

        $pecosa = DB::table('pecosas')->first();
        $this->assertNotNull($pecosa);

        $this->admin()->get(route('productos-pecosas.pecosas.show', $pecosa->id))->assertRedirect(route('dashboard'));
        $this->admin()->get(route('productos-pecosas.pecosas.edit', $pecosa->id))->assertRedirect(route('dashboard'));

        $payload['details'][0]['quantity'] = 3;
        $this->admin()->put(route('productos-pecosas.pecosas.update', $pecosa->id), $payload)
            ->assertRedirect(route('dashboard'));
        $this->assertDatabaseHas('detail_pecosas', ['pecosa_id' => $pecosa->id, 'quantity' => 3]);

        $this->admin()->delete(route('productos-pecosas.pecosas.destroy', $pecosa->id))
            ->assertRedirect(route('dashboard'));
        $this->assertDatabaseMissing('pecosas', ['id' => $pecosa->id]);
    }

    public function test_pecosa_valida_datos(): void
    {
        $this->admin()->post(route('productos-pecosas.pecosas.store'), [])
            ->assertSessionHasErrors(['delivery_date', 'association_id', 'details']);
    }

    // ==================== ACCESO ====================

    public function test_rutas_crud_requieren_autenticacion(): void
    {
        $this->post(route('socios-beneficiarios.personas.store'), [])->assertRedirect();
        $this->post(route('productos-pecosas.productos.store'), [])->assertRedirect();
        $this->post(route('movimientos.store'), [])->assertRedirect();
        $this->delete(route('productos-pecosas.pecosas.destroy', 1))->assertRedirect();
    }

    public function test_reportes_y_pdfs_requieren_autenticacion(): void
    {
        foreach ([
            route('reportes.generar', ['entidades' => ['comites']]),
            route('socios-beneficiarios.socios.ficha', 1),
            route('socios-beneficiarios.beneficiarios.padron'),
            route('socios-beneficiarios.evaluacion-nutricional.acta', 1),
            route('productos-pecosas.pecosas.comprobante', 1),
            route('productos-pecosas.pecosas.pdf-mensual'),
            route('club-reconocimientos.club.padron'),
            route('movimientos.reparticion'),
            route('movimientos.distribucion.export', ['year' => 2026, 'month' => 4, 'document' => 'reparto', 'format' => 'pdf']),
        ] as $url) {
            $this->get($url)->assertRedirect();
        }
    }
}
