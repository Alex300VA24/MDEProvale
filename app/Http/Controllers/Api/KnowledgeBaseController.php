<?php

namespace App\Http\Controllers\Api;

use App\Exceptions\AiProviderException;
use App\Http\Controllers\Controller;
use App\Models\KnowledgeBaseDocument;
use App\Services\KnowledgeBase\KnowledgeBaseRagService;
use App\Services\Rag\DocumentBinaryStorage;
use Illuminate\Http\Request;

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
        ]);
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
        ];
    }
}
