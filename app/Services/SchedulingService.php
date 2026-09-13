<?php

namespace App\Services;

use App\Models\Association;
use App\Models\Directive;
use App\Models\Pecosa;
use App\Models\Position;
use App\Models\State;
use App\Repositories\AssociationRepository;
use App\Services\ReparticionService;
use Carbon\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

class SchedulingService
{
    private AssociationRepository $associationRepo;
    private PDFService $pdfService;
    private ReparticionService $reparticionService;

    public function __construct(AssociationRepository $associationRepo, PDFService $pdfService, ReparticionService $reparticionService)
    {
        $this->associationRepo = $associationRepo;
        $this->pdfService = $pdfService;
        $this->reparticionService = $reparticionService;
    }

    public function generateProgramacionEntrega(int $month, int $year, ?string $sector = null): array
    {
        $startDate = Carbon::create($year, $month, 1)->startOfMonth()->toDateString();
        $endDate = Carbon::create($year, $month, 1)->endOfMonth()->toDateString();
        // PECOSAs emitidas en la última semana del mes anterior corresponden a
        // la repartición de este mes: se filtran por el período efectivo.
        [$pecosaFrom, $pecosaTo] = Pecosa::deliveryPeriodRange($year, $month);
        $estadoActivo = State::where('abbreviation', State::CURRENT)->first();

        $racion = $this->reparticionService->getActiveRacion($year, $month);
        $racionLabel = $racion
            ? $racion->racion_hojuelas_gramos . 'g / ' . $racion->racion_leche_militros . 'ml'
            : '';

        $associations = $this->associationRepo->getAssociationsWithSectorAndBeneficiaries(
            $estadoActivo ? $estadoActivo->id : null, $sector
        );

        $associationIds = $associations->pluck('id');

        $presidentPosition = Position::where('title', 'like', '%PRESIDENTA%')->first();
        $directivesByResolution = $this->getPresidentDirectivesByAssociation($associationIds, $presidentPosition ? $presidentPosition->id : null, $estadoActivo ? $estadoActivo->id : null);

        $pecosasByAssociation = Pecosa::with('detailPecosas:id,pecosa_id,quantity')
            ->whereIn('association_id', $associationIds)
            ->whereBetween('delivery_date', [$pecosaFrom->toDateString(), $pecosaTo->toDateString()])
            ->get()
            ->keyBy('association_id');

        return $associations->map(function ($association) use ($directivesByResolution, $pecosasByAssociation, $startDate, $endDate) {
            $presidenta = $this->resolvePresidentName($association, $directivesByResolution);
            [$totalBenef, $primeraPrioridad, $segundaPrioridad] = $this->calculatePriorities($association, $startDate, $endDate);
            $pecosa = $pecosasByAssociation->get($association->id);
            $bolsas = $pecosa ? $pecosa->detailPecosas->sum('quantity') : 0;

            $directive = $this->findDirective($association, $directivesByResolution);

            return [
                'codigo' => $association->code ?? $association->id,
                'nombre' => mb_strtoupper($association->name, 'UTF-8'),
                'presidenta' => $presidenta,
                'direccion' => $association->address ?? '',
                'sector' => optional(optional($association->placeSector)->sector)->title ?? '',
                'primera_prioridad' => $primeraPrioridad,
                'segunda_prioridad' => $segundaPrioridad,
                'total_beneficiarios' => $totalBenef,
                'bolsas' => $bolsas,
                'kilos' => 0,
                'racion' => $racionLabel,
                'fecha_entrega' => $pecosa ? date('d/m/Y', strtotime($pecosa->delivery_date)) : '',
                'recibe' => $presidenta,
                'dni' => $directive ? ($directive->partner ? ($directive->partner->people ? $directive->partner->people->dni : '') : '') : '',
            ];
        })->values()->toArray();
    }

    private function getPresidentDirectivesByAssociation($associationIds, $presidentPositionId, $activeStateId): Collection
    {
        return Directive::select(['id', 'partner_id', 'resolution_id', 'position_id', 'state_id'])
            ->where('position_id', $presidentPositionId)
            ->where('state_id', $activeStateId)
            ->whereHas('partner', fn($q) => $q->whereIn('association_id', $associationIds))
            ->with(['partner:id,person_id,association_id', 'partner.people:id,names,father_lastname'])
            ->get()
            ->mapToGroups(fn($d) => [$d->partner->association_id => $d])
            ->map(fn($c) => $c->first());
    }

    private function resolvePresidentName($association, $directivesByResolution)
    {
        $directive = $directivesByResolution->get($association->id);
        if ($directive && $directive->partner && $directive->partner->people) {
            return trim(collect([$directive->partner->people->names, $directive->partner->people->father_lastname, $directive->partner->people->mother_lastname])->filter()->implode(' '));
        }
        return $association->president_name ?? 'SIN ASIGNAR';
    }

    private function calculatePriorities($association, $startDate, $endDate): array
    {
        $partners = $association->partners()
            ->with(['beneficiaries.person'])
            ->where(function ($q) use ($endDate) {
                $q->whereNull('date_begin')->orWhere('date_begin', '<=', $endDate);
            })
            ->where(function ($q) use ($endDate) {
                $q->whereNull('date_end')->orWhere('date_end', '>=', $endDate);
            })
            ->get();

        $primeraPrioridad = 0;
        $segundaPrioridad = 0;
        $totalBenef = 0;

        foreach ($partners as $partner) {
            foreach ($partner->beneficiaries as $beneficiario) {
                $persona = $beneficiario->person;
                if (!$persona || !$persona->birthdate) continue;

                $edadAnos = Carbon::parse($persona->birthdate)->diffInYears($endDate);
                $totalBenef++;

                if ($edadAnos < 2) {
                    $primeraPrioridad++;
                } elseif ($edadAnos >= 2 && $edadAnos <= 6) {
                    $segundaPrioridad++;
                }
            }
        }

        return [$totalBenef, $primeraPrioridad, $segundaPrioridad];
    }

    private function findDirective($association, $directivesByResolution)
    {
        return $directivesByResolution->get($association->id);
    }
}
