<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class ResponsibleSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        DB::table('responsibles')->insert([
            'person_id' => 5655,
            'type' => 'chief',
            'active' => 1,
        ]);

        DB::table('responsibles')->insert([
            'person_id' => 5653,
            'type' => 'storekeeper',
            'active' => 1,
        ]);
    }
}
