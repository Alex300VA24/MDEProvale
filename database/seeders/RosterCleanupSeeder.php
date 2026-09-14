<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class RosterCleanupSeeder extends Seeder
{
    /**
     * El padrón marzo-setiembre 2026 es la fuente de verdad de socios,
     * beneficiarios y presidentas. Las resoluciones no se eliminan.
     */
    public function run(): void
    {
        DB::transaction(function () {
            // Las PECOSA conservan sus nombres históricos y liberan referencias
            // a socios que serán reconstruidos desde el padrón mensual.
            DB::table('pecosas')->update([
                'managing_partner_id' => null,
                'president_id' => null,
            ]);

            DB::table('directives')->delete();
            DB::table('obstetric_data')->delete();
            DB::table('beneficiary_histories')->delete();
            DB::table('beneficiaries')->delete();

            if (Schema::hasTable('association_roster_periods')) {
                DB::table('association_roster_periods')->delete();
            }

            if (Schema::hasTable('partner_roster_periods')) {
                DB::table('partner_roster_periods')->delete();
            }

            DB::table('partners')->delete();
        });
    }
}
