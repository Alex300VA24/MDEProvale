<?php

namespace Database\Seeders;

use Carbon\Carbon;
use Illuminate\Database\Seeder;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use RuntimeException;

class DirectiveSeeder extends Seeder
{
    public function run(): void
    {
        $path = database_path('seeders/data/association_roster_periods.json');
        if (!is_file($path)) {
            throw new RuntimeException("No se encontró {$path}");
        }

        $periodRows = json_decode(file_get_contents($path), true, 512, JSON_THROW_ON_ERROR);
        $associations = DB::table('associations')->get(['id', 'code', 'resolution_id'])->keyBy('code');
        $personIds = DB::table('people')->pluck('id', 'dni');
        $partnerIds = DB::table('partners')
            ->get(['id', 'person_id', 'association_id'])
            ->mapWithKeys(fn ($partner) => [
                $partner->person_id . '|' . $partner->association_id => $partner->id,
            ]);
        $presidentPositionId = DB::table('positions')->where('title', 'PRESIDENTA')->value('id');
        $currentStateId = DB::table('states')->where('abbreviation', 'VIG')->value('id');
        $expiredStateId = DB::table('states')->where('abbreviation', 'VEN')->value('id');

        if (!$presidentPositionId || !$currentStateId || !$expiredStateId) {
            throw new RuntimeException('Faltan los catálogos PRESIDENTA, VIG o VEN.');
        }

        $periodsByCommittee = [];
        foreach ($periodRows as $row) {
            [$code, $period, , , , $presidentDni] = array_pad($row, 6, null);
            if (!$presidentDni) {
                continue;
            }

            $periodsByCommittee[$code][$period] = $this->normalizeDni($presidentDni);
        }

        $lastSourcePeriod = '2026-09-01';
        $now = now();
        $directives = [];

        foreach ($periodsByCommittee as $code => $periods) {
            $association = $associations->get($code);
            if (!$association) {
                throw new RuntimeException("No se encontró el comité {$code} para crear sus presidentas.");
            }

            foreach ($this->presidentSpans($periods) as $span) {
                $personId = $personIds->get($span['dni']);
                $partnerId = $personId
                    ? $partnerIds->get($personId . '|' . $association->id)
                    : null;

                if (!$partnerId) {
                    throw new RuntimeException(
                        "La presidenta {$span['dni']} del comité {$code} no está registrada como socia."
                    );
                }

                $resolutionId = $this->resolutionForPeriod(
                    (int) $association->id,
                    (int) $association->resolution_id,
                    $span['start']
                );
                $isCurrent = $span['end'] === $lastSourcePeriod;

                $directives[] = [
                    'resolution_id' => $resolutionId,
                    'partner_id' => $partnerId,
                    'position_id' => $presidentPositionId,
                    'state_id' => $isCurrent ? $currentStateId : $expiredStateId,
                    'date_start' => $span['start'],
                    'date_end' => $isCurrent
                        ? null
                        : Carbon::parse($span['end'])->endOfMonth()->toDateString(),
                    'created_at' => $now,
                    'updated_at' => $now,
                ];
            }
        }

        DB::table('directives')->delete();
        foreach (array_chunk($directives, 500) as $chunk) {
            DB::table('directives')->insert($chunk);
        }

        $this->command->info('Períodos de presidentas vinculadas: ' . count($directives));
    }

    protected function presidentSpans(array $periods): array
    {
        ksort($periods);
        $spans = [];

        foreach ($periods as $period => $dni) {
            $lastIndex = array_key_last($spans);
            $previous = $lastIndex !== null ? $spans[$lastIndex] : null;
            $isConsecutive = $previous
                && Carbon::parse($previous['end'])->addMonth()->startOfMonth()->toDateString() === $period;

            if ($previous && $previous['dni'] === $dni && $isConsecutive) {
                $spans[$lastIndex]['end'] = $period;
                continue;
            }

            $spans[] = ['dni' => $dni, 'start' => $period, 'end' => $period];
        }

        return $spans;
    }

    protected function resolutionForPeriod(int $associationId, int $primaryResolutionId, string $period): int
    {
        $resolutionIds = DB::table('resolution_associations')
            ->where('association_id', $associationId)
            ->pluck('resolution_id')
            ->push($primaryResolutionId)
            ->unique()
            ->values();

        /** @var Collection<int, object> $resolutions */
        $resolutions = DB::table('resolutions')
            ->whereIn('id', $resolutionIds)
            ->get(['id', 'date_start', 'date_end']);
        $periodStart = Carbon::parse($period)->startOfMonth();
        $periodEnd = Carbon::parse($period)->endOfMonth();

        $covering = $resolutions
            ->filter(fn ($resolution) => (!$resolution->date_start || Carbon::parse($resolution->date_start)->lte($periodEnd))
                && (!$resolution->date_end || Carbon::parse($resolution->date_end)->gte($periodStart)))
            ->sortByDesc('date_start')
            ->first();

        if ($covering) {
            return (int) $covering->id;
        }

        $latest = $resolutions
            ->filter(fn ($resolution) => !$resolution->date_start || Carbon::parse($resolution->date_start)->lte($periodEnd))
            ->sortByDesc('date_start')
            ->first();

        return (int) ($latest->id ?? $primaryResolutionId);
    }

    protected function normalizeDni($value): string
    {
        $dni = trim((string) $value);

        return $dni === '178993805' ? '17899385' : $dni;
    }
}
