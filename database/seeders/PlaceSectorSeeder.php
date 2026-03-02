<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class PlaceSectorSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        // insert into SectoresZona(codSector, codZona) values(1, 1),(1,2),(2, 2),(3, 1),(3,2);
        DB::table('place_sectors')->insert([
            'sector_id' => 1,
            'place_id' => 1,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        DB::table('place_sectors')->insert([
            'sector_id' => 1,
            'place_id' => 2,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        DB::table('place_sectors')->insert([
            'sector_id' => 2,
            'place_id' => 2,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        DB::table('place_sectors')->insert([
            'sector_id' => 3,
            'place_id' => 1,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        DB::table('place_sectors')->insert([
            'sector_id' => 3,
            'place_id' => 2,
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }
}
