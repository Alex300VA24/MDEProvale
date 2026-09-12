<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
class TypeTransactionSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        // insert into TipoMovimiento(descripcion) values('Ingreso'),('Salida');
        DB::table('type_transactions')->insert([
            'title' => 'Ingreso',
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        DB::table('type_transactions')->insert([
            'title' => 'Salida',
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }
}
