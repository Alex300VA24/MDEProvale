<?php

namespace App\Services\KnowledgeBase;

use App\Exceptions\AiProviderException;
use App\Exceptions\KnowledgeBaseException;
use App\Models\KnowledgeBaseDocument;
use App\Models\NormativaDocument;
use App\Repositories\Contracts\KnowledgeBaseRepositoryInterface;
use App\Repositories\Contracts\NormativaRepositoryInterface;
use App\Services\Normativa\NormativaScraperService;
use App\Services\Rag\DocumentBinaryStorage;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Cache;

/**
 * Casos de uso de la base de conocimiento: documentos cargados y normativa municipal.
 */
class KnowledgeBaseService
{
    public function __construct(
        private KnowledgeBaseRepositoryInterface $documents,
        private NormativaRepositoryInterface $normativa,
        private KnowledgeBaseRagService $rag,
        private NormativaScraperService $scraper,
    ) {
    }

    /**
     * @return array{documents:Collection,municipal_documents:Collection,municipal_source:array}
     */
    public function overview(): array
    {
        return [
            'documents' => $this->documents->recentDocuments(50),
            'municipal_documents' => $this->normativa->relevantDocuments(50),
            'municipal_source' => [
                'url' => rtrim((string) config('normativa.base_url'), '/').config('normativa.listing_path'),
                'keywords' => array_values((array) config('normativa.pvl_keywords', [])),
                'indexed_count' => $this->normativa->relevantIndexedCount(),
                'last_import_at' => $this->normativa->lastRelevantUpdate(),
            ],
        ];
    }

    /**
     * Revisa el portal municipal e indexa las normas relacionadas con PROVALE.
     *
     * @throws KnowledgeBaseException si ya hay una extracción en curso o el portal falla
     */
    public function importNormativa(): array
    {
        $lock = Cache::lock('knowledge-base:normativa-import', 900);
        if (! $lock->get()) {
            throw new KnowledgeBaseException(
                'Ya hay una extracción de normativa en curso. Espere a que termine y vuelva a cargar la lista.',
                409,
            );
        }

        try {
            @set_time_limit(0);

            return $this->scraper->importRelevantDocuments();
        } catch (\Throwable $exception) {
            report($exception);

            throw new KnowledgeBaseException(
                'No se pudo completar la extracción desde el portal municipal. '.$exception->getMessage(),
                502,
            );
        } finally {
            $lock->release();
        }
    }

    /**
     * Importa una norma concreta por su enlace de copia verificable o número.
     *
     * @return array{result:array,document:NormativaDocument}
     *
     * @throws KnowledgeBaseException
     */
    public function importNormativaDocument(string $reference): array
    {
        $reference = trim($reference);
        $lock = Cache::lock('knowledge-base:normativa-document:'.sha1($reference), 180);

        if (! $lock->get()) {
            throw new KnowledgeBaseException(
                'Ese documento ya se está importando. Espere unos segundos y vuelva a intentarlo.',
                409,
            );
        }

        try {
            @set_time_limit(0);
            $result = $this->scraper->importDocument($reference);

            return [
                'result' => $result,
                'document' => $this->normativa->findOrFail($result['document_id']),
            ];
        } catch (\InvalidArgumentException $exception) {
            throw new KnowledgeBaseException($exception->getMessage(), 422);
        } catch (\UnexpectedValueException $exception) {
            throw new KnowledgeBaseException($exception->getMessage(), 404);
        } catch (\Throwable $exception) {
            report($exception);

            throw new KnowledgeBaseException(
                'No se pudo importar la copia verificable. '.$exception->getMessage(),
                502,
            );
        } finally {
            $lock->release();
        }
    }

    /**
     * Guarda e indexa un documento; reindexa uno ya cargado si falló antes o se pide.
     *
     * @param  array{title?:?string,reindex?:bool}  $attributes
     * @param  array{name:string,mime:?string,size:int}  $file
     * @return array{document:KnowledgeBaseDocument,reindexed:bool}
     *
     * @throws KnowledgeBaseException si el archivo está duplicado o no pudo indexarse
     */
    public function storeDocument(array $attributes, array $file, string $binary, ?int $userId): array
    {
        $hash = hash('sha256', $binary);
        $title = trim((string) ($attributes['title'] ?? ''));

        $duplicate = $this->documents->findByHash($hash);
        if ($duplicate) {
            if ($duplicate->index_status !== 'ERROR' && ! ($attributes['reindex'] ?? false)) {
                throw new KnowledgeBaseException('Este archivo ya fue indexado previamente.', 422, $duplicate);
            }

            $this->documents->updateDocument($duplicate, [
                'title' => $title !== '' ? $title : $duplicate->title,
                'file_name' => $file['name'],
                'mime_type' => $file['mime'] ?: $duplicate->mime_type,
                'file_size' => $file['size'],
                'file_path' => DocumentBinaryStorage::put('rag/kb', $hash, $binary),
                'file_data' => null,
            ]);

            try {
                $this->rag->indexDocument($duplicate, $binary);
            } catch (\Throwable $exception) {
                [$detail, $status] = $this->indexFailure($exception);

                throw new KnowledgeBaseException(
                    'El archivo se guardó, pero el reintento de indexación falló. '.$detail,
                    $status,
                    $this->documents->freshDocument($duplicate),
                );
            }

            return ['document' => $this->documents->freshDocument($duplicate), 'reindexed' => true];
        }

        $document = $this->documents->createDocument([
            'title' => $title !== '' ? $title : $file['name'],
            'file_name' => $file['name'],
            'mime_type' => $file['mime'] ?: 'application/octet-stream',
            'file_size' => $file['size'],
            'file_hash' => $hash,
            'file_path' => DocumentBinaryStorage::put('rag/kb', $hash, $binary),
            'file_data' => null,
            'created_by' => $userId,
        ]);

        try {
            $this->rag->indexDocument($document, $binary);
        } catch (\Throwable $exception) {
            [$detail, $status] = $this->indexFailure($exception);

            throw new KnowledgeBaseException(
                'Archivo guardado, pero no pudo indexarse. '.$detail,
                $status,
                $this->documents->freshDocument($document),
            );
        }

        return ['document' => $this->documents->freshDocument($document), 'reindexed' => false];
    }

    public function documentBinary(KnowledgeBaseDocument $document): ?string
    {
        return DocumentBinaryStorage::get($document->file_path, $document->file_data);
    }

    public function normativaBinary(NormativaDocument $document): ?string
    {
        return DocumentBinaryStorage::get($document->file_path, null);
    }

    public function deleteDocument(KnowledgeBaseDocument $document): void
    {
        DocumentBinaryStorage::delete($document->file_path);
        $this->documents->deleteDocument($document);
    }

    /** @return array{0:string,1:int} detalle para el usuario y estado HTTP */
    private function indexFailure(\Throwable $exception): array
    {
        $detail = $exception instanceof AiProviderException || $exception instanceof \RuntimeException
            ? $exception->getMessage()
            : 'Revise que el archivo contenga texto legible y vuelva a intentarlo.';
        $status = $exception instanceof AiProviderException
            ? $exception->httpStatus()
            : ($exception instanceof \RuntimeException ? 422 : 500);

        return [$detail, $status];
    }
}
