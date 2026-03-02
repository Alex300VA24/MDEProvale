<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class SectorSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        DB::table('sectors')->insert([
            'title' => 'Wichanzao',
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        DB::table('sectors')->insert([
            'title' => 'Central',
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        DB::table('sectors')->insert([
            'title' => 'Jerusalén',
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }
}
