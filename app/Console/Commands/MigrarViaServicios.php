<?php

namespace App\Console\Commands;

use App\Models\DetailProduct;
use App\Models\Pecosa;
use App\Models\Transaction;
use App\Services\PecosaService;
use App\Services\TransactionService;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

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

    private array $pendientes = [];

    public function handle(TransactionService $transactionService, PecosaService $pecosaService): int
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

        $ingresosOk = 0;
        $ingresosSkip = 0;
        $ingresosFail = 0;
        $pecosasOk = 0;
        $pecosasSkip = 0;
        $pecosasFail = 0;

        DB::beginTransaction();

        try {
            $this->output->progressStart(count($ingresos));
            foreach ($ingresos as $row) {
                $exists = Transaction::whereHas('typeTransaction', fn ($q) => $q->whereRaw('LOWER(title) = ?', ['ingreso']))
                    ->whereHas('detailProduct', function ($q) use ($row) {
                        $q->where('product_id', $row['product_id'])
                            ->where('quantity', $row['quantity'])
                            ->where('unit_price', $row['unit_price'])
                            ->where('start_date', $row['start_date']);
                    })
                    ->exists();

                if ($exists) {
                    $ingresosSkip++;
                    $this->output->progressAdvance();
                    continue;
                }

                try {
                    $transactionService->registerIngreso([
                        'product_id' => $row['product_id'],
                        'quantity' => $row['quantity'],
                        'unit_price' => $row['unit_price'],
                        'start_date' => $row['start_date'],
                        'end_date' => $row['end_date'],
                        'transaction_date' => $row['start_date'],
                        'document_number' => null,
                    ]);
                    $ingresosOk++;
                } catch (\Throwable $e) {
                    $ingresosFail++;
                    $this->pendientes[] = ['ingreso', $row['_producto'] . ' (' . $row['start_date'] . ')', $e->getMessage()];
                }
                $this->output->progressAdvance();
            }
            $this->output->progressFinish();

            $this->output->progressStart(count($pecosas));
            foreach ($pecosas as $row) {
                if (Pecosa::where('pecosa_number', $row['pecosa_number'])->exists()) {
                    $pecosasSkip++;
                    $this->output->progressAdvance();
                    continue;
                }

                $details = [];
                $motivoFalloLote = null;
                foreach ($row['details'] as $detail) {
                    $lot = $this->pickFifoLot($detail['product_id'], (float) $detail['quantity']);
                    if ($lot === null) {
                        $motivoFalloLote = "Sin lote con stock suficiente para {$detail['_producto']} (cant. {$detail['quantity']})";
                        break;
                    }
                    $details[] = [
                        'detail_product_id' => $lot,
                        'quantity' => $detail['quantity'],
                    ];
                }

                if ($motivoFalloLote !== null) {
                    $pecosasFail++;
                    $this->pendientes[] = ['pecosa', $row['pecosa_number'], $motivoFalloLote];
                    $this->output->progressAdvance();
                    continue;
                }

                try {
                    $pecosaService->createPecosa([
                        'pecosa_number' => $row['pecosa_number'],
                        'observation' => $row['observation'],
                        'delivery_date' => $row['delivery_date'],
                        'state_id' => $row['state_id'],
                        'association_id' => $row['association_id'],
                        'managing_partner_id' => $row['managing_partner_id'],
                        'details' => $details,
                    ]);
                    $pecosasOk++;
                } catch (\Throwable $e) {
                    $pecosasFail++;
                    $this->pendientes[] = ['pecosa', $row['pecosa_number'], $e->getMessage()];
                }
                $this->output->progressAdvance();
            }
            $this->output->progressFinish();

            if ($execute) {
                DB::commit();
            } else {
                DB::rollBack();
            }
        } catch (\Throwable $e) {
            DB::rollBack();
            $this->newLine();
            $this->error('Error fatal, se revirtio todo: ' . $e->getMessage());
            return self::FAILURE;
        }

        $this->newLine(2);
        $this->table(
            ['Tipo', 'OK', 'Ya existia', 'Fallo'],
            [
                ['Ingresos', $ingresosOk, $ingresosSkip, $ingresosFail],
                ['Pecosas', $pecosasOk, $pecosasSkip, $pecosasFail],
            ]
        );

        $this->writeReporte();

        return self::SUCCESS;
    }

    private function pickFifoLot(int $productId, float $needed): ?int
    {
        $lots = DetailProduct::where('product_id', $productId)
            ->withSum('stocks as used_quantity', 'quantity')
            ->orderBy('start_date')
            ->orderBy('id')
            ->get();

        foreach ($lots as $lot) {
            $available = (float) $lot->quantity - (float) ($lot->used_quantity ?? 0);
            if ($available >= $needed) {
                return $lot->id;
            }
        }

        return null;
    }

    private function writeReporte(): void
    {
        if (empty($this->pendientes)) {
            $this->info('Sin pendientes.');
            return;
        }

        $path = base_path($this->option('reporte'));
        $fh = fopen($path, 'w');
        fputcsv($fh, ['tipo', 'referencia', 'motivo']);
        foreach ($this->pendientes as $row) {
            fputcsv($fh, $row);
        }
        fclose($fh);

        $this->warn(count($this->pendientes) . " pendientes escritos en {$this->option('reporte')}");
    }
}
