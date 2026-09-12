<?php

namespace Database\Seeders;

use Carbon\Carbon;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use RuntimeException;

class TemporalBeneficiarieHistorySeeder extends Seeder
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
        $historyMonths = $this->loadHistoryMonths();
        $this->replaceHistories($historyMonths);
    }

    protected function loadHistoryMonths(): array
    {
        $result = [];

        foreach ($this->months as $index => $month) {
            $historyPath = database_path("seeders/data/history_months/{$month}.json");
            $beneficiaryPath = database_path("seeders/data/beneficiary_months/{$month}.json");

            if (!is_file($historyPath) || !is_file($beneficiaryPath)) {
                throw new RuntimeException("Faltan los datos mensuales de {$month}.");
            }

            $relationships = [];
            $beneficiaryRows = json_decode(file_get_contents($beneficiaryPath), true, 512, JSON_THROW_ON_ERROR);
            foreach ($beneficiaryRows as $row) {
                $key = $this->key($row[0] ?? '', $row[1] ?? '', $row[2] ?? '');
                if ($key) {
                    $relationships[$key] = $this->normalizeRelationship($row[3] ?? '');
                }
            }

            $historyRows = json_decode(file_get_contents($historyPath), true, 512, JSON_THROW_ON_ERROR);
            foreach ($historyRows as $row) {
                $key = $this->key($row[0] ?? '', $row[1] ?? '', $row[2] ?? '');
                if (!$key) {
                    continue;
                }

                $result[$key][$index] = [
                    'type_benefit_abbreviation' => trim((string) ($row[4] ?? '')),
                    'relationship_title' => $relationships[$key] ?? 'Hijos',
                ];
            }
        }

        // Una celda de mayo viene vacía, pero la misma persona conserva NI7
        // en los meses contiguos. Se completa desde la observación más cercana.
        foreach ($result as &$monthData) {
            foreach ($monthData as $monthIndex => &$data) {
                if ($data['type_benefit_abbreviation'] !== '') {
                    continue;
                }

                $nearest = collect($monthData)
                    ->filter(fn ($candidate) => $candidate['type_benefit_abbreviation'] !== '')
                    ->sortBy(fn ($candidate, $candidateIndex) => abs($candidateIndex - $monthIndex))
                    ->first();

                if ($nearest) {
                    $data['type_benefit_abbreviation'] = $nearest['type_benefit_abbreviation'];
                }
            }
            unset($data);
        }
        unset($monthData);

        return $result;
    }

    protected function replaceHistories(array $historyMonths): void
    {
        $personIds = DB::table('people')->pluck('id', 'dni');
        $associationIds = DB::table('associations')->pluck('id', 'code');
        $partnerIds = DB::table('partners')
            ->get(['id', 'person_id', 'association_id'])
            ->mapWithKeys(fn ($partner) => [
                $partner->person_id . '|' . $partner->association_id => $partner->id,
            ]);
        $beneficiaryIds = DB::table('beneficiaries')
            ->get(['id', 'person_id', 'partner_id'])
            ->mapWithKeys(fn ($beneficiary) => [
                $beneficiary->person_id . '|' . $beneficiary->partner_id => $beneficiary->id,
            ]);
        $typeBenefitIds = DB::table('type_benefits')->pluck('id', 'abbreviation');
        $relationshipIds = DB::table('relationships')->pluck('id', 'title');
        $currentStateId = DB::table('states')->where('abbreviation', 'VIG')->value('id');
        $expiredStateId = DB::table('states')->where('abbreviation', 'VEN')->value('id');

        if (!$currentStateId || !$expiredStateId) {
            throw new RuntimeException('Faltan los estados VIG o VEN.');
        }

        $lastMonthIndex = count($this->months) - 1;
        $now = now();
        $rows = [];

        foreach ($historyMonths as $key => $monthData) {
            [$personDni, $partnerDni, $associationCode] = explode('|', $key, 3);
            $personId = $personIds->get($personDni);
            $partnerPersonId = $personIds->get($partnerDni);
            $associationId = $associationIds->get($associationCode);
            $partnerId = $partnerPersonId && $associationId
                ? $partnerIds->get($partnerPersonId . '|' . $associationId)
                : null;
            $beneficiaryId = $personId && $partnerId
                ? $beneficiaryIds->get($personId . '|' . $partnerId)
                : null;

            if (!$beneficiaryId) {
                throw new RuntimeException(
                    "No se pudo resolver el historial de {$personDni}, socia {$partnerDni}, comité {$associationCode}."
                );
            }

            foreach ($this->contiguousSpans($monthData) as $span) {
                $typeBenefitId = $typeBenefitIds->get($span['data']['type_benefit_abbreviation']);
                $relationshipId = $relationshipIds->get($span['data']['relationship_title']);

                if (!$typeBenefitId || !$relationshipId) {
                    throw new RuntimeException("Catálogo incompleto para el historial de {$personDni}.");
                }

                $isCurrent = $span['end'] === $lastMonthIndex;
                $rows[] = [
                    'weight' => 0.0,
                    'height' => 0.0,
                    'hmg' => 0.0,
                    'date_begin' => $this->monthDates[$this->months[$span['start']]],
                    'date_end' => $isCurrent
                        ? null
                        : Carbon::parse($this->monthDates[$this->months[$span['end']]])->endOfMonth()->toDateString(),
                    'type_benefit_id' => $typeBenefitId,
                    'relationship_id' => $relationshipId,
                    'beneficiary_id' => $beneficiaryId,
                    'state_id' => $isCurrent ? $currentStateId : $expiredStateId,
                    'reason_disqualification_id' => null,
                    'created_at' => $now,
                    'updated_at' => $now,
                ];
            }
        }

        DB::transaction(function () use ($rows) {
            DB::table('obstetric_data')->delete();
            DB::table('beneficiary_histories')->delete();

            foreach (array_chunk($rows, 1000) as $chunk) {
                DB::table('beneficiary_histories')->insert($chunk);
            }
        });

        $this->command->info('Tramos históricos de beneficiarios insertados: ' . count($rows));
    }

    protected function contiguousSpans(array $monthData): array
    {
        ksort($monthData);
        $spans = [];

        foreach ($monthData as $monthIndex => $data) {
            $lastIndex = array_key_last($spans);
            $previous = $lastIndex !== null ? $spans[$lastIndex] : null;
            $sameData = $previous && $previous['data'] === $data;
            $isConsecutive = $previous && $previous['end'] + 1 === $monthIndex;

            if ($sameData && $isConsecutive) {
                $spans[$lastIndex]['end'] = $monthIndex;
                continue;
            }

            $spans[] = [
                'start' => $monthIndex,
                'end' => $monthIndex,
                'data' => $data,
            ];
        }

        return $spans;
    }

    protected function key($personDni, $partnerDni, $associationCode): ?string
    {
        $personDni = $this->normalizeDni($personDni);
        $partnerDni = $this->normalizeDni($partnerDni);
        $associationCode = trim((string) $associationCode);

        if (!$personDni || !$partnerDni || !$associationCode) {
            return null;
        }

        return $personDni . '|' . $partnerDni . '|' . $associationCode;
    }

    protected function normalizeRelationship($value): string
    {
        $relationship = trim((string) $value);

        return $relationship === '' ? 'Hijos' : $relationship;
    }

    protected function normalizeDni($value): string
    {
        $dni = trim((string) $value);

        return $dni === '178993805' ? '17899385' : $dni;
    }
}
