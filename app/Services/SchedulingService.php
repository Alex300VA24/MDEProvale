<?php

namespace App\Services;

use App\Models\Pecosa;
use App\Models\State;
use App\Models\MonthlyRoster;
use App\Repositories\Contracts\AssociationRepositoryInterface;
use App\Repositories\Contracts\CatalogRepositoryInterface;
use App\Repositories\Contracts\DistributionRepositoryInterface;
use App\Repositories\Contracts\PecosaRepositoryInterface;
use Carbon\Carbon;
use Illuminate\Support\Collection;

class SchedulingService
{
    public function __construct(
        private AssociationRepositoryInterface $associationRepo,
        private PecosaRepositoryInterface $pecosaRepo,
        private CatalogRepositoryInterface $catalogs,
        private DistributionRepositoryInterface $distributions,
        private PDFService $pdfService,
        private ReparticionService $reparticionService
    ) {
    }

    /**
     * @param  bool  $includeAll  true devuelve también los comités no seleccionados (con `incluido` = false);
     *                            false (PDF y reportes) solo los que se usan en el período.
     */
    public function generateProgramacionEntrega(int $month, int $year, ?string $sector = null, bool $includeAll = false, ?int $monthlyRosterId = null): array
    {
        $startDate = Carbon::create($year, $month, 1)->startOfMonth()->toDateString();
        $endDate = Carbon::create($year, $month, 1)->endOfMonth()->toDateString();
        // PECOSAs emitidas en la última semana del mes anterior corresponden a
        // la repartición de este mes: se filtran por el período efectivo.
        [$pecosaFrom, $pecosaTo] = Pecosa::deliveryPeriodRange($year, $month);
        $activeStateId = State::idFor(State::CURRENT);

        $racion = $this->reparticionService->getActiveRacion($year, $month);
        $configuration = $racion
            ? $this->reparticionService->getConfiguration($racion, $year, $month)
            : null;
        $racionLabel = $racion
            ? $racion->racion_hojuelas_gramos . 'g / ' . $racion->racion_leche_militros . 'ml'
            : '';

        // Se consideran todos los comités; los inactivos solo se usan si el usuario los selecciona.
        $associations = $this->associationRepo->getAssociationsWithSectorAndBeneficiaries();
        if ($sector !== null && trim($sector) !== '') {
            $associations = $associations
                ->filter(fn ($association) => optional(optional($association->placeSector)->sector)->title === $sector)
                ->values();
        }

        $associationIds = $associations->pluck('id');
        $roster = $monthlyRosterId
            ? MonthlyRoster::with('associations.entries')->where('status', MonthlyRoster::CLOSED)->find($monthlyRosterId)
            : null;
        $rosterAssociations = $roster?->associations->keyBy('association_id') ?? collect();

        $presidentPositionId = $this->catalogs->positionIdByTitle('PRESIDENTA', true);
        $directivesByResolution = ($presidentPositionId && $activeStateId)
            ? $this->pecosaRepo->getPresidentDirectivesByAssociation($associationIds, $presidentPositionId, $activeStateId)
            : collect();

        $pecosasByAssociation = $this->pecosaRepo->deliveriesInRange(
            $associationIds,
            $pecosaFrom->toDateString(),
            $pecosaTo->toDateString()
        );

        return $associations->map(function ($association) use ($directivesByResolution, $pecosasByAssociation, $startDate, $endDate, $racionLabel, $configuration, $roster, $rosterAssociations) {
            $rosterAssociation = $rosterAssociations->get($association->id);
            $associationSnapshot = $rosterAssociation?->association_snapshot ?? [];
            $presidenta = $rosterAssociation?->president_name
                ?: $this->resolvePresidentName($association, $directivesByResolution);
            [$baseBeneficiaries, $primeraPrioridad, $segundaPrioridad] = $this->calculatePriorities(
                $association,
                $startDate,
                $endDate,
                $rosterAssociation
            );
            $assignment = $configuration ? $configuration['assignments']->get($association->id) : null;
            $beneficiaryAdjustment = (int) ($assignment?->beneficiary_adjustment ?? 0);
            $totalBenef = max(0, $baseBeneficiaries + $beneficiaryAdjustment);
            $pecosa = $pecosasByAssociation->get($association->id);
            $bolsas = $pecosa ? $pecosa->detailPecosas->sum('quantity') : 0;
            $lecheQty = $pecosa ? $pecosa->detailPecosas->where('product_abbreviation', 'LEC')->sum('quantity') : 0;
            $hojuelaQty = $pecosa ? $pecosa->detailPecosas->where('product_abbreviation', 'HOJ')->sum('quantity') : 0;
            [$leche, $lecheRedondeo, $hojuela, $hojuelaRedondeo] = $this->calculateScheduledProducts(
                $totalBenef,
                $configuration,
                (float) $lecheQty,
                (float) $hojuelaQty
            );
            $leche = $assignment?->scheduled_milk !== null ? (float) $assignment->scheduled_milk : $leche;
            $lecheRedondeo = $assignment?->scheduled_milk_rounded !== null ? (float) $assignment->scheduled_milk_rounded : $lecheRedondeo;
            $hojuela = $assignment?->scheduled_oat !== null ? (float) $assignment->scheduled_oat : $hojuela;
            $hojuelaRedondeo = $assignment?->scheduled_oat_rounded !== null ? (float) $assignment->scheduled_oat_rounded : $hojuelaRedondeo;

            $directive = $this->findDirective($association, $directivesByResolution);

            $active = $association->isHabilitado();
            $isInRoster = ! $roster || (bool) $rosterAssociation;

            return [
                'association_id' => $association->id,
                'activo' => $active,
                'incluido' => (bool) ($assignment?->included ?? ($active && $isInRoster)),
                'codigo' => $associationSnapshot['code'] ?? $association->officialCodeAt($endDate),
                'nombre' => mb_strtoupper($associationSnapshot['name'] ?? $association->name, 'UTF-8'),
                'presidenta' => $presidenta,
                'direccion' => $associationSnapshot['address'] ?? $association->address ?? '',
                'sector' => $associationSnapshot['sector_name'] ?? optional(optional($association->placeSector)->sector)->title ?? '',
                'primera_prioridad' => $primeraPrioridad,
                'segunda_prioridad' => $segundaPrioridad,
                'beneficiarios_base' => $baseBeneficiaries,
                'ajuste_beneficiarios' => $beneficiaryAdjustment,
                'total_beneficiarios' => $totalBenef,
                'bolsas' => $bolsas,
                'kilos' => 0,
                'leche' => $leche,
                'leche_redondeo' => $lecheRedondeo,
                'hojuela' => $hojuela,
                'hojuela_redondeo' => $hojuelaRedondeo,
                'racion' => $racionLabel,
                'fecha_entrega' => $pecosa ? date('d/m/Y', strtotime($pecosa->delivery_date)) : '',
                'recibe' => $presidenta,
                'dni' => $directive ? ($directive->partner ? ($directive->partner->people ? $directive->partner->people->dni : '') : '') : '',
            ];
        })->when(! $includeAll, fn ($rows) => $rows->where('incluido', true))->values()->toArray();
    }

    private function resolvePresidentName($association, $directivesByResolution)
    {
        $directive = $directivesByResolution->get($association->id);
        if ($directive && $directive->partner && $directive->partner->people) {
            return trim(collect([$directive->partner->people->names, $directive->partner->people->father_lastname, $directive->partner->people->mother_lastname])->filter()->implode(' '));
        }
        return $association->president_name ?? 'SIN ASIGNAR';
    }

    private function calculatePriorities($association, $startDate, $endDate, $rosterAssociation = null): array
    {
        if ($rosterAssociation) {
            $entries = $rosterAssociation->entries;

            return [
                $entries->count(),
                $entries->filter(fn ($entry) => (int) ($entry->beneficiary_snapshot['priority'] ?? 2) === 1)->count(),
                $entries->filter(fn ($entry) => (int) ($entry->beneficiary_snapshot['priority'] ?? 2) !== 1)->count(),
            ];
        }

        $partners = $this->distributions->partnersWithBeneficiariesAt($association, $endDate);

        $primeraPrioridad = 0;
        $segundaPrioridad = 0;
        $totalBenef = 0;

        foreach ($partners as $partner) {
            foreach ($partner->beneficiaries as $beneficiario) {
                $totalBenef++;

                if ((int) $beneficiario->priority === 1) {
                    $primeraPrioridad++;
                    continue;
                }

                if ((int) $beneficiario->priority === 2) {
                    $segundaPrioridad++;
                    continue;
                }

                $persona = $beneficiario->person;
                if (!$persona || !$persona->birthdate) {
                    $segundaPrioridad++;
                    continue;
                }

                $edadAnos = Carbon::parse($persona->birthdate)->diffInYears($endDate);

                if ($edadAnos < 2) {
                    $primeraPrioridad++;
                } else {
                    $segundaPrioridad++;
                }
            }
        }

        return [$totalBenef, $primeraPrioridad, $segundaPrioridad];
    }

    private function calculateScheduledProducts(
        int $beneficiaries,
        ?array $configuration,
        float $fallbackMilk,
        float $fallbackOat
    ): array {
        if (! $configuration) {
            return [
                round($fallbackMilk, 2),
                (int) round($fallbackMilk, 0, PHP_ROUND_HALF_UP),
                round($fallbackOat, 2),
                (int) round($fallbackOat, 0, PHP_ROUND_HALF_UP),
            ];
        }

        $days = (int) $configuration['service_days'];
        $milk = $beneficiaries * (float) $configuration['milk_grams_per_beneficiary'] * $days
            / (float) $configuration['milk_can_grams'];
        $oat = $beneficiaries * (float) $configuration['oat_grams_per_beneficiary'] * $days
            / (float) $configuration['oat_bag_grams'];

        return [
            round($milk, 2),
            (int) round($milk, 0, PHP_ROUND_HALF_UP),
            round($oat, 2),
            (int) round($oat, 0, PHP_ROUND_HALF_UP),
        ];
    }

    private function findDirective($association, $directivesByResolution)
    {
        return $directivesByResolution->get($association->id);
    }
}
