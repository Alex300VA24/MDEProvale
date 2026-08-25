<?php

namespace App\Http\Controllers;

use App\Models\Partner;
use App\Models\Beneficiarie;
use App\Models\Association;
use App\Models\People;
use App\Models\Relationship;
use App\Models\State;
use App\Models\PlaceSector;
use App\Services\BeneficiaryReportService;
use Illuminate\Http\Request;
use Barryvdh\DomPDF\Facade\PDF;
use Illuminate\Validation\Rule;


class SociosBeneficiariosController extends Controller
{
    private BeneficiaryReportService $beneficiaryReportService;

    public function __construct(BeneficiaryReportService $beneficiaryReportService)
    {
        $this->beneficiaryReportService = $beneficiaryReportService;
    }

    // ==================== ÍNDICE PRINCIPAL ====================

    public function index(Request $request)
    {
        $query = Partner::query()
            ->select(['partners.id', 'partners.person_id', 'partners.association_id', 'partners.state_id', 'partners.date_begin', 'partners.date_end', 'partners.observations'])
            ->with([
                'people:id,names,father_lastname,mother_lastname,dni,address',
                'association:id,name,code',
                'state:id,title'
            ])
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

        $partners = $query->orderBy('id', 'desc')->paginate(10);
        $associations = Association::select(['id', 'name'])->get();
        $states = State::temporal()->get(['id', 'title']);
        
        // Get a sample of people for the dropdown - limit to avoid query timeout
        // Note: Since we have too many partners, it's not practical to exclude all of them
        // Instead, we'll load recently added people and let JS handle the filtering if needed
        $people = People::select(['id', 'names', 'father_lastname', 'mother_lastname', 'dni'])
            ->orderBy('id', 'desc')
            ->limit(100)
            ->get();
        
        // Get all people for the edit modal (to allow changing person)
        $allPeople = People::select(['id', 'names', 'father_lastname', 'mother_lastname', 'dni'])
            ->orderBy('names')
            ->limit(1000)
            ->get();
        
        $relationships = Relationship::select(['id', 'title'])->get();
        $placeSectors = PlaceSector::with(['place:id,code,title', 'sector:id,title'])->get();
        $typeBenefits = TypeBenefit::select(['id', 'title', 'abbreviation'])->get();
        $reasonDisqualifications = ReasonDisqualification::select(['id', 'title'])->get();

        return view('socios-beneficiarios.index', compact('partners', 'associations', 'states', 'people', 'allPeople', 'relationships', 'placeSectors', 'typeBenefits', 'reasonDisqualifications'));
    }

    // ==================== PERSONAS ====================

    public function indexPersonas(Request $request)
    {
        $query = People::select('id', 'names', 'father_lastname', 
                                'mother_lastname', 'dni', 'gender', 
                                'telephone_number', 'phone_number', 'birthdate', 'place_sector_id',
                                'address');

        if ($request->has('search') && $request->search != '') {
            $search = $request->search;
            $query->where('names', 'like', "%{$search}%")
                ->orWhere('father_lastname', 'like', "%{$search}%")
                ->orWhere('mother_lastname', 'like', "%{$search}%")
                ->orWhere('dni', 'like', "%{$search}%");
        }

        if ($request->has('gender') && $request->gender != '') {
            $query->where('gender', $request->gender);
        }

        if ($request->has('place_sector_id') && $request->place_sector_id != '') {
            $query->where('place_sector_id', $request->place_sector_id);
        }

        $people = $query->orderBy('id')->paginate(15);
        /*$placeSectors = PlaceSector::with([
            //'id',
            'place' => function ($query) {
                $query->select(['id', 'title']);
            }, 
            'sector' => function ($query) {
                $query->select(['id', 'title']);
            }
        ])->get();*/

        $placeSectors = PlaceSector::select('id', 'place_id', 'sector_id')
            ->with([
                    'place:id,title',   // solo id y title de places
                    'sector:id,title'   // solo id y title de sectors
                    ])
            ->get();

        return view('socios-beneficiarios.personas.index', compact('people', 'placeSectors'));
    }

    public function storePersona(Request $request)
    {
        $validated = $request->validate([
            'names' => 'required|string|max:255',
            'father_lastname' => 'required|string|max:255',
            'mother_lastname' => 'required|string|max:255',
            'dni' => 'required|string|size:8|unique:people,dni',
            'birthdate' => 'required|date',
            'gender' => 'required|in:M,F',
            'telephone_number' => 'nullable|string|max:6',
            'phone_number' => 'nullable|string|max:9',
            'address' => 'required|string|max:500',
            'place_sector_id' => 'required|exists:place_sectors,id',
        ]);

        People::create($validated);
        return redirect()->route('socios-beneficiarios.personas.index')->with('success', 'Persona registrada exitosamente');
    }

    public function updatePersona(Request $request, People $person)
    {
        $validated = $request->validate([
            'names' => 'required|string|max:255',
            'father_lastname' => 'required|string|max:255',
            'mother_lastname' => 'required|string|max:255',
            'dni' => 'required|string|size:8|unique:people,dni,' . $person->id,
            'birthdate' => 'required|date',
            'gender' => 'required|in:M,F',
            'telephone_number' => 'nullable|string|max:6',
            'phone_number' => 'nullable|string|max:9',
            'address' => 'required|string|max:500',
            'place_sector_id' => 'required|exists:place_sectors,id',
        ]);

        $person->update($validated);
        return redirect()->route('socios-beneficiarios.personas.index')->with('success', 'Persona actualizada exitosamente');
    }

    public function destroyPersona(People $person)
    {
        $hasPartners = $person->partners()->exists();
        $hasBeneficiaries = $person->beneficiaries()->exists();

        if ($hasPartners || $hasBeneficiaries) {
            return redirect()->route('socios-beneficiarios.personas.index')->with('error', 'No se puede eliminar la persona porque está asociada a un socio o beneficiario');
        }

        $person->delete();
        return redirect()->route('socios-beneficiarios.personas.index')->with('success', 'Persona eliminada exitosamente');
    }

    // ==================== SOCIOS ====================

    public function storeSocio(Request $request)
    {
        if ($request->has('create_person') && $request->create_person == '1') {
            $personData = $request->validate([
                'names' => 'required|string|max:255',
                'father_lastname' => 'required|string|max:255',
                'mother_lastname' => 'required|string|max:255',
                'dni' => 'required|string|size:8|unique:people,dni',
                'birthdate' => 'required|date',
                'gender' => 'required|in:M,F',
                'address' => 'required|string|max:500',
                'place_sector_id' => 'required|exists:place_sectors,id',
            ]);

            $person = People::create($personData);
            $request->merge(['person_id' => $person->id]);
        }

        $validated = $request->validate([
            'date_begin' => 'required|date',
            'date_end' => 'required|date',
            'observations' => 'nullable|string',
            'state_id' => ['required', Rule::exists('states', 'id')->where(fn ($q) => $q->whereIn('abbreviation', [State::CURRENT, State::EXPIRED]))],
            'person_id' => 'required|exists:people,id',
            'association_id' => 'required|exists:associations,id',
            'beneficiaries' => 'nullable|array',
            'beneficiaries.*.person_id' => 'required_with:beneficiaries|exists:people,id',
            'beneficiaries.*.relationship_id' => 'required_with:beneficiaries|exists:relationships,id',
            'beneficiaries.*.weight' => 'nullable|numeric|min:0',
            'beneficiaries.*.height' => 'nullable|numeric|min:0',
            'beneficiaries.*.hmg' => 'nullable|numeric|min:0',
            'beneficiaries.*.date_begin' => 'nullable|date',
            'beneficiaries.*.date_end' => 'nullable|date',
            'beneficiaries.*.type_benefit_id' => 'nullable|exists:type_benefits,id',
            'beneficiaries.*.history_state_id' => ['nullable', Rule::exists('states', 'id')->where(fn ($q) => $q->whereIn('abbreviation', [State::CURRENT, State::EXPIRED]))],
            'beneficiaries.*.reason_disqualification_id' => 'nullable|exists:reason_disqualifications,id',
        ]);

        $partner = Partner::create($validated);

        if ($request->has('beneficiaries') && !empty($request->beneficiaries)) {
            foreach ($request->beneficiaries as $beneficiary) {
                if (!empty($beneficiary['person_id']) && !empty($beneficiary['relationship_id'])) {
                    $ben = Beneficiarie::create([
                        'person_id' => $beneficiary['person_id'],
                        'partner_id' => $partner->id,
                        'relationship_id' => $beneficiary['relationship_id'],
                    ]);

                    // Guardar historial si se proporcionaron datos
                    if (!empty($beneficiary['type_benefit_id']) && !empty($beneficiary['history_state_id'])
                        && !empty($beneficiary['date_begin']) && !empty($beneficiary['date_end'])) {
                        \App\Models\BeneficiaryHistory::create([
                            'weight' => $beneficiary['weight'] ?? 0,
                            'height' => $beneficiary['height'] ?? 0,
                            'hmg' => $beneficiary['hmg'] ?? 0,
                            'date_begin' => $beneficiary['date_begin'],
                            'date_end' => $beneficiary['date_end'],
                            'type_benefit_id' => $beneficiary['type_benefit_id'],
                            'beneficiary_id' => $ben->id,
                            'state_id' => $beneficiary['history_state_id'],
                            'reason_disqualification_id' => $beneficiary['reason_disqualification_id'] ?? null,
                        ]);
                    }
                }
            }
        }

        return redirect()->route('socios-beneficiarios.socios.index')->with('success', 'Socio y beneficiarios creados exitosamente');
    }

    public function updateSocio(Request $request, Partner $partner)
    {
        $validated = $request->validate([
            'date_begin' => 'required|date',
            'date_end' => 'required|date',
            'observations' => 'nullable|string',
            'state_id' => ['required', Rule::exists('states', 'id')->where(fn ($q) => $q->whereIn('abbreviation', [State::CURRENT, State::EXPIRED]))],
            'person_id' => 'required|exists:people,id',
            'association_id' => 'required|exists:associations,id',
            'beneficiaries' => 'nullable|array',
            'beneficiaries.*.person_id' => 'required_with:beneficiaries|exists:people,id',
            'beneficiaries.*.relationship_id' => 'required_with:beneficiaries|exists:relationships,id',
        ]);

        $partner->update($validated);

        if ($request->has('beneficiaries')) {
            $partner->beneficiaries()->delete();

            foreach ($request->beneficiaries as $beneficiary) {
                if (!empty($beneficiary['person_id']) && !empty($beneficiary['relationship_id'])) {
                    Beneficiarie::create([
                        'person_id' => $beneficiary['person_id'],
                        'partner_id' => $partner->id,
                        'relationship_id' => $beneficiary['relationship_id'],
                    ]);
                }
            }
        }

        return redirect()->route('socios-beneficiarios.socios.index')->with('success', 'Socio y beneficiarios actualizados exitosamente');
    }

    public function destroySocio(Partner $partner)
    {
        $partner->beneficiaries()->delete();
        $partner->delete();
        return redirect()->route('socios-beneficiarios.socios.index')->with('success', 'Socio y beneficiarios eliminados exitosamente');
    }

    // ==================== BENEFICIARIOS ====================

    public function indexBeneficiarios(Request $request)
    {
        $query = Beneficiarie::with(['person', 'partner', 'relationship']);

        if ($request->has('search') && $request->search != '') {
            $search = $request->search;
            $query->whereHas('person', function ($q) use ($search) {
                $q->where('names', 'like', "%{$search}%")
                    ->orWhere('father_lastname', 'like', "%{$search}%")
                    ->orWhere('mother_lastname', 'like', "%{$search}%")
                    ->orWhere('dni', 'like', "%{$search}%");
            });
        }

        if ($request->has('partner_id') && $request->partner_id != '') {
            $query->where('partner_id', $request->partner_id);
        }

        if ($request->has('relationship_id') && $request->relationship_id != '') {
            $query->where('relationship_id', $request->relationship_id);
        }

        $beneficiaries = $query->orderBy('id', 'desc')->paginate(10);
        $partners = Partner::select(['id', 'person_id'])->with('people:id,names,father_lastname')->get();
        $relationships = Relationship::all();

        return view('socios-beneficiarios.beneficiarios.index', compact('beneficiaries', 'partners', 'relationships'));
    }

    public function imprimirFichaBeneficiario()
    {
        $logoPath = public_path('img/muni2.png');
        $pdf = PDF::loadView('ficha_beneficiario', compact('logoPath'));
        $pdf->setPaper('a4', 'portrait');
        return $pdf->stream('ficha-beneficiario-' . date('Y-m-d-His') . '.pdf');
    }


    // ==================== REPORTES ====================

    /**
     * Reporte Padrón de Beneficiarios del Club de Madres PVL
     * Filtrado por comité, mes y año — HISTÓRICO.
     * Delega la lógica a BeneficiaryReportService para evitar código duplicado.
     */
    public function reportePadronBeneficiarios(Request $request)
    {
        $associations = Association::all();
        $associationId = $request->get('association_id');
        $mes = $request->get('month', date('n'));
        $anio = $request->get('year', date('Y'));

        if (!$associationId) {
            return view('socios-beneficiarios.beneficiarios.padron-filtros', compact('associations', 'mes', 'anio'));
        }

        try {
            $data = $this->beneficiaryReportService->generatePadronReport($associationId, (int)$mes, (int)$anio);

            $pdf = PDF::loadView('reporte_beneficiario', $data);
            $pdf->setPaper('a4', 'landscape');
            return $pdf->stream('padron-beneficiarios-' . $data['comite'] . '-' . $mes . '-' . $anio . '.pdf');
        } catch (\DomainException $e) {
            return redirect()->back()->with('error', $e->getMessage());
        }
    }
}
