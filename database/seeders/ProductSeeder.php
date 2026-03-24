<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class ProductSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        DB::table('products')->insert([
            'title' => 'LECHE EVAPORADA ENTERA',
            'abbreviation' => 'LEC',
            'state_id' => 1,
            'uom_id' => 2,
        ]);
        
        DB::table('products')->insert([
            'title' => 'HOJUELA DE QUINUA AVENA PRECOCIDO FORTIFICADO CON VITAMINAS Y MINERALES',
            'abbreviation' => 'HOJ',
            'state_id' => 1,
            'uom_id' => 1,
        ]);
    }
}
