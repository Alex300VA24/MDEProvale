<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\StorePartnerRequest;
use App\Http\Requests\StorePersonaRequest;
use App\Http\Requests\UpdatePartnerRequest;
use App\Http\Requests\UpdatePersonaRequest;
use App\Http\Resources\BeneficiarieResource;
use App\Http\Resources\PartnerResource;
use App\Http\Resources\PersonaResource;
use App\Models\Association;
use App\Models\Beneficiarie;
use App\Models\Partner;
use App\Models\People;
use App\Models\PlaceSector;
use App\Models\ReasonDisqualification;
use App\Models\Relationship;
use App\Models\State;
use App\Models\TypeBenefit;
use App\Services\PartnerService;
use Illuminate\Http\Request;

class SociosBeneficiariosController extends Controller
{
    private const PARTNER_WITH = [
        'people:id,names,father_lastname,mother_lastname,dni,address',
        'association:id,name,code',
        'state:id,title',
        'beneficiaries.person:id,names,father_lastname,mother_lastname,dni,birthdate',
        'beneficiaries.relationship:id,title',
        'beneficiaries.histories.typeBenefit:id,title,abbreviation',
        'beneficiaries.histories.state:id,title',
        'beneficiaries.histories.reasonDisqualification:id,title',
    ];

    private const BENEFICIARIO_WITH = [
        'person',
        'partner.people:id,names,father_lastname',
        'relationship',
        'histories.typeBenefit:id,title,abbreviation',
        'histories.state:id,title',
        'histories.reasonDisqualification:id,title',
    ];

    private PartnerService $partnerService;

    public function __construct(PartnerService $partnerService)
    {
        $this->partnerService = $partnerService;
    }

    // ==================== SOCIOS ====================

    public function partners(Request $request)
    {
        $query = Partner::query()
            ->select(['partners.id', 'partners.person_id', 'partners.association_id', 'partners.state_id', 'partners.date_begin', 'partners.date_end', 'partners.observations'])
            ->with(self::PARTNER_WITH)
            ->withCount('beneficiaries');

        if ($request->filled('search')) {
            $search = $request->search;
            $query->whereHas('people', function ($q) use ($search) {
                $q->where('names', 'like', "%{$search}%")
                    ->orWhere('father_lastname', 'like', "%{$search}%")
                    ->orWhere('mother_lastname', 'like', "%{$search}%")
                    ->orWhere('dni', 'like', "%{$search}%");
            });
        }

        if ($request->filled('association_id')) {
            $query->where('association_id', $request->association_id);
        }

        if ($request->filled('state_id')) {
            $query->where('state_id', $request->state_id);
        }

        $partners = $query->orderBy('id', 'desc')
            ->paginate((int) $request->input('per_page', 10));

        return PartnerResource::collection($partners);
    }

    public function partnersOptions()
    {
        return response()->json([
            'associations' => Association::select(['id', 'name', 'code'])->orderBy('name')->get(),
            'states' => State::select(['id', 'title'])->get(),
            'people' => People::select(['id', 'names', 'father_lastname', 'mother_lastname', 'dni'])
                ->orderBy('id', 'desc')
                ->limit(100)
                ->get(),
            'all_people' => People::select(['id', 'names', 'father_lastname', 'mother_lastname', 'dni'])
                ->orderBy('names')
                ->limit(1000)
                ->get(),
            'relationships' => Relationship::select(['id', 'title'])->get(),
            'place_sectors' => PlaceSector::with(['place:id,code,title', 'sector:id,title'])->get(),
            'type_benefits' => TypeBenefit::select(['id', 'title', 'abbreviation'])->get(),
            'reason_disqualifications' => ReasonDisqualification::select(['id', 'title'])->get(),
        ]);
    }

    public function storePartner(StorePartnerRequest $request)
    {
        $partner = $this->partnerService->storeWithBeneficiaries(
            $request->validated(),
            $request->input('beneficiaries')
        );

        return (new PartnerResource($partner->load(self::PARTNER_WITH)))
            ->response()
            ->setStatusCode(201);
    }

    public function updatePartner(UpdatePartnerRequest $request, Partner $partner)
    {
        $partner = $this->partnerService->updateWithBeneficiaries(
            $partner,
            $request->validated(),
            $request->input('beneficiaries')
        );

        return new PartnerResource($partner->load(self::PARTNER_WITH));
    }

    public function destroyPartner(Partner $partner)
    {
        $this->partnerService->deleteWithRelations($partner);

        return response()->json(null, 204);
    }

    // ==================== PERSONAS ====================

    public function personas(Request $request)
    {
        $query = People::select('id', 'names', 'father_lastname', 'mother_lastname', 'dni', 'gender', 'telephone_number', 'phone_number', 'birthdate', 'place_sector_id', 'address')
            ->with('placeSector.place:id,title', 'placeSector.sector:id,title');

        if ($request->filled('search')) {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->where('names', 'like', "%{$search}%")
                    ->orWhere('father_lastname', 'like', "%{$search}%")
                    ->orWhere('mother_lastname', 'like', "%{$search}%")
                    ->orWhere('dni', 'like', "%{$search}%");
            });
        }

        if ($request->filled('gender')) {
            $query->where('gender', $request->gender);
        }

        if ($request->filled('place_sector_id')) {
            $query->where('place_sector_id', $request->place_sector_id);
        }

        $people = $query->orderBy('id')
            ->paginate((int) $request->input('per_page', 15));

        return PersonaResource::collection($people);
    }

    public function personasOptions()
    {
        return response()->json([
            'place_sectors' => PlaceSector::with(['place:id,title', 'sector:id,title'])->get(),
        ]);
    }

    public function storePersona(StorePersonaRequest $request)
    {
        $person = People::create($request->validated());

        return (new PersonaResource($person->load('placeSector.place:id,title', 'placeSector.sector:id,title')))
            ->response()
            ->setStatusCode(201);
    }

    public function updatePersona(UpdatePersonaRequest $request, People $person)
    {
        $person->update($request->validated());

        return new PersonaResource($person->load('placeSector.place:id,title', 'placeSector.sector:id,title'));
    }

    public function destroyPersona(People $person)
    {
        if ($person->partners()->exists() || $person->beneficiaries()->exists()) {
            return response()->json([
                'message' => 'No se puede eliminar la persona porque está asociada a un socio o beneficiario',
            ], 422);
        }

        $person->delete();

        return response()->json(null, 204);
    }

    // ==================== BENEFICIARIOS ====================

    public function beneficiarios(Request $request)
    {
        $query = Beneficiarie::with(self::BENEFICIARIO_WITH);

        if ($request->filled('search')) {
            $search = $request->search;
            $query->whereHas('person', function ($q) use ($search) {
                $q->where('names', 'like', "%{$search}%")
                    ->orWhere('father_lastname', 'like', "%{$search}%")
                    ->orWhere('mother_lastname', 'like', "%{$search}%")
                    ->orWhere('dni', 'like', "%{$search}%");
            });
        }

        if ($request->filled('partner_id')) {
            $query->where('partner_id', $request->partner_id);
        }

        if ($request->filled('relationship_id')) {
            $query->where('relationship_id', $request->relationship_id);
        }

        $beneficiaries = $query->orderBy('id', 'desc')
            ->paginate((int) $request->input('per_page', 10));

        return BeneficiarieResource::collection($beneficiaries);
    }

    public function beneficiariosOptions()
    {
        return response()->json([
            'partners' => Partner::select(['id', 'person_id'])
                ->with('people:id,names,father_lastname')
                ->orderBy('id')
                ->get()
                ->map(fn ($partner) => ['id' => $partner->id, 'name' => $partner->name]),
            'relationships' => Relationship::select(['id', 'title'])->get(),
        ]);
    }

    private function beneficiarioHistoryRules(): array
    {
        return [
            'weight' => 'nullable|numeric|min:0',
            'height' => 'nullable|numeric|min:0',
            'hmg' => 'nullable|numeric|min:0',
            'date_begin' => 'nullable|date',
            'date_end' => 'nullable|date',
            'type_benefit_id' => 'nullable|exists:type_benefits,id',
            'history_state_id' => 'nullable|exists:states,id',
            'reason_disqualification_id' => 'nullable|exists:reason_disqualifications,id',
        ];
    }

    /**
     * Crea o actualiza el registro "actual" de historial clínico/beneficio de un
     * beneficiario. Requiere tipo de beneficio, estado y fechas de inicio/fin
     * completos para persistir (misma regla que PartnerService::syncBeneficiaries
     * al guardar beneficiarios anidados en el formulario de Socio).
     */
    private function syncBeneficiarioHistory(Beneficiarie $beneficiarie, array $data): void
    {
        if (empty($data['type_benefit_id']) || empty($data['history_state_id'])
            || empty($data['date_begin']) || empty($data['date_end'])) {
            return;
        }

        $payload = [
            'weight' => $data['weight'] ?? 0,
            'height' => $data['height'] ?? 0,
            'hmg' => $data['hmg'] ?? 0,
            'date_begin' => $data['date_begin'],
            'date_end' => $data['date_end'],
            'type_benefit_id' => $data['type_benefit_id'],
            'state_id' => $data['history_state_id'],
            'reason_disqualification_id' => $data['reason_disqualification_id'] ?? null,
        ];

        $history = $beneficiarie->histories()->first();

        if ($history) {
            $history->update($payload);
        } else {
            $beneficiarie->histories()->create($payload);
        }
    }

    public function storeBeneficiario(Request $request)
    {
        $validated = $request->validate(array_merge([
            'person_id' => 'required|exists:people,id',
            'partner_id' => 'required|exists:partners,id',
            'relationship_id' => 'required|exists:relationships,id',
        ], $this->beneficiarioHistoryRules()));

        $beneficiarie = Beneficiarie::create([
            'person_id' => $validated['person_id'],
            'partner_id' => $validated['partner_id'],
            'relationship_id' => $validated['relationship_id'],
        ]);

        $this->syncBeneficiarioHistory($beneficiarie, $validated);

        return (new BeneficiarieResource($beneficiarie->load(self::BENEFICIARIO_WITH)))
            ->response()
            ->setStatusCode(201);
    }

    public function updateBeneficiario(Request $request, Beneficiarie $beneficiarie)
    {
        $validated = $request->validate(array_merge([
            'person_id' => 'required|exists:people,id',
            'partner_id' => 'required|exists:partners,id',
            'relationship_id' => 'required|exists:relationships,id',
        ], $this->beneficiarioHistoryRules()));

        $beneficiarie->update([
            'person_id' => $validated['person_id'],
            'partner_id' => $validated['partner_id'],
            'relationship_id' => $validated['relationship_id'],
        ]);

        $this->syncBeneficiarioHistory($beneficiarie, $validated);

        return new BeneficiarieResource($beneficiarie->load(self::BENEFICIARIO_WITH));
    }

    public function destroyBeneficiario(Beneficiarie $beneficiarie)
    {
        $beneficiarie->delete();

        return response()->json(null, 204);
    }
}
