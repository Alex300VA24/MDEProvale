<?php

namespace App\Console\Commands;

use App\Console\Support\CommandReporter;
use App\Services\Maintenance\MonthlyDataRebuildService;
use Illuminate\Console\Command;

class RebuildMonthlyData extends Command
{
    protected $signature = 'data:rebuild
        {--month= : Mes a reconstruir (YYYY-MM)}
        {--all : Reconstruir todos los meses Mar-Ago 2026}
        {--execute : Ejecutar realmente (por defecto es dry-run)}
        {--excel= : Ruta al archivo Excel}';

    protected $description = 'Reconstruir PECOSAs, detail_pecosas, transactions y product_stocks desde Excel fuente';

    public function handle(MonthlyDataRebuildService $rebuild): int
    {
        $excelPath = $this->option('excel') ?? base_path('migracion_productos/datos_sqlserver_v5.xlsx');

        if (!file_exists($excelPath)) {
            $this->error("Excel no encontrado: {$excelPath}");
            return 1;
        }

        if ($this->option('all')) {
            $months = ['2026-03', '2026-04', '2026-05', '2026-06', '2026-07', '2026-08'];
        } elseif ($this->option('month')) {
            $months = [$this->option('month')];
        } else {
            $this->error('Especifique --month=YYYY-MM o --all');
            return 1;
        }

        $done = $rebuild->rebuild($excelPath, $months, !$this->option('execute'), new CommandReporter($this));

        return $done ? 0 : 1;
    }
}
