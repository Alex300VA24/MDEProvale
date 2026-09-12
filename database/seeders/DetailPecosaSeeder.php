<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class DetailPecosaSeeder extends Seeder
{
    public function run(): void
    {
        $ruta = __DIR__ . '/data/detail_pecosas.json';
        if (!is_file($ruta)) {
            throw new \RuntimeException("No se encontro {$ruta}");
        }

        $filas = json_decode(file_get_contents($ruta), true);
        $idMap = PecosaSeeder::$idMap;

        $ahora = now();
        $lote = [];
        $inserted = 0;

        foreach ($filas as $fila) {
            $originalPecosaId = $fila[6] ?? 0;
            if (!isset($idMap[$originalPecosaId])) continue;

            $lote[] = [
                'priority'             => $fila[0],
                'quantity'             => $fila[1],
                'delivered_quantity'   => $fila[2],
                'unit_price'           => $fila[3],
                'subtotal'             => $fila[4],
                'detail_product_id'    => $fila[5],
                'pecosa_id'            => $idMap[$originalPecosaId],
                'product_name'         => $fila[7],
                'product_abbreviation' => $fila[8] ?? null,
                'uom_title'            => $fila[9] ?? null,
                'created_at'           => $ahora,
                'updated_at'           => $ahora,
            ];
            $inserted++;

            if (count($lote) >= 500) {
                DB::table('detail_pecosas')->insert($lote);
                $lote = [];
            }
        }

        if ($lote) {
            DB::table('detail_pecosas')->insert($lote);
        }

        $this->command->info("Detail pecosas insertados: {$inserted}");
    }
}
