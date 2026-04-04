<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class DirectiveSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        // Fraternidad
        DB::table('directives')->insert([
            'resolution_id' => 40,
            'partner_id'    => 1085,
            'position_id'   => 1,
            'state_id'      => 1,
            'date_start'   => null,
            'date_end'     => null,
            'created_at'    => now(),
            'updated_at'    => now()
        ]);
        // Nuestra Señora de Fatima
        DB::table('directives')->insert([
            'resolution_id' => 176,
            'partner_id'    => 1142,
            'position_id'   => 1,
            'state_id'      => 1,
            'date_start'   => null,
            'date_end'     => null,
            'created_at'    => now(),
            'updated_at'    => now()
        ]);
        // Nueva Esperanza
        DB::table('directives')->insert([
            'resolution_id' => 180,
            'partner_id'    => 113,
            'position_id'   => 1,
            'state_id'      => 1,
            'date_start'   => null,
            'date_end'     => null,
            'created_at'    => now(),
            'updated_at'    => now()
        ]);
        // Santa Catalina : Falta obtener datos
        /*DB::table('directives')->insert([
            'resolution_id' => 172,
            'partner_id'    => 1143,
            'position_id'   => 1,
            'state_id'      => 1,
            'date_start'   => null,
            'date_end'     => null,
            'created_at'    => now(),
            'updated_at'    => now()
        ]);*/
        DB::table('directives')->insert([
            'resolution_id' => 18,
            'partner_id'    => 509,
            'position_id'   => 1,
            'state_id'      => 1,
            'date_start'   => null,
            'date_end'     => null,
            'created_at'    => now(),
            'updated_at'    => now()
        ]);
        DB::table('directives')->insert([
            'resolution_id' => 178,
            'partner_id'    => 1235,
            'position_id'   => 1,
            'state_id'      => 1,
            'date_start'   => null,
            'date_end'     => null,
            'created_at'    => now(),
            'updated_at'    => now()
        ]);
        DB::table('directives')->insert([
            'resolution_id' => 141,
            'partner_id'    => 942,
            'position_id'   => 1,
            'state_id'      => 1,
            'date_start'   => null,
            'date_end'     => null,
            'created_at'    => now(),
            'updated_at'    => now()
        ]);
        DB::table('directives')->insert([
            'resolution_id' => 95,
            'partner_id'    => 908,
            'position_id'   => 1,
            'state_id'      => 1,
            'date_start'   => null,
            'date_end'     => null,
            'created_at'    => now(),
            'updated_at'    => now()
        ]);
        // Los niños de Belen : Falta datos
        /*DB::table('directives')->insert([
            'resolution_id' => 189,
            'partner_id'    => 909,
            'position_id'   => 1,
            'state_id'      => 1,
            'date_start'   => null,
            'date_end'     => null,
            'created_at'    => now(),
            'updated_at'    => now()
        ]);*/
        DB::table('directives')->insert([
            'resolution_id' => 7,
            'partner_id'    => 1293,
            'position_id'   => 1,
            'state_id'      => 1,
            'date_start'   => null,
            'date_end'     => null,
            'created_at'    => now(),
            'updated_at'    => now()
        ]);
        // Buen Socorro: Faltan datos
        /*DB::table('directives')->insert([
            'resolution_id' => 114,
            'partner_id'    => 1294,
            'position_id'   => 1,
            'state_id'      => 1,
            'date_start'   => null,
            'date_end'     => null,
            'created_at'    => now(),
            'updated_at'    => now()
        ]);*/
        DB::table('directives')->insert([
            'resolution_id' => 84,
            'partner_id'    => 782,
            'position_id'   => 1,
            'state_id'      => 1,
            'date_start'   => null,
            'date_end'     => null,
            'created_at'    => now(),
            'updated_at'    => now()
        ]);
        // CLEMENTINA ACUÑA DE PERALTA N° 2 : Falta datos
         /*
        DB::table('directives')->insert([
            'resolution_id' => 170,
            'partner_id'    => 782,
            'position_id'   => 1,
            'state_id'      => 1,
            'date_start'   => null,
            'date_end'     => null,
            'created_at'    => now(),
            'updated_at'    => now()
        ]);*/
        DB::table('directives')->insert([
            'resolution_id' => 154,
            'partner_id'    => 1705,
            'position_id'   => 1,
            'state_id'      => 1,
            'date_start'   => null,
            'date_end'     => null,
            'created_at'    => now(),
            'updated_at'    => now()
        ]);
        DB::table('directives')->insert([
            'resolution_id' => 166,
            'partner_id'    => 1273,
            'position_id'   => 1,
            'state_id'      => 1,
            'date_start'   => null,
            'date_end'     => null,
            'created_at'    => now(),
            'updated_at'    => now()
        ]);
        DB::table('directives')->insert([
            'resolution_id' => 96,
            'partner_id'    => 1542,
            'position_id'   => 1,
            'state_id'      => 1,
            'date_start'   => null,
            'date_end'     => null,
            'created_at'    => now(),
            'updated_at'    => now()
        ]);
        DB::table('directives')->insert([
            'resolution_id' => 98,
            'partner_id'    => 1305,
            'position_id'   => 1,
            'state_id'      => 1,
            'date_start'   => null,
            'date_end'     => null,
            'created_at'    => now(),
            'updated_at'    => now()
        ]);
        DB::table('directives')->insert([
            'resolution_id' => 88,
            'partner_id'    => 2404,
            'position_id'   => 1,
            'state_id'      => 1,
            'date_start'   => null,
            'date_end'     => null,
            'created_at'    => now(),
            'updated_at'    => now()
        ]);
        DB::table('directives')->insert([
            'resolution_id' => 169,
            'partner_id'    => 2440,
            'position_id'   => 1,
            'state_id'      => 1,
            'date_start'   => null,
            'date_end'     => null,
            'created_at'    => now(),
            'updated_at'    => now()
        ]);
        DB::table('directives')->insert([
            'resolution_id' => 121,
            'partner_id'    => 594,
            'position_id'   => 1,
            'state_id'      => 1,
            'date_start'   => null,
            'date_end'     => null,
            'created_at'    => now(),
            'updated_at'    => now()
        ]);
        DB::table('directives')->insert([
            'resolution_id' => 171,
            'partner_id'    => 2492,
            'position_id'   => 1,
            'state_id'      => 1,
            'date_start'   => null,
            'date_end'     => null,
            'created_at'    => now(),
            'updated_at'    => now()
        ]);
        DB::table('directives')->insert([
            'resolution_id' => 85,
            'partner_id'    => 429,
            'position_id'   => 1,
            'state_id'      => 1,
            'date_start'   => null,
            'date_end'     => null,
            'created_at'    => now(),
            'updated_at'    => now()
        ]);
        DB::table('directives')->insert([
            'resolution_id' => 91,
            'partner_id'    => 1413,
            'position_id'   => 1,
            'state_id'      => 1,
            'date_start'   => null,
            'date_end'     => null,
            'created_at'    => now(),
            'updated_at'    => now()
        ]);
        DB::table('directives')->insert([
            'resolution_id' => 20,
            'partner_id'    => 1629,
            'position_id'   => 1,
            'state_id'      => 1,
            'date_start'   => null,
            'date_end'     => null,
            'created_at'    => now(),
            'updated_at'    => now()
        ]);
        // Las Palmeras IIi : Falta datos
         /*
        DB::table('directives')->insert([
            'resolution_id' => 86,
            'partner_id'    => 1630,
            'position_id'   => 1,
            'state_id'      => 1,
            'date_start'   => null,
            'date_end'     => null,
            'created_at'    => now(),
            'updated_at'    => now()
        ]); */

        DB::table('directives')->insert([
            'resolution_id' => 91,
            'partner_id'    => 1926,
            'position_id'   => 1,
            'state_id'      => 1,
            'date_start'   => null,
            'date_end'     => null,
            'created_at'    => now(),
            'updated_at'    => now()
        ]);
        DB::table('directives')->insert([
            'resolution_id' => 100,
            'partner_id'    => 1978,
            'position_id'   => 1,
            'state_id'      => 1,
            'date_start'   => null,
            'date_end'     => null,
            'created_at'    => now(),
            'updated_at'    => now()
        ]);
        DB::table('directives')->insert([
            'resolution_id' => 168,
            'partner_id'    => 2214,
            'position_id'   => 1,
            'state_id'      => 1,
            'date_start'   => null,
            'date_end'     => null,
            'created_at'    => now(),
            'updated_at'    => now()
        ]);
        DB::table('directives')->insert([
            'resolution_id' => 112,
            'partner_id'    => 2239,
            'position_id'   => 1,
            'state_id'      => 1,
            'date_start'   => null,
            'date_end'     => null,
            'created_at'    => now(),
            'updated_at'    => now()
        ]);
        DB::table('directives')->insert([
            'resolution_id' => 90,
            'partner_id'    => 2321,
            'position_id'   => 1,
            'state_id'      => 1,
            'date_start'   => null,
            'date_end'     => null,
            'created_at'    => now(),
            'updated_at'    => now()
        ]);
        DB::table('directives')->insert([
            'resolution_id' => 75,
            'partner_id'    => 2348,
            'position_id'   => 1,
            'state_id'      => 1,
            'date_start'   => null,
            'date_end'     => null,
            'created_at'    => now(),
            'updated_at'    => now()
        ]);
        DB::table('directives')->insert([
            'resolution_id' => 99,
            'partner_id'    => 206,
            'position_id'   => 1,
            'state_id'      => 1,
            'date_start'   => null,
            'date_end'     => null,
            'created_at'    => now(),
            'updated_at'    => now()
        ]);
        DB::table('directives')->insert([
            'resolution_id' => 185,
            'partner_id'    => 662,
            'position_id'   => 1,
            'state_id'      => 1,
            'date_start'   => null,
            'date_end'     => null,
            'created_at'    => now(),
            'updated_at'    => now()
        ]);
        DB::table('directives')->insert([
            'resolution_id' => 67,
            'partner_id'    => 298,
            'position_id'   => 1,
            'state_id'      => 1,
            'date_start'   => null,
            'date_end'     => null,
            'created_at'    => now(),
            'updated_at'    => now()
        ]);
        DB::table('directives')->insert([
            'resolution_id' => 53,
            'partner_id'    => 2132,
            'position_id'   => 1,
            'state_id'      => 1,
            'date_start'   => null,
            'date_end'     => null,
            'created_at'    => now(),
            'updated_at'    => now()
        ]);
        DB::table('directives')->insert([
            'resolution_id' => 25,
            'partner_id'    => 1808,
            'position_id'   => 1,
            'state_id'      => 1,
            'date_start'   => null,
            'date_end'     => null,
            'created_at'    => now(),
            'updated_at'    => now()
        ]);
        DB::table('directives')->insert([
            'resolution_id' => 82,
            'partner_id'    => 2464,
            'position_id'   => 1,
            'state_id'      => 1,
            'date_start'   => null,
            'date_end'     => null,
            'created_at'    => now(),
            'updated_at'    => now()
        ]);
        // GLORIA VALDERRAMA GARCIA : Falta datos
         /*
        DB::table('directives')->insert([
            'resolution_id' => 116,
            'partner_id'    => 1809,
            'position_id'   => 1,
            'state_id'      => 1,
            'date_start'   => null,
            'date_end'     => null,
            'created_at'    => now(),
            'updated_at'    => now()
        ]);*/
        // HIJAS DE SION : Falta datos
         /*
        DB::table('directives')->insert([
            'resolution_id' => 78,
            'partner_id'    => 2465,
            'position_id'   => 1,
            'state_id'      => 1,
            'date_start'   => null,
            'date_end'     => null,
            'created_at'    => now(),
            'updated_at'    => now()
        ]);*/
        DB::table('directives')->insert([
            'resolution_id' => 38,
            'partner_id'    => 709,
            'position_id'   => 1,
            'state_id'      => 1,
            'date_start'   => null,
            'date_end'     => null,
            'created_at'    => now(),
            'updated_at'    => now()
        ]);
        DB::table('directives')->insert([
            'resolution_id' => 54,
            'partner_id'    => 777,
            'position_id'   => 1,
            'state_id'      => 1,
            'date_start'   => null,
            'date_end'     => null,
            'created_at'    => now(),
            'updated_at'    => now()
        ]);
        DB::table('directives')->insert([
            'resolution_id' => 129,
            'partner_id'    => 2160,
            'position_id'   => 1,
            'state_id'      => 1,
            'date_start'   => null,
            'date_end'     => null,
            'created_at'    => now(),
            'updated_at'    => now()
        ]);
        // JESUS MI SALVADOR: Falta datos
         /*
        DB::table('directives')->insert([
            'resolution_id' => 37,
            'partner_id'    => 2160,
            'position_id'   => 1,
            'state_id'      => 1,
            'date_start'   => null,
            'date_end'     => null,
            'created_at'    => now(),
            'updated_at'    => now()
        ]);*/
        DB::table('directives')->insert([
            'resolution_id' => 19,
            'partner_id'    => 479,
            'position_id'   => 1,
            'state_id'      => 1,
            'date_start'   => null,
            'date_end'     => null,
            'created_at'    => now(),
            'updated_at'    => now()
        ]);
        DB::table('directives')->insert([
            'resolution_id' => 50,
            'partner_id'    => 1370,
            'position_id'   => 1,
            'state_id'      => 1,
            'date_start'   => null,
            'date_end'     => null,
            'created_at'    => now(),
            'updated_at'    => now()
        ]);
        DB::table('directives')->insert([
            'resolution_id' => 130,
            'partner_id'    => 547,
            'position_id'   => 1,
            'state_id'      => 1,
            'date_start'   => null,
            'date_end'     => null,
            'created_at'    => now(),
            'updated_at'    => now()
        ]);
        DB::table('directives')->insert([
            'resolution_id' => 194,
            'partner_id'    => 626,
            'position_id'   => 1,
            'state_id'      => 1,
            'date_start'   => null,
            'date_end'     => null,
            'created_at'    => now(),
            'updated_at'    => now()
        ]);
        DB::table('directives')->insert([
            'resolution_id' => 83,
            'partner_id'    => 227,
            'position_id'   => 1,
            'state_id'      => 1,
            'date_start'   => null,
            'date_end'     => null,
            'created_at'    => now(),
            'updated_at'    => now()
        ]);
        // LOS GERANIOS: Falta datos
         /*
        DB::table('directives')->insert([
            'resolution_id' => 39,
            'partner_id'    => 227,
            'position_id'   => 1,
            'state_id'      => 1,
            'date_start'   => null,
            'date_end'     => null,
            'created_at'    => now(),
            'updated_at'    => now()
        ]);*/
        DB::table('directives')->insert([
            'resolution_id' => 110,
            'partner_id'    => 93,
            'position_id'   => 1,
            'state_id'      => 1,
            'date_start'   => null,
            'date_end'     => null,
            'created_at'    => now(),
            'updated_at'    => now()
        ]);
        DB::table('directives')->insert([
            'resolution_id' => 16,
            'partner_id'    => 823,
            'position_id'   => 1,
            'state_id'      => 1,
            'date_start'   => null,
            'date_end'     => null,
            'created_at'    => now(),
            'updated_at'    => now()
        ]);
        DB::table('directives')->insert([
            'resolution_id' => 123,
            'partner_id'    => 418,
            'position_id'   => 1,
            'state_id'      => 1,
            'date_start'   => null,
            'date_end'     => null,
            'created_at'    => now(),
            'updated_at'    => now()
        ]);
        DB::table('directives')->insert([
            'resolution_id' => 122,
            'partner_id'    => 1607,
            'position_id'   => 1,
            'state_id'      => 1,
            'date_start'   => null,
            'date_end'     => null,
            'created_at'    => now(),
            'updated_at'    => now()
        ]);
        DB::table('directives')->insert([
            'resolution_id' => 188,
            'partner_id'    => 1836,
            'position_id'   => 1,
            'state_id'      => 1,
            'date_start'   => null,
            'date_end'     => null,
            'created_at'    => now(),
            'updated_at'    => now()
        ]);
        DB::table('directives')->insert([
            'resolution_id' => 144,
            'partner_id'    => 264,
            'position_id'   => 1,
            'state_id'      => 1,
            'date_start'   => null,
            'date_end'     => null,
            'created_at'    => now(),
            'updated_at'    => now()
        ]);
        // MUJERES LUCHANDO POR UN FUTURO MEJOR: Falta datos
         /*
        DB::table('directives')->insert([
            'resolution_id' => 46,
            'partner_id'    => 1836,
            'position_id'   => 1,
            'state_id'      => 1,
            'date_start'   => null,
            'date_end'     => null,
            'created_at'    => now(),
            'updated_at'    => now()
        ]);*/
        DB::table('directives')->insert([
            'resolution_id' => 1,
            'partner_id'    => 1937,
            'position_id'   => 1,
            'state_id'      => 1,
            'date_start'   => null,
            'date_end'     => null,
            'created_at'    => now(),
            'updated_at'    => now()
        ]);
        // NIÑOS DEL TRIUNFO: Falta datos
         /*
        DB::table('directives')->insert([
            'resolution_id' => 104,
            'partner_id'    => 1937,
            'position_id'   => 1,
            'state_id'      => 1,
            'date_start'   => null,
            'date_end'     => null,
            'created_at'    => now(),
            'updated_at'    => now()
        ]); */
        DB::table('directives')->insert([
            'resolution_id' => 162,
            'partner_id'    => 1177,
            'position_id'   => 1,
            'state_id'      => 1,
            'date_start'   => null,
            'date_end'     => null,
            'created_at'    => now(),
            'updated_at'    => now()
        ]);
        DB::table('directives')->insert([
            'resolution_id' => 57,
            'partner_id'    => 640,
            'position_id'   => 1,
            'state_id'      => 1,
            'date_start'   => null,
            'date_end'     => null,
            'created_at'    => now(),
            'updated_at'    => now()
        ]);
        DB::table('directives')->insert([
            'resolution_id' => 113,
            'partner_id'    => 1508,
            'position_id'   => 1,
            'state_id'      => 1,
            'date_start'   => null,
            'date_end'     => null,
            'created_at'    => now(),
            'updated_at'    => now()
        ]);
        DB::table('directives')->insert([
            'resolution_id' => 133,
            'partner_id'    => 1892,
            'position_id'   => 1,
            'state_id'      => 1,
            'date_start'   => null,
            'date_end'     => null,
            'created_at'    => now(),
            'updated_at'    => now()
        ]);
        DB::table('directives')->insert([
            'resolution_id' => 174,
            'partner_id'    => 1783,
            'position_id'   => 1,
            'state_id'      => 1,
            'date_start'   => null,
            'date_end'     => null,
            'created_at'    => now(),
            'updated_at'    => now()
        ]);
        DB::table('directives')->insert([
            'resolution_id' => 175,
            'partner_id'    => 2274,
            'position_id'   => 1,
            'state_id'      => 1,
            'date_start'   => null,
            'date_end'     => null,
            'created_at'    => now(),
            'updated_at'    => now()
        ]);
        DB::table('directives')->insert([
            'resolution_id' => 103,
            'partner_id'    => 17,
            'position_id'   => 1,
            'state_id'      => 1,
            'date_start'   => null,
            'date_end'     => null,
            'created_at'    => now(),
            'updated_at'    => now()
        ]);
        // SANTISIMO SACRAMENTO: Falta datos
         /*
        DB::table('directives')->insert([
            'resolution_id' => 102,
            'partner_id'    => 2274,
            'position_id'   => 1,
            'state_id'      => 1,
            'date_start'   => null,
            'date_end'     => null,
            'created_at'    => now(),
            'updated_at'    => now()
        ]);*/
        DB::table('directives')->insert([
            'resolution_id' => 107,
            'partner_id'    => 1678,
            'position_id'   => 1,
            'state_id'      => 1,
            'date_start'   => null,
            'date_end'     => null,
            'created_at'    => now(),
            'updated_at'    => now()
        ]);
        DB::table('directives')->insert([
            'resolution_id' => 72,
            'partner_id'    => 1993,
            'position_id'   => 1,
            'state_id'      => 1,
            'date_start'   => null,
            'date_end'     => null,
            'created_at'    => now(),
            'updated_at'    => now()
        ]);
        DB::table('directives')->insert([
            'resolution_id' => 147,
            'partner_id'    => 2572,
            'position_id'   => 1,
            'state_id'      => 1,
            'date_start'   => null,
            'date_end'     => null,
            'created_at'    => now(),
            'updated_at'    => now()
        ]);
        DB::table('directives')->insert([
            'resolution_id' => 87,
            'partner_id'    => 844,
            'position_id'   => 1,
            'state_id'      => 1,
            'date_start'   => null,
            'date_end'     => null,
            'created_at'    => now(),
            'updated_at'    => now()
        ]);
        DB::table('directives')->insert([
            'resolution_id' => 158,
            'partner_id'    => 1901,
            'position_id'   => 1,
            'state_id'      => 1,
            'date_start'   => null,
            'date_end'     => null,
            'created_at'    => now(),
            'updated_at'    => now()
        ]);
        DB::table('directives')->insert([
            'resolution_id' => 167,
            'partner_id'    => 2063,
            'position_id'   => 1,
            'state_id'      => 1,
            'date_start'   => null,
            'date_end'     => null,
            'created_at'    => now(),
            'updated_at'    => now()
        ]);
        DB::table('directives')->insert([
            'resolution_id' => 92,
            'partner_id'    => 692,
            'position_id'   => 1,
            'state_id'      => 1,
            'date_start'   => null,
            'date_end'     => null,
            'created_at'    => now(),
            'updated_at'    => now()
        ]);
        DB::table('directives')->insert([
            'resolution_id' => 56,
            'partner_id'    => 495,
            'position_id'   => 1,
            'state_id'      => 1,
            'date_start'   => null,
            'date_end'     => null,
            'created_at'    => now(),
            'updated_at'    => now()
        ]);
        DB::table('directives')->insert([
            'resolution_id' => 21,
            'partner_id'    => 347,
            'position_id'   => 1,
            'state_id'      => 1,
            'date_start'   => null,
            'date_end'     => null,
            'created_at'    => now(),
            'updated_at'    => now()
        ]);

    }
}
