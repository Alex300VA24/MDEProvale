<?php

namespace App\Http\Controllers\Api;

use App\Exceptions\AiProviderException;
use App\Exceptions\KnowledgeBaseException;
use App\Http\Controllers\Controller;
use App\Http\Presenters\KnowledgeBasePresenter;
use App\Models\KnowledgeBaseDocument;
use App\Models\NormativaDocument;
use App\Services\KnowledgeBase\KnowledgeBaseRagService;
use App\Services\KnowledgeBase\KnowledgeBaseService;
use App\Services\UploadSettingsService;
use Illuminate\Http\Request;

class KnowledgeBaseController extends Controller
{
    public function __construct(
        private KnowledgeBaseService $knowledgeBase,
        private KnowledgeBasePresenter $presenter,
    ) {
    }

    public function index()
    {
        $overview = $this->knowledgeBase->overview();

        return response()->json([
            'documents' => $overview['documents']
                ->map(fn (KnowledgeBaseDocument $document) => $this->presenter->document($document)),
            'municipal_documents' => $overview['municipal_documents']
                ->map(fn (NormativaDocument $document) => $this->presenter->municipalDocument($document)),
            'municipal_source' => $overview['municipal_source'],
        ]);
    }

    public function importNormativa()
    {
        try {
            $result = $this->knowledgeBase->importNormativa();
        } catch (KnowledgeBaseException $exception) {
            return $this->fail($exception);
        }

        return response()->json([
            'message' => $result['importados'] > 0
                ? 'La normativa relacionada con PROVALE fue extraída e indexada.'
                : 'La revisión terminó sin documentos nuevos para indexar.',
            'result' => $result,
        ]);
    }

    public function importNormativaDocument(Request $request)
    {
        $validated = $request->validate([
            'reference' => ['required', 'string', 'max:500'],
        ]);

        try {
            $imported = $this->knowledgeBase->importNormativaDocument($validated['reference']);
        } catch (KnowledgeBaseException $exception) {
            return $this->fail($exception);
        }

        $result = $imported['result'];

        return response()->json([
            'message' => $result['ya_importado']
                ? 'La norma ya estaba indexada en la base de conocimiento.'
                : 'La copia verificable fue descargada e indexada correctamente.',
            'result' => $result,
            'document' => $this->presenter->municipalDocument($imported['document']),
        ], $result['ya_importado'] ? 200 : 201);
    }

    public function store(Request $request, UploadSettingsService $uploadSettings)
    {
        $validated = $request->validate([
            'title' => ['nullable', 'string', 'max:255'],
            'reindex' => ['sometimes', 'boolean'],
            'file' => [
                'required',
                'file',
                'max:'.$uploadSettings->maxKilobytes(),
                'mimes:pdf,jpg,jpeg,png,docx,xls,xlsx',
            ],
        ]);

        $file = $request->file('file');

        try {
            $stored = $this->knowledgeBase->storeDocument(
                $validated,
                [
                    'name' => $file->getClientOriginalName(),
                    'mime' => $file->getMimeType() ?: $file->getClientMimeType() ?: null,
                    'size' => $file->getSize(),
                ],
                file_get_contents($file->getRealPath()),
                $request->user()?->id,
            );
        } catch (KnowledgeBaseException $exception) {
            return $this->fail($exception);
        }

        return response()->json([
            'message' => $stored['reindexed']
                ? 'Documento reindexado correctamente con OCR.'
                : 'Documento indexado correctamente.',
            'document' => $this->presenter->document($stored['document']),
        ], $stored['reindexed'] ? 200 : 201);
    }

    public function download(KnowledgeBaseDocument $knowledgeBaseDocument)
    {
        $binary = $this->knowledgeBase->documentBinary($knowledgeBaseDocument);
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
        $this->knowledgeBase->deleteDocument($knowledgeBaseDocument);

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

    private function fail(KnowledgeBaseException $exception)
    {
        $payload = ['message' => $exception->getMessage()];
        if ($exception->document()) {
            $payload['document'] = $this->presenter->document($exception->document());
        }

        return response()->json($payload, $exception->status());
    }

    private function streamNormativaDocument(NormativaDocument $document, string $disposition)
    {
        $binary = $this->knowledgeBase->normativaBinary($document);
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
