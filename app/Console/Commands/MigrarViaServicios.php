<?php

namespace App\Console\Commands;

use App\Console\Support\CommandReporter;
use App\Services\Maintenance\ServiceMigrationService;
use Illuminate\Console\Command;

/**
 * Migra Ingresos y Pecosas llamando a los metodos reales del sistema
 * (TransactionService::registerIngreso, PecosaService::createPecosa) en vez
 * de INSERT directo, para que corran las mismas reglas de negocio que usa
 * la app (descuento de stock, snapshot de asociacion/socia, etc).
 *
 * Fuente: JSON generados por migracion_productos/exportar_para_servicios.py
 * a partir de datos_sqlserver_v5.xlsx.
 */
class MigrarViaServicios extends Command
{
    protected $signature = 'migrar:via-servicios
        {--execute : Confirma los cambios. Sin esta opcion se hace rollback (dry-run).}
        {--ingresos=migracion_productos/servicios_ingresos.json}
        {--pecosas=migracion_productos/servicios_pecosas.json}
        {--reporte=migracion_productos/reporte_pendientes_servicios.csv}';

    protected $description = 'Migra ingresos y pecosas llamando a TransactionService::registerIngreso y PecosaService::createPecosa';

    public function handle(ServiceMigrationService $migration): int
    {
        $ingresosPath = base_path($this->option('ingresos'));
        $pecosasPath = base_path($this->option('pecosas'));

        if (!is_file($ingresosPath) || !is_file($pecosasPath)) {
            $this->error("Falta JSON de origen. Corre primero: migracion_productos\\env\\Scripts\\python.exe -X utf8 migracion_productos\\exportar_para_servicios.py");
            return self::FAILURE;
        }

        $ingresos = json_decode(file_get_contents($ingresosPath), true);
        $pecosas = json_decode(file_get_contents($pecosasPath), true);

        $execute = (bool) $this->option('execute');
        $this->info($execute ? 'Modo EJECUCION (--execute): se confirman los cambios.' : 'Modo DRY-RUN: al final se hace rollback. Usa --execute para confirmar.');

        try {
            $result = $migration->migrate($ingresos, $pecosas, $execute, new CommandReporter($this));
        } catch (\Throwable $e) {
            $this->newLine();
            $this->error('Error fatal, se revirtio todo: ' . $e->getMessage());
            return self::FAILURE;
        }

        $this->newLine(2);
        $this->table(
            ['Tipo', 'OK', 'Ya existia', 'Fallo'],
            [
                ['Ingresos', $result['ingresos']['ok'], $result['ingresos']['skip'], $result['ingresos']['fail']],
                ['Pecosas', $result['pecosas']['ok'], $result['pecosas']['skip'], $result['pecosas']['fail']],
            ]
        );

        $this->writeReporte($result['pendientes']);

        return self::SUCCESS;
    }

    private function writeReporte(array $pendientes): void
    {
        if (empty($pendientes)) {
            $this->info('Sin pendientes.');
            return;
        }

        $path = base_path($this->option('reporte'));
        $fh = fopen($path, 'w');
        fputcsv($fh, ['tipo', 'referencia', 'motivo']);
        foreach ($pendientes as $row) {
            fputcsv($fh, $row);
        }
        fclose($fh);

        $this->warn(count($pendientes) . " pendientes escritos en {$this->option('reporte')}");
    }
}
