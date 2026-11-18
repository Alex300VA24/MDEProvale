<?php

namespace App\Services;

use App\Models\Association;
use App\Models\MonthlyRoster;
use App\Models\MonthlyRosterAssociation;
use Carbon\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class MonthlyRosterService
{
    public function __construct(private PadronGuardService $padronGuard)
    {
    }

    public function index(): Collection
    {
        return MonthlyRoster::query()
            ->with([
                'source:id,period,version',
                'creator:id,names,father_surname,mother_surname,username',
                'closer:id,names,father_surname,mother_surname,username',
            ])
            ->withCount('associations')
            ->withSum('associations as beneficiary_count', 'beneficiary_count')
            ->orderByDesc('period')
            ->orderByDesc('version')
            ->get()
            ->map(fn (MonthlyRoster $roster) => $this->summary($roster));
    }

    public function detail(MonthlyRoster $roster): array
    {
        $roster->load([
            'source:id,period,version',
            'creator:id,names,father_surname,mother_surname,username',
            'closer:id,names,father_surname,mother_surname,username',
            'associations' => fn ($query) => $query->orderBy('id'),
        ])->loadCount('associations')
            ->loadSum('associations as beneficiary_count', 'beneficiary_count');

        return $this->summary($roster) + [
            'associations' => $roster->associations->map(function (MonthlyRosterAssociation $association) {
                $snapshot = $association->association_snapshot ?? [];

                return [
                    'id' => $association->id,
                    'association_id' => $association->association_id,
                    'code' => $snapshot['code'] ?? '',
                    'name' => $snapshot['name'] ?? 'Comité sin nombre',
                    'president_name' => $association->president_name,
                    'partner_count' => (int) $association->partner_count,
                    'beneficiary_count' => (int) $association->beneficiary_count,
                ];
            })->values(),
        ];
    }

    public function deleteDraft(MonthlyRoster $roster): void
    {
        $this->assertDraft($roster);

        DB::transaction(fn () => $roster->delete());
    }

    public function closedOptions(): Collection
    {
        return MonthlyRoster::query()
            ->where('status', MonthlyRoster::CLOSED)
            ->withSum('associations as beneficiary_count', 'beneficiary_count')
            ->orderByDesc('period')
            ->orderByDesc('version')
            ->get()
            ->map(fn (MonthlyRoster $roster) => [
                'id' => $roster->id,
                'period' => $roster->period->toDateString(),
                'version' => $roster->version,
                'label' => $roster->label,
                'beneficiary_count' => (int) ($roster->beneficiary_count ?? 0),
            ]);
    }

    /**
     * @param  array<int, string>  $draftPeriods  periodos (Y-m-d) que se dejan en borrador sin cerrar
     */
    public function bootstrapHistoricalPeriods(array $draftPeriods = []): int
    {
        $periods = DB::table('association_roster_periods')
            ->select('period')
            ->distinct()
            ->orderBy('period')
            ->pluck('period');
        $created = 0;

        foreach ($periods as $period) {
            $periodStart = Carbon::parse($period)->startOfMonth()->toDateString();
            if (MonthlyRoster::whereDate('period', $periodStart)->exists()) {
                continue;
            }

            $roster = MonthlyRoster::create([
                'period' => $periodStart,
                'version' => 1,
                'status' => MonthlyRoster::DRAFT,
            ]);
            $this->materialize($roster);
            if (! in_array($periodStart, $draftPeriods, true)) {
                $this->close($roster, null);
            }
            $created++;
        }

        return $created;
    }

    public function createDraft(string $period, ?int $sourceRosterId, ?int $userId): MonthlyRoster
    {
        $periodStart = Carbon::parse($period)->startOfMonth()->toDateString();

        if (MonthlyRoster::whereDate('period', $periodStart)->where('status', MonthlyRoster::DRAFT)->exists()) {
            throw ValidationException::withMessages([
                'period' => 'Ya existe un padrón en borrador para ese período.',
            ]);
        }

        $source = $sourceRosterId
            ? MonthlyRoster::where('status', MonthlyRoster::CLOSED)->findOrFail($sourceRosterId)
            : MonthlyRoster::where('status', MonthlyRoster::CLOSED)
                ->whereDate('period', '<', $periodStart)
                ->orderByDesc('period')
                ->orderByDesc('version')
                ->first();

        if ($source?->period->gt(Carbon::parse($periodStart))) {
            throw ValidationException::withMessages([
                'source_roster_id' => 'El padrón de origen no puede ser posterior al período que se está creando.',
            ]);
        }

        $version = (int) MonthlyRoster::whereDate('period', $periodStart)->max('version') + 1;

        if ($version === 1) {
            $periodDate = Carbon::parse($periodStart);
            $this->assertPeriodUnblocked($periodDate);
        }

        return DB::transaction(function () use ($periodStart, $version, $source, $userId) {
            $roster = MonthlyRoster::create([
                'period' => $periodStart,
                'version' => max(1, $version),
                'status' => MonthlyRoster::DRAFT,
                'source_roster_id' => $source?->id,
                'created_by' => $userId,
            ]);

            if ($source) {
                $this->cloneSnapshots($source, $roster);
            } else {
                $this->materialize($roster);
            }

            return $roster->fresh(['source']);
        });
    }

    public function revise(MonthlyRoster $closed, string $reason, ?int $userId): MonthlyRoster
    {
        if ($closed->status !== MonthlyRoster::CLOSED) {
            throw ValidationException::withMessages(['roster' => 'Solo puede corregirse un padrón cerrado vigente.']);
        }

        $draft = $this->createDraft($closed->period->toDateString(), $closed->id, $userId);
        $draft->update(['correction_reason' => trim($reason)]);

        return $draft->fresh(['source']);
    }

    public function sync(MonthlyRoster $roster): MonthlyRoster
    {
        $this->assertDraft($roster);

        DB::transaction(function () use ($roster) {
            $roster->associations()->delete();
            $this->materialize($roster);
        });

        return $roster->fresh();
    }

    public function close(MonthlyRoster $roster, ?int $userId): MonthlyRoster
    {
        $this->assertDraft($roster);

        if ($roster->version === 1) {
            $this->assertPeriodUnblocked($roster->period);
        }

        $associationCount = $roster->associations()->count();
        $beneficiaryCount = (int) $roster->associations()->sum('beneficiary_count');
        if ($associationCount === 0 || $beneficiaryCount === 0) {
            throw ValidationException::withMessages([
                'roster' => 'El padrón no puede cerrarse sin comités y beneficiarios. Sincronice o importe sus datos primero.',
            ]);
        }

        if ($roster->version > 1 && trim((string) $roster->correction_reason) === '') {
            throw ValidationException::withMessages([
                'correction_reason' => 'Indique el motivo de la corrección antes de cerrar la nueva versión.',
            ]);
        }

        DB::transaction(function () use ($roster, $userId) {
            MonthlyRoster::whereDate('period', $roster->period->toDateString())
                ->where('status', MonthlyRoster::CLOSED)
                ->whereKeyNot($roster->id)
                ->update(['status' => MonthlyRoster::SUPERSEDED, 'updated_at' => now()]);

            $roster->update([
                'status' => MonthlyRoster::CLOSED,
                'closed_by' => $userId,
                'closed_at' => now(),
            ]);
        });

        return $roster->fresh(['source', 'closer']);
    }

    public function officialForPeriod(int $year, int $month): ?MonthlyRoster
    {
        return MonthlyRoster::query()
            ->whereDate('period', Carbon::create($year, $month, 1)->toDateString())
            ->where('status', MonthlyRoster::CLOSED)
            ->orderByDesc('version')
            ->first();
    }

    public function hasAny(): bool
    {
        return MonthlyRoster::query()->exists();
    }

    public function latestClosedForServicePeriod(string $servicePeriod): ?MonthlyRoster
    {
        return MonthlyRoster::query()
            ->where('status', MonthlyRoster::CLOSED)
            ->whereDate('period', '<=', Carbon::parse($servicePeriod)->startOfMonth()->toDateString())
            ->orderByDesc('period')
            ->orderByDesc('version')
            ->first();
    }

    public function associationSnapshot(int $year, int $month, int $associationId): ?MonthlyRosterAssociation
    {
        $roster = $this->officialForPeriod($year, $month);

        return $roster?->associations()
            ->with('entries')
            ->where('association_id', $associationId)
            ->first();
    }

    private function assertDraft(MonthlyRoster $roster): void
    {
        if ($roster->status !== MonthlyRoster::DRAFT) {
            throw ValidationException::withMessages([
                'roster' => 'Un padrón cerrado es inmutable. Cree una revisión para corregirlo.',
            ]);
        }
    }

    private function assertPeriodUnblocked(Carbon $period): void
    {
        try {
            $this->padronGuard->assertNoneBlockedForPeriod($period->year, $period->month);
        } catch (\DomainException $exception) {
            throw ValidationException::withMessages([
                'period' => $exception->getMessage(),
            ]);
        }
    }

    private function cloneSnapshots(MonthlyRoster $source, MonthlyRoster $target): void
    {
        $source->load('associations.entries');
        $now = now();

        foreach ($source->associations as $association) {
            $copy = $target->associations()->create($association->only([
                'association_id',
                'partner_count',
                'beneficiary_count',
                'president_name',
                'president_dni',
                'association_snapshot',
            ]));

            $association->entries
                ->map(function ($entry) use ($copy, $now) {
                    $data = $entry->only([
                        'partner_id',
                        'beneficiary_id',
                        'beneficiary_history_id',
                        'partner_snapshot',
                        'beneficiary_snapshot',
                    ]);

                    return [
                        ...$data,
                        'monthly_roster_association_id' => $copy->id,
                        'partner_snapshot' => json_encode($data['partner_snapshot'], JSON_UNESCAPED_UNICODE),
                        'beneficiary_snapshot' => json_encode($data['beneficiary_snapshot'], JSON_UNESCAPED_UNICODE),
                        'created_at' => $now,
                        'updated_at' => $now,
                    ];
                })
                ->chunk(500)
                ->each(fn (Collection $rows) => DB::table('monthly_roster_entries')->insert($rows->all()));
        }
    }

    private function materialize(MonthlyRoster $roster): void
    {
        $start = $roster->period->copy()->startOfMonth()->toDateString();
        $end = $roster->period->copy()->endOfMonth()->toDateString();

        $importedAssociationIds = DB::table('association_roster_periods')
            ->whereDate('period', $start)
            ->pluck('association_id');

        $activeAssociationIds = DB::table('partners')
            ->where(fn ($query) => $query->whereNull('date_begin')->orWhereDate('date_begin', '<=', $end))
            ->where(fn ($query) => $query->whereNull('date_end')->orWhereDate('date_end', '>=', $start))
            ->distinct()
            ->pluck('association_id');

        // Con padrón importado: incluir además comités nuevos nunca importados.
        $associationIds = $importedAssociationIds->isNotEmpty()
            ? $importedAssociationIds->merge(
                $activeAssociationIds->diff(
                    DB::table('association_roster_periods')->distinct()->pluck('association_id')
                )
            )->unique()->values()
            : $activeAssociationIds;

        $associations = Association::with(['placeSector.place', 'placeSector.sector', 'typePremises'])
            ->whereIn('id', $associationIds)
            ->orderBy('code')
            ->get();

        foreach ($associations as $association) {
            $entries = $this->entryRows($association->id, $start, $end, $importedAssociationIds->isNotEmpty());
            if ($entries->isEmpty()) {
                continue;
            }

            $importedHeader = DB::table('association_roster_periods')
                ->where('association_id', $association->id)
                ->whereDate('period', $start)
                ->first();
            $president = $association->getPresidentaAt($end);
            $presidentPerson = $president?->people;

            $header = $roster->associations()->create([
                'association_id' => $association->id,
                'partner_count' => $entries->pluck('partner_id')->unique()->count(),
                'beneficiary_count' => $entries->count(),
                'president_name' => $importedHeader?->president_name
                    ?: ($presidentPerson ? $this->fullName($presidentPerson) : null),
                'president_dni' => $presidentPerson?->dni,
                'association_snapshot' => [
                    'code' => $association->officialCodeAt($end),
                    'name' => $association->name,
                    'company_name' => $association->company_name,
                    'address' => $association->address,
                    'zone_code' => $association->placeSector?->place?->code,
                    'zone_name' => $association->placeSector?->place?->title,
                    'sector_name' => $association->placeSector?->sector?->title,
                    'premises_type' => $association->typePremises?->title,
                ],
            ]);

            foreach ($entries as $entry) {
                $header->entries()->create($entry);
            }
        }
    }

    private function entryRows(int $associationId, string $start, string $end, bool $useImportedRoster): Collection
    {
        $query = DB::table('partners')
            ->join('people as partner_people', 'partner_people.id', '=', 'partners.person_id')
            ->join('beneficiaries', 'beneficiaries.partner_id', '=', 'partners.id')
            ->join('people as beneficiary_people', 'beneficiary_people.id', '=', 'beneficiaries.person_id')
            ->join('beneficiary_histories', 'beneficiary_histories.beneficiary_id', '=', 'beneficiaries.id')
            ->leftJoin('relationships', 'relationships.id', '=', 'beneficiary_histories.relationship_id')
            ->leftJoin('relationships as current_relationship', 'current_relationship.id', '=', 'beneficiaries.relationship_id')
            ->leftJoin('type_benefits', 'type_benefits.id', '=', 'beneficiary_histories.type_benefit_id')
            ->leftJoin('reason_disqualifications', 'reason_disqualifications.id', '=', 'beneficiary_histories.reason_disqualification_id')
            ->leftJoin('obstetric_data', 'obstetric_data.beneficiary_history_id', '=', 'beneficiary_histories.id')
            ->where('partners.association_id', $associationId)
            ->whereDate('beneficiary_histories.date_begin', '<=', $end)
            ->where(fn ($dates) => $dates->whereNull('beneficiary_histories.date_end')->orWhereDate('beneficiary_histories.date_end', '>=', $start));

        $activeInPeriod = fn ($q) => $q
            ->where(fn ($dates) => $dates->whereNull('partners.date_begin')->orWhereDate('partners.date_begin', '<=', $end))
            ->where(fn ($dates) => $dates->whereNull('partners.date_end')->orWhereDate('partners.date_end', '>=', $start));

        if ($useImportedRoster) {
            // Socios importados para el período, o socios nuevos que nunca fueron importados.
            $query->where(fn ($q) => $q
                ->whereExists(fn ($sub) => $sub->selectRaw('1')->from('partner_roster_periods')
                    ->whereColumn('partner_roster_periods.partner_id', 'partners.id')
                    ->whereDate('partner_roster_periods.period', $start))
                ->orWhere(fn ($new) => $new
                    ->whereNotExists(fn ($sub) => $sub->selectRaw('1')->from('partner_roster_periods')
                        ->whereColumn('partner_roster_periods.partner_id', 'partners.id'))
                    ->where($activeInPeriod)));
        } else {
            $query->where($activeInPeriod);
        }

        return $query->get([
            'partners.id as partner_id',
            'partners.date_begin as partner_date_begin',
            'partners.date_end as partner_date_end',
            'partners.observations as partner_observations',
            'partner_people.names as partner_names',
            'partner_people.father_lastname as partner_father_lastname',
            'partner_people.mother_lastname as partner_mother_lastname',
            'partner_people.dni as partner_dni',
            'partner_people.address as partner_address',
            'beneficiaries.id as beneficiary_id',
            'beneficiaries.priority',
            'beneficiary_histories.id as beneficiary_history_id',
            'beneficiary_histories.date_begin',
            'beneficiary_histories.date_end',
            'beneficiary_histories.is_malnourished',
            'beneficiary_histories.is_disabled',
            'beneficiary_people.names as beneficiary_names',
            'beneficiary_people.father_lastname as beneficiary_father_lastname',
            'beneficiary_people.mother_lastname as beneficiary_mother_lastname',
            'beneficiary_people.dni as beneficiary_dni',
            'beneficiary_people.birthdate',
            'beneficiary_people.gender',
            'type_benefits.abbreviation as benefit_type',
            'reason_disqualifications.id as reason_disqualification_id',
            'reason_disqualifications.title as reason_disqualification',
            'obstetric_data.date_last_menstrual_period as fum',
            DB::raw('COALESCE(relationships.title, current_relationship.title) as relationship'),
        ])->map(fn ($row) => [
            'partner_id' => $row->partner_id,
            'beneficiary_id' => $row->beneficiary_id,
            'beneficiary_history_id' => $row->beneficiary_history_id,
            'partner_snapshot' => [
                'names' => $row->partner_names,
                'father_lastname' => $row->partner_father_lastname,
                'mother_lastname' => $row->partner_mother_lastname,
                'dni' => $row->partner_dni,
                'address' => $row->partner_address,
                'date_begin' => $row->partner_date_begin,
                'date_end' => $row->partner_date_end,
                'observations' => $row->partner_observations,
            ],
            'beneficiary_snapshot' => [
                'names' => $row->beneficiary_names,
                'father_lastname' => $row->beneficiary_father_lastname,
                'mother_lastname' => $row->beneficiary_mother_lastname,
                'dni' => $row->beneficiary_dni,
                'birthdate' => $row->birthdate,
                'gender' => $row->gender,
                'relationship' => $row->relationship,
                'benefit_type' => $row->benefit_type,
                'priority' => (int) $row->priority,
                'date_begin' => $row->date_begin,
                'date_end' => $row->date_end,
                'reason_disqualification_id' => $row->reason_disqualification_id,
                'reason_disqualification' => $row->reason_disqualification,
                'is_malnourished' => (bool) $row->is_malnourished,
                'is_disabled' => (bool) $row->is_disabled,
                'fum' => $row->fum,
            ],
        ]);
    }

    private function fullName($person): string
    {
        return trim(collect([$person->names, $person->father_lastname, $person->mother_lastname])->filter()->implode(' '));
    }

    private function summary(MonthlyRoster $roster): array
    {
        return [
            'id' => $roster->id,
            'period' => $roster->period->toDateString(),
            'version' => $roster->version,
            'status' => $roster->status,
            'label' => $roster->label,
            'source' => $roster->source ? [
                'id' => $roster->source->id,
                'period' => $roster->source->period->toDateString(),
                'version' => $roster->source->version,
                'label' => $roster->source->label,
            ] : null,
            'association_count' => (int) ($roster->associations_count ?? $roster->associations()->count()),
            'beneficiary_count' => (int) ($roster->beneficiary_count ?? $roster->associations()->sum('beneficiary_count')),
            'correction_reason' => $roster->correction_reason,
            'closed_at' => $roster->closed_at?->toIso8601String(),
            'created_by' => $this->userDisplayName($roster->creator),
            'closed_by' => $this->userDisplayName($roster->closer),
        ];
    }

    private function userDisplayName($user): ?string
    {
        if (! $user) {
            return null;
        }

        return trim(collect([
            $user->names,
            $user->father_surname,
            $user->mother_surname,
        ])->filter()->implode(' ')) ?: $user->username;
    }
}
