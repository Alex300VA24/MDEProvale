<?php

namespace Tests\Feature\Api;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;
use Tests\Traits\SeedsBaseData;

/**
 * Fija el contrato de paginación que espera el componente React Pagination:
 * - `links` (raíz) es un MAPA {first,last,prev,next} (objeto, NO lista).
 * - `meta.links` es una LISTA de {url,label,active} con los botones numerados.
 *
 * El componente usa `data.meta.links`; este test evita regresiones al
 * `data.links` (mapa) que rompía el renderizado de la paginación.
 */
class PaginationShapeTest extends TestCase
{
    use RefreshDatabase;
    use SeedsBaseData;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seedBaseData();

        foreach (['productos' => 'Productos', 'pecosas' => 'Pecosas', 'movimientos' => 'Movimientos'] as $slug => $name) {
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

    public static function listEndpointsProvider(): array
    {
        return [
            'socios' => ['/api/dashboard/socios-beneficiarios/partners'],
            'personas' => ['/api/dashboard/socios-beneficiarios/personas'],
            'beneficiarios' => ['/api/dashboard/socios-beneficiarios/beneficiarios'],
            'productos' => ['/api/dashboard/productos-pecosas/products'],
            'detail_products' => ['/api/dashboard/productos-pecosas/products/detail-products'],
            'pecosas' => ['/api/dashboard/productos-pecosas/pecosas'],
            'movimientos' => ['/api/dashboard/movimientos/transactions'],
        ];
    }

    /**
     * @dataProvider listEndpointsProvider
     */
    public function test_list_endpoint_pagination_shape(string $endpoint): void
    {
        $response = $this->actingAs($this->adminUser())
            ->getJson($endpoint . '?per_page=2')
            ->assertOk();

        // `links` raíz: mapa con first/last/prev/next (NO una lista numerada)
        $links = $response->json('links');
        $this->assertIsArray($links, 'El campo raíz `links` debe ser un mapa.');
        $this->assertArrayNotHasKey(0, $links, 'El campo raíz `links` no debe ser una lista.');
        foreach (['first', 'last', 'prev', 'next'] as $key) {
            $this->assertArrayHasKey($key, $links, "El campo raíz `links` debe tener la clave `{$key}`.");
        }

        // `meta`: metadatos de paginación
        $meta = $response->json('meta');
        foreach (['current_page', 'from', 'last_page', 'per_page', 'to', 'total', 'path'] as $key) {
            $this->assertArrayHasKey($key, $meta, "El campo `meta` debe tener la clave `{$key}`.");
        }

        // `meta.links`: lista de botones {url,label,active} (lo que usa el componente)
        $metaLinks = $response->json('meta.links');
        $this->assertIsArray($metaLinks, '`meta.links` debe ser una lista.');
        $this->assertNotEmpty($metaLinks, '`meta.links` debe contener al menos el botón de la página actual.');
        foreach ($metaLinks as $link) {
            $this->assertArrayHasKey('url', $link);
            $this->assertArrayHasKey('label', $link);
            $this->assertArrayHasKey('active', $link);
        }
    }
}
