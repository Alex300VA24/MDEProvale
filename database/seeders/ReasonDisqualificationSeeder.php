<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class ReasonDisqualificationSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        DB::table('reason_disqualifications')->insert([
            'title' => 'Pasó la fecha de parto',
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        DB::table('reason_disqualifications')->insert([
            'title' => 'Pasó la fecha de lactancia',
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        DB::table('reason_disqualifications')->insert([
            'title' => 'Niño mayor de 6 años',
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        DB::table('reason_disqualifications')->insert([
            'title' => 'Niño mayor de 13 años',
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }
}
