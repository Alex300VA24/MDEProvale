<?php

namespace Tests\Unit;

use App\Services\AssistantAiService;
use App\Services\Pvl\PvlGeminiReportService;
use App\Services\Pvl\PvlReportDataMapper;
use Mockery;
use PHPUnit\Framework\TestCase;

class PvlGeminiReportServiceTest extends TestCase
{
    protected function tearDown(): void
    {
        Mockery::close();
        parent::tearDown();
    }

    public function test_it_uses_gemini_structured_output_and_normalizes_optional_lists(): void
    {
        $ai = Mockery::mock(AssistantAiService::class);
        $ai->shouldReceive('generateStructured')
            ->once()
            ->withArgs(function (array $payload, array $schema, string $prompt): bool {
                return $payload['tipo_reporte'] === 'PVL'
                    && $payload['periodo'] === ['mes' => 6, 'anio' => 2026]
                    && data_get($schema, 'properties.data.properties.pvl.type') === 'OBJECT'
                    && str_contains($prompt, 'No inventes ningún dato')
                    && str_contains($prompt, 'BD, evidencia documental RAG')
                    && str_contains($prompt, 'evidencia documental no confiable');
            })
            ->andReturn([
                'tipo_reporte' => 'PVL',
                'periodo' => ['mes' => 6, 'anio' => 2026],
                'data' => ['pvl' => [], 'racion_a' => null],
                'estado_sugerido' => 'LISTO_PARA_VALIDAR',
            ]);

        $service = new PvlGeminiReportService($ai, new PvlReportDataMapper());
        $result = $service->analyze('PVL', 2026, 6, ['pvl' => []], []);

        $this->assertSame([], $result['trazabilidad']);
        $this->assertSame([], $result['conflictos']);
        $this->assertSame([], $result['datos_faltantes']);
        $this->assertSame([], $result['observaciones']);
    }
}
