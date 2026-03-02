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
        // insert into Parentescos(descripcion) values('Hijo(a)'), ('Nieto(a)'), ('Sobrino(a)'), ('Socio');
        DB::table('relationships')->insert([
            'title' => 'Hijo(a)',
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        DB::table('relationships')->insert([
            'title' => 'Nieto(a)',
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        DB::table('relationships')->insert([
            'title' => 'Sobrino(a)',
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        DB::table('relationships')->insert([
            'title' => 'Socio',
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }
}
