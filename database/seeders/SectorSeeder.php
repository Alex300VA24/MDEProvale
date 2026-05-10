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
        // Sectores de la Zona 1 id = 1
        DB::table('sectors')->insert([
            'title' => 'Nuevo Horizonte',
            'created_at' => now(),
            'updated_at' => now(),
        ]);
        // Sectores de la Zona 1 id = 2
        DB::table('sectors')->insert([ 
            'title' => 'Los Pinos',
            'created_at' => now(),
            'updated_at' => now(),
        ]);
        // Sectores de la Zona 1 id = 3
        DB::table('sectors')->insert([
            'title' => 'Las Palmeras',
            'created_at' => now(),
            'updated_at' => now(),
        ]);
        // Sectores de la Zona 1 id = 4
        DB::table('sectors')->insert([
            'title' => 'Las Palmeras IV Sector',
            'created_at' => now(),
            'updated_at' => now(),
        ]);
        // Sectores de la Zona 1 id = 5
        DB::table('sectors')->insert([
            'title' => 'Las Palmeras I',
            'created_at' => now(),
            'updated_at' => now(),
        ]);
        // Sectores de la Zona 1 id = 6
        DB::table('sectors')->insert([
            'title' => 'Las Palmeras II',
            'created_at' => now(),
            'updated_at' => now(),
        ]);
        // Sectores de la Zona 1 id = 7
        DB::table('sectors')->insert([
            'title' => 'Las Palmeras III',
            'created_at' => now(),
            'updated_at' => now(),
        ]);
        // Sectores de la Zona 1 id = 8
        DB::table('sectors')->insert([
            'title' => 'Ampliación Las Palmeras',
            'created_at' => now(),
            'updated_at' => now(),
        ]);
        // Sectores de la Zona 1 id = 9
        DB::table('sectors')->insert([
            'title' => 'Ampliación Las Palmeras II',
            'created_at' => now(),
            'updated_at' => now(),
        ]);
        // Sectores de la Zona 1 id = 10
        DB::table('sectors')->insert([
            'title' => 'Ampliación Las Palmeras V',
            'created_at' => now(),
            'updated_at' => now(),
        ]);
        // Sectores de la Zona 1 id = 10
        DB::table('sectors')->insert([
            'title' => 'María Elena Moyano',
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        // Sectores de la Zona 1 id = 11
        DB::table('sectors')->insert([
            'title' => 'Clementina Peralta de Acuña',
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        // Sectores de la Zona 2 id=12
        DB::table('sectors')->insert([
            'title' => 'Primavera I',
            'created_at' => now(),
            'updated_at' => now(),
        ]);
        // Sectores de la Zona 2 id = 13
        DB::table('sectors')->insert([
            'title' => 'Primavera II',
            'created_at' => now(),
            'updated_at' => now(),
        ]);
        // Sectores de la Zona 2 id = 14
        DB::table('sectors')->insert([
            'title' => 'Primavera III',
            'created_at' => now(),
            'updated_at' => now(),
        ]);
        // Sectores de la Zona 2 id = 15
        DB::table('sectors')->insert([
            'title' => 'Wichanzao',
            'created_at' => now(),
            'updated_at' => now(),
        ]);
        DB::table('sectors')->insert([
            'title' => 'Wichanzao I',
            'created_at' => now(),
            'updated_at' => now(),
        ]);
        // Sectores de la Zona 2 id = 16
        DB::table('sectors')->insert([
            'title' => 'Wichanzao II',
            'created_at' => now(),
            'updated_at' => now(),
        ]);
        // Sectores de la Zona 2 id = 17
        DB::table('sectors')->insert([
            'title' => 'Wichanzao III',
            'created_at' => now(),
            'updated_at' => now(),
        ]);
        DB::table('sectors')->insert([
            'title' => 'Wichanzao IV',
            'created_at' => now(),
            'updated_at' => now(),
        ]);
        DB::table('sectors')->insert([
            'title' => 'Las Lomas de Wichanzao',
            'created_at' => now(),
            'updated_at' => now(),
        ]);
        // Sectores de la Zona 2 id = 18
        DB::table('sectors')->insert([
            'title' => 'Ampliación Clementina Peralta',
            'created_at' => now(),
            'updated_at' => now(),
        ]);
        // Sectores de la Zona 2 id = 18
        DB::table('sectors')->insert([
            'title' => 'Ampliación Clementina Peralta II',
            'created_at' => now(),
            'updated_at' => now(),
        ]);
        DB::table('sectors')->insert([
            'title' => 'Ramiro Priale',
            'created_at' => now(),
            'updated_at' => now(),
        ]);
        DB::table('sectors')->insert([
            'title' => 'Alberto Fujimori',
            'created_at' => now(),
            'updated_at' => now(),
        ]);
        DB::table('sectors')->insert([
            'title' => 'Nicaragua',
            'created_at' => now(),
            'updated_at' => now(),
        ]);
        DB::table('sectors')->insert([
            'title' => 'Tacabamba',
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
            'title' => 'Nuevo Indoamerica',
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
            'title' => 'Ampliación los Diamantes',
            'created_at' => now(),
            'updated_at' => now(),
        ]);
        DB::table('sectors')->insert([
            'title' => 'Los Olivos',
            'created_at' => now(),
            'updated_at' => now(),
        ]);
        DB::table('sectors')->insert([
            'title' => 'Ampliación los Olivos',
            'created_at' => now(),
            'updated_at' => now(),
        ]);
        DB::table('sectors')->insert([
            'title' => 'El Mirador',
            'created_at' => now(),
            'updated_at' => now(),
        ]);
        DB::table('sectors')->insert([
            'title' => 'El Mirador I',
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
        DB::table('sectors')->insert([
            'title' => 'Simon Bolivar',
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
            'title' => 'Nuevo Jerusalen III',
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
        DB::table('sectors')->insert([
            'title' => 'Ampliación Virgen de la Puerta',
            'created_at' => now(),
            'updated_at' => now(),
        ]);
        // Los Laureles Zona 6 id=33:
        DB::table('sectors')->insert([
            'title' => 'Los Laureles',
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        // Sectores de la Zona 7 id=34
        DB::table('sectors')->insert([
            'title' => 'Santa Veronica',
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        // Sectores de la Zona 8 id=35
        DB::table('sectors')->insert([
            'title' => 'Jerusalen',
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        // Sectores de la Zona 9 id=36
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

        
    }
}
