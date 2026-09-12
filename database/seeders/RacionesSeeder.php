<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class RacionesSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        DB::table('raciones')->updateOrInsert([
            'year' => '2026',
            'month_start' => 1,
            'month_end' => 12,
        ], [
            'racion_hojuelas_gramos' => 51.5,
            'racion_leche_militros' => 44,
            'active' => true,
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }
}
