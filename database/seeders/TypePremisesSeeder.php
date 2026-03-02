<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
class TypePremisesSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        // insert into TiposLocal(descripcion) values('propio'),('provisional'),('municipalidad');
        DB::table('type_premises')->insert([
            'title' => 'Propio',
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        DB::table('type_premises')->insert([
            'title' => 'Provisional',
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        DB::table('type_premises')->insert([
            'title' => 'Municipalidad',
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }
}
