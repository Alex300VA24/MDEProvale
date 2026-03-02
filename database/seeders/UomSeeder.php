<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
class UomSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        // insert into UnidadMedida(descripcion) values('Bolsa'),('Tarro');
        DB::table('uoms')->insert([
            'title' => 'Bolsa',
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        DB::table('uoms')->insert([
            'title' => 'Tarro',
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }
}
