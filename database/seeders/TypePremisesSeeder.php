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
        $now = now();
        DB::table('type_premises')->upsert([
            ['id' => 1, 'title' => 'Provisional', 'created_at' => $now, 'updated_at' => $now],
            ['id' => 2, 'title' => 'Propio', 'created_at' => $now, 'updated_at' => $now],
            ['id' => 3, 'title' => 'Municipalidad', 'created_at' => $now, 'updated_at' => $now],
        ], ['id'], ['title', 'updated_at']);
    }
}
