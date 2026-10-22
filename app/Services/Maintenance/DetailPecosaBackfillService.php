<?php

namespace App\Services\Maintenance;

use App\Repositories\Contracts\DataMaintenanceRepositoryInterface;
use Illuminate\Support\Facades\DB;
use Throwable;

/**
 * Rellena detail_pecosas (hoy vacía) para las pecosas ya migradas.
 *
 * Por cada línea de producto del origen:
 *   1. Si ya existe un product_stocks vinculado a esa pecosa+producto, se usa
 *      su detail_product_id (dato real, prioridad máxima).
 *   2. Si no hay vínculo, se elige el lote (detail_products) que cubre la fecha
 *      de entrega. No crea nuevos product_stocks: no hay riesgo de descontar
 *      stock dos veces, detail_pecosas es solo registro/reporte.
 */
class DetailPecosaBackfillService
{
    public function __construct(private DataMaintenanceRepositoryInterface $data)
    {
    }

    /**
     * Sin $execute se hace rollback al final (dry-run). Ante un error fatal revierte todo y relanza.
     *
     * @return array{creadas:int, ya_existian:int, sin_pecosa:int, sin_lote:int, pendientes:array}
     *
     * @throws Throwable
     */
    public function backfill(array $pecosasSource, bool $execute, ProgressReporter $out): array
    {
        $lotsByProduct = $this->data->lotsGroupedByProduct();
        $stocksByPecosa = $this->data->stocksLinkedToPecosasGrouped();
        $pecosasByNumber = $this->data->pecosasByNumber(collect($pecosasSource)->pluck('pecosa_number'));
        $productsMeta = $this->data->productsMeta();
        $uomTitles = $this->data->uomTitles();

        $creadas = 0;
        $yaExistian = 0;
        $sinPecosa = 0;
        $sinLote = 0;
        $pendientes = [];

        DB::beginTransaction();

        try {
            $out->progressStart(count($pecosasSource));
            foreach ($pecosasSource as $row) {
                $pecosa = $pecosasByNumber->get($row['pecosa_number']);
                if (!$pecosa) {
                    $sinPecosa++;
                    $pendientes[] = ['sin_pecosa', $row['pecosa_number'], 'La pecosa no existe en la base'];
                    $out->progressAdvance();
                    continue;
                }

                if ($this->data->pecosaHasDetails($pecosa->id)) {
                    $yaExistian++;
                    $out->progressAdvance();
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
                            $pendientes[] = ['sin_lote', $row['pecosa_number'], "Sin lote para producto {$detail['_producto']} (product_id={$productId})"];
                            continue;
                        }
                        $detailProductId = $lot->id;
                        $unitPrice = (float) $lot->unit_price;
                    }

                    $product = $productsMeta->get($productId);

                    $this->data->createDetailPecosa([
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
                $out->progressAdvance();
            }
            $out->progressFinish();

            if ($execute) {
                DB::commit();
            } else {
                DB::rollBack();
            }
        } catch (Throwable $e) {
            DB::rollBack();

            throw $e;
        }

        return [
            'creadas' => $creadas,
            'ya_existian' => $yaExistian,
            'sin_pecosa' => $sinPecosa,
            'sin_lote' => $sinLote,
            'pendientes' => $pendientes,
        ];
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
}
