<?php

namespace App\Http\Controllers;

use App\Http\Requests\StorePartnerRequest;
use App\Http\Requests\UpdatePartnerRequest;
use App\Models\Partner;
use App\Models\VerifiedDocument;
use App\Services\PartnerService;
use App\Services\BeneficiaryReportService;
use App\Services\VerifiedDocumentService;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Barryvdh\DomPDF\Facade\PDF;

class PartnerController extends Controller
{
    private PartnerService $partnerService;
    private BeneficiaryReportService $beneficiaryReportService;

    public function __construct(
        PartnerService $partnerService,
        BeneficiaryReportService $beneficiaryReportService,
        private VerifiedDocumentService $verifiedDocumentService
    )
    {
        $this->partnerService = $partnerService;
        $this->beneficiaryReportService = $beneficiaryReportService;
    }

    public function index(Request $request)
    {
        $data = $this->partnerService->indexData($request->only(['search', 'association_id', 'state_id']));

        return view('socios-beneficiarios.index', $data);
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
        $associations = $this->partnerService->allAssociations();
        $associationId = $request->get('association_id');
        $mes = $request->get('month', date('n'));
        $anio = $request->get('year', date('Y'));

        if (!$associationId) {
            return view('socios-beneficiarios.beneficiarios.padron-filtros', compact('associations', 'mes', 'anio'));
        }

        try {
            $data = $this->beneficiaryReportService->generatePadronReport($associationId, (int)$mes, (int)$anio);

            $identifier = sprintf(
                'BEN-%04d-%02d-%s-%s',
                $anio,
                $mes,
                Str::upper((string) $data['comite']),
                Str::upper(Str::random(8))
            );
            $filename = 'padron-beneficiarios-' . $data['comite'] . '-' . $mes . '-' . $anio . '.pdf';

            [, $contents, $safeFilename] = $this->verifiedDocumentService->issue(
                VerifiedDocument::TYPE_BENEFICIARY_REGISTER,
                $identifier,
                [
                    'periodo' => sprintf('%04d-%02d', $anio, $mes),
                    'comite' => $data['comite'],
                    'club' => $data['club_nombre'],
                    'beneficiarios' => $data['total_beneficiarios'],
                ],
                'reporte_beneficiario',
                $data,
                $filename,
                $request->user()?->id,
                'a4',
                'landscape'
            );

            return response($contents, 200, [
                'Content-Type' => 'application/pdf',
                'Content-Disposition' => 'inline; filename="' . $safeFilename . '"',
            ]);
        } catch (\DomainException $e) {
            if ($request->expectsJson()) {
                return response()->json(['message' => $e->getMessage()], 422);
            }
            return redirect()->back()->with('error', $e->getMessage());
        }
    }

    public function imprimirFicha(Partner $partner)
    {
        $partner = $this->partnerService->partnerForFicha($partner);

        $logoPath = public_path('img/muni2.png');
        $pdf = PDF::loadView('ficha_beneficiario', compact('partner', 'logoPath'));
        $pdf->setPaper('a4', 'portrait');

        $safeName = Str::slug($partner->name ?: 'socio-' . $partner->id);
        return $pdf->stream("ficha-socio-{$safeName}.pdf");
    }
}
