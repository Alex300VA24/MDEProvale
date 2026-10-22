<?php

namespace App\Console\Commands;

use App\Console\Support\CommandReporter;
use App\Services\Maintenance\LegacyDateBackfillService;
use Illuminate\Console\Command;

/**
 * Corrección puntual de datos migrados: los socios y beneficiarios sin fecha
 * heredan la fecha de la resolución de su comité. La regla vive en
 * LegacyDateBackfillService.
 */
class BackfillLegacyPartnerDates extends Command
{
    protected $signature = 'data:backfill-legacy-dates {--dry-run : Muestra los cambios sin aplicarlos}';

    protected $description = 'Corrige fechas de socios/beneficiarios migrados heredando la fecha de la resolución de su comité';

    public function handle(LegacyDateBackfillService $backfill): int
    {
        $result = $backfill->run((bool) $this->option('dry-run'), new CommandReporter($this));

        $this->info("Socios actualizados: {$result['partners']}");
        $this->info("Historiales de beneficiarios actualizados: {$result['histories']}");
        if ($result['skipped'] > 0) {
            $this->warn("Comités sin resolución (omitidos): {$result['skipped']}");
        }

        return self::SUCCESS;
    }
}
