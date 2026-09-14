<?php

namespace Database\Seeders;

use App\Models\Pecosa;
use App\Models\State;
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
        $stateIds = DB::table('states')
            ->whereIn('abbreviation', [State::CURRENT, State::EXPIRED])
            ->pluck('id', 'abbreviation');
        if (! isset($stateIds[State::CURRENT], $stateIds[State::EXPIRED])) {
            throw new \RuntimeException('Faltan estados VIGENTE/VENCIDO para importar PECOSAs.');
        }
        $responsibleIds = DB::table('responsibles')
            ->join('people', 'people.id', '=', 'responsibles.person_id')
            ->select('responsibles.id', 'responsibles.type', 'people.dni')
            ->get()
            ->keyBy(fn ($row) => "{$row->type}:{$row->dni}");
        $presidentsByPeriod = DB::table('association_roster_periods as roster')
            ->leftJoin('partners', 'partners.id', '=', 'roster.president_partner_id')
            ->leftJoin('people', 'people.id', '=', 'partners.person_id')
            ->get([
                'roster.association_id',
                'roster.period',
                'roster.president_name',
                'roster.president_partner_id',
                'roster.beneficiary_count',
                'people.dni as president_dni',
            ])
            ->keyBy(fn ($row) => $row->association_id . '|' . substr((string) $row->period, 0, 10));

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
            $effectivePeriod = Pecosa::effectiveDeliveryDate($row['delivery_date'])
                ->startOfMonth()
                ->toDateString();
            $president = $presidentsByPeriod->get($associationId . '|' . $effectivePeriod);
            $stateAbbreviation = Pecosa::stateAbbreviationForDeliveryDate($row['delivery_date']);
            $payload[] = [
                'pecosa_number' => $row['pecosa_number'],
                'observation' => $row['observation'],
                'delivery_date' => $row['delivery_date'] . ' 00:00:00',
                'chief_id' => $chief->id ?? null,
                'storekeeper_id' => $storekeeper->id ?? null,
                'managing_partner_id' => $president->president_partner_id ?? null,
                'president_id' => $president->president_partner_id ?? null,
                'state_id' => $stateIds[$stateAbbreviation],
                'association_id' => $associationId,
                'chief_name' => $row['chief_name'],
                'chief_dni' => $row['chief_dni'],
                'storekeeper_name' => $row['storekeeper_name'],
                'storekeeper_dni' => $row['storekeeper_dni'],
                'managing_partner_name' => $president->president_name ?? null,
                'managing_partner_dni' => $president->president_dni ?? null,
                'president_name' => $president->president_name ?? null,
                'president_dni' => $president->president_dni ?? null,
                'association_name' => $row['association_name'],
                'association_code' => $row['association_code'],
                'association_address' => $row['association_address'],
                'beneficiaries_count' => $president->beneficiary_count ?? null,
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
                    'managing_partner_id',
                    'president_id',
                    'state_id',
                    'association_id',
                    'chief_name',
                    'chief_dni',
                    'storekeeper_name',
                    'storekeeper_dni',
                    'managing_partner_name',
                    'managing_partner_dni',
                    'president_name',
                    'president_dni',
                    'association_name',
                    'association_code',
                    'association_address',
                    'beneficiaries_count',
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
