<?php

namespace App\Http\Controllers\Api;

use App\Exceptions\AiProviderException;
use App\Http\Controllers\Controller;
use App\Models\KnowledgeBaseDocument;
use App\Models\NormativaDocument;
use App\Services\KnowledgeBase\KnowledgeBaseRagService;
use App\Services\Normativa\NormativaScraperService;
use App\Services\Rag\DocumentBinaryStorage;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;

class KnowledgeBaseController extends Controller
{
    public function index()
    {
        return response()->json([
            'documents' => KnowledgeBaseDocument::query()
                ->latest('id')
                ->limit(50)
                ->get()
                ->map(fn (KnowledgeBaseDocument $document) => $this->documentResource($document)),
            'municipal_documents' => NormativaDocument::query()
                ->where('relevancia_pvl', true)
                ->latest('fecha_documento')
                ->latest('id')
                ->limit(50)
                ->get()
                ->map(fn (NormativaDocument $document) => $this->municipalDocumentResource($document)),
            'municipal_source' => [
                'url' => rtrim((string) config('normativa.base_url'), '/').config('normativa.listing_path'),
                'keywords' => array_values((array) config('normativa.pvl_keywords', [])),
                'indexed_count' => NormativaDocument::query()
                    ->where('relevancia_pvl', true)
                    ->where('index_status', 'INDEXADO')
                    ->count(),
                'last_import_at' => NormativaDocument::query()
                    ->where('relevancia_pvl', true)
                    ->max('updated_at'),
            ],
        ]);
    }

    public function importNormativa(NormativaScraperService $scraper)
    {
        $lock = Cache::lock('knowledge-base:normativa-import', 900);
        if (! $lock->get()) {
            return response()->json([
                'message' => 'Ya hay una extracción de normativa en curso. Espere a que termine y vuelva a cargar la lista.',
            ], 409);
        }

        try {
            @set_time_limit(0);
            $result = $scraper->importRelevantDocuments();

            return response()->json([
                'message' => $result['importados'] > 0
                    ? 'La normativa relacionada con PROVALE fue extraída e indexada.'
                    : 'La revisión terminó sin documentos nuevos para indexar.',
                'result' => $result,
            ]);
        } catch (\Throwable $exception) {
            report($exception);

            return response()->json([
                'message' => 'No se pudo completar la extracción desde el portal municipal. '.$exception->getMessage(),
            ], 502);
        } finally {
            $lock->release();
        }
    }

    public function importNormativaDocument(Request $request, NormativaScraperService $scraper)
    {
        $validated = $request->validate([
            'reference' => ['required', 'string', 'max:500'],
        ]);
        $reference = trim($validated['reference']);
        $lock = Cache::lock('knowledge-base:normativa-document:'.sha1($reference), 180);

        if (! $lock->get()) {
            return response()->json([
                'message' => 'Ese documento ya se está importando. Espere unos segundos y vuelva a intentarlo.',
            ], 409);
        }

        try {
            @set_time_limit(0);
            $result = $scraper->importDocument($reference);
            $document = NormativaDocument::findOrFail($result['document_id']);

            return response()->json([
                'message' => $result['ya_importado']
                    ? 'La norma ya estaba indexada en la base de conocimiento.'
                    : 'La copia verificable fue descargada e indexada correctamente.',
                'result' => $result,
                'document' => $this->municipalDocumentResource($document),
            ], $result['ya_importado'] ? 200 : 201);
        } catch (\InvalidArgumentException $exception) {
            return response()->json(['message' => $exception->getMessage()], 422);
        } catch (\UnexpectedValueException $exception) {
            return response()->json(['message' => $exception->getMessage()], 404);
        } catch (\Throwable $exception) {
            report($exception);

            return response()->json([
                'message' => 'No se pudo importar la copia verificable. '.$exception->getMessage(),
            ], 502);
        } finally {
            $lock->release();
        }
    }

    public function store(Request $request, KnowledgeBaseRagService $rag)
    {
        $validated = $request->validate([
            'title' => ['nullable', 'string', 'max:255'],
            'reindex' => ['sometimes', 'boolean'],
            'file' => [
                'required',
                'file',
                'max:'.(int) config('knowledge_base.max_document_kb', 20480),
                'mimes:pdf,jpg,jpeg,png,docx,xls,xlsx',
            ],
        ]);

        $file = $request->file('file');
        $binary = file_get_contents($file->getRealPath());
        $hash = hash('sha256', $binary);

        $duplicate = KnowledgeBaseDocument::query()->where('file_hash', $hash)->first();
        if ($duplicate) {
            if ($duplicate->index_status === 'ERROR' || ($validated['reindex'] ?? false)) {
                $duplicate->forceFill([
                    'title' => trim((string) ($validated['title'] ?? '')) !== ''
                        ? trim($validated['title'])
                        : $duplicate->title,
                    'file_name' => $file->getClientOriginalName(),
                    'mime_type' => $file->getMimeType() ?: $file->getClientMimeType() ?: $duplicate->mime_type,
                    'file_size' => $file->getSize(),
                    'file_path' => DocumentBinaryStorage::put('rag/kb', $hash, $binary),
                    'file_data' => null,
                ])->save();

                try {
                    $rag->indexDocument($duplicate, $binary);
                } catch (\Throwable $exception) {
                    $detail = $exception instanceof AiProviderException || $exception instanceof \RuntimeException
                        ? $exception->getMessage()
                        : 'Revise que el archivo contenga texto legible y vuelva a intentarlo.';
                    $status = $exception instanceof AiProviderException
                        ? $exception->httpStatus()
                        : ($exception instanceof \RuntimeException ? 422 : 500);

                    return response()->json([
                        'message' => 'El archivo se guardó, pero el reintento de indexación falló. '.$detail,
                        'document' => $this->documentResource($duplicate->fresh()),
                    ], $status);
                }

                return response()->json([
                    'message' => 'Documento reindexado correctamente con OCR.',
                    'document' => $this->documentResource($duplicate->fresh()),
                ]);
            }

            return response()->json([
                'message' => 'Este archivo ya fue indexado previamente.',
                'document' => $this->documentResource($duplicate),
            ], 422);
        }

        $document = KnowledgeBaseDocument::create([
            'title' => trim((string) ($validated['title'] ?? '')) !== ''
                ? trim($validated['title'])
                : $file->getClientOriginalName(),
            'file_name' => $file->getClientOriginalName(),
            'mime_type' => $file->getMimeType() ?: $file->getClientMimeType() ?: 'application/octet-stream',
            'file_size' => $file->getSize(),
            'file_hash' => $hash,
            'file_path' => DocumentBinaryStorage::put('rag/kb', $hash, $binary),
            'file_data' => null,
            'created_by' => $request->user()?->id,
        ]);

        try {
            $rag->indexDocument($document, $binary);
        } catch (\Throwable $exception) {
            $detail = $exception instanceof AiProviderException || $exception instanceof \RuntimeException
                ? $exception->getMessage()
                : 'Revise que el archivo contenga texto legible y vuelva a intentarlo.';
            $status = $exception instanceof AiProviderException
                ? $exception->httpStatus()
                : ($exception instanceof \RuntimeException ? 422 : 500);

            return response()->json([
                'message' => 'Archivo guardado, pero no pudo indexarse. '.$detail,
                'document' => $this->documentResource($document->fresh()),
            ], $status);
        }

        return response()->json([
            'message' => 'Documento indexado correctamente.',
            'document' => $this->documentResource($document->fresh()),
        ], 201);
    }

    public function download(KnowledgeBaseDocument $knowledgeBaseDocument)
    {
        $binary = DocumentBinaryStorage::get($knowledgeBaseDocument->file_path, $knowledgeBaseDocument->file_data);
        if ($binary === null) {
            abort(404, 'Archivo no disponible.');
        }

        return response($binary, 200, [
            'Content-Type' => $knowledgeBaseDocument->mime_type,
            'Content-Disposition' => 'inline; filename="'.addslashes($knowledgeBaseDocument->file_name).'"',
            'Cache-Control' => 'private, no-store',
        ]);
    }

    public function previewNormativaDocument(NormativaDocument $normativaDocument)
    {
        return $this->streamNormativaDocument($normativaDocument, 'inline');
    }

    public function downloadNormativaDocument(NormativaDocument $normativaDocument)
    {
        return $this->streamNormativaDocument($normativaDocument, 'attachment');
    }

    public function destroy(KnowledgeBaseDocument $knowledgeBaseDocument)
    {
        DocumentBinaryStorage::delete($knowledgeBaseDocument->file_path);
        $knowledgeBaseDocument->delete();

        return response()->json(['message' => 'Documento y fragmentos eliminados.']);
    }

    public function ask(Request $request, KnowledgeBaseRagService $rag)
    {
        $validated = $request->validate([
            'question' => ['required', 'string', 'max:1000'],
        ]);

        try {
            $result = $rag->answer($validated['question']);
        } catch (AiProviderException $exception) {
            return response()->json(['message' => $exception->getMessage()], $exception->httpStatus());
        } catch (\Throwable $exception) {
            return response()->json([
                'message' => $exception->getMessage() ?: 'No se pudo generar una respuesta.',
            ], 500);
        }

        return response()->json($result);
    }

    private function documentResource(KnowledgeBaseDocument $document): array
    {
        return [
            'id' => $document->id,
            'title' => $document->title,
            'file_name' => $document->file_name,
            'mime_type' => $document->mime_type,
            'file_size' => $document->file_size,
            'index_status' => $document->index_status,
            'index_error' => $document->index_error,
            'created_at' => optional($document->created_at)->format('d/m/Y H:i'),
            'download_url' => route('api.base-conocimiento.documents.download', $document),
            'preview_url' => route('api.base-conocimiento.documents.download', $document),
            'sort_date' => optional($document->created_at)->toISOString(),
            'origin' => 'upload',
        ];
    }

    private function municipalDocumentResource(NormativaDocument $document): array
    {
        return [
            'id' => 'normativa-'.$document->id,
            'title' => $document->titulo,
            'file_name' => trim($document->tipo_documento.' '.($document->numero ? 'N° '.$document->numero : '')),
            'mime_type' => $document->mime_type ?: 'application/pdf',
            'file_size' => $document->file_size,
            'index_status' => $document->index_status,
            'index_error' => $document->index_error,
            'created_at' => optional($document->fecha_documento)->format('d/m/Y'),
            'sort_date' => optional($document->fecha_documento)->format('Y-m-d'),
            'preview_url' => $document->file_path
                ? route('api.base-conocimiento.normativa.preview', $document)
                : null,
            'download_url' => $document->file_path
                ? route('api.base-conocimiento.normativa.download', $document)
                : null,
            'official_url' => $document->pdf_url,
            'stored' => $document->file_path !== null,
            'origin' => 'municipal',
            'summary' => $document->relevancia_resumen,
        ];
    }

    private function streamNormativaDocument(NormativaDocument $document, string $disposition)
    {
        $binary = DocumentBinaryStorage::get($document->file_path, null);
        if ($binary === null) {
            abort(404, 'El PDF municipal aún no está guardado. Ejecute nuevamente la extracción de normativa.');
        }

        $fileName = $document->file_name ?: 'NORMATIVA-'.$document->external_id.'.pdf';

        return response($binary, 200, [
            'Content-Type' => $document->mime_type ?: 'application/pdf',
            'Content-Disposition' => $disposition.'; filename="'.addslashes($fileName).'"',
            'Cache-Control' => 'private, no-store',
            'X-Content-Type-Options' => 'nosniff',
        ]);
    }
}
