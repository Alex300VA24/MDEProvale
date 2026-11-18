<?php

namespace App\Services;

use App\Exceptions\ProgramacionEntregaException;
use App\Models\MonthlyRoster;
use App\Repositories\Contracts\DistributionRepositoryInterface;
use Illuminate\Support\Facades\DB;

class ProgramacionEntregaService
{
    public function __construct(
        private SchedulingService $schedulingService,
        private ReparticionService $reparticionService,
        private DistributionRepositoryInterface $distributions,
        private MonthlyRosterService $monthlyRosters
    ) {
    }

    /**
     * Programación del período con totales, sectores y cálculo vigente.
     *
     * @throws ProgramacionEntregaException si el período no tiene registro o no tiene ingreso
     */
    public function build(int $year, int $month, string $sector = '', ?int $requestedRosterId = null): array
    {
        $this->assertPeriodHasRecord($year, $month);

        if (! $this->reparticionService->hasIngresoForPeriod($year, $month)) {
            throw $this->ingresoRequired($year, $month);
        }

        $savedPeriod = $this->distributions->periodWithAssignments($year, $month);
        $selectedRosterId = $requestedRosterId
            ?? $savedPeriod?->monthly_roster_id
            ?? $this->monthlyRosters->latestClosedForServicePeriod(sprintf('%04d-%02d-01', $year, $month))?->id;
        if ($selectedRosterId && ! $this->isUsableRoster($selectedRosterId, $year, $month)) {
            throw new ProgramacionEntregaException('Seleccione un padrón cerrado del mismo mes o de un mes anterior.');
        }
        $allRows = collect($this->schedulingService->generateProgramacionEntrega($month, $year, null, true, $selectedRosterId));
        $racion = $this->reparticionService->getActiveRacion($year, $month);
        $calculation = null;
        if ($racion) {
            $configuration = $this->reparticionService->getConfiguration($racion, $year, $month);
            unset($configuration['assignments']);
            $calculation = $configuration;
        }
        $sectors = $allRows->pluck('sector')->filter()->unique()->sort()->values();
        $rows = $sector === ''
            ? $allRows
            : $allRows->where('sector', $sector)->values();

        $used = $rows->where('incluido', true);

        return [
            'data' => $rows->values(),
            'sectors' => $sectors,
            'summary' => [
                'committees' => $used->count(),
                'beneficiaries' => $used->sum('total_beneficiarios'),
                'milk' => $used->sum('leche'),
                'milk_rounded' => $used->sum('leche_redondeo'),
                'oat' => $used->sum('hojuela'),
                'oat_rounded' => $used->sum('hojuela_redondeo'),
            ],
            'period' => ['year' => $year, 'month' => $month],
            'calculation' => $calculation,
            'roster_selection' => [
                'selected_id' => $selectedRosterId,
                'confirmed' => (bool) $savedPeriod?->monthly_roster_id
                    && (int) $savedPeriod->monthly_roster_id === (int) $selectedRosterId,
                'options' => $this->monthlyRosters->closedOptions(),
            ],
        ];
    }

    /**
     * Guarda los valores editados de la programación (ajuste de beneficiarios y cantidades).
     *
     * @param  array<int, array<string, mixed>>  $rows
     * @throws ProgramacionEntregaException
     */
    public function save(int $year, int $month, array $rows, ?int $monthlyRosterId = null): void
    {
        $this->assertPeriodHasRecord($year, $month);

        $racion = $this->reparticionService->getActiveRacion($year, $month);
        if (! $racion) {
            throw new ProgramacionEntregaException('No hay ración configurada para el período seleccionado.');
        }

        if (! $this->reparticionService->hasIngresoForPeriod($year, $month)) {
            throw $this->ingresoRequired($year, $month);
        }

        $monthlyRosterId ??= $this->monthlyRosters->latestClosedForServicePeriod(sprintf('%04d-%02d-01', $year, $month))?->id;
        if (! $monthlyRosterId && MonthlyRoster::exists()) {
            throw new ProgramacionEntregaException('Debe existir y seleccionarse un padrón cerrado para guardar la programación.');
        }
        if ($monthlyRosterId && ! $this->isUsableRoster($monthlyRosterId, $year, $month)) {
            throw new ProgramacionEntregaException('Seleccione un padrón cerrado del mismo mes o de un mes anterior.');
        }

        $baseRows = collect($this->schedulingService->generateProgramacionEntrega($month, $year, null, true, $monthlyRosterId))
            ->keyBy('association_id');
        $unknownAssociation = collect($rows)
            ->contains(fn ($row) => ! $baseRows->has($row['association_id']));
        if ($unknownAssociation) {
            throw new ProgramacionEntregaException('Uno de los comités no pertenece a la programación seleccionada.');
        }

        $configuration = $this->reparticionService->getConfiguration($racion, $year, $month);

        DB::transaction(function () use ($rows, $year, $month, $configuration, $baseRows, $monthlyRosterId) {
            $period = $this->distributions->firstOrCreatePeriod($year, $month, [
                'service_days' => $configuration['service_days'],
                'milk_grams_per_beneficiary' => $configuration['milk_grams_per_beneficiary'],
                'oat_grams_per_beneficiary' => $configuration['oat_grams_per_beneficiary'],
                'milk_can_grams' => $configuration['milk_can_grams'],
                'oat_bag_grams' => $configuration['oat_bag_grams'],
                'milk_cans_per_box' => $configuration['milk_cans_per_box'],
                'oat_kg_per_sack' => $configuration['oat_kg_per_sack'],
                'monthly_roster_id' => $monthlyRosterId,
            ]);
            $this->distributions->setPeriodRoster($period, $monthlyRosterId);

            foreach ($rows as $row) {
                $baseBeneficiaries = (int) $baseRows->get($row['association_id'])['beneficiarios_base'];
                $this->distributions->upsertAssignment($period->id, $row['association_id'], [
                    'beneficiary_adjustment' => (int) $row['total_beneficiarios'] - $baseBeneficiaries,
                    'included' => $row['incluido'] ?? null,
                    'scheduled_milk' => round((float) $row['leche'], 2),
                    'scheduled_milk_rounded' => (int) $row['leche_redondeo'],
                    'scheduled_oat' => round((float) $row['hojuela'], 2),
                    'scheduled_oat_rounded' => (int) $row['hojuela_redondeo'],
                ]);
            }
        });
    }

    private function assertPeriodHasRecord(int $year, int $month): void
    {
        if ($this->reparticionService->isBeforeFirstRecordedPeriod($year, $month)) {
            throw new ProgramacionEntregaException(
                $this->reparticionService->periodRecordNotFoundMessage($year, $month),
                404,
                'PERIOD_NOT_FOUND'
            );
        }
    }

    private function isUsableRoster(int $rosterId, int $year, int $month): bool
    {
        return MonthlyRoster::query()
            ->whereKey($rosterId)
            ->where('status', MonthlyRoster::CLOSED)
            ->whereDate('period', '<=', sprintf('%04d-%02d-01', $year, $month))
            ->exists();
    }

    private function ingresoRequired(int $year, int $month): ProgramacionEntregaException
    {
        return new ProgramacionEntregaException(
            $this->reparticionService->ingresoRequiredMessage($year, $month),
            422,
            'INGRESO_REQUIRED'
        );
    }
}
