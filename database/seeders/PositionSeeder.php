<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class PositionSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {

        DB::table('positions')->insert([
            'title' => 'Presidenta',
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        DB::table('positions')->insert([
            'title' => 'Vice Presidenta',
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        DB::table('positions')->insert([
            'title' => 'Almacenera',
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        DB::table('positions')->insert([
            'title' => 'Tesorera',
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        DB::table('positions')->insert([
            'title' => 'Secretaria',
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        DB::table('positions')->insert([
            'title' => 'Vocal',
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        DB::table('positions')->insert([
            'title' => 'Coordinadora',
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        DB::table('positions')->insert([
            'title' => 'Fiscal',
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }
}
