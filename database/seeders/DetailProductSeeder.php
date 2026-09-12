<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

/**
 * Carga los lotes generados por migracion_final_productos.py.
 * El orden del JSON conserva los IDs usados por los demás seeders.
 */
class DetailProductSeeder extends Seeder
{
    public function run(): void
    {
        $ruta = __DIR__ . '/data/detail_products.json';

        if (! is_file($ruta)) {
            throw new \RuntimeException("No se encontro {$ruta}. Ejecuta migracion_final_productos.py --execute.");
        }

        $filas = json_decode(file_get_contents($ruta), true);
        if (! is_array($filas)) {
            throw new \RuntimeException("El archivo {$ruta} no tiene un JSON valido.");
        }

        $ahora = now();
        $lote = [];
        $cutoff = '2026-01-01';

        foreach ($filas as $originalIndex => $fila) {
            $startDate = $fila[3] ?? '';
            if ($startDate < $cutoff) continue;

            $lote[] = [
                // Los demas JSON referencian el ID que tenia esta fila en el origen.
                'id' => $originalIndex + 1,
                'product_id' => $fila[0],
                'unit_price' => $fila[1],
                'quantity' => $fila[2],
                'start_date' => $startDate,
                'end_date' => $fila[4],
                'created_at' => $ahora,
                'updated_at' => $ahora,
            ];

            if (count($lote) >= 500) {
                DB::table('detail_products')->insert($lote);
                $lote = [];
            }
        }

        if ($lote) {
            DB::table('detail_products')->insert($lote);
        }

        // El último mes del JSON es el periodo operativo del seeder.
        $ultimaFecha = collect($filas)->pluck(4)->filter()->max();
        if ($ultimaFecha) {
            $inicioPeriodo = substr($ultimaFecha, 0, 7) . '-01';
            $finPeriodo = $ultimaFecha;

            DB::table('products')->update([
                'state_id' => 4,
                'updated_at' => $ahora,
            ]);

            DB::table('products')
                ->whereExists(function ($query) use ($inicioPeriodo, $finPeriodo) {
                    $query->select(DB::raw(1))
                        ->from('detail_products')
                        ->whereColumn('detail_products.product_id', 'products.id')
                        ->where('detail_products.start_date', '<=', $finPeriodo)
                        ->where('detail_products.end_date', '>=', $inicioPeriodo);
                })
                ->update([
                    'state_id' => 3,
                    'updated_at' => $ahora,
                ]);
        }
    }
}
