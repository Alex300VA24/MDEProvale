<?php

namespace Database\Seeders;

use Carbon\Carbon;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use RuntimeException;

class TemporalPartnerSeeder extends Seeder
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
        $partnerMonths = $this->loadPartnerMonths();
        $this->upsertPartners($partnerMonths);
        $this->syncRosterPeriods($partnerMonths);
    }

    protected function loadPartnerMonths(): array
    {
        $result = [];

        foreach ($this->months as $index => $month) {
            $path = database_path("seeders/data/partner_months/{$month}.json");
            if (!is_file($path)) {
                throw new RuntimeException("No se encontró {$path}");
            }

            $data = json_decode(file_get_contents($path), true, 512, JSON_THROW_ON_ERROR);
            foreach ($data as $row) {
                $dni = $this->normalizeDni($row[0] ?? '');
                $code = trim((string) ($row[1] ?? ''));
                if (!$dni || !$code) {
                    continue;
                }

                $key = $dni . '|' . $code;
                $result[$key][$index] = [
                    'observations' => $row[2] ?? 'Socia titular',
                    'is_president' => (int) ($row[3] ?? 0) === 1,
                ];
            }
        }

        return $result;
    }

    protected function upsertPartners(array $partnerMonths): void
    {
        $personIds = DB::table('people')->pluck('id', 'dni');
        $associationIds = DB::table('associations')->pluck('id', 'code');
        $currentStateId = DB::table('states')->where('abbreviation', 'VIG')->value('id');
        $expiredStateId = DB::table('states')->where('abbreviation', 'VEN')->value('id');
        $presidentPositionId = DB::table('positions')->where('title', 'PRESIDENTA')->value('id');

        if (!$currentStateId || !$expiredStateId || !$presidentPositionId) {
            throw new RuntimeException('Faltan los catálogos VIG, VEN o PRESIDENTA.');
        }

        $lastMonthIndex = count($this->months) - 1;
        $now = now();
        $processed = 0;

        foreach ($partnerMonths as $key => $monthData) {
            [$dni, $code] = explode('|', $key, 2);
            $personId = $personIds->get($dni);
            $associationId = $associationIds->get($code);

            if (!$personId || !$associationId) {
                throw new RuntimeException("No se pudo resolver la socia {$dni} del comité {$code}.");
            }

            $monthIndexes = array_keys($monthData);
            sort($monthIndexes);
            $firstMonth = $monthIndexes[0];
            $lastObservedMonth = end($monthIndexes);
            $isCurrent = array_key_exists($lastMonthIndex, $monthData);
            $latestData = $monthData[$lastObservedMonth];
            $currentData = $monthData[$lastMonthIndex] ?? null;

            DB::table('partners')->updateOrInsert(
                ['person_id' => $personId, 'association_id' => $associationId],
                [
                    'date_begin' => $this->monthDates[$this->months[$firstMonth]],
                    'date_end' => $isCurrent
                        ? null
                        : Carbon::parse($this->monthDates[$this->months[$lastObservedMonth]])->endOfMonth()->toDateString(),
                    'observations' => $latestData['observations'],
                    'state_id' => $isCurrent ? $currentStateId : $expiredStateId,
                    // La directiva conserva presidencias anteriores; este campo
                    // identifica únicamente a la presidenta del padrón vigente.
                    'position_id' => ($currentData['is_president'] ?? false) ? $presidentPositionId : null,
                    'created_at' => $now,
                    'updated_at' => $now,
                ]
            );
            $processed++;
        }

        $this->command->info("Socias sincronizadas: {$processed}");
    }

    protected function syncRosterPeriods(array $partnerMonths): void
    {
        $personIds = DB::table('people')->pluck('id', 'dni');
        $associationIds = DB::table('associations')->pluck('id', 'code');
        $partnerIds = DB::table('partners')
            ->get(['id', 'person_id', 'association_id'])
            ->mapWithKeys(fn ($partner) => [
                $partner->person_id . '|' . $partner->association_id => $partner->id,
            ]);

        $now = now();
        $periods = [];

        foreach ($partnerMonths as $key => $monthData) {
            [$dni, $code] = explode('|', $key, 2);
            $personId = $personIds->get($dni);
            $associationId = $associationIds->get($code);
            $partnerId = $personId && $associationId
                ? $partnerIds->get($personId . '|' . $associationId)
                : null;

            if (!$partnerId) {
                throw new RuntimeException("No se pudo resolver el período de la socia {$dni} del comité {$code}.");
            }

            foreach (array_keys($monthData) as $monthIndex) {
                $periods[] = [
                    'partner_id' => $partnerId,
                    'period' => $this->monthDates[$this->months[$monthIndex]],
                    'created_at' => $now,
                    'updated_at' => $now,
                ];
            }
        }

        DB::table('partner_roster_periods')->delete();
        foreach (array_chunk($periods, 1000) as $chunk) {
            DB::table('partner_roster_periods')->insert($chunk);
        }

        $this->command->info('Presencias mensuales de socias insertadas: ' . count($periods));
    }

    protected function normalizeDni($value): string
    {
        $dni = trim((string) $value);

        return $dni === '178993805' ? '17899385' : $dni;
    }
}
