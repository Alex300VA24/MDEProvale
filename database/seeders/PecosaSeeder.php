<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class PecosaSeeder extends Seeder
{
    /** @var array<int, int> PEC_id de SQL Server => pecosas.id */
    public static array $idMap = [];

    public function run(): void
    {
        $path = __DIR__ . '/data/pecosas.json';
        if (! is_file($path)) {
            throw new \RuntimeException("No se encontro {$path}. Ejecuta migracion_productos/creando_seeders.py.");
        }

        $rows = json_decode(file_get_contents($path), true);
        if (! is_array($rows)) {
            throw new \RuntimeException("El archivo {$path} no contiene JSON valido.");
        }

        $associationIds = DB::table('associations')->pluck('id', 'code');
        $responsibleIds = DB::table('responsibles')
            ->join('people', 'people.id', '=', 'responsibles.person_id')
            ->select('responsibles.id', 'responsibles.type', 'people.dni')
            ->get()
            ->keyBy(fn ($row) => "{$row->type}:{$row->dni}");

        self::$idMap = [];
        $now = now();
        $payload = [];
        $sourceByNumber = [];
        foreach ($rows as $row) {
            $associationId = $associationIds[$row['association_code']] ?? null;
            if (! $associationId) {
                throw new \RuntimeException("Asociacion destino no encontrada: {$row['association_code']}");
            }

            $chief = $responsibleIds->get('chief:' . ($row['chief_dni'] ?? ''));
            $storekeeper = $responsibleIds->get('storekeeper:' . ($row['storekeeper_dni'] ?? ''));
            $payload[] = [
                'pecosa_number' => $row['pecosa_number'],
                'observation' => $row['observation'],
                'delivery_date' => $row['delivery_date'] . ' 00:00:00',
                'chief_id' => $chief->id ?? null,
                'storekeeper_id' => $storekeeper->id ?? null,
                'managing_partner_id' => null,
                'president_id' => null,
                'state_id' => $row['state_id'],
                'association_id' => $associationId,
                'chief_name' => $row['chief_name'],
                'chief_dni' => $row['chief_dni'],
                'storekeeper_name' => $row['storekeeper_name'],
                'storekeeper_dni' => $row['storekeeper_dni'],
                'association_name' => $row['association_name'],
                'association_code' => $row['association_code'],
                'association_address' => $row['association_address'],
                'created_at' => $now,
                'updated_at' => $now,
            ];
            $sourceByNumber[$row['pecosa_number']] = (int) $row['source_pecosa_id'];
        }

        foreach (array_chunk($payload, 500) as $chunk) {
            DB::table('pecosas')->upsert(
                $chunk,
                ['pecosa_number'],
                [
                    'observation',
                    'delivery_date',
                    'chief_id',
                    'storekeeper_id',
                    'state_id',
                    'association_id',
                    'chief_name',
                    'chief_dni',
                    'storekeeper_name',
                    'storekeeper_dni',
                    'association_name',
                    'association_code',
                    'association_address',
                    'updated_at',
                ]
            );
        }

        $idsByNumber = DB::table('pecosas')
            ->whereIn('pecosa_number', array_keys($sourceByNumber))
            ->pluck('id', 'pecosa_number');
        foreach ($sourceByNumber as $number => $sourceId) {
            if (! isset($idsByNumber[$number])) {
                throw new \RuntimeException("PECOSA no insertada: {$number}");
            }
            self::$idMap[$sourceId] = (int) $idsByNumber[$number];
        }

        $this->command->info('Pecosas insertadas: ' . count($rows));
    }
}
