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
        // Sectores de la Zona 1
        DB::table('sectors')->insert([
            'title' => 'Nuevo Horizonte',
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        DB::table('sectors')->insert([
            'title' => 'Los Pinos',
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        DB::table('sectors')->insert([
            'title' => 'Las Palmeras',
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        DB::table('sectors')->insert([
            'title' => 'Las Palmeras IV Sector',
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        DB::table('sectors')->insert([
            'title' => 'Las Palmeras I',
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        DB::table('sectors')->insert([
            'title' => 'Ampliación Las Palmeras',
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        DB::table('sectors')->insert([
            'title' => 'Ampliación Las Palmeras II',
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        DB::table('sectors')->insert([
            'title' => 'Ampliación Las Palmeras V',
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        DB::table('sectors')->insert([
            'title' => 'María Elena Moyano',
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        DB::table('sectors')->insert([
            'title' => 'Clementina Peralta de Acuña',
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        // Sectores de la Zona 2 id=10
        DB::table('sectors')->insert([
            'title' => 'Primavera I',
            'created_at' => now(),
            'updated_at' => now(),
        ]);
        DB::table('sectors')->insert([
            'title' => 'Primavera II',
            'created_at' => now(),
            'updated_at' => now(),
        ]);
        DB::table('sectors')->insert([
            'title' => 'Primavera III',
            'created_at' => now(),
            'updated_at' => now(),
        ]);
        DB::table('sectors')->insert([
            'title' => 'Wichanzao',
            'created_at' => now(),
            'updated_at' => now(),
        ]);
        DB::table('sectors')->insert([
            'title' => 'Wichanzao II',
            'created_at' => now(),
            'updated_at' => now(),
        ]);
        DB::table('sectors')->insert([
            'title' => 'Wichanzao III',
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        DB::table('sectors')->insert([
            'title' => 'Ampliación Clementina Peralta',
            'created_at' => now(),
            'updated_at' => now(),
        ]);
        DB::table('sectors')->insert([
            'title' => 'Ramiro Priale',
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        // Sectores de la Zona 3 id=18
        DB::table('sectors')->insert([
            'title' => 'Indoamerica',
            'created_at' => now(),
            'updated_at' => now(),
        ]);
        DB::table('sectors')->insert([
            'title' => 'Ampliación Nuevo Indoamerica',
            'created_at' => now(),
            'updated_at' => now(),
        ]);
        DB::table('sectors')->insert([
            'title' => 'Los Diamantes',
            'created_at' => now(),
            'updated_at' => now(),
        ]);
        DB::table('sectors')->insert([
            'title' => 'Los Olivos',
            'created_at' => now(),
            'updated_at' => now(),
        ]);
        DB::table('sectors')->insert([
            'title' => 'El Mirador II',
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        DB::table('sectors')->insert([
            'title' => 'Los Rosales',
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        // Sectores de la Zona 4 id=24
        DB::table('sectors')->insert([
            'title' => 'Fraternidad',
            'created_at' => now(),
            'updated_at' => now(),
        ]);
        DB::table('sectors')->insert([
            'title' => 'Fraternidad I',
            'created_at' => now(),
            'updated_at' => now(),
        ]);
        DB::table('sectors')->insert([
            'title' => 'Ampliación Fraternidad II',
            'created_at' => now(),
            'updated_at' => now(),
        ]);
        DB::table('sectors')->insert([
            'title' => 'Pueblo del Sol',
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        // Sectores de la Zona 5 id=28
        DB::table('sectors')->insert([
            'title' => 'Central',
            'created_at' => now(),
            'updated_at' => now(),
        ]);
        DB::table('sectors')->insert([
            'title' => 'San Martin',
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        // Sectores de la Zona 6 id=30
        DB::table('sectors')->insert([
            'title' => 'Nuevo Jerusalen',
            'created_at' => now(),
            'updated_at' => now(),
        ]);
        DB::table('sectors')->insert([
            'title' => 'Manuel Seoane',
            'created_at' => now(),
            'updated_at' => now(),
        ]);
        DB::table('sectors')->insert([
            'title' => 'Virgen de la Puerta',
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        // Sectores de la Zona 7 id=33
        DB::table('sectors')->insert([
            'title' => 'Santa Veronica',
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        // Sectores de la Zona 8 id=34
        DB::table('sectors')->insert([
            'title' => 'Jerusalen',
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        // Sectores de la Zona 9 id=35
        DB::table('sectors')->insert([
            'title' => 'Manuel Arevalo',
            'created_at' => now(),
            'updated_at' => now(),
        ]);
        DB::table('sectors')->insert([
            'title' => 'Villa Hermosa',
            'created_at' => now(),
            'updated_at' => now(),
        ]);
        DB::table('sectors')->insert([
            'title' => 'Alan Garcia',
            'created_at' => now(),
            'updated_at' => now(),
        ]);
        // Sectores de la Zona 10 id=39
        DB::table('sectors')->insert([
            'title' => 'Virgen del Socorro',
            'created_at' => now(),
            'updated_at' => now(),
        ]);
        DB::table('sectors')->insert([
            'title' => 'Sol Naciente',
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        // Los Laureles : id=40
        DB::table('sectors')->insert([
            'title' => 'Los Laureles',
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }
}
