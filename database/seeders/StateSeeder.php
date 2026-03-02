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
        DB::table('states')->insert([
            'title' => 'Activo',
            'abbreviation' => 'A',  
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        DB::table('states')->insert([
            'title' => 'Inactivo',
            'abbreviation' => 'I',  
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        DB::table('states')->insert([
            'title' => 'Histórico',
            'abbreviation' => 'H',  
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        DB::table('states')->insert([
            'title' => 'Reconocimiento Pendiente',
            'abbreviation' => 'RP',  
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        DB::table('states')->insert([
            'title' => 'Reconocimiento Vencido',
            'abbreviation' => 'RV',  
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }
}
