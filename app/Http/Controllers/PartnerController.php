<?php

namespace App\Http\Controllers;

use App\Http\Requests\StorePartnerRequest;
use App\Http\Requests\UpdatePartnerRequest;
use App\Models\Partner;
use App\Models\Association;
use App\Models\People;
use App\Models\Relationship;
use App\Models\TypeBenefit;
use App\Models\ReasonDisqualification;
use App\Models\PlaceSector;
use App\Models\State;
use App\Services\PartnerService;
use App\Services\BeneficiaryReportService;
use Barryvdh\DomPDF\Facade\PDF;
use Illuminate\Http\Request;

class PartnerController extends Controller
{
    private PartnerService $partnerService;
    private BeneficiaryReportService $beneficiaryReportService;

    public function __construct(PartnerService $partnerService, BeneficiaryReportService $beneficiaryReportService)
    {
        $this->partnerService = $partnerService;
        $this->beneficiaryReportService = $beneficiaryReportService;
    }

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
        $states = State::select(['id', 'title'])->get();
        $people = People::select(['id', 'names', 'father_lastname', 'mother_lastname', 'dni'])
            ->orderBy('id', 'desc')
            ->limit(100)
            ->get();
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

    public function store(StorePartnerRequest $request)
    {
        try {
            $this->partnerService->storeWithBeneficiaries(
                $request->validated(),
                $request->input('beneficiaries')
            );
            return redirect()->route('partners.index')->with('success', 'Socio y beneficiarios creados exitosamente');
        } catch (\Exception $e) {
            return back()->withInput()->with('error', 'Error al crear socio: ' . $e->getMessage());
        }
    }

    public function update(UpdatePartnerRequest $request, Partner $partner)
    {
        try {
            $this->partnerService->updateWithBeneficiaries(
                $partner,
                $request->validated(),
                $request->input('beneficiaries')
            );
            return redirect()->route('partners.index')->with('success', 'Socio y beneficiarios actualizados exitosamente');
        } catch (\Exception $e) {
            return back()->withInput()->with('error', 'Error al actualizar socio: ' . $e->getMessage());
        }
    }

    public function destroy(Partner $partner)
    {
        try {
            $this->partnerService->deleteWithRelations($partner);
            return redirect()->route('partners.index')->with('success', 'Socio y beneficiarios eliminados exitosamente');
        } catch (\Exception $e) {
            return back()->with('error', 'Error al eliminar socio: ' . $e->getMessage());
        }
    }

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