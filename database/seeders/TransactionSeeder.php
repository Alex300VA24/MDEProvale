<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class TransactionSeeder extends Seeder
{
    /** @var array<int, int> DPE_id de SQL Server => transactions.id de salida */
    public static array $detailExitIdMap = [];

    public function run(): void
    {
        $path = __DIR__ . '/data/transactions.json';
        if (! is_file($path)) {
            throw new \RuntimeException("No se encontro {$path}. Ejecuta migracion_productos/creando_seeders.py.");
        }

        $rows = json_decode(file_get_contents($path), true);
        if (! is_array($rows)) {
            throw new \RuntimeException("El archivo {$path} no contiene JSON valido.");
        }

        $productMap = DetailProductSeeder::$idMap;
        $pecosaMap = PecosaSeeder::$idMap;
        $typeIds = DB::table('type_transactions')->pluck('id', 'title');

        foreach (['Ingreso', 'Salida'] as $title) {
            if (! isset($typeIds[$title])) {
                throw new \RuntimeException("Tipo de movimiento no encontrado: {$title}");
            }
        }

        $missingProducts = collect($rows)->pluck('source_product_id')->unique()->diff(array_keys($productMap));
        $missingPecosas = collect($rows)
            ->pluck('source_pecosa_id')
            ->filter()
            ->unique()
            ->diff(array_keys($pecosaMap));
        if ($missingProducts->isNotEmpty() || $missingPecosas->isNotEmpty()) {
            throw new \RuntimeException(
                'Referencias de movimientos sin resolver. PEC_id=' . $missingPecosas->implode(',') .
                '; PRO_id=' . $missingProducts->implode(',')
            );
        }

        $pecosaNumbers = collect($rows)
            ->where('type_title', 'Salida')
            ->pluck('document_number')
            ->filter()
            ->unique()
            ->values();
        $detailProductIds = array_values($productMap);

        // Limpiar solo los movimientos que pertenecen al corte importado.
        DB::table('transactions')
            ->whereIn('document_number', $pecosaNumbers)
            ->delete();
        DB::table('transactions')
            ->whereIn('detail_product_id', $detailProductIds)
            ->where('type_transaction_id', $typeIds['Ingreso'])
            ->delete();

        $now = now();
        $payload = [];
        foreach ($rows as $row) {
            $payload[] = [
                'quantity' => $row['quantity'],
                'unit_price' => $row['unit_price'],
                'total_price' => $row['total_price'],
                'document_number' => $row['document_number'],
                'adjustment' => $row['adjustment'],
                'transaction_date' => $row['transaction_date'],
                'detail_product_id' => $productMap[(int) $row['source_product_id']],
                'type_transaction_id' => $typeIds[$row['type_title']],
                'product_name' => $row['product_name'],
                'uom_title' => $row['uom_title'],
                'created_at' => $now,
                'updated_at' => $now,
            ];
        }

        foreach (array_chunk($payload, 500) as $chunk) {
            DB::table('transactions')->insert($chunk);
        }

        $exitTransactions = DB::table('transactions')
            ->where('type_transaction_id', $typeIds['Salida'])
            ->whereIn('document_number', $pecosaNumbers)
            ->get(['id', 'detail_product_id', 'document_number'])
            ->keyBy(fn ($row) => "{$row->document_number}:{$row->detail_product_id}");

        self::$detailExitIdMap = [];
        foreach ($rows as $row) {
            if ($row['type_title'] !== 'Salida') {
                continue;
            }

            $detailProductId = $productMap[(int) $row['source_product_id']];
            $key = "{$row['document_number']}:{$detailProductId}";
            $transaction = $exitTransactions->get($key);
            if (! $transaction) {
                throw new \RuntimeException("Movimiento de salida no insertado para DPE_id {$row['source_detail_id']}");
            }
            self::$detailExitIdMap[(int) $row['source_detail_id']] = (int) $transaction->id;
        }

        $expectedExits = collect($rows)->where('type_title', 'Salida')->count();
        if (count(self::$detailExitIdMap) !== $expectedExits) {
            throw new \RuntimeException('No se pudieron enlazar todos los movimientos de salida con DETALLE_PECOSA.');
        }

        $this->command->info('Movimientos insertados: ' . count($rows));
    }
}
