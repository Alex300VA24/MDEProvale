<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use RuntimeException;

class TemporalBeneficiarieSeeder extends Seeder
{
    protected array $months = [
        'marzo', 'abril', 'mayo', 'junio',
        'julio', 'agosto', 'septiembre',
    ];

    protected array $monthDates = [
        'marzo'      => '2026-03-01',
        'abril'      => '2026-04-01',
        'mayo'       => '2026-05-01',
        'junio'      => '2026-06-01',
        'julio'      => '2026-07-01',
        'agosto'     => '2026-08-01',
        'septiembre' => '2026-09-01',
    ];

    public function run(): void
    {
        $beneficiaryMonths = $this->loadBeneficiaryMonths();
        $this->upsertBeneficiaries($beneficiaryMonths);
    }

    protected function loadBeneficiaryMonths(): array
    {
        $result = [];

        foreach ($this->months as $index => $month) {
            $path = database_path("seeders/data/beneficiary_months/{$month}.json");
            if (!is_file($path)) {
                throw new RuntimeException("No se encontró {$path}");
            }

            $data = json_decode(file_get_contents($path), true, 512, JSON_THROW_ON_ERROR);
            foreach ($data as $row) {
                $personDni = $this->normalizeDni($row[0] ?? '');
                $partnerDni = $this->normalizeDni($row[1] ?? '');
                $associationCode = trim((string) ($row[2] ?? ''));

                if (!$personDni || !$partnerDni || !$associationCode) {
                    continue;
                }

                $key = $personDni . '|' . $partnerDni . '|' . $associationCode;
                $result[$key][$index] = [
                    'relationship_title' => $this->normalizeRelationship($row[3] ?? ''),
                ];
            }
        }

        return $result;
    }

    protected function upsertBeneficiaries(array $beneficiaryMonths): void
    {
        $personIds = DB::table('people')->pluck('id', 'dni');
        $associationIds = DB::table('associations')->pluck('id', 'code');
        $relationshipIds = DB::table('relationships')->pluck('id', 'title');
        $partnerIds = DB::table('partners')
            ->get(['id', 'person_id', 'association_id'])
            ->mapWithKeys(fn ($partner) => [
                $partner->person_id . '|' . $partner->association_id => $partner->id,
            ]);

        $now = now();
        $processed = 0;

        foreach ($beneficiaryMonths as $key => $monthData) {
            [$personDni, $partnerDni, $associationCode] = explode('|', $key, 3);
            $personId = $personIds->get($personDni);
            $partnerPersonId = $personIds->get($partnerDni);
            $associationId = $associationIds->get($associationCode);
            $partnerId = $partnerPersonId && $associationId
                ? $partnerIds->get($partnerPersonId . '|' . $associationId)
                : null;

            $latestData = end($monthData);
            $relationshipId = $relationshipIds->get($latestData['relationship_title']);

            if (!$personId || !$partnerId || !$relationshipId) {
                throw new RuntimeException(
                    "No se pudo resolver el beneficiario {$personDni}, socia {$partnerDni}, comité {$associationCode}."
                );
            }

            $firstMonth = min(array_keys($monthData));
            DB::table('beneficiaries')->updateOrInsert(
                ['person_id' => $personId, 'partner_id' => $partnerId],
                [
                    'relationship_id' => $relationshipId,
                    'created_at' => $this->monthDates[$this->months[$firstMonth]],
                    'updated_at' => $now,
                ]
            );
            $processed++;
        }

        $this->command->info("Beneficiarios sincronizados: {$processed}");
    }

    protected function normalizeRelationship($value): string
    {
        $relationship = trim((string) $value);

        // Una fila de menor no trae parentesco entre julio y setiembre.
        return $relationship === '' ? 'Hijos' : $relationship;
    }

    protected function normalizeDni($value): string
    {
        $dni = trim((string) $value);

        return $dni === '178993805' ? '17899385' : $dni;
    }
}
