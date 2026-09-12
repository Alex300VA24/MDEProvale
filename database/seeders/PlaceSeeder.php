<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
class PlaceSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        DB::table('places')->insert([
            'code' => '01',
            'title' => 'Zona 1',
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        DB::table('places')->insert([
            'code' => '02',
            'title' => 'Zona 2',
            'created_at' => now(),
            'updated_at' => now(),
        ]);
        DB::table('places')->insert([
            'code' => '03',
            'title' => 'Zona 3',
            'created_at' => now(),
            'updated_at' => now(),
        ]);
        DB::table('places')->insert([
            'code' => '04',
            'title' => 'Zona 4',
            'created_at' => now(),
            'updated_at' => now(),
        ]);
        DB::table('places')->insert([
            'code' => '05',
            'title' => 'Zona 5',
            'created_at' => now(),
            'updated_at' => now(),
        ]);
        DB::table('places')->insert([
            'code' => '06',
            'title' => 'Zona 6',
            'created_at' => now(),
            'updated_at' => now(),
        ]);
        DB::table('places')->insert([
            'code' => '07',
            'title' => 'Zona 7',
            'created_at' => now(),
            'updated_at' => now(),
        ]);
        DB::table('places')->insert([
            'code' => '08',
            'title' => 'Zona 8',
            'created_at' => now(),
            'updated_at' => now(),
        ]);
        DB::table('places')->insert([
            'code' => '09',
            'title' => 'Zona 9',
            'created_at' => now(),
            'updated_at' => now(),
        ]);
        DB::table('places')->insert([
            'code' => '10',
            'title' => 'Zona 10',
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }
}
