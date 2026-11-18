<?php

namespace Database\Seeders;

use App\Services\MonthlyRosterService;
use Illuminate\Database\Seeder;

class MonthlyRosterSeeder extends Seeder
{
    /**
     * Periodos cuyo padrón aún está incompleto: se crean en borrador, no cerrados.
     * Quitar el periodo de la lista cuando llegue el padrón definitivo.
     */
    private const DRAFT_PERIODS = ['2026-11-01'];

    public function run(): void
    {
        $created = app(MonthlyRosterService::class)->bootstrapHistoricalPeriods(self::DRAFT_PERIODS);
        $this->command?->info("Fotografías mensuales creadas: {$created}");
    }
}
