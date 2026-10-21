<?php

namespace App\Http\Controllers\Api;

use App\Exceptions\ReniecException;
use App\Http\Controllers\Controller;
use App\Http\Requests\StorePartnerRequest;
use App\Http\Requests\StorePersonaRequest;
use App\Http\Requests\UpdatePartnerRequest;
use App\Http\Requests\UpdatePersonaRequest;
use App\Http\Resources\BeneficiarieResource;
use App\Http\Resources\PartnerResource;
use App\Http\Resources\PersonaResource;
use App\Models\Beneficiarie;
use App\Models\DocumentAttachment;
use App\Models\Partner;
use App\Models\People;
use App\Services\BeneficiaryService;
use App\Services\DocumentAttachmentService;
use App\Services\PartnerService;
use App\Services\PersonService;
use App\Services\UploadSettingsService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Illuminate\Validation\Rule;

class SociosBeneficiariosController extends Controller
{
    private const PARTNER_FIELDS_OUT_OF_MODEL = ['family_document', 'conadis_document', 'beneficiary_documents', 'beneficiaries', 'fum', 'benefit_child_person_id'];

    public function __construct(
        private PartnerService $partnerService,
        private PersonService $personService,
        private BeneficiaryService $beneficiaryService
    ) {
    }

    // ==================== SOCIOS ====================

    public function partners(Request $request)
    {
        $partners = $this->partnerService->paginate(
            $request->only(['search', 'association_id', 'state_id']),
            (int) $request->input('per_page', 10)
        );

        return PartnerResource::collection($partners);
    }

    public function partnersOptions()
    {
        return response()->json($this->partnerService->optionsData());
    }

    public function storePartner(StorePartnerRequest $request)
    {
        $partner = $this->partnerService->register(
            $request->safe()->except(self::PARTNER_FIELDS_OUT_OF_MODEL),
            $request->input('beneficiaries'),
            $request->only(['fum', 'benefit_child_person_id']),
            $this->registrationDocuments($request)
        );

        return (new PartnerResource($this->partnerService->detail($partner)))
            ->response()
            ->setStatusCode(201);
    }

    public function updatePartner(UpdatePartnerRequest $request, Partner $partner)
    {
        $partner = $this->partnerService->modify(
            $partner,
            $request->safe()->except(self::PARTNER_FIELDS_OUT_OF_MODEL),
            $request->input('beneficiaries'),
            $request->only(['fum', 'benefit_child_person_id']),
            $this->registrationDocuments($request)
        );

        return new PartnerResource($this->partnerService->detail($partner));
    }

    public function destroyPartner(Partner $partner)
    {
        $this->partnerService->deleteWithRelations($partner);

        return response()->json(null, 204);
    }

    // ==================== PERSONAS ====================

    public function personas(Request $request)
    {
        $people = $this->personService->paginate(
            $request->only(['search', 'gender', 'place_sector_id']),
            (int) $request->input('per_page', 15)
        );

        return PersonaResource::collection($people);
    }

    public function personasOptions()
    {
        return response()->json(['place_sectors' => $this->personService->placeSectors()]);
    }

    public function consultarReniec(Request $request)
    {
        Gate::authorize('create', People::class);

        $validated = $request->validate([
            'dni' => ['required', 'regex:/^\d{8}$/'],
        ], [
            'dni.regex' => 'Ingrese un DNI válido de 8 dígitos.',
        ]);

        try {
            $person = $this->personService->lookupReniec($validated['dni'], (string) $request->user()->getAuthIdentifier());
        } catch (ReniecException $exception) {
            return $this->reniecError($exception);
        }

        return response()->json(['data' => $person])
            ->header('Cache-Control', 'no-store, private');
    }

    public function storePersona(StorePersonaRequest $request)
    {
        $person = $this->personService->register($request->validated(), (string) $request->user()->getAuthIdentifier());

        return (new PersonaResource($this->personService->detail($person)))
            ->response()
            ->setStatusCode(201);
    }

    public function showPersona(Request $request, People $person)
    {
        return response()->json([
            'data' => (new PersonaResource($this->personService->detail($person)))->resolve($request),
        ])->header('Cache-Control', 'no-store, private');
    }

    public function fotoReniec(People $person)
    {
        try {
            $photo = $this->personService->reniecPhoto($person);
        } catch (ReniecException $exception) {
            return $this->reniecError($exception);
        }

        return response()->json(['data' => ['photo' => $photo]])
            ->header('Cache-Control', 'no-store, private');
    }

    public function updatePersona(UpdatePersonaRequest $request, People $person)
    {
        $this->personService->update($person, $request->validated());

        return new PersonaResource($this->personService->detail($person));
    }

    public function destroyPersona(People $person)
    {
        try {
            $this->personService->delete($person);
        } catch (\DomainException $e) {
            return response()->json(['message' => $e->getMessage()], 422);
        }

        return response()->json(null, 204);
    }

    // ==================== BENEFICIARIOS ====================

    public function beneficiarios(Request $request)
    {
        [$beneficiaries, $year, $month] = $this->beneficiaryService->paginateByPeriod(
            $request->only(['search', 'partner_id', 'relationship_id']),
            $request->filled('year') ? (int) $request->input('year') : null,
            $request->filled('month') ? (int) $request->input('month') : null,
            (int) $request->input('per_page', 10)
        );

        return BeneficiarieResource::collection($beneficiaries)->additional([
            'period' => ['year' => $year, 'month' => $month],
        ]);
    }

    public function beneficiariosOptions()
    {
        return response()->json($this->beneficiaryService->optionsData());
    }

    public function showBeneficiario(Beneficiarie $beneficiarie)
    {
        return new BeneficiarieResource($this->beneficiaryService->detail($beneficiarie));
    }

    public function storeBeneficiario(Request $request)
    {
        $beneficiarie = $this->beneficiaryService->register($request->validate($this->beneficiarioRules()));

        return (new BeneficiarieResource($beneficiarie))
            ->response()
            ->setStatusCode(201);
    }

    public function updateBeneficiario(Request $request, Beneficiarie $beneficiarie)
    {
        $beneficiarie = $this->beneficiaryService->modify($beneficiarie, $request->validate($this->beneficiarioRules()));

        return new BeneficiarieResource($beneficiarie);
    }

    public function destroyBeneficiario(Beneficiarie $beneficiarie)
    {
        try {
            $this->beneficiaryService->deactivate($beneficiarie);
        } catch (\DomainException $e) {
            return response()->json(['message' => $e->getMessage()], 422);
        }

        return response()->json(null, 204);
    }

    // ==================== DOCUMENTOS ADJUNTOS ====================

    public function uploadDocument(Request $request, UploadSettingsService $uploadSettings)
    {
        $validated = $request->validate([
            'attachable_type' => ['required', 'string', Rule::in(['partner', 'beneficiarie'])],
            'attachable_id' => ['required', 'integer'],
            'document_type' => ['required', 'string', Rule::in(DocumentAttachment::TYPES)],
            'file' => [
                'required',
                'file',
                Rule::when(
                    in_array($request->input('document_type'), [DocumentAttachment::TYPE_EXPEDIENTE_UNICO, DocumentAttachment::TYPE_CONADIS], true),
                    ['mimes:pdf'],
                    ['mimes:pdf,jpg,jpeg,png,webp']
                ),
                'max:'.$uploadSettings->maxKilobytes(),
            ],
        ]);

        $attachment = $this->beneficiaryService->attachDocument(
            $validated['attachable_type'],
            (int) $validated['attachable_id'],
            $validated['document_type'],
            $request->file('file')
        );

        return response()->json([
            'id' => $attachment->id,
            'document_type' => $attachment->document_type,
            'file_name' => $attachment->file_name,
            'mime_type' => $attachment->mime_type,
            'file_size' => $attachment->file_size,
            'created_at' => $attachment->created_at?->toISOString(),
            'url' => route('api.socios-beneficiarios.documents.show', $attachment->id),
        ], 201);
    }

    public function downloadDocument(DocumentAttachment $documentAttachment, DocumentAttachmentService $attachmentService)
    {
        return response($attachmentService->getDecryptedContent($documentAttachment), 200, [
            'Content-Type' => $documentAttachment->mime_type,
            'Content-Disposition' => 'inline; filename="' . $documentAttachment->file_name . '"',
        ]);
    }

    public function destroyDocument(DocumentAttachment $documentAttachment, DocumentAttachmentService $attachmentService)
    {
        $attachmentService->delete($documentAttachment);

        return response()->json(null, 204);
    }

    private function beneficiarioRules(): array
    {
        return [
            'person_id' => 'required|exists:people,id',
            'partner_id' => 'required|exists:partners,id',
            'relationship_id' => 'required|exists:relationships,id',
            'priority' => 'nullable|integer|in:1,2',
            'date_begin' => 'nullable|date',
            'type_benefit_id' => 'nullable|exists:type_benefits,id',
            'history_state_id' => 'nullable|exists:states,id',
            'reason_disqualification_id' => 'nullable|exists:reason_disqualifications,id',
        ];
    }

    private function registrationDocuments(Request $request): array
    {
        return [
            'family' => $request->file('family_document'),
            'conadis' => $request->file('conadis_document'),
            'beneficiaries' => $request->file('beneficiary_documents', []),
        ];
    }

    private function reniecError(ReniecException $exception)
    {
        return response()->json([
            'message' => $exception->getMessage(),
            'code' => $exception->resultCode(),
        ], $exception->httpStatus())->header('Cache-Control', 'no-store, private');
    }
}
