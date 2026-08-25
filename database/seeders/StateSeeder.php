<?php

namespace Database\Seeders;

use Illuminate\Support\Facades\DB;
use Illuminate\Database\Seeder;

class StateSeeder extends Seeder
{
    /**
     * Run the database seeds.
     * 
     * @return void
     */
    public function run()
    {
        $now = now();

        DB::table('states')->upsert([
            ['id' => 1, 'title' => 'Activo', 'abbreviation' => 'ACT', 'created_at' => $now, 'updated_at' => $now],
            ['id' => 2, 'title' => 'Inactivo', 'abbreviation' => 'INA', 'created_at' => $now, 'updated_at' => $now],
            ['id' => 3, 'title' => 'Vigente', 'abbreviation' => 'VIG', 'created_at' => $now, 'updated_at' => $now],
            ['id' => 4, 'title' => 'Vencido', 'abbreviation' => 'VEN', 'created_at' => $now, 'updated_at' => $now],
            ['id' => 5, 'title' => 'Pendiente', 'abbreviation' => 'PEN', 'created_at' => $now, 'updated_at' => $now],
        ], ['id'], ['title', 'abbreviation', 'updated_at']);
    }
}
