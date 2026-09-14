<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use RuntimeException;

class AssociationRosterPeriodSeeder extends Seeder
{
    public function run(): void
    {
        $path = database_path('seeders/data/association_roster_periods.json');
        if (!is_file($path)) {
            throw new RuntimeException("No se encontró {$path}");
        }

        $rows = json_decode(file_get_contents($path), true, 512, JSON_THROW_ON_ERROR);
        $associationIds = DB::table('associations')->pluck('id', 'code');
        $personIds = DB::table('people')->pluck('id', 'dni');
        $partnerIds = DB::table('partners')
            ->get(['id', 'person_id', 'association_id'])
            ->mapWithKeys(fn ($partner) => [
                $partner->person_id . '|' . $partner->association_id => $partner->id,
            ]);

        $now = now();
        $inserts = [];
        $unmatchedPresidentMonths = 0;

        foreach ($rows as $row) {
            [$code, $period, $partnerCount, $beneficiaryCount, $presidentName, $presidentDni] = array_pad($row, 6, null);
            $associationId = $associationIds->get((string) $code);
            if (!$associationId) {
                throw new RuntimeException("El comité {$code} del padrón mensual no existe.");
            }

            $presidentPartnerId = null;
            if ($presidentDni) {
                $personId = $personIds->get($this->normalizeDni($presidentDni));
                $presidentPartnerId = $personId
                    ? $partnerIds->get($personId . '|' . $associationId)
                    : null;
            }

            if ($presidentName && !$presidentPartnerId) {
                $unmatchedPresidentMonths++;
            }

            $inserts[] = [
                'association_id' => $associationId,
                'period' => $period,
                'partner_count' => (int) $partnerCount,
                'beneficiary_count' => (int) $beneficiaryCount,
                'president_name' => $presidentName ?: null,
                'president_partner_id' => $presidentPartnerId,
                'created_at' => $now,
                'updated_at' => $now,
            ];
        }

        DB::table('association_roster_periods')->delete();
        foreach (array_chunk($inserts, 500) as $chunk) {
            DB::table('association_roster_periods')->insert($chunk);
        }

        $this->command->info('Períodos mensuales de comités insertados: ' . count($inserts));
        if ($unmatchedPresidentMonths > 0) {
            $this->command->warn(
                "Presidentas conservadas solo por nombre (no figuran como socias ese mes): {$unmatchedPresidentMonths}"
            );
        }
    }

    private function normalizeDni($value): string
    {
        $dni = trim((string) $value);

        return $dni === '178993805' ? '17899385' : $dni;
    }
}
