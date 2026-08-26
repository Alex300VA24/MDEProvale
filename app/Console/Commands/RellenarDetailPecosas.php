<?php

namespace App\Console\Commands;

use App\Models\DetailPecosa;
use App\Models\DetailProduct;
use App\Models\Pecosa;
use App\Models\ProductStock;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

/**
 * Rellena detail_pecosas (hoy vacia) para las pecosas ya migradas.
 *
 * Las pecosas, transactions (salida) y product_stocks ya existen en la base
 * (migradas antes con migracion_final_productos.py). Por cada linea de
 * producto de detail_pecosas en el Excel (servicios_pecosas.json):
 *   1. Si ya existe un product_stocks vinculado a esa pecosa+producto, se usa
 *      su detail_product_id (dato real, prioridad maxima).
 *   2. Si no hay vinculo (pasa en ~6700 de 13924 lineas), se elige el lote
 *      (detail_products) que cubre la fecha de entrega, igual criterio que
 *      PecosaService::createPecosa usaria. No crea nuevos product_stocks: no
 *      hay riesgo de descontar stock dos veces, detail_pecosas es solo
 *      registro/reporte.
 */
class RellenarDetailPecosas extends Command
{
    protected $signature = 'migrar:rellenar-detail-pecosas
        {--execute : Confirma los cambios. Sin esta opcion se hace rollback (dry-run).}
        {--pecosas=migracion_productos/servicios_pecosas.json}
        {--reporte=migracion_productos/reporte_pendientes_detail_pecosas.csv}';

    protected $description = 'Rellena detail_pecosas para pecosas ya migradas, usando product_stocks existente o FIFO por fecha de entrega';

    private array $pendientes = [];

    public function handle(): int
    {
        $path = base_path($this->option('pecosas'));
        if (!is_file($path)) {
            $this->error("Falta JSON de origen. Corre primero: migracion_productos\\env\\Scripts\\python.exe -X utf8 migracion_productos\\exportar_para_servicios.py");
            return self::FAILURE;
        }

        $pecosasSource = json_decode(file_get_contents($path), true);
        $execute = (bool) $this->option('execute');
        $this->info($execute ? 'Modo EJECUCION (--execute): se confirman los cambios.' : 'Modo DRY-RUN: al final se hace rollback. Usa --execute para confirmar.');

        // Lotes por producto, ordenados por vigencia.
        $lotsByProduct = DetailProduct::orderBy('start_date')->orderBy('id')->get()
            ->groupBy('product_id');

        // product_stocks ya vinculados a una pecosa, agrupados por pecosa_id.
        $stocksByPecosa = ProductStock::whereNotNull('pecosa_id')
            ->with('detailProduct:id,product_id,unit_price')
            ->get()
            ->groupBy('pecosa_id');

        $pecosaNumbers = collect($pecosasSource)->pluck('pecosa_number');
        $pecosasById = Pecosa::whereIn('pecosa_number', $pecosaNumbers)
            ->get(['id', 'pecosa_number', 'delivery_date'])
            ->keyBy('pecosa_number');

        $productsMeta = DB::table('products')->select('id', 'title', 'abbreviation', 'uom_id')
            ->get()->keyBy('id');
        $uomTitles = DB::table('uoms')->pluck('title', 'id');

        $creadas = 0;
        $yaExistian = 0;
        $sinPecosa = 0;
        $sinLote = 0;

        DB::beginTransaction();

        try {
            $this->output->progressStart(count($pecosasSource));
            foreach ($pecosasSource as $row) {
                $pecosa = $pecosasById->get($row['pecosa_number']);
                if (!$pecosa) {
                    $sinPecosa++;
                    $this->pendientes[] = ['sin_pecosa', $row['pecosa_number'], 'La pecosa no existe en la base'];
                    $this->output->progressAdvance();
                    continue;
                }

                if (DetailPecosa::where('pecosa_id', $pecosa->id)->exists()) {
                    $yaExistian++;
                    $this->output->progressAdvance();
                    continue;
                }

                $stocksDisponibles = ($stocksByPecosa->get($pecosa->id) ?? collect())->values();
                $usados = [];

                foreach ($row['details'] as $index => $detail) {
                    $productId = $detail['product_id'];
                    $quantity = $detail['quantity'];

                    $stock = $stocksDisponibles->first(function ($s, $i) use ($productId, $usados) {
                        return !in_array($i, $usados, true)
                            && $s->detailProduct
                            && $s->detailProduct->product_id === $productId;
                    });

                    if ($stock) {
                        $usados[] = $stocksDisponibles->search($stock);
                        $detailProductId = $stock->detail_product_id;
                        $unitPrice = (float) $stock->detailProduct->unit_price;
                    } else {
                        $lot = $this->pickLotForDate($lotsByProduct->get($productId), $pecosa->delivery_date);
                        if (!$lot) {
                            $sinLote++;
                            $this->pendientes[] = ['sin_lote', $row['pecosa_number'], "Sin lote para producto {$detail['_producto']} (product_id={$productId})"];
                            continue;
                        }
                        $detailProductId = $lot->id;
                        $unitPrice = (float) $lot->unit_price;
                    }

                    $product = $productsMeta->get($productId);

                    DetailPecosa::create([
                        'pecosa_id' => $pecosa->id,
                        'detail_product_id' => $detailProductId,
                        'quantity' => $quantity,
                        'delivered_quantity' => $quantity,
                        'unit_price' => $unitPrice,
                        'subtotal' => round($quantity * $unitPrice, 2),
                        'priority' => $index + 1,
                        'product_name' => $product->title ?? $detail['_producto'],
                        'product_abbreviation' => $product->abbreviation ?? null,
                        'uom_title' => $product ? ($uomTitles[$product->uom_id] ?? null) : null,
                    ]);
                    $creadas++;
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
            ['detail_pecosas creadas', 'Pecosas ya tenian detalle', 'Pecosa no existe', 'Lineas sin lote'],
            [[$creadas, $yaExistian, $sinPecosa, $sinLote]]
        );

        $this->writeReporte();

        return self::SUCCESS;
    }

    private function pickLotForDate($lots, ?string $deliveryDate)
    {
        if (!$lots || $lots->isEmpty()) {
            return null;
        }
        if (!$deliveryDate) {
            return $lots->first();
        }

        $covering = $lots->first(fn ($l) => $l->start_date->toDateString() <= $deliveryDate && $l->end_date->toDateString() >= $deliveryDate);
        if ($covering) {
            return $covering;
        }

        $past = $lots->filter(fn ($l) => $l->start_date->toDateString() <= $deliveryDate)->last();
        return $past ?: $lots->first();
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
