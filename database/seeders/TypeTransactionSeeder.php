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
        $now = now();
        DB::table('type_transactions')->upsert([
            ['id' => 1, 'title' => 'Ingreso', 'created_at' => $now, 'updated_at' => $now],
            ['id' => 2, 'title' => 'Salida', 'created_at' => $now, 'updated_at' => $now],
        ], ['id'], ['title', 'updated_at']);
    }
}
