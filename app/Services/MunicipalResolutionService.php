<?php

namespace App\Services;

use App\Exceptions\MunicipalPortalException;
use App\Models\Resolution;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Http\Client\PendingRequest;
use Illuminate\Http\Client\Pool;
use Illuminate\Http\Client\Response;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class MunicipalResolutionService
{
    private const BASE_URL = 'https://www.muniesperanza.gob.pe';
    private const SEARCH_URL = self::BASE_URL.'/website/loads/cargar_archivos.php';
    private const TIPO_RESOLUCION_ALCALDIA = 2;
    private const CONNECT_TIMEOUT = 6;
    private const SEARCH_TIMEOUT = 12;
    private const PDF_TIMEOUT = 25;
    private const TTL_FOUND = 3600;
    private const TTL_NOT_FOUND = 300;

    /**
     * Busca la resolución en el portal.
     *
     * @return array{pdf_url: string, titulo: string, fecha: ?string}|null null si el portal no la publica
     * @throws MunicipalPortalException si el portal no responde o responde con error
     *         (los fallos nunca se cachean: un corte de internet no debe quedar como "no existe")
     */
    public function resolve(Resolution $resolution): ?array
    {
        if (! $resolution->document) {
            return null;
        }

        return $this->resolveByDocument($resolution->document, $resolution->date_start?->month);
    }

    /**
     * Busca por número de documento (formato NNNN-AAAA) sin necesidad de la
     * resolución en BD: sirve antes de registrar.
     *
     * @return array{pdf_url: string, titulo: string, fecha: ?string}|null null si el portal no la publica
     * @throws MunicipalPortalException si el portal no responde o responde con error
     */
    public function resolveByDocument(string $document, ?int $preferredMonth = null): ?array
    {
        $document = trim($document);

        if (! preg_match('/^(\d+)-(\d{4})$/', $document, $documentParts)) {
            return null;
        }

        $cacheKey = "resolucion_externa_v3_{$document}";
        $cached = Cache::get($cacheKey);

        if (is_array($cached)) {
            return $cached;
        }
        if ($cached === false) {
            return null;
        }

        [, $number, $year] = $documentParts;

        $match = $this->searchMonths($document, $year, $number, $preferredMonth);

        Cache::put($cacheKey, $match ?? false, $match ? self::TTL_FOUND : self::TTL_NOT_FOUND);

        return $match;
    }

    /**
     * Descarga el PDF de la resolución desde el portal municipal.
     *
     * @return array{body: string, filename: string}|null null si el portal no la publica
     * @throws MunicipalPortalException si el portal no responde o no devuelve el PDF
     */
    public function fetchPdf(Resolution $resolution): ?array
    {
        $match = $this->resolve($resolution);

        if (! $match) {
            return null;
        }

        try {
            $pdf = $this->httpClient(self::PDF_TIMEOUT)->retry(2, 400, $this->retryOnConnection(), throw: false)->get($match['pdf_url']);
        } catch (ConnectionException $e) {
            $this->logFailure('pdf', $e->getMessage());
            throw MunicipalPortalException::fromConnection($e);
        }

        if (! $pdf->successful()) {
            $this->logFailure('pdf', 'HTTP '.$pdf->status());
            throw MunicipalPortalException::fromStatus($pdf->status());
        }

        if (! str_starts_with(ltrim($pdf->body()), '%PDF')) {
            $this->logFailure('pdf', 'respuesta sin cabecera %PDF');
            throw new MunicipalPortalException(
                MunicipalPortalException::PDF_INVALID,
                'El portal respondió, pero el archivo no es un PDF válido.',
                502,
                'El documento puede estar dañado o movido en el portal. Inténtalo más tarde.',
            );
        }

        return ['body' => $pdf->body(), 'filename' => 'Resolucion-'.$resolution->document.'-MDE.pdf'];
    }

    /**
     * Mes preferido primero (caso común: 1 sola petición); si no aparece,
     * los demás meses se consultan en paralelo en vez de uno por uno.
     */
    private function searchMonths(string $document, string $year, string $number, ?int $preferredMonth): ?array
    {
        $failure = null;
        $remaining = range(1, 12);

        if ($preferredMonth) {
            $remaining = array_values(array_diff($remaining, [$preferredMonth]));

            try {
                $response = $this->httpClient(self::SEARCH_TIMEOUT)
                    ->retry(2, 300, $this->retryOnConnection(), throw: false)
                    ->get(self::SEARCH_URL, ['d' => $this->query($year, $preferredMonth, $number)]);
            } catch (ConnectionException $e) {
                // Si el portal no responde, probar 11 meses más solo multiplica la espera.
                $this->logFailure('search', $e->getMessage());
                throw MunicipalPortalException::fromConnection($e);
            }

            if ($response->successful()) {
                if ($match = $this->extractMatch($response->body(), $document)) {
                    return $match;
                }
            } else {
                $failure = MunicipalPortalException::fromStatus($response->status());
                $this->logFailure('search', 'HTTP '.$response->status());
            }
        }

        $responses = Http::pool(fn (Pool $pool) => array_map(
            fn (int $month) => $this->configure($pool->as((string) $month), self::SEARCH_TIMEOUT)
                ->get(self::SEARCH_URL, ['d' => $this->query($year, $month, $number)]),
            $remaining,
        ));

        $connectionError = null;
        $statusError = $failure;

        foreach ($remaining as $month) {
            $response = $responses[(string) $month] ?? null;

            if ($response instanceof Response) {
                if ($response->successful()) {
                    if ($match = $this->extractMatch($response->body(), $document)) {
                        return $match;
                    }
                } else {
                    $statusError ??= MunicipalPortalException::fromStatus($response->status());
                }
            } elseif ($response instanceof \Throwable) {
                $connectionError ??= $response;
            }
        }

        // Ninguna coincidencia: solo es "no existe" si el portal contestó todos los meses.
        if ($connectionError) {
            $this->logFailure('search', $connectionError->getMessage());
            throw MunicipalPortalException::fromConnection($connectionError);
        }
        if ($statusError) {
            throw $statusError;
        }

        return null;
    }

    private function query(string $year, int $month, string $number): string
    {
        return "{$year}|{$month}|{$number}|".self::TIPO_RESOLUCION_ALCALDIA;
    }

    private function extractMatch(string $html, string $document): ?array
    {
        preg_match_all(
            '/<tr\b[^>]*class=[\'\"][^\'\"]*\bod\b[^\'\"]*[\'\"][^>]*>(.*?)<\/tr>/is',
            $html,
            $rows,
        );

        foreach ($rows[1] ?? [] as $row) {
            preg_match_all('/<td\b[^>]*>(.*?)<\/td>/is', $row, $cells);
            $title = $this->plainText($cells[1][2] ?? '');

            if (! preg_match('/(?<!\d)'.preg_quote($document, '/').'(?!\d)/i', $title)) {
                continue;
            }

            if (! preg_match('/window\.open\(\s*[\'\"]([^\'\"]+\.pdf)[\'\"]/i', $row, $pdfMatch)) {
                continue;
            }

            $relativePath = preg_replace('#^(?:\.\./)+#', '', ltrim($pdfMatch[1], '/'));

            return [
                'pdf_url' => self::BASE_URL.'/'.ltrim((string) $relativePath, '/'),
                'titulo' => $title,
                'fecha' => $this->plainText($cells[1][3] ?? '') ?: null,
            ];
        }

        return null;
    }

    private function plainText(string $html): string
    {
        return trim(html_entity_decode(strip_tags($html), ENT_QUOTES | ENT_HTML5, 'UTF-8'));
    }

    private function httpClient(int $timeout): PendingRequest
    {
        return $this->configure(Http::withOptions([]), $timeout);
    }

    private function configure(PendingRequest $request, int $timeout): PendingRequest
    {
        return $request
            ->connectTimeout(self::CONNECT_TIMEOUT)
            ->timeout($timeout)
            ->withHeaders(['User-Agent' => 'Mozilla/5.0'])
            ->withoutVerifying();
    }

    private function retryOnConnection(): \Closure
    {
        return fn ($exception) => $exception instanceof ConnectionException;
    }

    private function logFailure(string $stage, string $detail): void
    {
        Log::warning('Portal municipal: fallo en '.$stage, ['detalle' => $detail]);
    }
}
