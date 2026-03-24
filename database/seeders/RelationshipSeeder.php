<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;


class RelationshipSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        DB::table('relationships')->insert([
            'title' => 'Socio',
            'created_at' => now(),
            'updated_at' => now(),
        ]);
        DB::table('relationships')->insert([
            'title' => 'Hijos',
            'created_at' => now(),
            'updated_at' => now(),
        ]);
        DB::table('relationships')->insert([
            'title' => 'Apoderado',
            'created_at' => now(),
            'updated_at' => now(),
        ]);
        DB::table('relationships')->insert([
            'title' => 'Tutelado',
            'created_at' => now(),
            'updated_at' => now(),
        ]);

    }
}
