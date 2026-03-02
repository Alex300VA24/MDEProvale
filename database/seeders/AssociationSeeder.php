<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class AssociationSeeder extends Seeder
{
    public function run()
    {
        $clubs = [
            ['code' => '0001', 'name' => 'Club de Madres San Luis', 'address' => 'Mz. A lt. 01'],
            ['code' => '0002', 'name' => 'Club de Madres Primavera', 'address' => 'Mz. B lt. 02'],
            ['code' => '0003', 'name' => 'Club de Madres Esperanza', 'address' => 'Mz. C lt. 03'],
            ['code' => '0004', 'name' => 'Club de Madres Nuevo Amanecer', 'address' => 'Mz. D lt. 04'],
            ['code' => '0005', 'name' => 'Club de Madres Divino Jesus', 'address' => 'Mz. E lt. 05'],
            ['code' => '0006', 'name' => 'Club de Madres Santa Maria', 'address' => 'Mz. F lt. 06'],
            ['code' => '0007', 'name' => 'Club de Madres Las Flores', 'address' => 'Mz. G lt. 07'],
            ['code' => '0008', 'name' => 'Club de Madres Virgen del Carmen', 'address' => 'Mz. H lt. 08'],
            ['code' => '0009', 'name' => 'Club de Madres San Jose', 'address' => 'Mz. I lt. 09'],
            ['code' => '0010', 'name' => 'Club de Madres La Esperanza', 'address' => 'Mz. J lt. 10'],
        ];

        foreach ($clubs as $index => $club) {
            DB::table('associations')->insert([
                'code' => $club['code'],
                'name' => $club['name'],
                'place_sector_id' => ($index % 5) + 1,
                'type_premises_id' => 1,
                'address' => $club['address'],
                'property_number' => null,
                'observation' => 'Club de madres fundado en el 2020',
                'state_id' => 1,
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }
    }
}
