<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class DetailPecosaSeeder extends Seeder
{
    public function run(): void
    {
        $path = __DIR__ . '/data/detail_pecosas.json';
        if (! is_file($path)) {
            throw new \RuntimeException("No se encontro {$path}. Ejecuta migracion_productos/creando_seeders.py.");
        }

        $rows = json_decode(file_get_contents($path), true);
        if (! is_array($rows)) {
            throw new \RuntimeException("El archivo {$path} no contiene JSON valido.");
        }

        $pecosaMap = PecosaSeeder::$idMap;
        $productMap = DetailProductSeeder::$idMap;
        $missingPecosas = collect($rows)->pluck('source_pecosa_id')->unique()->diff(array_keys($pecosaMap));
        $missingProducts = collect($rows)->pluck('source_product_id')->unique()->diff(array_keys($productMap));
        if ($missingPecosas->isNotEmpty() || $missingProducts->isNotEmpty()) {
            throw new \RuntimeException(
                'Referencias sin resolver. PEC_id=' . $missingPecosas->implode(',') .
                '; PRO_id=' . $missingProducts->implode(',')
            );
        }

        $pecosaIds = array_values($pecosaMap);
        DB::table('detail_pecosas')->whereIn('pecosa_id', $pecosaIds)->delete();

        $now = now();
        $payload = [];
        foreach ($rows as $row) {
            $payload[] = [
                'priority' => $row['priority'],
                'quantity' => $row['quantity'],
                'delivered_quantity' => $row['delivered_quantity'],
                'unit_price' => $row['unit_price'],
                'subtotal' => $row['subtotal'],
                'detail_product_id' => $productMap[(int) $row['source_product_id']],
                'pecosa_id' => $pecosaMap[(int) $row['source_pecosa_id']],
                'product_name' => $row['product_name'],
                'product_abbreviation' => $row['product_abbreviation'],
                'uom_title' => $row['uom_title'],
                'created_at' => $now,
                'updated_at' => $now,
            ];
        }

        foreach (array_chunk($payload, 500) as $chunk) {
            DB::table('detail_pecosas')->insert($chunk);
        }

        $this->command->info('Detalles de PECOSA insertados: ' . count($rows));
    }
}
