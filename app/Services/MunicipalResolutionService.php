<?php

namespace App\Services;

use App\Models\Resolution;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;

class MunicipalResolutionService
{
    private const BASE_URL = 'https://www.muniesperanza.gob.pe';
    private const SEARCH_URL = self::BASE_URL.'/website/loads/cargar_archivos.php';
    private const TIPO_RESOLUCION_ALCALDIA = 2;

    public function resolve(Resolution $resolution): ?array
    {
        if (!$resolution->document) {
            return null;
        }

        $cacheKey = "resolucion_externa_v2_{$resolution->id}_{$resolution->document}";

        return Cache::remember($cacheKey, 3600, function () use ($resolution) {
            if (!preg_match('/^(\d+)-(\d{4})$/', $resolution->document, $documentParts)) {
                return null;
            }

            [, $number, $year] = $documentParts;
            $preferredMonth = $resolution->date_start?->month;
            $months = array_values(array_unique(array_filter([
                $preferredMonth,
                ...range(1, 12),
            ])));

            foreach ($months as $month) {
                try {
                    $response = $this->httpClient(15)->get(self::SEARCH_URL, [
                        'd' => "{$year}|{$month}|{$number}|".self::TIPO_RESOLUCION_ALCALDIA,
                    ]);
                } catch (ConnectionException $exception) {
                    return null;
                }

                if (!$response->successful()) {
                    continue;
                }

                $match = $this->extractMatch($response->body(), $resolution->document);
                if ($match) {
                    return $match;
                }
            }

            return null;
        });
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

            if (!preg_match('/(?<!\d)'.preg_quote($document, '/').'(?!\d)/i', $title)) {
                continue;
            }

            if (!preg_match('/window\.open\(\s*[\'\"]([^\'\"]+\.pdf)[\'\"]/i', $row, $pdfMatch)) {
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

    private function httpClient(int $timeout)
    {
        return Http::timeout($timeout)
            ->withHeaders(['User-Agent' => 'Mozilla/5.0'])
            ->withoutVerifying();
    }
}
