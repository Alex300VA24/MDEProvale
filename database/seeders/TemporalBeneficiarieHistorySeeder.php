<?php

namespace Database\Seeders;

use Carbon\Carbon;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use RuntimeException;

class TemporalBeneficiarieHistorySeeder extends Seeder
{
    protected const EXPECTED_MONTHLY_TOTALS = [
        'marzo' => 3872,
        'abril' => 3872,
        'mayo' => 3862,
        'junio' => 3871,
        'julio' => 3872,
        'agosto' => 3822,
        'septiembre' => 3870,
    ];

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

    /**
     * Ocurrencias que la consolidacion por DNI/socia/comite no puede expresar.
     *
     * Estas claves repetidas existen literalmente en los Excel de marzo y
     * abril. Se conservan como relaciones historicas sin inventar registros
     * para las filas vacias ni completar meses con datos de otro periodo.
     */
    protected array $duplicateOccurrenceAdjustments = [
        'marzo' => [
            '93775401|43843452|030',
            '93824304|71836488|093',
            '94210807|48294572|170',
            '91938806|42011201|302',
        ],
        'abril' => [
            '93824304|71836488|093',
        ],
        'mayo' => [],
        'junio' => [],
        'julio' => [],
        'agosto' => [],
        'septiembre' => [],
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

        $this->assertExpectedSourceTotals($result);

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
        $duplicateOccurrences = [];

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

        foreach ($this->duplicateOccurrenceAdjustments as $month => $keys) {
            $monthIndex = array_search($month, $this->months, true);
            foreach ($keys as $key) {
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
                $data = $historyMonths[$key][$monthIndex] ?? null;

                if (!$beneficiaryId || !$data) {
                    throw new RuntimeException("No se pudo contabilizar la ocurrencia historica {$key} de {$month}.");
                }

                $typeBenefitId = $typeBenefitIds->get($data['type_benefit_abbreviation']);
                $relationshipId = $relationshipIds->get($data['relationship_title']);
                if (!$typeBenefitId || !$relationshipId) {
                    throw new RuntimeException("Catalogo incompleto para la ocurrencia historica {$key} de {$month}.");
                }

                $source = DB::table('beneficiaries')->where('id', $beneficiaryId)->first();
                $duplicateOccurrences[] = [
                    'beneficiary' => [
                        'person_id' => $source->person_id,
                        'partner_id' => $source->partner_id,
                        'relationship_id' => $relationshipId,
                        'priority' => $source->priority ?? 1,
                        'created_at' => $this->monthDates[$month],
                        'updated_at' => $now,
                    ],
                    'history' => [
                        'weight' => 0.0,
                        'height' => 0.0,
                        'hmg' => 0.0,
                        'date_begin' => $this->monthDates[$month],
                        'date_end' => $monthIndex === $lastMonthIndex
                            ? null
                            : Carbon::parse($this->monthDates[$month])->endOfMonth()->toDateString(),
                        'type_benefit_id' => $typeBenefitId,
                        'relationship_id' => $relationshipId,
                        'state_id' => $monthIndex === $lastMonthIndex ? $currentStateId : $expiredStateId,
                        'reason_disqualification_id' => null,
                        'created_at' => $now,
                        'updated_at' => $now,
                    ],
                ];
            }
        }

        DB::transaction(function () use ($rows, $duplicateOccurrences) {
            DB::table('obstetric_data')->delete();
            DB::table('beneficiary_histories')->delete();

            foreach (array_chunk($rows, 1000) as $chunk) {
                DB::table('beneficiary_histories')->insert($chunk);
            }

            foreach ($duplicateOccurrences as $occurrence) {
                $beneficiaryId = DB::table('beneficiaries')->insertGetId($occurrence['beneficiary']);
                DB::table('beneficiary_histories')->insert([
                    ...$occurrence['history'],
                    'beneficiary_id' => $beneficiaryId,
                ]);
            }
        });

        $this->assertSeededMonthlyTotals();

        $this->command->info(
            'Tramos historicos de beneficiarios insertados: '
            . (count($rows) + count($duplicateOccurrences))
        );
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

    protected function assertExpectedSourceTotals(array $historyMonths): void
    {
        foreach ($this->months as $monthIndex => $month) {
            $base = collect($historyMonths)
                ->filter(fn ($monthData) => array_key_exists($monthIndex, $monthData))
                ->count();
            $adjusted = $base + count($this->duplicateOccurrenceAdjustments[$month] ?? []);

            $expected = self::EXPECTED_MONTHLY_TOTALS[$month];
            if ($adjusted !== $expected) {
                throw new RuntimeException(
                    "El padron de {$month} totaliza {$adjusted}; se esperaban {$expected}."
                );
            }
        }
    }

    protected function assertSeededMonthlyTotals(): void
    {
        foreach ($this->months as $month) {
            $start = $this->monthDates[$month];
            $end = Carbon::parse($start)->endOfMonth()->toDateString();
            $count = DB::table('beneficiary_histories')
                ->whereDate('date_begin', '<=', $end)
                ->where(function ($query) use ($start) {
                    $query->whereNull('date_end')
                        ->orWhereDate('date_end', '>=', $start);
                })
                ->count();

            $expected = self::EXPECTED_MONTHLY_TOTALS[$month];
            if ($count !== $expected) {
                throw new RuntimeException(
                    "La base sembrada para {$month} contiene {$count} beneficiarios; se esperaban {$expected}."
                );
            }
        }
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
