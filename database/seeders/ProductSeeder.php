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
            'title' => 'Leche Evaporada',
            'code' => 'PROD-001',
            'abbreviation' => 'LCH',
            'stock' => 100,
            'unit_price' => 100.00,
            'state_id' => 1,
            'uom_id' => 1,
        ]);
        
        DB::table('products')->insert([
            'title' => 'Hojuela de Quinua',
            'code' => 'PROD-002',
            'abbreviation' => 'QUN',
            'stock' => 100,
            'unit_price' => 100.00,
            'state_id' => 1,
            'uom_id' => 1,
        ]);
    }
}
