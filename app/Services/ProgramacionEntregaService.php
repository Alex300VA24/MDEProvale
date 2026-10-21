<?php

namespace App\Services;

use App\Exceptions\ProgramacionEntregaException;
use App\Repositories\Contracts\DistributionRepositoryInterface;
use Illuminate\Support\Facades\DB;

class ProgramacionEntregaService
{
    public function __construct(
        private SchedulingService $schedulingService,
        private ReparticionService $reparticionService,
        private DistributionRepositoryInterface $distributions
    ) {
    }

    /**
     * Programación del período con totales, sectores y cálculo vigente.
     *
     * @throws ProgramacionEntregaException si el período no tiene registro o no tiene ingreso
     */
    public function build(int $year, int $month, string $sector = ''): array
    {
        $this->assertPeriodHasRecord($year, $month);

        if (! $this->reparticionService->hasIngresoForPeriod($year, $month)) {
            throw $this->ingresoRequired($year, $month);
        }

        $allRows = collect($this->schedulingService->generateProgramacionEntrega($month, $year));
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

        return [
            'data' => $rows->values(),
            'sectors' => $sectors,
            'summary' => [
                'committees' => $rows->count(),
                'beneficiaries' => $rows->sum('total_beneficiarios'),
                'milk' => $rows->sum('leche'),
                'milk_rounded' => $rows->sum('leche_redondeo'),
                'oat' => $rows->sum('hojuela'),
                'oat_rounded' => $rows->sum('hojuela_redondeo'),
            ],
            'period' => ['year' => $year, 'month' => $month],
            'calculation' => $calculation,
        ];
    }

    /**
     * Guarda los valores editados de la programación (ajuste de beneficiarios y cantidades).
     *
     * @param  array<int, array<string, mixed>>  $rows
     * @throws ProgramacionEntregaException
     */
    public function save(int $year, int $month, array $rows): void
    {
        $this->assertPeriodHasRecord($year, $month);

        $racion = $this->reparticionService->getActiveRacion($year, $month);
        if (! $racion) {
            throw new ProgramacionEntregaException('No hay ración configurada para el período seleccionado.');
        }

        if (! $this->reparticionService->hasIngresoForPeriod($year, $month)) {
            throw $this->ingresoRequired($year, $month);
        }

        $baseRows = collect($this->schedulingService->generateProgramacionEntrega($month, $year))
            ->keyBy('association_id');
        $unknownAssociation = collect($rows)
            ->contains(fn ($row) => ! $baseRows->has($row['association_id']));
        if ($unknownAssociation) {
            throw new ProgramacionEntregaException('Uno de los comités no pertenece a la programación seleccionada.');
        }

        $configuration = $this->reparticionService->getConfiguration($racion, $year, $month);

        DB::transaction(function () use ($rows, $year, $month, $configuration, $baseRows) {
            $period = $this->distributions->firstOrCreatePeriod($year, $month, [
                'service_days' => $configuration['service_days'],
                'milk_grams_per_beneficiary' => $configuration['milk_grams_per_beneficiary'],
                'oat_grams_per_beneficiary' => $configuration['oat_grams_per_beneficiary'],
                'milk_can_grams' => $configuration['milk_can_grams'],
                'oat_bag_grams' => $configuration['oat_bag_grams'],
                'milk_cans_per_box' => $configuration['milk_cans_per_box'],
                'oat_kg_per_sack' => $configuration['oat_kg_per_sack'],
            ]);

            foreach ($rows as $row) {
                $baseBeneficiaries = (int) $baseRows->get($row['association_id'])['beneficiarios_base'];
                $this->distributions->upsertAssignment($period->id, $row['association_id'], [
                    'beneficiary_adjustment' => (int) $row['total_beneficiarios'] - $baseBeneficiaries,
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

    private function ingresoRequired(int $year, int $month): ProgramacionEntregaException
    {
        return new ProgramacionEntregaException(
            $this->reparticionService->ingresoRequiredMessage($year, $month),
            422,
            'INGRESO_REQUIRED'
        );
    }
}
