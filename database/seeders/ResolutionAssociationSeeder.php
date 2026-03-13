<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class ResolutionAssociationSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        // Fraternidad: 1
        DB::table('resolution_associations')->insert([
            'resolution_id' => 66,
            'association_id' => 1,
        ]);
        DB::table('resolution_associations')->insert([
            'resolution_id' => 71,
            'association_id' => 1,
        ]);

        // Nuestra señora de fatima: 2
        DB::table('resolution_associations')->insert([
            'resolution_id' => 41,
            'association_id' => 2,
        ]);
        DB::table('resolution_associations')->insert([
            'resolution_id' => 59,
            'association_id' => 2,
        ]);

        // Nueva Esperanza: 3
        DB::table('resolution_associations')->insert([
            'resolution_id' => 51,
            'association_id' => 3,
        ]);
        DB::table('resolution_associations')->insert([
            'resolution_id' => 73,
            'association_id' => 3,
        ]);

        // Santa Catalina: 4
        DB::table('resolution_associations')->insert([
            'resolution_id' => 193,
            'association_id' => 4,
        ]);
        DB::table('resolution_associations')->insert([
            'resolution_id' => 12,
            'association_id' => 4,
        ]);

        // SILOE: 5
        DB::table('resolution_associations')->insert([
            'resolution_id' => 36,
            'association_id' => 5,
        ]);
        DB::table('resolution_associations')->insert([
            'resolution_id' => 33,
            'association_id' => 5,
        ]);

        // Virgenes del sol: 6
        DB::table('resolution_associations')->insert([
            'resolution_id' => 196,
            'association_id' => 6,
        ]);
        DB::table('resolution_associations')->insert([
            'resolution_id' => 4,
            'association_id' => 6,
        ]);

        // Clementine Peralta de Acuña: 7
        DB::table('resolution_associations')->insert([
            'resolution_id' => 183,
            'association_id' => 7,
        ]);
        DB::table('resolution_associations')->insert([
            'resolution_id' => 10,
            'association_id' => 7,
        ]);

        // Corazon de Jesus N° 8: 8
        DB::table('resolution_associations')->insert([
            'resolution_id' => 145,
            'association_id' => 8,
        ]);
        DB::table('resolution_associations')->insert([
            'resolution_id' => 163,
            'association_id' => 8,
        ]);

        // Los niños de belen: 9
        DB::table('resolution_associations')->insert([
            'resolution_id' => 161,
            'association_id' => 9,
        ]);
        DB::table('resolution_associations')->insert([
            'resolution_id' => 6,
            'association_id' => 9,
        ]);
        
        // Micaela bastidas: 10
        DB::table('resolution_associations')->insert([
            'resolution_id' => 32,
            'association_id' => 10,
        ]);
        DB::table('resolution_associations')->insert([
            'resolution_id' => 62,
            'association_id' => 10,
        ]);

        // Buen Socorro: 11
        DB::table('resolution_associations')->insert([
            'resolution_id' => 187,
            'association_id' => 11,
        ]);
        DB::table('resolution_associations')->insert([
            'resolution_id' => 26,
            'association_id' => 11,
        ]);

        // Cesar Acuña Peralta: 12
        DB::table('resolution_associations')->insert([
            'resolution_id' => 111,
            'association_id' => 12,
        ]);
        DB::table('resolution_associations')->insert([
            'resolution_id' => 106,
            'association_id' => 12,
        ]);

        // Clementine acuña peralta n° 2: 13
        DB::table('resolution_associations')->insert([
            'resolution_id' => 195,
            'association_id' => 13,
        ]);
        DB::table('resolution_associations')->insert([
            'resolution_id' => 9,
            'association_id' => 13,
        ]);

        // Clementina peralta de acuña: 14
        DB::table('resolution_associations')->insert([
            'resolution_id' => 44,
            'association_id' => 14,
        ]);
        DB::table('resolution_associations')->insert([
            'resolution_id' => 60,
            'association_id' => 14,
        ]);

        // Estrella de la Esperanza: 15
        DB::table('resolution_associations')->insert([
            'resolution_id' => 182,
            'association_id' => 15,
        ]);
        DB::table('resolution_associations')->insert([
            'resolution_id' => 165,
            'association_id' => 15,
        ]);

        // Unidas: 16
        DB::table('resolution_associations')->insert([
            'resolution_id' => 157,
            'association_id' => 16,
        ]);
        DB::table('resolution_associations')->insert([
            'resolution_id' => 152,
            'association_id' => 16,
        ]);

        // Rios de Agua Viva: 17
        DB::table('resolution_associations')->insert([
            'resolution_id' => 132,
            'association_id' => 17,
        ]);
        DB::table('resolution_associations')->insert([
            'resolution_id' => 127,
            'association_id' => 17,
        ]);

        // Jehova es mi Pastor: 18
        DB::table('resolution_associations')->insert([
            'resolution_id' => 156,
            'association_id' => 18,
        ]);
        DB::table('resolution_associations')->insert([
            'resolution_id' => 150,
            'association_id' => 18,
        ]);

        // Jesus me guia: 19
        DB::table('resolution_associations')->insert([
            'resolution_id' => 190,
            'association_id' => 19,
        ]);
        DB::table('resolution_associations')->insert([
            'resolution_id' => 14,
            'association_id' => 19,
        ]);

        // San Pedro: 20
        DB::table('resolution_associations')->insert([
            'resolution_id' => 186,
            'association_id' => 20,
        ]);
        DB::table('resolution_associations')->insert([
            'resolution_id' => 30,
            'association_id' => 20,
        ]);

        // Tania Soledad Baca Romero: 21
        DB::table('resolution_associations')->insert([
            'resolution_id' => 184,
            'association_id' => 21,
        ]);
        DB::table('resolution_associations')->insert([
            'resolution_id' => 11,
            'association_id' => 21,
        ]);

        // Madres Trabajando por el gran cambio: 22
        DB::table('resolution_associations')->insert([
            'resolution_id' => 131,
            'association_id' => 22,
        ]);
        DB::table('resolution_associations')->insert([
            'resolution_id' => 118,
            'association_id' => 22,
        ]);

        // Virgen de la puerta: 23
        DB::table('resolution_associations')->insert([
            'resolution_id' => 61,
            'association_id' => 23,
        ]);
        DB::table('resolution_associations')->insert([
            'resolution_id' => 31,
            'association_id' => 23,
        ]);

        // Clementina Peralta de Acuña: 24
        DB::table('resolution_associations')->insert([
            'resolution_id' => 52,
            'association_id' => 24,
        ]);
        DB::table('resolution_associations')->insert([
            'resolution_id' => 45,
            'association_id' => 24,
        ]);

        // Las Palmeras III: 25
        DB::table('resolution_associations')->insert([
            'resolution_id' => 105,
            'association_id' => 25,
        ]);
        DB::table('resolution_associations')->insert([
            'resolution_id' => 101,
            'association_id' => 25,
        ]);

        // Virgen de la Puerta: 26
        DB::table('resolution_associations')->insert([
            'resolution_id' => 155,
            'association_id' => 26,
        ]);
        DB::table('resolution_associations')->insert([
            'resolution_id' => 134,
            'association_id' => 26,
        ]);

        // Zoila de la torre de haya (santa veronica): 27
        DB::table('resolution_associations')->insert([
            'resolution_id' => 173,
            'association_id' => 27,
        ]);
        DB::table('resolution_associations')->insert([
            'resolution_id' => 143,
            'association_id' => 27,
        ]);

        // Zoila de la torre de haya (jerusalen): 28
        DB::table('resolution_associations')->insert([
            'resolution_id' => 191,
            'association_id' => 28,
        ]);
        DB::table('resolution_associations')->insert([
            'resolution_id' => 8,
            'association_id' => 28,
        ]);

        // Nuestra Sra. del Perpetuo Socorro: 29
        DB::table('resolution_associations')->insert([
            'resolution_id' => 139,
            'association_id' => 29,
        ]);
        DB::table('resolution_associations')->insert([
            'resolution_id' => 136,
            'association_id' => 29,
        ]);

        // Manuel Arevalo: 30
        DB::table('resolution_associations')->insert([
            'resolution_id' => 153,
            'association_id' => 30,
        ]);
        DB::table('resolution_associations')->insert([
            'resolution_id' => 128,
            'association_id' => 30,
        ]);

        // Ramiro Priale: 31
        DB::table('resolution_associations')->insert([
            'resolution_id' => 97,
            'association_id' => 31,
        ]);
        DB::table('resolution_associations')->insert([
            'resolution_id' => 93,
            'association_id' => 31,
        ]);
        
        // Amigos de Jesus: 32
        DB::table('resolution_associations')->insert([
            'resolution_id' => 146,
            'association_id' => 32,
        ]);
        DB::table('resolution_associations')->insert([
            'resolution_id' => 140,
            'association_id' => 32,
        ]);

        // Bodas del Cordero: 33
        DB::table('resolution_associations')->insert([
            'resolution_id' => 13,
            'association_id' => 33,
        ]);

        // Corazon de jesus: 34
        DB::table('resolution_associations')->insert([
            'resolution_id' => 74,
            'association_id' => 34,
        ]);

        // Domitila chungara: 35
        DB::table('resolution_associations')->insert([
            'resolution_id' => 81,
            'association_id' => 35,
        ]);
        DB::table('resolution_associations')->insert([
            'resolution_id' => 63,
            'association_id' => 35,
        ]);

        // Edith sonrisas de niños: 36
        DB::table('resolution_associations')->insert([
            'resolution_id' => 47,
            'association_id' => 36,
        ]);

        // Gloria Valderrama Garcia: 38
        DB::table('resolution_associations')->insert([
            'resolution_id' => 159,
            'association_id' => 38,
        ]);
        DB::table('resolution_associations')->insert([
            'resolution_id' => 108,
            'association_id' => 38,
        ]);

        // Hijas de Sion: 39
        DB::table('resolution_associations')->insert([
            'resolution_id' => 138,
            'association_id' => 39,
        ]);
        DB::table('resolution_associations')->insert([
            'resolution_id' => 124,
            'association_id' => 39,
        ]);

        // Hilo Rojo: 40
        DB::table('resolution_associations')->insert([
            'resolution_id' => 55,
            'association_id' => 40,
        ]);
        DB::table('resolution_associations')->insert([
            'resolution_id' => 48,
            'association_id' => 40,
        ]);


        // Indoamericana: 41
        DB::table('resolution_associations')->insert([
            'resolution_id' => 77,
            'association_id' => 41,
        ]);
        DB::table('resolution_associations')->insert([
            'resolution_id' => 70,
            'association_id' => 41,
        ]);

        // Jerusalen madres unidas: 42
        DB::table('resolution_associations')->insert([
            'resolution_id' => 137,
            'association_id' => 42,
        ]);

        // Jesus mi salvador: 44
        DB::table('resolution_associations')->insert([
            'resolution_id' => 69,
            'association_id' => 44,
        ]);
        DB::table('resolution_associations')->insert([
            'resolution_id' => 65,
            'association_id' => 44,
        ]);

        // Juana malaver de garrido: 45
        DB::table('resolution_associations')->insert([
            'resolution_id' => 68,
            'association_id' => 45,
        ]);
        DB::table('resolution_associations')->insert([
            'resolution_id' => 23,
            'association_id' => 45,
        ]);

        // La fuerza del pueblo: 47
        DB::table('resolution_associations')->insert([
            'resolution_id' => 119,
            'association_id' => 47,
        ]);

        // Las Dalias: 48
        DB::table('resolution_associations')->insert([
            'resolution_id' => 5,
            'association_id' => 48,
        ]);

        // Los angeles de jesus: 49
        DB::table('resolution_associations')->insert([
            'resolution_id' => 148,
            'association_id' => 49,
        ]);
        DB::table('resolution_associations')->insert([
            'resolution_id' => 142,
            'association_id' => 49,
        ]);

        // Los geranios: 50
        DB::table('resolution_associations')->insert([
            'resolution_id' => 80,
            'association_id' => 50,
        ]);
        DB::table('resolution_associations')->insert([
            'resolution_id' => 76,
            'association_id' => 50,
        ]);

        // Madre josefina potel: 52
        DB::table('resolution_associations')->insert([
            'resolution_id' => 117,
            'association_id' => 52,
        ]);
        DB::table('resolution_associations')->insert([
            'resolution_id' => 115,
            'association_id' => 52,
        ]);

        // Madres trabajando por ampliacion: 53
        DB::table('resolution_associations')->insert([
            'resolution_id' => 42,
            'association_id' => 53,
        ]);
        DB::table('resolution_associations')->insert([
            'resolution_id' => 27,
            'association_id' => 53,
        ]);

        // Manos Solidarias: 54
        DB::table('resolution_associations')->insert([
            'resolution_id' => 34,
            'association_id' => 54,
        ]);
        DB::table('resolution_associations')->insert([
            'resolution_id' => 22,
            'association_id' => 54,
        ]);

        // Martin Namay: 55
        DB::table('resolution_associations')->insert([
            'resolution_id' => 89,
            'association_id' => 55,
        ]);
        DB::table('resolution_associations')->insert([
            'resolution_id' => 28,
            'association_id' => 55,
        ]);

        // Mujeres Luchadoras: 56
        DB::table('resolution_associations')->insert([
            'resolution_id' => 125,
            'association_id' => 56,
        ]);

        // Mujeres luchando por un futuro mejor: 57
        DB::table('resolution_associations')->insert([
            'resolution_id' => 58,
            'association_id' => 57,
        ]);

        // Niño manuelito: 58
        DB::table('resolution_associations')->insert([
            'resolution_id' => 35,
            'association_id' => 58,
        ]);
        DB::table('resolution_associations')->insert([
            'resolution_id' => 29,
            'association_id' => 58,
        ]);

        // Nuestra Señora auxilio de los cristianos: 60
        DB::table('resolution_associations')->insert([
            'resolution_id' => 192,
            'association_id' => 60,
        ]);
        DB::table('resolution_associations')->insert([
            'resolution_id' => 2,
            'association_id' => 60,
        ]);

        // Nuevo eden: 61
        DB::table('resolution_associations')->insert([
            'resolution_id' => 79,
            'association_id' => 61,
        ]);
        DB::table('resolution_associations')->insert([
            'resolution_id' => 64,
            'association_id' => 61,
        ]);

        // Nuevo Paraiso: 62
        DB::table('resolution_associations')->insert([
            'resolution_id' => 49,
            'association_id' => 62,
        ]);
        DB::table('resolution_associations')->insert([
            'resolution_id' => 24,
            'association_id' => 62,
        ]);

        // Rosita de amor: 64
        DB::table('resolution_associations')->insert([
            'resolution_id' => 135,
            'association_id' => 64,
        ]);

        // San jose: 65
        DB::table('resolution_associations')->insert([
            'resolution_id' => 197,
            'association_id' => 65,
        ]);
        DB::table('resolution_associations')->insert([
            'resolution_id' => 15,
            'association_id' => 65,
        ]);

        // Santa rita de casia: 66
        DB::table('resolution_associations')->insert([
            'resolution_id' => 126,
            'association_id' => 66,
        ]);
        DB::table('resolution_associations')->insert([
            'resolution_id' => 120,
            'association_id' => 66,
        ]);

        // Santisimo sacramento: 67
        DB::table('resolution_associations')->insert([
            'resolution_id' => 179,
            'association_id' => 67,
        ]);
        DB::table('resolution_associations')->insert([
            'resolution_id' => 17,
            'association_id' => 67,
        ]);

        // Tania Soledad baca romero II: 71
        DB::table('resolution_associations')->insert([
            'resolution_id' => 151,
            'association_id' => 71,
        ]);
        DB::table('resolution_associations')->insert([
            'resolution_id' => 149,
            'association_id' => 71,
        ]);

        // Victor Raul: 74
        DB::table('resolution_associations')->insert([
            'resolution_id' => 181,
            'association_id' => 74,
        ]);
        DB::table('resolution_associations')->insert([
            'resolution_id' => 3,
            'association_id' => 74,
        ]);

        // Virgen de guadalupe: 75
        DB::table('resolution_associations')->insert([
            'resolution_id' => 164,
            'association_id' => 75,
        ]);
        DB::table('resolution_associations')->insert([
            'resolution_id' => 160,
            'association_id' => 75,
        ]);

        // Wilmer Sanchez: 77
        DB::table('resolution_associations')->insert([
            'resolution_id' => 43,
            'association_id' => 77,
        ]);


    }
}
