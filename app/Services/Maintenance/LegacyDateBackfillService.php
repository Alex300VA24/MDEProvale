<?php

namespace App\Services\Maintenance;

use App\Models\Association;
use App\Repositories\Contracts\DataMaintenanceRepositoryInterface;
use Illuminate\Support\Facades\DB;

/**
 * Corrección puntual de datos migrados (no un flujo permanente de la app):
 * un comité no puede existir sin su resolución de reconocimiento, así que la
 * fecha de esa resolución es la fecha real de origen del comité. Los socios y
 * beneficiarios migrados sin fecha (o con la fecha fija de siembra) la heredan:
 * Resolución -> Comité -> Beneficiario.
 *
 * Solo toca partners.date_begin cuando está en NULL y beneficiary_histories.date_begin
 * cuando tiene el valor fijo de siembra, así que no afecta registros reales creados
 * desde la aplicación (que siempre traen su propia fecha vía StorePartnerRequest).
 */
class LegacyDateBackfillService
{
    private const HISTORY_PLACEHOLDER_DATE = '2026-03-24';

    public function __construct(private DataMaintenanceRepositoryInterface $data)
    {
    }

    /**
     * @return array{partners:int, histories:int, skipped:int}
     */
    public function run(bool $dryRun, ProgressReporter $out): array
    {
        $partnersUpdated = 0;
        $historiesUpdated = 0;
        $skippedNoResolution = 0;

        DB::beginTransaction();

        foreach ($this->data->associationsWithResolutions() as $association) {
            $foundingDate = $this->foundingDateFor($association);

            if (!$foundingDate) {
                $skippedNoResolution++;
                continue;
            }

            $partnerIds = $this->data->partnerIdsWithoutBeginDate($association->id);

            if ($partnerIds->isEmpty()) {
                continue;
            }

            $out->line("Comité {$association->code} - {$association->name}: {$foundingDate} -> {$partnerIds->count()} socio(s)");

            if (!$dryRun) {
                $this->data->setPartnersBeginDate($partnerIds, $foundingDate);
            }
            $partnersUpdated += $partnerIds->count();

            $historyIds = $this->data->placeholderHistoryIds($partnerIds, self::HISTORY_PLACEHOLDER_DATE);

            if ($historyIds->isNotEmpty()) {
                if (!$dryRun) {
                    $this->data->setHistoriesBeginDate($historyIds, $foundingDate);
                }
                $historiesUpdated += $historyIds->count();
            }
        }

        if ($dryRun) {
            DB::rollBack();
            $out->warn('DRY RUN: ningún cambio fue aplicado.');
        } else {
            DB::commit();
        }

        return ['partners' => $partnersUpdated, 'histories' => $historiesUpdated, 'skipped' => $skippedNoResolution];
    }

    private function foundingDateFor(Association $association): ?string
    {
        $dates = collect();

        if ($association->resolution) {
            $dates->push($association->resolution->date_start);
        }

        foreach ($association->resolutionsHistory as $resolution) {
            $dates->push($resolution->date_start);
        }

        return $dates->filter()->sort()->first();
    }
}
