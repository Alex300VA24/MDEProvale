<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class TransactionSeeder extends Seeder
{
    public function run(): void
    {
        $ruta = __DIR__ . '/data/transactions.json';
        if (!is_file($ruta)) {
            $this->command->warn("No se encontro {$ruta}. Saltando transacciones.");
            return;
        }

        $filas = json_decode(file_get_contents($ruta), true);
        $cutoff = '2026-02-01';
        $ahora = now();
        $lote = [];
        $inserted = 0;

        foreach ($filas as $originalIndex => $fila) {
            $date = $fila[4] ?? '';
            if ($date < $cutoff) continue;

            $lote[] = [
                // product_stocks conserva referencias a los IDs del sistema origen.
                'id'                  => $originalIndex + 1,
                'quantity'            => $fila[0],
                'unit_price'          => $fila[1],
                'total_price'         => $fila[2],
                'document_number'     => $fila[3],
                'transaction_date'    => $date,
                'detail_product_id'   => $fila[5],
                'type_transaction_id' => $fila[6],
                'product_name'        => $fila[7] ?? null,
                'uom_title'           => $fila[8] ?? null,
                'created_at'          => $ahora,
                'updated_at'          => $ahora,
            ];
            $inserted++;

            if (count($lote) >= 500) {
                DB::table('transactions')->insert($lote);
                $lote = [];
            }
        }

        if ($lote) {
            DB::table('transactions')->insert($lote);
        }

        $this->command->info("Transacciones insertadas (>= {$cutoff}): {$inserted}");
    }
}
