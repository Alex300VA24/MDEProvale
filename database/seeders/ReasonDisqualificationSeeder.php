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
            'title' => 'Ninguna',
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        DB::table('reason_disqualifications')->insert([
            'title' => 'Pas├│ la fecha de parto',
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        DB::table('reason_disqualifications')->insert([
            'title' => 'Pas├│ la fecha de lactancia',
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        DB::table('reason_disqualifications')->insert([
            'title' => 'Ni├▒o mayor de 13 a├▒os',
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }
}
