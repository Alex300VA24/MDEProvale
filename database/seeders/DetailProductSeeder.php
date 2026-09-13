<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class DetailProductSeeder extends Seeder
{
    /** @var array<int, int> PRO_id de SQL Server => detail_products.id */
    public static array $idMap = [];

    public function run(): void
    {
        $path = __DIR__ . '/data/detail_products.json';
        if (! is_file($path)) {
            throw new \RuntimeException("No se encontro {$path}. Ejecuta migracion_productos/creando_seeders.py.");
        }

        $rows = json_decode(file_get_contents($path), true);
        if (! is_array($rows)) {
            throw new \RuntimeException("El archivo {$path} no contiene JSON valido.");
        }

        self::$idMap = [];
        $now = now();
        foreach ($rows as $row) {
            $productId = DB::table('products')->where('title', $row['product_title'])->value('id');
            if (! $productId) {
                throw new \RuntimeException("Producto destino no encontrado: {$row['product_title']}");
            }

            DB::table('detail_products')->updateOrInsert(
                [
                    'product_id' => $productId,
                    'start_date' => $row['start_date'],
                    'end_date' => $row['end_date'],
                ],
                [
                    'unit_price' => $row['unit_price'],
                    'quantity' => $row['quantity'],
                    'created_at' => $now,
                    'updated_at' => $now,
                ]
            );

            $id = DB::table('detail_products')
                ->where('product_id', $productId)
                ->where('start_date', $row['start_date'])
                ->where('end_date', $row['end_date'])
                ->value('id');
            self::$idMap[(int) $row['source_product_id']] = (int) $id;
        }

        $lastDate = collect($rows)->pluck('end_date')->filter()->max();
        if ($lastDate) {
            $periodStart = substr($lastDate, 0, 7) . '-01';
            DB::table('products')->update(['state_id' => 4, 'updated_at' => $now]);
            DB::table('products')
                ->whereExists(function ($query) use ($periodStart, $lastDate) {
                    $query->select(DB::raw(1))
                        ->from('detail_products')
                        ->whereColumn('detail_products.product_id', 'products.id')
                        ->where('detail_products.start_date', '<=', $lastDate)
                        ->where('detail_products.end_date', '>=', $periodStart);
                })
                ->update(['state_id' => 3, 'updated_at' => $now]);
        }

        $this->command->info('Lotes insertados: ' . count($rows));
    }
}
