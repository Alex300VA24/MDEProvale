<?php

namespace Tests\Unit;

use App\Models\Resolution;
use App\Services\MunicipalResolutionService;
use Illuminate\Http\Client\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class MunicipalResolutionServiceTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        Cache::flush();
    }

    public function test_it_finds_a_resolution_when_its_publication_month_differs_from_date_start(): void
    {
        Http::fake(function (Request $request) {
            parse_str((string) parse_url($request->url(), PHP_URL_QUERY), $query);

            if (($query['d'] ?? null) === '2021|3|0207|2') {
                return Http::response($this->resultHtml(), 200);
            }

            return Http::response($this->emptyHtml(), 200);
        });

        $resolution = new Resolution([
            'document' => '0207-2021',
            'date_start' => '2025-05-12',
        ]);
        $resolution->id = 987654;

        $result = app(MunicipalResolutionService::class)->resolve($resolution);

        $this->assertSame('RESOLUCION DE ALCALDIA N°0207-2021-MDE', $result['titulo']);
        $this->assertSame('02/03/2021', $result['fecha']);
        $this->assertSame(
            'https://www.muniesperanza.gob.pe/admin/panel/img/resolucion-0207.pdf',
            $result['pdf_url'],
        );
        Http::assertSentCount(4);
    }

    public function test_it_ignores_a_different_document_returned_by_the_portal(): void
    {
        Http::fake(Http::response(str_replace('0207-2021', '1207-2021', $this->resultHtml()), 200));

        $resolution = new Resolution([
            'document' => '0207-2021',
            'date_start' => '2021-03-02',
        ]);
        $resolution->id = 987655;

        $this->assertNull(app(MunicipalResolutionService::class)->resolve($resolution));
        Http::assertSentCount(12);
    }

    private function resultHtml(): string
    {
        return <<<'HTML'
<table>
    <tr class='od'>
        <td>1</td>
        <td>RESOLUCIONES DE ALCALDÍA</td>
        <td>RESOLUCION DE ALCALDIA N°0207-2021-MDE</td>
        <td>02/03/2021</td>
        <td><a onclick=window.open('../../admin/panel/img/resolucion-0207.pdf','window')>PDF</a></td>
    </tr>
</table>
HTML;
    }

    private function emptyHtml(): string
    {
        return '<table><tr><td>Sin registros</td></tr></table>';
    }
}
