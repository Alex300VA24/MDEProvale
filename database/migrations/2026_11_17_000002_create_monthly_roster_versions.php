<?php

use Carbon\Carbon;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('monthly_rosters', function (Blueprint $table) {
            $table->id();
            $table->date('period');
            $table->unsignedSmallInteger('version')->default(1);
            $table->string('status', 20)->default('DRAFT');
            $table->foreignId('source_roster_id')->nullable()->constrained('monthly_rosters')->nullOnDelete();
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('closed_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('closed_at')->nullable();
            $table->text('correction_reason')->nullable();
            $table->timestamps();

            $table->unique(['period', 'version'], 'monthly_rosters_period_version_unique');
            $table->index(['period', 'status'], 'monthly_rosters_period_status_index');
        });

        Schema::create('monthly_roster_associations', function (Blueprint $table) {
            $table->id();
            $table->foreignId('monthly_roster_id')->constrained('monthly_rosters')->cascadeOnDelete();
            $table->foreignId('association_id')->nullable()->constrained('associations')->nullOnDelete();
            $table->unsignedInteger('partner_count')->default(0);
            $table->unsignedInteger('beneficiary_count')->default(0);
            $table->string('president_name', 200)->nullable();
            $table->string('president_dni', 20)->nullable();
            $table->json('association_snapshot');
            $table->timestamps();

            $table->unique(['monthly_roster_id', 'association_id'], 'monthly_roster_association_unique');
        });

        Schema::create('monthly_roster_entries', function (Blueprint $table) {
            $table->id();
            $table->foreignId('monthly_roster_association_id')
                ->constrained('monthly_roster_associations', indexName: 'monthly_roster_entries_association_fk')
                ->cascadeOnDelete();
            $table->foreignId('partner_id')->nullable()->constrained('partners')->nullOnDelete();
            $table->foreignId('beneficiary_id')->nullable()->constrained('beneficiaries')->nullOnDelete();
            $table->foreignId('beneficiary_history_id')->nullable()->constrained('beneficiary_histories')->nullOnDelete();
            $table->json('partner_snapshot');
            $table->json('beneficiary_snapshot');
            $table->timestamps();

            $table->index(['monthly_roster_association_id', 'partner_id'], 'monthly_roster_entries_partner_index');
        });

        Schema::table('pecosas', function (Blueprint $table) {
            $table->date('service_period')->nullable()->after('delivery_date')->index();
            $table->foreignId('monthly_roster_id')
                ->nullable()
                ->after('service_period')
                ->constrained('monthly_rosters')
                ->nullOnDelete();
        });

        Schema::table('distribution_periods', function (Blueprint $table) {
            $table->foreignId('monthly_roster_id')
                ->nullable()
                ->after('month')
                ->constrained('monthly_rosters')
                ->nullOnDelete();
        });

        DB::table('pecosas')->orderBy('id')->chunkById(500, function ($pecosas) {
            foreach ($pecosas as $pecosa) {
                $date = Carbon::parse($pecosa->delivery_date);
                $effective = $date->day > $date->daysInMonth - 7
                    ? $date->copy()->addMonthNoOverflow()->startOfMonth()
                    : $date->copy()->startOfMonth();

                DB::table('pecosas')->where('id', $pecosa->id)->update([
                    'service_period' => $effective->toDateString(),
                ]);
            }
        });

        $this->bootstrapHistoricalRosters();
        $this->linkHistoricalUsesToAvailableRosters();
    }

    private function linkHistoricalUsesToAvailableRosters(): void
    {
        $rosters = DB::table('monthly_rosters')
            ->where('status', 'CLOSED')
            ->orderByDesc('period')
            ->orderByDesc('version')
            ->get(['id', 'period']);

        if ($rosters->isEmpty()) {
            return;
        }

        DB::table('pecosas')
            ->whereNull('monthly_roster_id')
            ->whereNotNull('service_period')
            ->orderBy('id')
            ->chunkById(500, function ($pecosas) use ($rosters) {
                foreach ($pecosas as $pecosa) {
                    $roster = $rosters->first(
                        fn ($candidate) => $candidate->period <= $pecosa->service_period
                    );
                    if ($roster) {
                        DB::table('pecosas')->where('id', $pecosa->id)->update([
                            'monthly_roster_id' => $roster->id,
                        ]);
                    }
                }
            });

        DB::table('distribution_periods')
            ->whereNull('monthly_roster_id')
            ->orderBy('id')
            ->chunkById(500, function ($periods) use ($rosters) {
                foreach ($periods as $period) {
                    $servicePeriod = sprintf('%04d-%02d-01', $period->year, $period->month);
                    $roster = $rosters->first(
                        fn ($candidate) => $candidate->period <= $servicePeriod
                    );
                    if ($roster) {
                        DB::table('distribution_periods')->where('id', $period->id)->update([
                            'monthly_roster_id' => $roster->id,
                        ]);
                    }
                }
            });
    }

    private function bootstrapHistoricalRosters(): void
    {
        if (! Schema::hasTable('association_roster_periods')) {
            return;
        }

        $periods = DB::table('association_roster_periods')
            ->select('period')
            ->distinct()
            ->orderBy('period')
            ->pluck('period');

        foreach ($periods as $periodValue) {
            $period = Carbon::parse($periodValue)->startOfMonth();
            $rosterId = DB::table('monthly_rosters')->insertGetId([
                'period' => $period->toDateString(),
                'version' => 1,
                'status' => 'CLOSED',
                'closed_at' => now(),
                'created_at' => now(),
                'updated_at' => now(),
            ]);

            $headers = DB::table('association_roster_periods as roster')
                ->join('associations', 'associations.id', '=', 'roster.association_id')
                ->leftJoin('place_sectors', 'place_sectors.id', '=', 'associations.place_sector_id')
                ->leftJoin('places', 'places.id', '=', 'place_sectors.place_id')
                ->leftJoin('sectors', 'sectors.id', '=', 'place_sectors.sector_id')
                ->leftJoin('type_premises', 'type_premises.id', '=', 'associations.type_premises_id')
                ->leftJoin('partners as president_partner', 'president_partner.id', '=', 'roster.president_partner_id')
                ->leftJoin('people as president_person', 'president_person.id', '=', 'president_partner.person_id')
                ->whereDate('roster.period', $period->toDateString())
                ->get([
                    'roster.*',
                    'associations.code',
                    'associations.name',
                    'associations.company_name',
                    'associations.address',
                    'places.code as zone_code',
                    'places.title as zone_name',
                    'sectors.title as sector_name',
                    'type_premises.title as premises_type',
                    'president_person.dni as president_dni',
                ]);

            foreach ($headers as $header) {
                $snapshotId = DB::table('monthly_roster_associations')->insertGetId([
                    'monthly_roster_id' => $rosterId,
                    'association_id' => $header->association_id,
                    'partner_count' => $header->partner_count,
                    'beneficiary_count' => $header->beneficiary_count,
                    'president_name' => $header->president_name,
                    'president_dni' => $header->president_dni,
                    'association_snapshot' => json_encode([
                        'code' => $header->code,
                        'name' => $header->name,
                        'company_name' => $header->company_name,
                        'address' => $header->address,
                        'zone_code' => $header->zone_code,
                        'zone_name' => $header->zone_name,
                        'sector_name' => $header->sector_name,
                        'premises_type' => $header->premises_type,
                    ], JSON_UNESCAPED_UNICODE),
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);

                $this->snapshotEntries($snapshotId, (int) $header->association_id, $period);
            }
        }
    }

    private function snapshotEntries(int $associationSnapshotId, int $associationId, Carbon $period): void
    {
        $start = $period->copy()->startOfMonth()->toDateString();
        $end = $period->copy()->endOfMonth()->toDateString();

        $rows = DB::table('partner_roster_periods as roster')
            ->join('partners', 'partners.id', '=', 'roster.partner_id')
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
            ->whereDate('roster.period', $start)
            ->whereDate('beneficiary_histories.date_begin', '<=', $end)
            ->where(function ($query) use ($start) {
                $query->whereNull('beneficiary_histories.date_end')
                    ->orWhereDate('beneficiary_histories.date_end', '>=', $start);
            })
            ->get([
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
            ]);

        $inserts = $rows->map(fn ($row) => [
            'monthly_roster_association_id' => $associationSnapshotId,
            'partner_id' => $row->partner_id,
            'beneficiary_id' => $row->beneficiary_id,
            'beneficiary_history_id' => $row->beneficiary_history_id,
            'partner_snapshot' => json_encode([
                'names' => $row->partner_names,
                'father_lastname' => $row->partner_father_lastname,
                'mother_lastname' => $row->partner_mother_lastname,
                'dni' => $row->partner_dni,
                'address' => $row->partner_address,
                'date_begin' => $row->partner_date_begin,
                'date_end' => $row->partner_date_end,
                'observations' => $row->partner_observations,
            ], JSON_UNESCAPED_UNICODE),
            'beneficiary_snapshot' => json_encode([
                'names' => $row->beneficiary_names,
                'father_lastname' => $row->beneficiary_father_lastname,
                'mother_lastname' => $row->beneficiary_mother_lastname,
                'dni' => $row->beneficiary_dni,
                'birthdate' => $row->birthdate,
                'gender' => $row->gender,
                'relationship' => $row->relationship,
                'benefit_type' => $row->benefit_type,
                'priority' => $row->priority,
                'date_begin' => $row->date_begin,
                'date_end' => $row->date_end,
                'reason_disqualification_id' => $row->reason_disqualification_id,
                'reason_disqualification' => $row->reason_disqualification,
                'is_malnourished' => (bool) $row->is_malnourished,
                'is_disabled' => (bool) $row->is_disabled,
                'fum' => $row->fum,
            ], JSON_UNESCAPED_UNICODE),
            'created_at' => now(),
            'updated_at' => now(),
        ])->all();

        foreach (array_chunk($inserts, 500) as $chunk) {
            DB::table('monthly_roster_entries')->insert($chunk);
        }
    }

    public function down(): void
    {
        Schema::table('distribution_periods', function (Blueprint $table) {
            $table->dropConstrainedForeignId('monthly_roster_id');
        });

        Schema::table('pecosas', function (Blueprint $table) {
            $table->dropConstrainedForeignId('monthly_roster_id');
            $table->dropColumn('service_period');
        });

        Schema::dropIfExists('monthly_roster_entries');
        Schema::dropIfExists('monthly_roster_associations');
        Schema::dropIfExists('monthly_rosters');
    }
};
