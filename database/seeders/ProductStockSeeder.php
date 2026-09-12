<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class ProductStockSeeder extends Seeder
{
    public function run(): void
    {
        $ruta = __DIR__ . '/data/product_stocks.json';
        if (!is_file($ruta)) {
            throw new \RuntimeException("No se encontro {$ruta}");
        }

        $filas = json_decode(file_get_contents($ruta), true);
        $pecosaMap = PecosaSeeder::$idMap;
        $detailProductIds = DB::table('detail_products')->pluck('id')->flip();
        $transactionIds = DB::table('transactions')->pluck('id')->flip();

        $ahora = now();
        $lote = [];
        $inserted = 0;

        foreach ($filas as $fila) {
            $originalPecosaId = $fila[1] ?? null;
            $detailProductId = $fila[0] ?? null;

            // Solo se conservan movimientos de las pecosas y lotes importados.
            if (! $originalPecosaId
                || ! isset($pecosaMap[$originalPecosaId])
                || ! isset($detailProductIds[$detailProductId])) {
                continue;
            }

            $newPecosaId = $pecosaMap[$originalPecosaId];
            $transactionId = $fila[2] ?? null;
            if ($transactionId && ! isset($transactionIds[$transactionId])) {
                $transactionId = null;
            }

            $lote[] = [
                'detail_product_id' => $detailProductId,
                'pecosa_id'         => $newPecosaId,
                'transaction_id'    => $transactionId,
                'quantity'          => $fila[3],
                'observation'       => $fila[4],
                'created_at'        => $ahora,
                'updated_at'        => $ahora,
            ];
            $inserted++;

            if (count($lote) >= 500) {
                DB::table('product_stocks')->insert($lote);
                $lote = [];
            }
        }

        if ($lote) {
            DB::table('product_stocks')->insert($lote);
        }

        $this->command->info("Product stocks insertados: {$inserted}");
    }
}
