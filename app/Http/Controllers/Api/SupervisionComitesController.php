<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Association;
use App\Models\DocumentAttachment;
use App\Models\SupervisionVisit;
use App\Services\DocumentAttachmentService;
use App\Services\SupervisionService;
use App\Services\UploadSettingsService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class SupervisionComitesController extends Controller
{
    public function __construct(private SupervisionService $supervision)
    {
    }

    /**
     * Matriz mensual de supervisión: un comité por fila, un día por columna.
     */
    public function index(Request $request): JsonResponse
    {
        $data = $request->validate([
            'year' => ['required', 'integer', 'between:2000,2100'],
            'month' => ['required', 'integer', 'between:1,12'],
        ]);

        $matrix = $this->supervision->monthlyMatrix(
            (int) $data['year'],
            (int) $data['month'],
            fn (DocumentAttachment $document) => $this->documentData($document)
        );

        return response()->json(['rows' => $matrix['rows'], 'off_days' => (object) $matrix['off_days']]);
    }

    /**
     * Marca un día: SI / NO. Sin resultado borra la marca.
     */
    public function setVisit(Request $request, Association $association): JsonResponse
    {
        $data = $request->validate([
            'date' => ['required', 'date_format:Y-m-d'],
            'result' => ['nullable', 'in:SI,NO'],
        ]);

        $visitId = $this->supervision->setVisit($association, $data['date'], $data['result'] ?? null, $request->user()?->id);

        return response()->json(['id' => $visitId, 'date' => $data['date'], 'result' => $data['result'] ?? null]);
    }

    /**
     * Marca o desmarca un día sin supervisión (feriado, falta de combustible, etc.).
     */
    public function setOffDay(Request $request): JsonResponse
    {
        $data = $request->validate([
            'date' => ['required', 'date_format:Y-m-d'],
            'off' => ['required', 'boolean'],
            'reason' => ['required_if:off,true', 'nullable', 'string', 'max:255'],
        ]);

        $this->supervision->setOffDay($data['date'], (bool) $data['off'], $data['reason'] ?? null, $request->user()?->id);

        return response()->json(['date' => $data['date'], 'off' => $data['off']]);
    }

    public function uploadAttachment(Request $request, SupervisionVisit $supervisionVisit, UploadSettingsService $uploadSettings): JsonResponse
    {
        $data = $request->validate([
            'document_type' => ['required', Rule::in([DocumentAttachment::TYPE_SUPERVISION_DOCUMENT, DocumentAttachment::TYPE_SUPERVISION_PHOTO])],
            'file' => [
                'required', 'file',
                Rule::when($request->input('document_type') === DocumentAttachment::TYPE_SUPERVISION_DOCUMENT, ['mimes:pdf'], ['mimes:jpg,jpeg,png,webp']),
                'max:'.$uploadSettings->maxKilobytes(),
            ],
        ]);

        $attachment = $this->supervision->attach($supervisionVisit->id, $data['document_type'], $request->file('file'));

        return response()->json($this->documentData($attachment), 201);
    }

    public function downloadAttachment(DocumentAttachment $documentAttachment, DocumentAttachmentService $attachments)
    {
        abort_unless($documentAttachment->attachable_type === SupervisionVisit::class, 404);

        return response($attachments->getDecryptedContent($documentAttachment), 200, [
            'Content-Type' => $documentAttachment->mime_type,
            'Content-Disposition' => 'inline; filename="'.$documentAttachment->file_name.'"',
        ]);
    }

    public function destroyAttachment(DocumentAttachment $documentAttachment, DocumentAttachmentService $attachments): JsonResponse
    {
        abort_unless($documentAttachment->attachable_type === SupervisionVisit::class, 404);
        $attachments->delete($documentAttachment);

        return response()->json(null, 204);
    }

    private function documentData(DocumentAttachment $document): array
    {
        return [
            'id' => $document->id,
            'document_type' => $document->document_type,
            'file_name' => $document->file_name,
            'mime_type' => $document->mime_type,
            'file_size' => $document->file_size,
            'url' => route('api.supervision-comites.attachments.show', $document),
        ];
    }
}
