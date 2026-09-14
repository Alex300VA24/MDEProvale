<?php

use Carbon\Carbon;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('association_roster_periods') || ! Schema::hasTable('pecosas')) {
            return;
        }

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

        DB::table('pecosas')
            ->select(['id', 'association_id', 'delivery_date'])
            ->orderBy('id')
            ->chunkById(500, function ($pecosas) use ($presidentsByPeriod) {
                foreach ($pecosas as $pecosa) {
                    $deliveryDate = $pecosa->delivery_date ? Carbon::parse($pecosa->delivery_date) : null;
                    if ($deliveryDate && $deliveryDate->day > $deliveryDate->daysInMonth - 7) {
                        $deliveryDate->addMonthNoOverflow();
                    }
                    $period = $deliveryDate
                        ? $deliveryDate->startOfMonth()->toDateString()
                        : null;
                    $president = $period
                        ? $presidentsByPeriod->get($pecosa->association_id . '|' . $period)
                        : null;

                    if (! $president) {
                        continue;
                    }

                    DB::table('pecosas')->where('id', $pecosa->id)->update([
                        'president_id' => $president->president_partner_id,
                        'managing_partner_id' => $president->president_partner_id,
                        'president_name' => $president->president_name,
                        'president_dni' => $president->president_dni,
                        'managing_partner_name' => $president->president_name,
                        'managing_partner_dni' => $president->president_dni,
                        'beneficiaries_count' => $president->beneficiary_count,
                        'updated_at' => now(),
                    ]);
                }
            });
    }

    public function down(): void
    {
        // Datos históricos: no se borran durante rollback.
    }
};
