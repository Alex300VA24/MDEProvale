<?php

namespace Tests\Feature\Api;

use App\Services\AssistantAiService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Mockery;
use Tests\TestCase;
use Tests\Traits\SeedsBaseData;

class ReportesTrimestralApiTest extends TestCase
{
    use RefreshDatabase;
    use SeedsBaseData;

    private const BASE = '/api/dashboard/reportes-pvl/trimestral';

    protected function setUp(): void
    {
        parent::setUp();

        $this->seedBaseData();
        Cache::flush();
        $moduleId = DB::table('modules')->where('slug', 'reportes')->value('id')
            ?? DB::table('modules')->insertGetId(['name' => 'Reportes', 'slug' => 'reportes', 'description' => 'Reportes', 'icon' => 'fa-chart-bar', 'route' => null, 'order' => 8]);
        DB::table('module_rol')->updateOrInsert([
            'module_id' => $moduleId,
            'rol_id' => 1,
        ], ['can_view' => true, 'can_create' => true, 'can_edit' => true, 'can_delete' => true]);
    }

    public function test_requires_authentication(): void
    {
        $this->getJson(self::BASE.'?year=2026&quarter=2')->assertUnauthorized();
    }

    public function test_quarterly_report_validates_and_returns_the_period_structure(): void
    {
        $this->actingAs($this->adminUser())->getJson(self::BASE.'?year=2026&quarter=9')->assertUnprocessable();

        $this->actingAs($this->adminUser())
            ->getJson(self::BASE.'?year=2026&quarter=2')
            ->assertOk()
            ->assertJsonPath('quarter_text', 'II TRIMESTRE')
            ->assertJsonCount(3, 'months')
            ->assertJsonStructure(['totals', 'ingresos_por_producto', 'distribucion_mensual', 'beneficiarios_categorias', 'no_disponible']);
    }

    public function test_analysis_is_requested_to_the_ai_provider_and_cached(): void
    {
        $ai = Mockery::mock(AssistantAiService::class);
        $ai->shouldReceive('generateStructured')->once()->andReturn([
            'resumen' => 'Resumen IA.', 'analisis_mensual' => ['Abril.'], 'hallazgos' => ['H1'],
            'conclusiones' => ['C1'], 'recomendaciones' => ['R1'],
        ]);
        $ai->shouldReceive('modelIdentifier')->andReturn('modelo-prueba');
        $this->app->instance(AssistantAiService::class, $ai);

        foreach ([1, 2] as $_) {
            $this->actingAs($this->adminUser())
                ->postJson(self::BASE.'/analisis', ['year' => 2026, 'quarter' => 2])
                ->assertOk()
                ->assertJsonPath('resumen', 'Resumen IA.')
                ->assertJsonPath('modelo', 'modelo-prueba');
        }
    }

    public function test_document_preview_includes_analysis_and_header_data(): void
    {
        $response = $this->actingAs($this->adminUser())->postJson(self::BASE.'/documento', [
            'year' => 2026, 'quarter' => 2,
            'meta' => ['number' => '123-2026-MDE/GDS/SGPS', 'to' => 'Destinatario'],
            'analysis' => ['resumen' => 'Resumen', 'conclusiones' => ['Conclusión uno'], 'recomendaciones' => []],
        ])->assertOk()->assertJsonPath('letterhead.programa', 'PROVALE');

        $blocks = collect($response->json('blocks'));
        $this->assertSame('INFORME N° 123-2026-MDE/GDS/SGPS', $blocks->firstWhere('type', 'title')['text']);
        $this->assertTrue($blocks->contains(fn ($b) => $b['type'] === 'h' && str_contains($b['text'], 'Conclusiones')));
    }

    public function test_export_downloads_word_and_pdf_files(): void
    {
        $png = 'data:image/png;base64,'.base64_encode((string) $this->png());
        $payload = ['year' => 2026, 'quarter' => 2, 'meta' => ['from' => 'Remitente'], 'charts' => ['ingresos_mes' => $png]];

        $ai = Mockery::mock(AssistantAiService::class);
        $ai->shouldReceive('generateStructured')->andReturn([
            'resumen' => 'Resumen IA.', 'analisis_mensual' => [], 'hallazgos' => [], 'conclusiones' => ['C1'], 'recomendaciones' => [],
            'graficos' => ['ingresos_mes' => 'Texto del gráfico de ingresos.'],
        ]);
        $ai->shouldReceive('modelIdentifier')->andReturn('modelo-prueba');
        $this->app->instance(AssistantAiService::class, $ai);

        $docx = $this->actingAs($this->adminUser())->post(self::BASE.'/exportar/docx', $payload);
        $docx->assertOk();
        $this->assertStringContainsString('INFORME-PVL-2T-2026.docx', $docx->headers->get('content-disposition'));
        $this->assertStringStartsWith('PK', $docx->baseResponse->getFile()->getContent());

        $pdf = $this->actingAs($this->adminUser())->post(self::BASE.'/exportar/pdf', $payload);
        $pdf->assertOk();
        $this->assertStringStartsWith('%PDF', $pdf->getContent());

        $this->actingAs($this->adminUser())->post(self::BASE.'/exportar/xls', $payload)->assertNotFound();
    }

    public function test_document_places_the_chart_analysis_under_its_figure(): void
    {
        $response = $this->actingAs($this->adminUser())->postJson(self::BASE.'/documento', [
            'year' => 2026, 'quarter' => 2,
            'analysis' => ['resumen' => 'R', 'graficos' => ['ingresos_mes' => 'Texto del gráfico de ingresos.']],
        ])->assertOk();

        $blocks = collect($response->json('blocks'))->values();
        $i = $blocks->search(fn ($b) => $b['type'] === 'image' && $b['key'] === 'ingresos_mes');
        $this->assertSame('Texto del gráfico de ingresos.', $blocks[$i + 1]['text']);
    }

    private function png(): string
    {
        ob_start();
        imagepng(imagecreatetruecolor(20, 10));

        return (string) ob_get_clean();
    }
}
