<?php

namespace App\Services\Normativa;

use App\Models\Notification;
use App\Models\NormativaDocument;
use App\Services\AssistantAiService;
use App\Services\Rag\DocumentBinaryStorage;
use Carbon\Carbon;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Http\Client\Response;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

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
                if ($this->directKeywordMatch($document) !== null
                    ? $this->importDirectMatch($document)
                    : $this->classifyAndNotify($document)) {
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

    /**
     * Importa bajo demanda el historial visible del portal que pueda estar
     * relacionado con el PVL. A diferencia del escaneo diario, no aplica la
     * ventana de antigüedad y vuelve a intentar documentos relevantes que
     * hubieran fallado al indexarse.
     *
     * @return array{revisados:int,encontrados:int,clasificados:int,importados:int,ya_importados:int,descartados:int,errores:int}
     */
    public function importRelevantDocuments(): array
    {
        $stats = [
            'revisados' => 0,
            'encontrados' => 0,
            'clasificados' => 0,
            'importados' => 0,
            'ya_importados' => 0,
            'descartados' => 0,
            'errores' => 0,
        ];

        foreach ((array) config('normativa.tipos', []) as $tipoId => $tipoLabel) {
            $items = $this->fetchListing((int) $tipoId);
            $stats['revisados'] += count($items);

            foreach ($items as $item) {
                $candidate = new NormativaDocument([
                    'tipo_documento' => $tipoLabel,
                    'titulo' => $item['titulo'],
                    'asunto' => $item['asunto'],
                    'concepto' => $item['concepto'],
                ]);

                if (! $this->matchesKeywords($candidate)) {
                    continue;
                }

                $stats['encontrados']++;
                $document = NormativaDocument::query()->firstOrNew(['external_id' => $item['external_id']]);
                $document->fill([
                    'tipo_id' => $tipoId,
                    'tipo_documento' => $tipoLabel,
                    'periodo' => $item['fecha_documento'] ? mb_substr($item['fecha_documento'], 0, 7) : null,
                    'numero' => $item['numero'] ? mb_substr($item['numero'], 0, 80) : null,
                    'titulo' => mb_substr($item['titulo'], 0, 250),
                    'asunto' => $item['asunto'],
                    'concepto' => $item['concepto'],
                    'fecha_documento' => $item['fecha_documento'],
                    'pdf_url' => $item['pdf_url'],
                ])->save();

                $directKeyword = $this->directKeywordMatch($document);
                if ($directKeyword !== null) {
                    $this->markDirectlyRelevant($document, $directKeyword);
                }

                if ($document->index_status === 'INDEXADO') {
                    if ($this->ensureStoredPdfSafely($document)) {
                        $stats['ya_importados']++;
                    } else {
                        $stats['errores']++;
                    }
                    continue;
                }

                if ($document->relevancia_pvl === false) {
                    $stats['descartados']++;
                    continue;
                }

                if ($document->relevancia_pvl === true) {
                    if ($this->indexDocument($document)) {
                        $this->notify($document);
                        $stats['importados']++;
                    } else {
                        $stats['errores']++;
                    }
                    continue;
                }

                $stats['clasificados']++;
                if ($this->classifyAndNotify($document)) {
                    $stats['importados']++;
                } elseif ($document->fresh()->relevancia_pvl === false) {
                    $stats['descartados']++;
                } else {
                    $stats['errores']++;
                }
            }

            usleep(max(0, (int) config('normativa.request_delay_ms', 300)) * 1000);
        }

        if ($stats['revisados'] === 0) {
            throw new \RuntimeException('El portal municipal no devolvió documentos. Intente nuevamente en unos minutos.');
        }

        return $stats;
    }

    /**
     * Importa una norma concreta indicada por su enlace de Copia verificable
     * o por su número oficial. La elección manual confirma su relevancia para
     * la base de conocimiento, por lo que no depende de la clasificación IA.
     *
     * @return array{document_id:int,numero:?string,titulo:string,importado:bool,ya_importado:bool,index_status:string}
     */
    public function importDocument(string $reference): array
    {
        $item = $this->resolveDocumentReference($reference);
        $document = NormativaDocument::query()->firstOrNew(['external_id' => $item['external_id']]);
        $document->fill([
            'tipo_id' => $item['tipo_id'],
            'tipo_documento' => $item['tipo_documento'],
            'periodo' => $item['fecha_documento'] ? mb_substr($item['fecha_documento'], 0, 7) : null,
            'numero' => $item['numero'] ? mb_substr($item['numero'], 0, 80) : null,
            'titulo' => mb_substr($item['titulo'], 0, 250),
            'asunto' => $item['asunto'],
            'concepto' => $item['concepto'],
            'fecha_documento' => $item['fecha_documento'],
            'pdf_url' => $item['pdf_url'],
            'relevancia_pvl' => true,
            'relevancia_resumen' => $item['asunto'] ?: $item['concepto'],
            'relevancia_motivo' => 'Documento agregado manualmente desde la copia verificable del portal municipal.',
        ])->save();

        if ($document->index_status === 'INDEXADO') {
            $this->ensureStoredPdf($document);

            return $this->manualImportResult($document, false, true);
        }

        if (! $this->indexDocument($document)) {
            throw new \RuntimeException(
                $document->fresh()->index_error ?: 'No fue posible indexar la copia verificable.'
            );
        }

        $this->notify($document);

        return $this->manualImportResult($document->fresh(), true, false);
    }

    /** @return array<int, array{external_id:int, tipo_id:int, tipo_documento:string, numero:?string, titulo:string, asunto:?string, concepto:?string, fecha_documento:?string, pdf_url:string}> */
    private function fetchListing(int $tipo, ?string $query = null): array
    {
        $baseUrl = rtrim((string) config('normativa.base_url'), '/');
        $listingPath = config('normativa.listing_path');
        $parameters = ['tipo' => $tipo];
        if ($query !== null && trim($query) !== '') {
            $parameters['q'] = trim($query);
        }

        try {
            $response = $this->officialRequest($baseUrl.$listingPath, $parameters);
        } catch (ConnectionException $exception) {
            Log::warning('No se pudo conectar con el portal de normativa municipal.', [
                'tipo' => $tipo,
                'exception' => $exception::class,
            ]);

            if ($query !== null) {
                throw new \RuntimeException('No se pudo conectar con el portal municipal para buscar la resolución.', 0, $exception);
            }

            return [];
        }

        if (! $response->successful()) {
            if ($query !== null) {
                throw new \RuntimeException('El portal municipal rechazó la búsqueda de la resolución (HTTP '.$response->status().').');
            }

            return [];
        }

        return $this->parseListing($response->body(), $baseUrl);
    }

    /** @return array<int, array{external_id:int, tipo_id:int, tipo_documento:string, numero:?string, titulo:string, asunto:?string, concepto:?string, fecha_documento:?string, pdf_url:string}> */
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
            $metadata = $this->plainText($this->firstMatch('/<small>(.*?)<\/small>/is', $article));
            $typeText = trim(explode('·', $metadata)[0] ?? '');
            $tipoId = $this->typeIdFromLabel($typeText);
            $tipoDocumento = $this->typeLabel($tipoId, $typeText, $titulo);
            $fecha = null;
            if (preg_match('/<span>(\d{4}-\d{2}-\d{2}|\d{2}\/\d{2}\/\d{4})<\/span>/', $article, $fechaMatch)) {
                $fecha = $this->parseDate($fechaMatch[1]);
            }

            $pdfPath = $this->firstMatch('/norma_descargar\.php\?id=\d+/i', $article) ?: null;

            $items[] = [
                'external_id' => (int) $idMatch[1],
                'tipo_id' => $tipoId,
                'tipo_documento' => $tipoDocumento,
                'numero' => $numero,
                'titulo' => $titulo,
                'asunto' => $asunto ?: null,
                'concepto' => $concepto ?: null,
                'fecha_documento' => $fecha,
                'pdf_url' => $baseUrl.'/website/mde2026/'.$pdfPath,
            ];
        }

        usort($items, static function (array $left, array $right): int {
            $dateOrder = ($right['fecha_documento'] ?? '') <=> ($left['fecha_documento'] ?? '');

            return $dateOrder !== 0
                ? $dateOrder
                : ($right['external_id'] <=> $left['external_id']);
        });

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

    /** @return array{external_id:int,tipo_id:int,tipo_documento:string,numero:?string,titulo:string,asunto:?string,concepto:?string,fecha_documento:?string,pdf_url:string} */
    private function resolveDocumentReference(string $reference): array
    {
        $reference = trim($reference);
        if ($reference === '') {
            throw new \InvalidArgumentException('Ingrese el enlace de Copia verificable o el número de resolución.');
        }

        if (filter_var($reference, FILTER_VALIDATE_URL)) {
            return $this->fetchVerificationItem($this->externalIdFromVerifiedUrl($reference));
        }

        $searchNumber = $this->resolutionNumber($reference);
        if ($searchNumber === null) {
            throw new \InvalidArgumentException('Use un número como 0750-2026-MDE o pegue el enlace completo de Copia verificable.');
        }

        $items = $this->fetchListing(0, $searchNumber);
        $needle = $this->numberKey($searchNumber);
        $matches = array_values(array_filter(
            $items,
            fn (array $item): bool => $item['numero'] !== null
                && ($this->numberKey($item['numero']) === $needle
                    || str_contains($this->numberKey($item['numero']), $needle))
        ));

        if ($matches === []) {
            throw new \UnexpectedValueException('No se encontró una norma con ese número en el portal municipal. Revise el dato o use su enlace de Copia verificable.');
        }

        return $matches[0];
    }

    private function externalIdFromVerifiedUrl(string $url): int
    {
        $parts = parse_url($url);
        $official = parse_url((string) config('normativa.base_url'));
        $expectedPath = (string) config('normativa.download_path');

        if (! is_array($parts)
            || ! in_array(strtolower((string) ($parts['scheme'] ?? '')), ['http', 'https'], true)
            || strtolower((string) ($parts['host'] ?? '')) !== strtolower((string) ($official['host'] ?? ''))
            || rtrim((string) ($parts['path'] ?? ''), '/') !== rtrim($expectedPath, '/')) {
            throw new \InvalidArgumentException('El enlace debe ser una Copia verificable del portal oficial de la Municipalidad de La Esperanza.');
        }

        parse_str((string) ($parts['query'] ?? ''), $query);
        $externalId = filter_var($query['id'] ?? null, FILTER_VALIDATE_INT, ['options' => ['min_range' => 1]]);
        if ($externalId === false) {
            throw new \InvalidArgumentException('El enlace de Copia verificable no contiene un identificador válido.');
        }

        return (int) $externalId;
    }

    /** @return array{external_id:int,tipo_id:int,tipo_documento:string,numero:?string,titulo:string,asunto:?string,concepto:?string,fecha_documento:?string,pdf_url:string} */
    private function fetchVerificationItem(int $externalId): array
    {
        $url = $this->verifiedUrl($externalId);
        $response = $this->officialRequest($url);
        if (! $response->successful()) {
            throw new \RuntimeException('El portal municipal no pudo abrir la Copia verificable (HTTP '.$response->status().').');
        }

        $data = $this->verificationData($response->body());
        if ($data === null) {
            throw new \UnexpectedValueException('El enlace no corresponde a una Copia verificable disponible en el portal municipal.');
        }

        $titulo = trim((string) ($data['titulo'] ?? ''));
        if ($titulo === '') {
            throw new \UnexpectedValueException('La Copia verificable no contiene los datos oficiales de la norma.');
        }

        $tipoText = trim((string) ($data['tipo'] ?? ''));
        $tipoId = $this->typeIdFromLabel($tipoText ?: $titulo);

        return [
            'external_id' => $externalId,
            'tipo_id' => $tipoId,
            'tipo_documento' => $this->typeLabel($tipoId, $tipoText, $titulo),
            'numero' => $this->resolutionNumber($titulo),
            'titulo' => $titulo,
            'asunto' => trim((string) ($data['asunto'] ?? '')) ?: null,
            'concepto' => trim((string) ($data['concepto'] ?? '')) ?: null,
            'fecha_documento' => $this->parseDate((string) ($data['fecha'] ?? '')),
            'pdf_url' => $url,
        ];
    }

    private function verifiedUrl(int $externalId): string
    {
        return rtrim((string) config('normativa.base_url'), '/')
            .config('normativa.download_path').'?id='.$externalId;
    }

    private function resolutionNumber(string $value): ?string
    {
        if (preg_match('/(?:N[°º]\s*)?([0-9]{1,6}\s*[-\/]\s*[0-9]{4}(?:\s*[-\/]\s*[A-Z0-9]+)*)/iu', $value, $matches)) {
            return strtoupper(preg_replace('/\s+/u', '', $matches[1]) ?? $matches[1]);
        }

        return null;
    }

    private function numberKey(string $value): string
    {
        return preg_replace('/[^a-z0-9]/', '', $this->normalize($value)) ?? '';
    }

    private function parseDate(string $value): ?string
    {
        $value = trim($value);
        foreach (['Y-m-d', 'd/m/Y'] as $format) {
            try {
                $date = Carbon::createFromFormat($format, $value);
                if ($date !== false && $date->format($format) === $value) {
                    return $date->format('Y-m-d');
                }
            } catch (\Throwable) {
                // Prueba el siguiente formato admitido.
            }
        }

        return null;
    }

    private function typeIdFromLabel(string $value): int
    {
        $normalized = $this->normalize($value);

        if (str_contains($normalized, 'gerencial')) {
            return 12;
        }
        if (str_contains($normalized, 'jefatural')) {
            return 13;
        }
        if (str_contains($normalized, 'ordenanza')) {
            return 1;
        }
        if (str_contains($normalized, 'decreto')) {
            return 3;
        }
        if (str_contains($normalized, 'acuerdo')) {
            return 4;
        }
        if (str_contains($normalized, 'resolucion')) {
            return 2;
        }

        foreach ((array) config('normativa.tipos', []) as $typeId => $label) {
            if (str_contains($normalized, $this->normalize((string) $label))) {
                return (int) $typeId;
            }
        }

        return 2;
    }

    private function typeLabel(int $typeId, string $portalLabel, string $title): string
    {
        $types = (array) config('normativa.tipos', []);

        return (string) ($types[$typeId]
            ?? (trim($portalLabel) !== '' ? trim($portalLabel) : trim(strtok($title, 'N°º'))));
    }

    private function officialRequest(string $url, array $query = []): Response
    {
        $request = Http::timeout((int) config('normativa.request_timeout', 20))
            ->withHeaders(['User-Agent' => 'Mozilla/5.0'])
            ->withoutVerifying();

        // Laravel/Guzzle reemplaza la query que ya existe en la URL cuando se
        // le pasa un segundo argumento vacío. Las copias verificables incluyen
        // su `id` (y el PDF su `c`) en la propia URL, por lo que `get($url, [])`
        // terminaba solicitando el endpoint sin identificador y el portal
        // respondía HTTP 400.
        return $query === []
            ? $request->get($url)
            : $request->get($url, $query);
    }

    private function verificationData(string $html): ?array
    {
        if (! preg_match('/window\.MDE_VERIFIED_PDF\s*=\s*(\{.*?\})\s*;/s', $html, $matches)) {
            return null;
        }

        $data = json_decode(html_entity_decode($matches[1], ENT_QUOTES | ENT_HTML5, 'UTF-8'), true);

        return is_array($data) ? $data : null;
    }

    /** @return array{document_id:int,numero:?string,titulo:string,importado:bool,ya_importado:bool,index_status:string} */
    private function manualImportResult(NormativaDocument $document, bool $imported, bool $alreadyImported): array
    {
        return [
            'document_id' => $document->id,
            'numero' => $document->numero,
            'titulo' => $document->titulo,
            'importado' => $imported,
            'ya_importado' => $alreadyImported,
            'index_status' => $document->index_status,
        ];
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
        return $this->matchingKeyword($document, (array) config('normativa.pvl_keywords', [])) !== null;
    }

    private function directKeywordMatch(NormativaDocument $document): ?string
    {
        return $this->matchingKeyword($document, (array) config('normativa.direct_import_keywords', []));
    }

    /** @param array<int, string> $keywords */
    private function matchingKeyword(NormativaDocument $document, array $keywords): ?string
    {
        $haystack = $this->normalize($document->titulo.' '.$document->asunto.' '.$document->concepto);

        foreach ($keywords as $keyword) {
            if (str_contains($haystack, $this->normalize($keyword))) {
                return $keyword;
            }
        }

        return null;
    }

    private function normalize(string $text): string
    {
        $text = mb_strtolower($text, 'UTF-8');
        $replacements = ['á' => 'a', 'é' => 'e', 'í' => 'i', 'ó' => 'o', 'ú' => 'u', 'ñ' => 'n'];

        $text = strtr($text, $replacements);

        return trim(preg_replace('/[^a-z0-9]+/u', ' ', $text) ?? $text);
    }

    private function markDirectlyRelevant(NormativaDocument $document, string $keyword): void
    {
        $document->forceFill([
            'relevancia_pvl' => true,
            'relevancia_resumen' => $document->relevancia_resumen
                ?: $document->asunto
                ?: $document->concepto
                ?: $document->titulo,
            'relevancia_motivo' => 'Coincidencia directa con el término PROVALE configurado: '.$keyword.'.',
        ])->save();
    }

    private function importDirectMatch(NormativaDocument $document): bool
    {
        $keyword = $this->directKeywordMatch($document);
        if ($keyword === null) {
            return false;
        }

        $this->markDirectlyRelevant($document, $keyword);

        if (! $this->indexDocument($document)) {
            return false;
        }

        $this->notify($document);

        return true;
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

        if (! $this->indexDocument($document)) {
            return false;
        }

        $this->notify($document);

        return true;
    }

    private function indexDocument(NormativaDocument $document): bool
    {
        try {
            $this->rag->indexDocument($document, $this->ensureStoredPdf($document));

            return true;
        } catch (\Throwable $exception) {
            $document->forceFill([
                'index_status' => 'ERROR',
                'index_error' => mb_substr($exception->getMessage(), 0, 1000),
            ])->save();

            Log::warning('No se pudo indexar el PDF de una norma municipal relevante.', [
                'document_id' => $document->id,
                'exception' => $exception::class,
                'message' => $exception->getMessage(),
            ]);

            return false;
        }
    }

    private function ensureStoredPdfSafely(NormativaDocument $document): bool
    {
        try {
            $this->ensureStoredPdf($document);
            $document->forceFill(['index_error' => null])->save();

            return true;
        } catch (\Throwable $exception) {
            $document->forceFill([
                'index_error' => mb_substr('PDF local no disponible: '.$exception->getMessage(), 0, 1000),
            ])->save();

            Log::warning('No se pudo guardar localmente una norma municipal ya indexada.', [
                'document_id' => $document->id,
                'exception' => $exception::class,
                'message' => $exception->getMessage(),
            ]);

            return false;
        }
    }

    private function ensureStoredPdf(NormativaDocument $document): string
    {
        $stored = DocumentBinaryStorage::get($document->file_path, null);
        if (is_string($stored) && $this->isPdf($stored)) {
            $binary = $this->validatePdfSize($stored);
        } else {
            $binary = $this->downloadVerifiedPdf($document);
        }

        $hash = hash('sha256', $binary);
        $path = DocumentBinaryStorage::put('rag/normativa', $hash, $binary);
        $previousPath = $document->file_path;

        $document->forceFill([
            'file_name' => $this->pdfFileName($document),
            'mime_type' => 'application/pdf',
            'file_size' => strlen($binary),
            'file_hash' => $hash,
            'file_path' => $path,
        ])->save();

        if ($previousPath && $previousPath !== $path) {
            DocumentBinaryStorage::delete($previousPath);
        }

        return $binary;
    }

    private function pdfFileName(NormativaDocument $document): string
    {
        $label = trim($document->tipo_documento.' '.($document->numero ?: $document->external_id));
        $slug = Str::upper(Str::slug(Str::ascii($label)));

        return ($slug !== '' ? $slug : 'NORMATIVA-'.$document->external_id).'.pdf';
    }

    private function downloadVerifiedPdf(NormativaDocument $document): string
    {
        $response = $this->officialRequest($document->pdf_url);
        if (! $response->successful()) {
            throw new \RuntimeException('No se pudo abrir la Copia verificable (HTTP '.$response->status().').');
        }

        $binary = $response->body();
        if ($this->isPdf($binary)) {
            return $this->validatePdfSize($binary);
        }

        $data = $this->verificationData($binary);
        if ($data === null) {
            throw new \RuntimeException('La Copia verificable no contiene un enlace válido al PDF oficial.');
        }

        $references = array_values(array_unique(array_filter([
            trim((string) ($data['cache_url'] ?? '')),
            trim((string) ($data['archivo_url'] ?? '')),
        ])));

        foreach ($references as $pdfReference) {
            $pdfResponse = $this->officialRequest($this->officialPdfUrl($pdfReference));
            if ($pdfResponse->successful() && $this->isPdf($pdfResponse->body())) {
                return $this->validatePdfSize($pdfResponse->body());
            }
        }

        throw new \RuntimeException('El portal no entregó un PDF válido desde la Copia verificable.');
    }

    private function officialPdfUrl(string $reference): string
    {
        $reference = html_entity_decode($reference, ENT_QUOTES | ENT_HTML5, 'UTF-8');
        $baseUrl = rtrim((string) config('normativa.base_url'), '/');

        if (filter_var($reference, FILTER_VALIDATE_URL)) {
            $parts = parse_url($reference);
            $official = parse_url($baseUrl);
            $path = (string) ($parts['path'] ?? '');
            if (strtolower((string) ($parts['host'] ?? '')) !== strtolower((string) ($official['host'] ?? ''))
                || ! preg_match('#^/website/mde2026/(?:norma_verificable_cache|norma_archivo)\.php$#', $path)) {
                throw new \RuntimeException('La Copia verificable intentó redirigir a un archivo no autorizado.');
            }

            return $reference;
        }

        if (! preg_match('#^(?:norma_verificable_cache|norma_archivo)\.php\?#', $reference)) {
            throw new \RuntimeException('La Copia verificable contiene una ruta de PDF no autorizada.');
        }

        return $baseUrl.'/website/mde2026/'.$reference;
    }

    private function isPdf(string $binary): bool
    {
        return str_starts_with(ltrim($binary), '%PDF-');
    }

    private function validatePdfSize(string $binary): string
    {
        $maxBytes = max(1, (int) config('normativa.max_document_kb', 25600)) * 1024;
        if (strlen($binary) > $maxBytes) {
            throw new \RuntimeException('El PDF supera el tamaño máximo permitido para la base de conocimiento.');
        }

        return $binary;
    }

    private function notify(NormativaDocument $document): void
    {
        if ($document->notified_at !== null) {
            return;
        }

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
