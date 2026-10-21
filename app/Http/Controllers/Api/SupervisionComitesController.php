<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Association;
use App\Models\SupervisionOffDay;
use App\Models\SupervisionVisit;
use App\Models\DocumentAttachment;
use App\Services\DocumentAttachmentService;
use App\Services\UploadSettingsService;
use Carbon\Carbon;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;

class SupervisionComitesController extends Controller
{
    /**
     * Matriz mensual de supervisión: un comité por fila, un día por columna.
     */
    public function index(Request $request): JsonResponse
    {
        $data = $request->validate([
            'year' => ['required', 'integer', 'between:2000,2100'],
            'month' => ['required', 'integer', 'between:1,12'],
        ]);

        $start = Carbon::create($data['year'], $data['month'], 1)->startOfDay();
        $end = $start->copy()->endOfMonth();

        $associations = Association::with(['placeSector.place', 'placeSector.sector'])
            ->whereHas('resolution')
            ->orderBy('code')
            ->get();

        $visits = SupervisionVisit::with('documents')
            ->whereBetween('visit_date', [$start->toDateString(), $end->toDateString()])
            ->get()
            ->groupBy('association_id');

        $rows = $associations->map(function (Association $association) use ($visits) {
            $presidenta = $association->getPresidenta();
            $person = $presidenta?->people;

            return [
                'id' => $association->id,
                'code' => $association->code,
                'name' => $association->name,
                'president' => $person
                    ? trim("{$person->names} {$person->father_lastname} {$person->mother_lastname}")
                    : '',
                'address' => $association->address,
                'sector' => $association->placeSector
                    ? trim(($association->placeSector->place?->title ?? '') . ' ' . ($association->placeSector->sector?->title ?? ''))
                    : '',
                'beneficiaries' => $presidenta ? $this->countBeneficiaries($association) : 0,
                'visits' => ($visits[$association->id] ?? collect())
                    ->mapWithKeys(fn ($v) => [$v->visit_date->format('Y-m-d') => $v->result])
                    ->all(),
                'visit_details' => ($visits[$association->id] ?? collect())
                    ->mapWithKeys(fn ($visit) => [$visit->visit_date->format('Y-m-d') => [
                        'id' => $visit->id,
                        'documents' => $visit->documents->map(fn ($document) => $this->documentData($document))->values(),
                    ]])->all(),
            ];
        })->values();

        $offDays = SupervisionOffDay::whereBetween('off_date', [$start->toDateString(), $end->toDateString()])
            ->get()
            ->mapWithKeys(fn ($d) => [$d->off_date->format('Y-m-d') => $d->reason ?? ''])
            ->all();

        return response()->json(['rows' => $rows, 'off_days' => (object) $offDays]);
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

        if (empty($data['result'])) {
            $visit = SupervisionVisit::where('association_id', $association->id)
                ->whereDate('visit_date', $data['date'])
                ->first();
            if ($visit?->documents()->exists()) {
                throw ValidationException::withMessages(['result' => 'Retire los adjuntos antes de limpiar la visita.']);
            }
            $visit?->delete();
            $visitId = null;
        } else {
            $visit = SupervisionVisit::updateOrCreate(
                ['association_id' => $association->id, 'visit_date' => $data['date']],
                ['result' => $data['result'], 'user_id' => $request->user()?->id]
            );
            $visitId = $visit->id;
        }

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

        if ($data['off']) {
            SupervisionOffDay::updateOrCreate(
                ['off_date' => $data['date']],
                ['reason' => $data['reason'] ?? null, 'user_id' => $request->user()?->id]
            );
        } else {
            SupervisionOffDay::whereDate('off_date', $data['date'])->delete();
        }

        return response()->json(['date' => $data['date'], 'off' => $data['off']]);
    }

    public function uploadAttachment(
        Request $request,
        SupervisionVisit $supervisionVisit,
        DocumentAttachmentService $attachments,
        UploadSettingsService $uploadSettings
    ): JsonResponse {
        $data = $request->validate([
            'document_type' => ['required', Rule::in([DocumentAttachment::TYPE_SUPERVISION_DOCUMENT, DocumentAttachment::TYPE_SUPERVISION_PHOTO])],
            'file' => [
                'required', 'file',
                Rule::when($request->input('document_type') === DocumentAttachment::TYPE_SUPERVISION_DOCUMENT, ['mimes:pdf'], ['mimes:jpg,jpeg,png,webp']),
                'max:'.$uploadSettings->maxKilobytes(),
            ],
        ]);

        $attachment = DB::transaction(function () use ($request, $supervisionVisit, $attachments, $data) {
            $visit = SupervisionVisit::query()->lockForUpdate()->findOrFail($supervisionVisit->id);
            $count = $visit->documents()->where('document_type', $data['document_type'])->count();
            $limit = $data['document_type'] === DocumentAttachment::TYPE_SUPERVISION_DOCUMENT ? 1 : 3;
            if ($count >= $limit) {
                throw ValidationException::withMessages(['file' => $limit === 1 ? 'Solo se permite un documento escaneado.' : 'Solo se permiten tres fotografías.']);
            }

            return $attachments->store($visit, $data['document_type'], $request->file('file'));
        });

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

    private function countBeneficiaries(Association $association): int
    {
        return \App\Models\Beneficiarie::whereIn(
            'partner_id',
            $association->partners()->select('id')
        )->count();
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
