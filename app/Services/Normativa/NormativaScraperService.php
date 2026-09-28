<?php

namespace App\Services\Normativa;

use App\Models\Notification;
use App\Models\NormativaDocument;
use App\Services\AssistantAiService;
use Carbon\Carbon;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class NormativaScraperService
{
    public function __construct(
        private AssistantAiService $ai,
        private NormativaRagService $rag,
    ) {
    }

    /**
     * Descubre normas nuevas en normativa.php, clasifica su relevancia para el
     * Programa de Vaso de Leche con IA y notifica las que apliquen.
     *
     * @return array{descubiertos:int, clasificados:int, relevantes:int}
     */
    public function scanAndNotify(): array
    {
        $tipos = (array) config('normativa.tipos', []);
        $descubiertos = 0;
        $clasificados = 0;
        $relevantes = 0;

        foreach ($tipos as $tipoId => $tipoLabel) {
            $items = $this->fetchListing((int) $tipoId);

            foreach ($items as $item) {
                if (NormativaDocument::where('external_id', $item['external_id'])->exists()) {
                    continue;
                }

                $document = NormativaDocument::create([
                    'external_id' => $item['external_id'],
                    'tipo_id' => $tipoId,
                    'tipo_documento' => $tipoLabel,
                    'periodo' => $item['fecha_documento'] ? mb_substr($item['fecha_documento'], 0, 7) : null,
                    'numero' => $item['numero'] ? mb_substr($item['numero'], 0, 80) : null,
                    'titulo' => mb_substr($item['titulo'], 0, 250),
                    'asunto' => $item['asunto'],
                    'concepto' => $item['concepto'],
                    'fecha_documento' => $item['fecha_documento'],
                    'pdf_url' => $item['pdf_url'],
                ]);
                $descubiertos++;

                if (! $this->isWithinBackfillWindow($document->fecha_documento)) {
                    $document->forceFill(['index_status' => 'DESCARTADO'])->save();
                    continue;
                }

                if (! $this->matchesKeywords($document)) {
                    $document->forceFill(['index_status' => 'NO_RELEVANTE'])->save();
                    continue;
                }

                $clasificados++;
                if ($this->classifyAndNotify($document)) {
                    $relevantes++;
                }
            }

            usleep(max(0, (int) config('normativa.request_delay_ms', 300)) * 1000);
        }

        return [
            'descubiertos' => $descubiertos,
            'clasificados' => $clasificados,
            'relevantes' => $relevantes,
        ];
    }

    /** @return array<int, array{external_id:int, numero:?string, titulo:string, asunto:?string, concepto:?string, fecha_documento:?string, pdf_url:string}> */
    private function fetchListing(int $tipo): array
    {
        $baseUrl = rtrim((string) config('normativa.base_url'), '/');
        $listingPath = config('normativa.listing_path');

        try {
            $response = Http::timeout((int) config('normativa.request_timeout', 20))
                ->withHeaders(['User-Agent' => 'Mozilla/5.0'])
                ->withoutVerifying()
                ->get($baseUrl.$listingPath, ['tipo' => $tipo]);
        } catch (ConnectionException $exception) {
            Log::warning('No se pudo conectar con el portal de normativa municipal.', [
                'tipo' => $tipo,
                'exception' => $exception::class,
            ]);

            return [];
        }

        if (! $response->successful()) {
            return [];
        }

        return $this->parseListing($response->body(), $baseUrl);
    }

    /** @return array<int, array{external_id:int, numero:?string, titulo:string, asunto:?string, concepto:?string, fecha_documento:?string, pdf_url:string}> */
    private function parseListing(string $html, string $baseUrl): array
    {
        preg_match_all('/<article class="mde-doc">(.*?)<\/article>/is', $html, $articles);
        $items = [];

        foreach ($articles[1] ?? [] as $article) {
            if (! preg_match('/norma_descargar\.php\?id=(\d+)/i', $article, $idMatch)) {
                continue;
            }

            $titulo = $this->plainText($this->firstMatch('/<strong>(.*?)<\/strong>/is', $article));
            if ($titulo === '') {
                continue;
            }

            $numero = null;
            if (preg_match('/N[°º]\s*([\w\-\/]+)/ui', $titulo, $numeroMatch)) {
                $numero = $numeroMatch[1];
            }

            $asunto = $this->plainText($this->firstMatch(
                '/<div class="mde-norma-subject"><span>Asunto<\/span><p>(.*?)<\/p><\/div>/is',
                $article,
            ));
            $concepto = $this->plainText($this->firstMatch(
                '/<div class="mde-norma-concept"><span>Concepto<\/span><p>(.*?)<\/p><\/div>/is',
                $article,
            ));
            $fecha = preg_match('/<span>(\d{4}-\d{2}-\d{2})<\/span>/', $article, $fechaMatch)
                ? $fechaMatch[1]
                : null;

            $pdfPath = $this->firstMatch('/norma_descargar\.php\?id=\d+/i', $article) ?: null;

            $items[] = [
                'external_id' => (int) $idMatch[1],
                'numero' => $numero,
                'titulo' => $titulo,
                'asunto' => $asunto ?: null,
                'concepto' => $concepto ?: null,
                'fecha_documento' => $fecha,
                'pdf_url' => $baseUrl.'/website/mde2026/'.$pdfPath,
            ];
        }

        return $items;
    }

    private function firstMatch(string $pattern, string $subject): string
    {
        return preg_match($pattern, $subject, $matches) ? ($matches[1] ?? $matches[0]) : '';
    }

    private function plainText(string $html): string
    {
        return trim(html_entity_decode(strip_tags($html), ENT_QUOTES | ENT_HTML5, 'UTF-8'));
    }

    private function isWithinBackfillWindow(?string $fecha): bool
    {
        if (! $fecha) {
            return true;
        }

        $days = max(0, (int) config('normativa.backfill_days', 30));

        return Carbon::parse($fecha)->greaterThanOrEqualTo(now()->subDays($days)->startOfDay());
    }

    private function matchesKeywords(NormativaDocument $document): bool
    {
        $haystack = $this->normalize($document->titulo.' '.$document->asunto.' '.$document->concepto);

        foreach ((array) config('normativa.pvl_keywords', []) as $keyword) {
            if (str_contains($haystack, $this->normalize($keyword))) {
                return true;
            }
        }

        return false;
    }

    private function normalize(string $text): string
    {
        $text = mb_strtolower($text, 'UTF-8');
        $replacements = ['á' => 'a', 'é' => 'e', 'í' => 'i', 'ó' => 'o', 'ú' => 'u', 'ñ' => 'n'];

        return strtr($text, $replacements);
    }

    private function classifyAndNotify(NormativaDocument $document): bool
    {
        try {
            $result = $this->ai->generateStructured(
                [
                    'tipo_documento' => $document->tipo_documento,
                    'numero' => $document->numero,
                    'titulo' => $document->titulo,
                    'asunto' => $document->asunto,
                    'concepto' => $document->concepto,
                ],
                $this->classificationSchema(),
                $this->classificationPrompt(),
            );
        } catch (\Throwable $exception) {
            Log::warning('No se pudo clasificar una norma municipal con IA.', [
                'document_id' => $document->id,
                'exception' => $exception::class,
                'message' => $exception->getMessage(),
            ]);
            $document->forceFill(['index_status' => 'ERROR', 'index_error' => mb_substr($exception->getMessage(), 0, 1000)])->save();

            return false;
        }

        $relevante = (bool) ($result['relevante'] ?? false);
        $resumen = trim((string) ($result['resumen'] ?? ''));
        $motivo = trim((string) ($result['motivo'] ?? ''));

        $document->forceFill([
            'relevancia_pvl' => $relevante,
            'relevancia_resumen' => $resumen ?: null,
            'relevancia_motivo' => $motivo ?: null,
        ])->save();

        if (! $relevante) {
            $document->forceFill(['index_status' => 'NO_RELEVANTE'])->save();

            return false;
        }

        $this->indexDocument($document);
        $this->notify($document);

        return true;
    }

    private function indexDocument(NormativaDocument $document): void
    {
        try {
            $response = Http::timeout((int) config('normativa.request_timeout', 20))
                ->withHeaders(['User-Agent' => 'Mozilla/5.0'])
                ->withoutVerifying()
                ->get($document->pdf_url);

            if (! $response->successful()) {
                throw new \RuntimeException('No se pudo descargar el PDF de la norma (HTTP '.$response->status().').');
            }

            $this->rag->indexDocument($document, $response->body());
        } catch (\Throwable $exception) {
            Log::warning('No se pudo indexar el PDF de una norma municipal relevante.', [
                'document_id' => $document->id,
                'exception' => $exception::class,
                'message' => $exception->getMessage(),
            ]);
        }
    }

    private function notify(NormativaDocument $document): void
    {
        Notification::create([
            'type' => 'normativa_pvl',
            'title' => trim($document->tipo_documento.' '.($document->numero ? 'N° '.$document->numero : '')),
            'description' => $document->relevancia_resumen ?: $document->asunto,
            'status' => 'informativo',
            'requested_at' => now(),
            'is_seen' => false,
            'metadata' => [
                'normativa_document_id' => $document->id,
                'external_id' => $document->external_id,
                'pdf_url' => $document->pdf_url,
                'fecha_documento' => optional($document->fecha_documento)->format('Y-m-d'),
                'tipo_documento' => $document->tipo_documento,
                'motivo' => $document->relevancia_motivo,
            ],
        ]);

        $document->forceFill(['notified_at' => now()])->save();
    }

    private function classificationSchema(): array
    {
        return [
            'type' => 'OBJECT',
            'properties' => [
                'relevante' => ['type' => 'BOOLEAN'],
                'resumen' => ['type' => 'STRING'],
                'motivo' => ['type' => 'STRING'],
            ],
            'required' => ['relevante', 'resumen', 'motivo'],
        ];
    }

    private function classificationPrompt(): string
    {
        return <<<'PROMPT'
Eres un analista legal-administrativo de la Municipalidad Distrital de La Esperanza
(La Libertad, Perú). Evalúas si una norma municipal (ordenanza, resolución, decreto o
acuerdo de concejo) es relevante para la gestión del Programa del Vaso de Leche (PVL):
socias, beneficiarios, clubes de madres, comités, raciones, productos, presupuesto,
distribución, directivas o procedimientos que afecten directamente al programa.

No es relevante si solo menciona el programa de forma incidental (ej. conformación de
comités administrativos genéricos que listan muchos programas sociales, sin cambios
operativos concretos para el PVL) ni si trata asuntos ajenos (compras de otras áreas,
designaciones de personal sin relación al PVL, trámites administrativos generales).

Responde SIEMPRE en JSON acorde al schema: "relevante" (boolean), "resumen" (máximo 2
oraciones en español, dirigido a un funcionario del PVL, explicando qué cambia o qué debe
saber), "motivo" (una oración breve justificando la clasificación).
PROMPT;
    }
}
