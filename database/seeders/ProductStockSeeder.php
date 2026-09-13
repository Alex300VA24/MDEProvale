<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class ProductStockSeeder extends Seeder
{
    public function run(): void
    {
        $path = __DIR__ . '/data/product_stocks.json';
        if (! is_file($path)) {
            throw new \RuntimeException("No se encontro {$path}. Ejecuta migracion_productos/creando_seeders.py.");
        }

        $rows = json_decode(file_get_contents($path), true);
        if (! is_array($rows)) {
            throw new \RuntimeException("El archivo {$path} no contiene JSON valido.");
        }

        $pecosaMap = PecosaSeeder::$idMap;
        $productMap = DetailProductSeeder::$idMap;
        $transactionMap = TransactionSeeder::$detailExitIdMap;
        $missingPecosas = collect($rows)->pluck('source_pecosa_id')->unique()->diff(array_keys($pecosaMap));
        $missingProducts = collect($rows)->pluck('source_product_id')->unique()->diff(array_keys($productMap));
        $missingTransactions = collect($rows)->pluck('source_detail_id')->unique()->diff(array_keys($transactionMap));
        if ($missingPecosas->isNotEmpty() || $missingProducts->isNotEmpty() || $missingTransactions->isNotEmpty()) {
            throw new \RuntimeException(
                'Referencias de stock sin resolver. PEC_id=' . $missingPecosas->implode(',') .
                '; PRO_id=' . $missingProducts->implode(',') .
                '; DPE_id=' . $missingTransactions->implode(',')
            );
        }

        DB::table('product_stocks')->whereIn('pecosa_id', array_values($pecosaMap))->delete();

        $now = now();
        $payload = [];
        foreach ($rows as $row) {
            $payload[] = [
                'detail_product_id' => $productMap[(int) $row['source_product_id']],
                'pecosa_id' => $pecosaMap[(int) $row['source_pecosa_id']],
                'transaction_id' => $transactionMap[(int) $row['source_detail_id']],
                'quantity' => $row['quantity'],
                'observation' => $row['observation'],
                'created_at' => $now,
                'updated_at' => $now,
            ];
        }

        foreach (array_chunk($payload, 500) as $chunk) {
            DB::table('product_stocks')->insert($chunk);
        }

        $this->command->info('Movimientos de stock insertados: ' . count($rows));
    }
}
