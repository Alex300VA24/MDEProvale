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
        DB::table('raciones')->insert([
            'year' => '2026',
            'racion_hojuelas_gramos' => 51.5,
            'racion_leche_militros' => 44,
            'created_at' => now(),
            'updated_at' => now()
        ]);

    }
}
