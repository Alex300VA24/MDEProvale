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
            'title' => 'Clementina Peralta de Acuña',
            'created_at' => now(),
            'updated_at' => now(),
        ]);
        DB::table('sectors')->insert([
            'title' => 'Primavera',
            'created_at' => now(),
            'updated_at' => now(),
        ]);
        DB::table('sectors')->insert([
            'title' => 'Wichanzao',
            'created_at' => now(),
            'updated_at' => now(),
        ]);
        DB::table('sectors')->insert([
            'title' => 'Ramiro Priale',
            'created_at' => now(),
            'updated_at' => now(),
        ]);
        DB::table('sectors')->insert([
            'title' => 'Nuevo Indoamerica',
            'created_at' => now(),
            'updated_at' => now(),
        ]);
        DB::table('sectors')->insert([
            'title' => 'Indoamerica',
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
            'title' => 'El Mirador',
            'created_at' => now(),
            'updated_at' => now(),
        ]);
        DB::table('sectors')->insert([
            'title' => 'Fraternidad',
            'created_at' => now(),
            'updated_at' => now(),
        ]);
        DB::table('sectors')->insert([
            'title' => 'Pueblo el Sol',
            'created_at' => now(),
            'updated_at' => now(),
        ]);
        DB::table('sectors')->insert([
            'title' => 'Sector Central',
            'created_at' => now(),
            'updated_at' => now(),
        ]);
        DB::table('sectors')->insert([
            'title' => 'San Martin',
            'created_at' => now(),
            'updated_at' => now(),
        ]);
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
        DB::table('sectors')->insert([
            'title' => 'Santa Veronica',
            'created_at' => now(),
            'updated_at' => now(),
        ]);
        DB::table('sectors')->insert([
            'title' => 'Jerusalen',
            'created_at' => now(),
            'updated_at' => now(),
        ]);
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
