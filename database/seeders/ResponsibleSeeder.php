<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class ResponsibleSeeder extends Seeder
{
    public function run(): void
    {
        // Personal operativo de PROVALE. No pertenece al padrón de los comités,
        // pero debe existir para conservar las firmas históricas de las PECOSAs.
        $people = [
            ['dni' => '74295078', 'names' => 'ALESSANDRA THAIS', 'father_lastname' => 'URQUIAGA', 'mother_lastname' => 'ACEVEDO', 'gender' => 'F', 'birthdate' => '1990-01-01'],
            ['dni' => '17985528', 'names' => 'YOLANDA', 'father_lastname' => 'ROMERO', 'mother_lastname' => 'SALVADOR', 'gender' => 'F', 'birthdate' => '1990-01-01'],
            ['dni' => '42613416', 'names' => 'ROSA IMERITA', 'father_lastname' => 'PRIETO', 'mother_lastname' => 'SALINAS', 'gender' => 'F', 'birthdate' => '1993-01-13'],
            ['dni' => '70012952', 'names' => 'EVA JACKELINE', 'father_lastname' => 'CHÁVEZ', 'mother_lastname' => 'QUISPE', 'gender' => 'F', 'birthdate' => '1993-01-13'],
            ['dni' => '41639793', 'names' => 'JEANETTE FANNY', 'father_lastname' => 'TORRES', 'mother_lastname' => 'VARAS', 'gender' => 'F', 'birthdate' => '1990-01-01'],
        ];

        $now = now();
        foreach ($people as $person) {
            DB::table('people')->updateOrInsert(
                ['dni' => $person['dni']],
                array_merge($person, [
                    'telephone_number' => null,
                    'phone_number' => null,
                    'address' => 'PROVALE',
                    'place_sector_id' => null,
                    'updated_at' => $now,
                    'created_at' => $now,
                ])
            );
        }

        $personIds = DB::table('people')
            ->whereIn('dni', array_column($people, 'dni'))
            ->pluck('id', 'dni');

        $start = '2026-08-30 13:35:18';
        $rows = [
            ['dni' => '74295078', 'type' => 'storekeeper', 'active' => false],
            ['dni' => '17985528', 'type' => 'chief', 'active' => false],
            ['dni' => '42613416', 'type' => 'storekeeper', 'active' => true],
            ['dni' => '42613416', 'type' => 'chief', 'active' => false],
            ['dni' => '70012952', 'type' => 'chief', 'active' => false],
            ['dni' => '41639793', 'type' => 'chief', 'active' => true],
            ['dni' => '41639793', 'type' => 'storekeeper', 'active' => false],
        ];

        foreach ($rows as $row) {
            $personId = $personIds[$row['dni']];
            $end = $row['active'] ? null : $start;

            DB::table('responsibles')->updateOrInsert(
                [
                    'person_id' => $personId,
                    'type' => $row['type'],
                    'start_date' => $start,
                ],
                [
                    'active' => $row['active'],
                    'end_date' => $end,
                    'created_at' => $start,
                    'updated_at' => $end ?? $now,
                ]
            );
        }
    }
}
