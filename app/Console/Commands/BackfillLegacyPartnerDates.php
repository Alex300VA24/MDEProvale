<?php

namespace App\Console\Commands;

use App\Models\Association;
use App\Models\BeneficiaryHistory;
use App\Models\Partner;
use Illuminate\Console\Command;
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
class BackfillLegacyPartnerDates extends Command
{
    protected $signature = 'data:backfill-legacy-dates {--dry-run : Muestra los cambios sin aplicarlos}';

    protected $description = 'Corrige fechas de socios/beneficiarios migrados heredando la fecha de la resolución de su comité';

    private const HISTORY_PLACEHOLDER_DATE = '2026-03-24';

    public function handle(): int
    {
        $dryRun = (bool) $this->option('dry-run');

        $associations = Association::with(['resolution', 'resolutionsHistory'])->get();

        $partnersUpdated = 0;
        $historiesUpdated = 0;
        $skippedNoResolution = 0;

        DB::beginTransaction();

        foreach ($associations as $association) {
            $foundingDate = $this->foundingDateFor($association);

            if (!$foundingDate) {
                $skippedNoResolution++;
                continue;
            }

            $partnerIds = Partner::where('association_id', $association->id)
                ->whereNull('date_begin')
                ->pluck('id');

            if ($partnerIds->isEmpty()) {
                continue;
            }

            $this->line("Comité {$association->code} - {$association->name}: {$foundingDate} -> {$partnerIds->count()} socio(s)");

            if (!$dryRun) {
                Partner::whereIn('id', $partnerIds)->update(['date_begin' => $foundingDate]);
            }
            $partnersUpdated += $partnerIds->count();

            $historyIds = BeneficiaryHistory::whereHas('beneficiary', fn ($q) => $q->whereIn('partner_id', $partnerIds))
                ->where('date_begin', self::HISTORY_PLACEHOLDER_DATE)
                ->pluck('id');

            if ($historyIds->isNotEmpty()) {
                if (!$dryRun) {
                    BeneficiaryHistory::whereIn('id', $historyIds)->update(['date_begin' => $foundingDate]);
                }
                $historiesUpdated += $historyIds->count();
            }
        }

        if ($dryRun) {
            DB::rollBack();
            $this->warn('DRY RUN: ningún cambio fue aplicado.');
        } else {
            DB::commit();
        }

        $this->info("Socios actualizados: {$partnersUpdated}");
        $this->info("Historiales de beneficiarios actualizados: {$historiesUpdated}");
        if ($skippedNoResolution > 0) {
            $this->warn("Comités sin resolución (omitidos): {$skippedNoResolution}");
        }

        return self::SUCCESS;
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
