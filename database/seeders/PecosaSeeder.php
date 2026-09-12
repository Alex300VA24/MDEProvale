<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class PecosaSeeder extends Seeder
{
    public static array $idMap = [];

    public function run(): void
    {
        $ruta = __DIR__ . '/data/pecosas.json';
        if (!is_file($ruta)) {
            throw new \RuntimeException("No se encontro {$ruta}");
        }

        $filas = json_decode(file_get_contents($ruta), true);
        $cutoff = '2026-02-01';
        $ahora = now();
        $lote = [];
        $numbersByOriginalId = [];
        self::$idMap = [];

        $originalId = 0;
        foreach ($filas as $fila) {
            $originalId++;
            $date = $fila[2] ?? '';
            if ($date < $cutoff) continue;

            $assocCode = $fila[4] ?? '';
            $assocId = DB::table('associations')->where('code', $assocCode)->value('id');
            $numbersByOriginalId[$originalId] = $fila[0];

            $lote[] = [
                'pecosa_number'    => $fila[0],
                'observation'      => $fila[1],
                'delivery_date'    => $date . ' 00:00:00',
                'state_id'         => $fila[3] ?? 4,
                'association_id'   => $assocId,
                'association_name' => $fila[5] ?? null,
                'association_code' => $assocCode,
                'created_at'       => $ahora,
                'updated_at'       => $ahora,
            ];

            if (count($lote) >= 500) {
                $this->upsertPecosas($lote);
                $lote = [];
            }
        }

        if ($lote) {
            $this->upsertPecosas($lote);
        }

        $idsByNumber = DB::table('pecosas')
            ->whereIn('pecosa_number', array_values($numbersByOriginalId))
            ->pluck('id', 'pecosa_number');

        foreach ($numbersByOriginalId as $sourceId => $number) {
            if (isset($idsByNumber[$number])) {
                self::$idMap[$sourceId] = (int) $idsByNumber[$number];
            }
        }

        $total = DB::table('pecosas')->count();
        $this->command->info("Pecosas insertadas (>= {$cutoff}): {$total}");
    }

    private function upsertPecosas(array $rows): void
    {
        DB::table('pecosas')->upsert(
            $rows,
            ['pecosa_number'],
            [
                'observation',
                'delivery_date',
                'state_id',
                'association_id',
                'association_name',
                'association_code',
                'updated_at',
            ]
        );
    }
}
