<?php

use Carbon\Carbon;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    private const PERIOD = '2026-09-01';
    private const PERIOD_END = '2026-09-30';
    private const PREVIOUS_END = '2026-08-31';

    public function up(): void
    {
        Schema::create('association_code_histories', function (Blueprint $table) {
            $table->id();
            $table->foreignId('association_id')->constrained('associations')->cascadeOnDelete();
            $table->string('code', 20);
            $table->date('valid_from');
            $table->date('valid_to')->nullable();
            $table->string('source', 30)->default('SISTEMA');
            $table->text('observation')->nullable();
            $table->timestamps();

            $table->unique(['association_id', 'valid_from'], 'association_code_history_period_unique');
            $table->index(['code', 'valid_from', 'valid_to'], 'association_code_history_lookup');
        });

        $solYAreanaId = DB::table('associations')->where('code', '019')->value('id');
        $isabelId = DB::table('associations')->where('code', '390')->value('id');

        // En una instalación nueva las asociaciones se crean después, mediante
        // seeders. AssociationCodeHistorySeeder completa allí este catálogo.
        if (!$solYAreanaId || !$isabelId) {
            return;
        }

        DB::transaction(function () use ($solYAreanaId, $isabelId) {
            DB::table('associations')->where('id', $solYAreanaId)->update([
                'code' => '019-H',
                'observation' => 'Identidad histórica de SOL Y ARENA DE LAS PALMERITAS; usó el código 019 hasta agosto de 2026.',
                'updated_at' => now(),
            ]);
            DB::table('associations')->where('id', $isabelId)->update([
                'code' => '019',
                'observation' => 'ISABEL CRUZ DE MARCELO; usa el código oficial 019 desde setiembre de 2026. En la importación de SQL Server fue identificado provisionalmente como 390.',
                'updated_at' => now(),
            ]);

            DB::table('resolutions')
                ->where('document', 'PENDIENTE-COMITE-390')
                ->update(['document' => 'PENDIENTE-COMITE-019-ISABEL', 'updated_at' => now()]);

            $this->insertCodeHistory($solYAreanaId, '2024-06-28', self::PREVIOUS_END,
                'SOL Y ARENA DE LAS PALMERITAS; código 019 antes de su reutilización.');
            $this->insertCodeHistory($isabelId, self::PERIOD, null,
                'ISABEL CRUZ DE MARCELO; código 019 reutilizado desde setiembre de 2026.');

            $partnerMap = $this->moveSeptemberPartners($solYAreanaId, $isabelId);
            $this->moveSeptemberDirectiveAndSummary($solYAreanaId, $isabelId, $partnerMap);
            $this->backfillPecosaSnapshots();
        });
    }

    public function down(): void
    {
        DB::transaction(function () {
            $isabelId = DB::table('associations')->where('code', '019')->where('name', 'ISABEL CRUZ DE MARCELO')->value('id');
            $historicalId = DB::table('associations')->where('code', '019-H')->value('id');

            if ($isabelId) {
                DB::table('associations')->where('id', $isabelId)->update(['code' => '390', 'updated_at' => now()]);
            }
            if ($historicalId) {
                DB::table('associations')->where('id', $historicalId)->update(['code' => '019', 'updated_at' => now()]);
            }
        });

        Schema::dropIfExists('association_code_histories');
    }

    private function insertCodeHistory(int $associationId, string $validFrom, ?string $validTo, string $observation): void
    {
        DB::table('association_code_histories')->insert([
            'association_id' => $associationId,
            'code' => '019',
            'valid_from' => $validFrom,
            'valid_to' => $validTo,
            'source' => 'PADRON',
            'observation' => $observation,
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }

    /** @return array<int, int> */
    private function moveSeptemberPartners(int $sourceAssociationId, int $targetAssociationId): array
    {
        $currentStateId = DB::table('states')->where('abbreviation', 'VIG')->value('id');
        $expiredStateId = DB::table('states')->where('abbreviation', 'VEN')->value('id');
        $sourcePartnerIds = DB::table('partner_roster_periods as roster')
            ->join('partners', 'partners.id', '=', 'roster.partner_id')
            ->where('partners.association_id', $sourceAssociationId)
            ->whereDate('roster.period', self::PERIOD)
            ->pluck('partners.id');

        $partnerMap = [];
        foreach ($sourcePartnerIds as $sourcePartnerId) {
            $source = DB::table('partners')->where('id', $sourcePartnerId)->first();
            $hasEarlierRoster = DB::table('partner_roster_periods')
                ->where('partner_id', $sourcePartnerId)
                ->whereDate('period', '<', self::PERIOD)
                ->exists();

            if (!$hasEarlierRoster) {
                DB::table('partners')->where('id', $sourcePartnerId)->update([
                    'association_id' => $targetAssociationId,
                    'date_begin' => self::PERIOD,
                    'date_end' => null,
                    'state_id' => $currentStateId,
                    'updated_at' => now(),
                ]);
                $partnerMap[(int) $sourcePartnerId] = (int) $sourcePartnerId;
                continue;
            }

            $targetPartnerId = DB::table('partners')
                ->where('person_id', $source->person_id)
                ->where('association_id', $targetAssociationId)
                ->value('id');

            if (!$targetPartnerId) {
                $copy = (array) $source;
                unset($copy['id']);
                $copy['association_id'] = $targetAssociationId;
                $copy['date_begin'] = self::PERIOD;
                $copy['date_end'] = null;
                $copy['state_id'] = $currentStateId;
                $copy['created_at'] = now();
                $copy['updated_at'] = now();
                $targetPartnerId = DB::table('partners')->insertGetId($copy);
            }

            $partnerMap[(int) $sourcePartnerId] = (int) $targetPartnerId;
            $this->moveSeptemberBeneficiaries((int) $sourcePartnerId, (int) $targetPartnerId);

            $lastHistoricalPeriod = DB::table('partner_roster_periods')
                ->where('partner_id', $sourcePartnerId)
                ->whereDate('period', '<', self::PERIOD)
                ->max('period');
            DB::table('partners')->where('id', $sourcePartnerId)->update([
                'date_end' => Carbon::parse($lastHistoricalPeriod)->endOfMonth()->toDateString(),
                'state_id' => $expiredStateId,
                'position_id' => null,
                'updated_at' => now(),
            ]);
        }

        foreach ($partnerMap as $sourcePartnerId => $targetPartnerId) {
            if ($sourcePartnerId !== $targetPartnerId) {
                DB::table('partner_roster_periods')
                    ->where('partner_id', $sourcePartnerId)
                    ->whereDate('period', self::PERIOD)
                    ->update(['partner_id' => $targetPartnerId, 'updated_at' => now()]);
            }
        }

        return $partnerMap;
    }

    private function moveSeptemberBeneficiaries(int $sourcePartnerId, int $targetPartnerId): void
    {
        $beneficiaries = DB::table('beneficiaries as beneficiary')
            ->where('beneficiary.partner_id', $sourcePartnerId)
            ->whereExists(function ($query) {
                $query->selectRaw('1')
                    ->from('beneficiary_histories as history')
                    ->whereColumn('history.beneficiary_id', 'beneficiary.id')
                    ->whereDate('history.date_begin', '<=', self::PERIOD_END)
                    ->where(fn ($dates) => $dates->whereNull('history.date_end')->orWhereDate('history.date_end', '>=', self::PERIOD));
            })
            ->get();

        foreach ($beneficiaries as $beneficiary) {
            $hasEarlierHistory = DB::table('beneficiary_histories')
                ->where('beneficiary_id', $beneficiary->id)
                ->whereDate('date_begin', '<', self::PERIOD)
                ->exists();

            if (!$hasEarlierHistory) {
                DB::table('beneficiaries')->where('id', $beneficiary->id)->update([
                    'partner_id' => $targetPartnerId,
                    'updated_at' => now(),
                ]);
                continue;
            }

            $targetBeneficiaryId = DB::table('beneficiaries')
                ->where('person_id', $beneficiary->person_id)
                ->where('partner_id', $targetPartnerId)
                ->value('id');
            if (!$targetBeneficiaryId) {
                $copy = (array) $beneficiary;
                unset($copy['id']);
                $copy['partner_id'] = $targetPartnerId;
                $copy['created_at'] = now();
                $copy['updated_at'] = now();
                $targetBeneficiaryId = DB::table('beneficiaries')->insertGetId($copy);
            }

            DB::table('beneficiary_histories')
                ->where('beneficiary_id', $beneficiary->id)
                ->whereDate('date_begin', '>=', self::PERIOD)
                ->update(['beneficiary_id' => $targetBeneficiaryId, 'updated_at' => now()]);
        }
    }

    /** @param array<int, int> $partnerMap */
    private function moveSeptemberDirectiveAndSummary(
        int $sourceAssociationId,
        int $targetAssociationId,
        array $partnerMap
    ): void {
        foreach ($partnerMap as $sourcePartnerId => $targetPartnerId) {
            if ($sourcePartnerId === $targetPartnerId) {
                continue;
            }

            DB::table('directives')
                ->where('partner_id', $sourcePartnerId)
                ->whereDate('date_start', '>=', self::PERIOD)
                ->update(['partner_id' => $targetPartnerId, 'updated_at' => now()]);
        }

        $summary = DB::table('association_roster_periods')
            ->where('association_id', $sourceAssociationId)
            ->whereDate('period', self::PERIOD)
            ->first();
        if (!$summary) {
            return;
        }

        $presidentPartnerId = $summary->president_partner_id
            ? ($partnerMap[(int) $summary->president_partner_id] ?? $summary->president_partner_id)
            : null;
        DB::table('association_roster_periods')->where('id', $summary->id)->update([
            'association_id' => $targetAssociationId,
            'president_partner_id' => $presidentPartnerId,
            'updated_at' => now(),
        ]);
    }

    private function backfillPecosaSnapshots(): void
    {
        $pecosas = DB::table('pecosas')
            ->where(function ($query) {
                $query->whereNull('beneficiaries_count')
                    ->orWhereNull('president_name')
                    ->orWhereIn('association_id', DB::table('associations')->where('code', '019')->select('id'));
            })
            ->get();

        foreach ($pecosas as $pecosa) {
            $effective = Carbon::parse($pecosa->delivery_date);
            if ($effective->day > $effective->daysInMonth - 7) {
                $effective->addMonthNoOverflow()->startOfDay();
            }
            $periodStart = $effective->copy()->startOfMonth()->toDateString();
            $periodEnd = $effective->copy()->endOfMonth()->toDateString();

            $roster = DB::table('association_roster_periods')
                ->where('association_id', $pecosa->association_id)
                ->whereDate('period', $periodStart)
                ->first();
            $beneficiaryCount = $roster?->beneficiary_count;
            if ($beneficiaryCount === null) {
                $beneficiaryCount = DB::table('beneficiary_histories as history')
                    ->join('beneficiaries', 'beneficiaries.id', '=', 'history.beneficiary_id')
                    ->join('partners', 'partners.id', '=', 'beneficiaries.partner_id')
                    ->where('partners.association_id', $pecosa->association_id)
                    ->whereDate('history.date_begin', '<=', $periodEnd)
                    ->where(fn ($dates) => $dates->whereNull('history.date_end')->orWhereDate('history.date_end', '>=', $periodStart))
                    ->count();
                if ($beneficiaryCount === 0) {
                    $beneficiaryCount = null;
                }
            }

            $presidentPartnerId = $roster?->president_partner_id;
            $presidentName = $roster?->president_name;
            $presidentDni = null;
            if (!$presidentPartnerId) {
                $directive = DB::table('directives')
                    ->join('partners', 'partners.id', '=', 'directives.partner_id')
                    ->join('positions', 'positions.id', '=', 'directives.position_id')
                    ->where('partners.association_id', $pecosa->association_id)
                    ->where('positions.title', 'PRESIDENTA')
                    ->where(fn ($dates) => $dates->whereNull('directives.date_start')->orWhereDate('directives.date_start', '<=', $periodEnd))
                    ->where(fn ($dates) => $dates->whereNull('directives.date_end')->orWhereDate('directives.date_end', '>=', $periodStart))
                    ->orderByDesc('directives.date_start')
                    ->first(['partners.id as partner_id']);
                $presidentPartnerId = $directive?->partner_id;
            }

            if ($presidentPartnerId) {
                $person = DB::table('partners')
                    ->join('people', 'people.id', '=', 'partners.person_id')
                    ->where('partners.id', $presidentPartnerId)
                    ->first(['people.names', 'people.father_lastname', 'people.mother_lastname', 'people.dni']);
                if ($person) {
                    $presidentName = trim(collect([$person->names, $person->father_lastname, $person->mother_lastname])->filter()->implode(' '));
                    $presidentDni = $person->dni;
                }
            }

            $officialCode = DB::table('association_code_histories')
                ->where('association_id', $pecosa->association_id)
                ->whereDate('valid_from', '<=', $periodEnd)
                ->where(fn ($dates) => $dates->whereNull('valid_to')->orWhereDate('valid_to', '>=', $periodStart))
                ->orderByDesc('valid_from')
                ->value('code');

            $updates = array_filter([
                'beneficiaries_count' => $beneficiaryCount,
                'president_id' => $presidentPartnerId,
                'managing_partner_id' => $presidentPartnerId,
                'president_name' => $presidentName,
                'managing_partner_name' => $presidentName,
                'president_dni' => $presidentDni,
                'managing_partner_dni' => $presidentDni,
                'association_code' => $officialCode,
                'updated_at' => now(),
            ], fn ($value) => $value !== null);

            if (count($updates) > 1) {
                DB::table('pecosas')->where('id', $pecosa->id)->update($updates);
            }
        }
    }
};
