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
        foreach (['Socio', 'Hijo', 'Hijos', 'Apoderado', 'Nieto', 'Tutelado'] as $title) {
            DB::table('relationships')->updateOrInsert(
                ['title' => $title],
                ['updated_at' => now(), 'created_at' => now()]
            );
        }
    }
}
