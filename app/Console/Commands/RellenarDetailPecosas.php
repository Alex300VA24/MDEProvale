<?php

namespace App\Console\Commands;

use App\Console\Support\CommandReporter;
use App\Services\Maintenance\DetailPecosaBackfillService;
use Illuminate\Console\Command;

/**
 * Rellena detail_pecosas (hoy vacia) para las pecosas ya migradas.
 *
 * Las pecosas, transactions (salida) y product_stocks ya existen en la base
 * (migradas antes con migracion_final_productos.py). La regla de relleno vive
 * en DetailPecosaBackfillService.
 */
class RellenarDetailPecosas extends Command
{
    protected $signature = 'migrar:rellenar-detail-pecosas
        {--execute : Confirma los cambios. Sin esta opcion se hace rollback (dry-run).}
        {--pecosas=migracion_productos/servicios_pecosas.json}
        {--reporte=migracion_productos/reporte_pendientes_detail_pecosas.csv}';

    protected $description = 'Rellena detail_pecosas para pecosas ya migradas, usando product_stocks existente o FIFO por fecha de entrega';

    public function handle(DetailPecosaBackfillService $backfill): int
    {
        $path = base_path($this->option('pecosas'));
        if (!is_file($path)) {
            $this->error("Falta JSON de origen. Corre primero: migracion_productos\\env\\Scripts\\python.exe -X utf8 migracion_productos\\exportar_para_servicios.py");
            return self::FAILURE;
        }

        $pecosasSource = json_decode(file_get_contents($path), true);
        $execute = (bool) $this->option('execute');
        $this->info($execute ? 'Modo EJECUCION (--execute): se confirman los cambios.' : 'Modo DRY-RUN: al final se hace rollback. Usa --execute para confirmar.');

        try {
            $result = $backfill->backfill($pecosasSource, $execute, new CommandReporter($this));
        } catch (\Throwable $e) {
            $this->newLine();
            $this->error('Error fatal, se revirtio todo: ' . $e->getMessage());
            return self::FAILURE;
        }

        $this->newLine(2);
        $this->table(
            ['detail_pecosas creadas', 'Pecosas ya tenian detalle', 'Pecosa no existe', 'Lineas sin lote'],
            [[$result['creadas'], $result['ya_existian'], $result['sin_pecosa'], $result['sin_lote']]]
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
