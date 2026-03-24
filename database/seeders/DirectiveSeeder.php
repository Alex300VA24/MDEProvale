<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class DirectiveSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        DB::table('directives')->insert([
            'resolution_id' => 40,
            'partner_id'    => 1086,
            'position_id'   => 1,
            'state_id'      => 1,
            'created_at'    => now(),
            'updated_at'    => now()
        ]);
    }
}
