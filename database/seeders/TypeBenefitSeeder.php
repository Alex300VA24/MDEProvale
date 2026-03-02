<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
class TypeBenefitSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        /*
        insert into TiposBeneficio(descripcion, edadMinima, edadMaxima, prioridad, observaciones)
            values('niño (0-6 años)', 0, 6, 1, ''),('niño (7-13 años)', 7, 13, 2, ''),
            ('adulto mayor', 65, null, 2, ''),('madre gestante', 12, null, 1, ''),
            ('madre lactante', 12, null, 1, ''),('persona con TBC', 0, null, 2, ''); 
        */
        DB::table('type_benefits')->insert([
            'title' => 'Niño (0-6 años)',
            'min_age' => 0,
            'max_age' => 6,
            'priority' => 1,
            'observation' => '',
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        DB::table('type_benefits')->insert([
            'title' => 'Niño (7-13 años)',
            'min_age' => 7,
            'max_age' => 13,
            'priority' => 2,
            'observation' => '',
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        DB::table('type_benefits')->insert([
            'title' => 'Adulto mayor',
            'min_age' => 65,
            'max_age' => null,
            'priority' => 2,
            'observation' => '',
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        DB::table('type_benefits')->insert([
            'title' => 'Madre gestante',
            'min_age' => 12,
            'max_age' => null,
            'priority' => 1,
            'observation' => '',
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        DB::table('type_benefits')->insert([
            'title' => 'Madre lactante',
            'min_age' => 12,
            'max_age' => null,
            'priority' => 1,
            'observation' => '',
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        DB::table('type_benefits')->insert([
            'title' => 'Persona con TBC',
            'min_age' => 0,
            'max_age' => null,
            'priority' => 2,
            'observation' => '',
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }
}
