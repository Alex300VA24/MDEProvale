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
        $now = now();
        DB::table('uoms')->upsert([
            ['id' => 1, 'title' => 'Bolsa', 'created_at' => $now, 'updated_at' => $now],
            ['id' => 2, 'title' => 'Tarro', 'created_at' => $now, 'updated_at' => $now],
        ], ['id'], ['title', 'updated_at']);
    }
}
