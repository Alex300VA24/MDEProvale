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
            'association_id' => 15,
        ]);
        DB::table('resolution_associations')->insert([
            'resolution_id' => 14,
            'association_id' => 15,
        ]);

        // San Pedro: 20
        DB::table('resolution_associations')->insert([
            'resolution_id' => 186,
            'association_id' => 15,
        ]);
        DB::table('resolution_associations')->insert([
            'resolution_id' => 30,
            'association_id' => 15,
        ]);
/*
        // Tania Soledad Baca Romero: 21
        DB::table('resolution_associations')->insert([
            'resolution_id' => 182,
            'association_id' => 15,
        ]);
        DB::table('resolution_associations')->insert([
            'resolution_id' => 165,
            'association_id' => 15,
        ]);

        // Madres Trabajando por el gran cambio: 22
        DB::table('resolution_associations')->insert([
            'resolution_id' => 182,
            'association_id' => 15,
        ]);
        DB::table('resolution_associations')->insert([
            'resolution_id' => 165,
            'association_id' => 15,
        ]);

        // Virgen de la puerta: 23
        DB::table('resolution_associations')->insert([
            'resolution_id' => 182,
            'association_id' => 15,
        ]);
        DB::table('resolution_associations')->insert([
            'resolution_id' => 165,
            'association_id' => 15,
        ]);

        // Clementina Peralta de Acuña: 24
        DB::table('resolution_associations')->insert([
            'resolution_id' => 182,
            'association_id' => 15,
        ]);
        DB::table('resolution_associations')->insert([
            'resolution_id' => 165,
            'association_id' => 15,
        ]);

        // Las Palmeras III: 25
        DB::table('resolution_associations')->insert([
            'resolution_id' => 182,
            'association_id' => 15,
        ]);
        DB::table('resolution_associations')->insert([
            'resolution_id' => 165,
            'association_id' => 15,
        ]);

        // Virgen de la Puerta: 26
        DB::table('resolution_associations')->insert([
            'resolution_id' => 182,
            'association_id' => 15,
        ]);
        DB::table('resolution_associations')->insert([
            'resolution_id' => 165,
            'association_id' => 15,
        ]);

        // Zoila de la torre de haya (santa veronica): 27
        DB::table('resolution_associations')->insert([
            'resolution_id' => 182,
            'association_id' => 15,
        ]);
        DB::table('resolution_associations')->insert([
            'resolution_id' => 165,
            'association_id' => 15,
        ]);

        // Zoila de la torre de haya (jerusalen): 29
        DB::table('resolution_associations')->insert([
            'resolution_id' => 182,
            'association_id' => 15,
        ]);
        DB::table('resolution_associations')->insert([
            'resolution_id' => 165,
            'association_id' => 15,
        ]);

        // Nuestra Sra. del Perpetuo Socorro: 30
        DB::table('resolution_associations')->insert([
            'resolution_id' => 182,
            'association_id' => 15,
        ]);
        DB::table('resolution_associations')->insert([
            'resolution_id' => 165,
            'association_id' => 15,
        ]);

        // Manuel Arevalo: 31
        DB::table('resolution_associations')->insert([
            'resolution_id' => 182,
            'association_id' => 15,
        ]);
        DB::table('resolution_associations')->insert([
            'resolution_id' => 165,
            'association_id' => 15,
        ]);

        // Ramiro Priale: 32
        DB::table('resolution_associations')->insert([
            'resolution_id' => 182,
            'association_id' => 15,
        ]);
        DB::table('resolution_associations')->insert([
            'resolution_id' => 165,
            'association_id' => 15,
        ]);
        
        // Amigos de Jesus: 33
        DB::table('resolution_associations')->insert([
            'resolution_id' => 182,
            'association_id' => 15,
        ]);
        DB::table('resolution_associations')->insert([
            'resolution_id' => 165,
            'association_id' => 15,
        ]);

        // Bodas del Cordero: 34
        DB::table('resolution_associations')->insert([
            'resolution_id' => 182,
            'association_id' => 15,
        ]);
        DB::table('resolution_associations')->insert([
            'resolution_id' => 165,
            'association_id' => 15,
        ]);

        // Corazon de jesus: 35
        DB::table('resolution_associations')->insert([
            'resolution_id' => 182,
            'association_id' => 15,
        ]);
        DB::table('resolution_associations')->insert([
            'resolution_id' => 165,
            'association_id' => 15,
        ]);

        // Domitila chungara: 36
        DB::table('resolution_associations')->insert([
            'resolution_id' => 182,
            'association_id' => 15,
        ]);
        DB::table('resolution_associations')->insert([
            'resolution_id' => 165,
            'association_id' => 15,
        ]);


        // Edith sonrisas de niños: 37
        DB::table('resolution_associations')->insert([
            'resolution_id' => 182,
            'association_id' => 15,
        ]);
        DB::table('resolution_associations')->insert([
            'resolution_id' => 165,
            'association_id' => 15,
        ]);

        // En angel: 38
        DB::table('resolution_associations')->insert([
            'resolution_id' => 182,
            'association_id' => 15,
        ]);
        DB::table('resolution_associations')->insert([
            'resolution_id' => 165,
            'association_id' => 15,
        ]);

        // Gloria Valderrama Garcia: 39
        DB::table('resolution_associations')->insert([
            'resolution_id' => 182,
            'association_id' => 15,
        ]);
        DB::table('resolution_associations')->insert([
            'resolution_id' => 165,
            'association_id' => 15,
        ]);

        // Hijas de Sion: 40
        DB::table('resolution_associations')->insert([
            'resolution_id' => 182,
            'association_id' => 15,
        ]);
        DB::table('resolution_associations')->insert([
            'resolution_id' => 165,
            'association_id' => 15,
        ]);

        // Hilo Rojo: 41
        DB::table('resolution_associations')->insert([
            'resolution_id' => 182,
            'association_id' => 15,
        ]);
        DB::table('resolution_associations')->insert([
            'resolution_id' => 165,
            'association_id' => 15,
        ]);


        // Indoamericana: 42
        DB::table('resolution_associations')->insert([
            'resolution_id' => 182,
            'association_id' => 15,
        ]);
        DB::table('resolution_associations')->insert([
            'resolution_id' => 165,
            'association_id' => 15,
        ]);

        // Jerusalen madres unidas: 43
        DB::table('resolution_associations')->insert([
            'resolution_id' => 182,
            'association_id' => 15,
        ]);
        DB::table('resolution_associations')->insert([
            'resolution_id' => 165,
            'association_id' => 15,
        ]);


        // Jesus es mi salvacion: 44
        DB::table('resolution_associations')->insert([
            'resolution_id' => 182,
            'association_id' => 15,
        ]);
        DB::table('resolution_associations')->insert([
            'resolution_id' => 165,
            'association_id' => 15,
        ]);

        // Jesus mi salvador: 45
        DB::table('resolution_associations')->insert([
            'resolution_id' => 182,
            'association_id' => 15,
        ]);
        DB::table('resolution_associations')->insert([
            'resolution_id' => 165,
            'association_id' => 15,
        ]);

        // Juana malaver de garrido: 46
        DB::table('resolution_associations')->insert([
            'resolution_id' => 182,
            'association_id' => 15,
        ]);
        DB::table('resolution_associations')->insert([
            'resolution_id' => 165,
            'association_id' => 15,
        ]);

        // La caridad: 47
        DB::table('resolution_associations')->insert([
            'resolution_id' => 182,
            'association_id' => 15,
        ]);
        DB::table('resolution_associations')->insert([
            'resolution_id' => 165,
            'association_id' => 15,
        ]);

        // La fuerza del pueblo: 48
        DB::table('resolution_associations')->insert([
            'resolution_id' => 182,
            'association_id' => 15,
        ]);
        DB::table('resolution_associations')->insert([
            'resolution_id' => 165,
            'association_id' => 15,
        ]);

        // Las Dalias: 49
        DB::table('resolution_associations')->insert([
            'resolution_id' => 182,
            'association_id' => 15,
        ]);
        DB::table('resolution_associations')->insert([
            'resolution_id' => 165,
            'association_id' => 15,
        ]);

        // Los angeles de jesus: 50
        DB::table('resolution_associations')->insert([
            'resolution_id' => 182,
            'association_id' => 15,
        ]);
        DB::table('resolution_associations')->insert([
            'resolution_id' => 165,
            'association_id' => 15,
        ]);


        // Los geranios: 51
        DB::table('resolution_associations')->insert([
            'resolution_id' => 182,
            'association_id' => 15,
        ]);
        DB::table('resolution_associations')->insert([
            'resolution_id' => 165,
            'association_id' => 15,
        ]);

        // Madre de cristo: 52
        DB::table('resolution_associations')->insert([
            'resolution_id' => 182,
            'association_id' => 15,
        ]);
        DB::table('resolution_associations')->insert([
            'resolution_id' => 165,
            'association_id' => 15,
        ]);


        // Madre josefina potel: 53
        DB::table('resolution_associations')->insert([
            'resolution_id' => 182,
            'association_id' => 15,
        ]);
        DB::table('resolution_associations')->insert([
            'resolution_id' => 165,
            'association_id' => 15,
        ]);

        // Madres trabajando por ampliacion: 54
        DB::table('resolution_associations')->insert([
            'resolution_id' => 182,
            'association_id' => 15,
        ]);
        DB::table('resolution_associations')->insert([
            'resolution_id' => 165,
            'association_id' => 15,
        ]);

        // Manos Solidarias: 55
        DB::table('resolution_associations')->insert([
            'resolution_id' => 182,
            'association_id' => 15,
        ]);
        DB::table('resolution_associations')->insert([
            'resolution_id' => 165,
            'association_id' => 15,
        ]);

        // Martin Namay: 56
        DB::table('resolution_associations')->insert([
            'resolution_id' => 182,
            'association_id' => 15,
        ]);
        DB::table('resolution_associations')->insert([
            'resolution_id' => 165,
            'association_id' => 15,
        ]);

        // Mujeres Luchadoras: 57
        DB::table('resolution_associations')->insert([
            'resolution_id' => 182,
            'association_id' => 15,
        ]);
        DB::table('resolution_associations')->insert([
            'resolution_id' => 165,
            'association_id' => 15,
        ]);

        // Mujeres luchando por un futuro mejor: 58
        DB::table('resolution_associations')->insert([
            'resolution_id' => 182,
            'association_id' => 15,
        ]);
        DB::table('resolution_associations')->insert([
            'resolution_id' => 165,
            'association_id' => 15,
        ]);

        // Niño manuelito: 59
        DB::table('resolution_associations')->insert([
            'resolution_id' => 182,
            'association_id' => 15,
        ]);
        DB::table('resolution_associations')->insert([
            'resolution_id' => 165,
            'association_id' => 15,
        ]);

        // Niños del triunfo: 60
        DB::table('resolution_associations')->insert([
            'resolution_id' => 182,
            'association_id' => 15,
        ]);
        DB::table('resolution_associations')->insert([
            'resolution_id' => 165,
            'association_id' => 15,
        ]);

        // Nuestra Señora auxilio de los cristianos: 61
        DB::table('resolution_associations')->insert([
            'resolution_id' => 182,
            'association_id' => 15,
        ]);
        DB::table('resolution_associations')->insert([
            'resolution_id' => 165,
            'association_id' => 15,
        ]);

        // Nuevo eden: 62
        DB::table('resolution_associations')->insert([
            'resolution_id' => 182,
            'association_id' => 15,
        ]);
        DB::table('resolution_associations')->insert([
            'resolution_id' => 165,
            'association_id' => 15,
        ]);

        // Nuevo Paraiso: 63
        DB::table('resolution_associations')->insert([
            'resolution_id' => 182,
            'association_id' => 15,
        ]);
        DB::table('resolution_associations')->insert([
            'resolution_id' => 165,
            'association_id' => 15,
        ]);

        // Nuevo Jerusalen la alegria de los niños: 64
        DB::table('resolution_associations')->insert([
            'resolution_id' => 182,
            'association_id' => 15,
        ]);
        DB::table('resolution_associations')->insert([
            'resolution_id' => 165,
            'association_id' => 15,
        ]);

        // Rosita de amor: 65
        DB::table('resolution_associations')->insert([
            'resolution_id' => 182,
            'association_id' => 15,
        ]);
        DB::table('resolution_associations')->insert([
            'resolution_id' => 165,
            'association_id' => 15,
        ]);

        // San jose: 66
        DB::table('resolution_associations')->insert([
            'resolution_id' => 182,
            'association_id' => 15,
        ]);
        DB::table('resolution_associations')->insert([
            'resolution_id' => 165,
            'association_id' => 15,
        ]);

        // Santa rita de casia: 67
        DB::table('resolution_associations')->insert([
            'resolution_id' => 182,
            'association_id' => 15,
        ]);
        DB::table('resolution_associations')->insert([
            'resolution_id' => 165,
            'association_id' => 15,
        ]);

        // Santisimo sacramento: 68
        DB::table('resolution_associations')->insert([
            'resolution_id' => 182,
            'association_id' => 15,
        ]);
        DB::table('resolution_associations')->insert([
            'resolution_id' => 165,
            'association_id' => 15,
        ]);

        // Sembrando esperanza: 69
        DB::table('resolution_associations')->insert([
            'resolution_id' => 182,
            'association_id' => 15,
        ]);
        DB::table('resolution_associations')->insert([
            'resolution_id' => 165,
            'association_id' => 15,
        ]);

        // Sol y arena de las palmeritas: 70
        DB::table('resolution_associations')->insert([
            'resolution_id' => 182,
            'association_id' => 15,
        ]);
        DB::table('resolution_associations')->insert([
            'resolution_id' => 165,
            'association_id' => 15,
        ]);

        // Supermamas n1: 71
        DB::table('resolution_associations')->insert([
            'resolution_id' => 182,
            'association_id' => 15,
        ]);
        DB::table('resolution_associations')->insert([
            'resolution_id' => 165,
            'association_id' => 15,
        ]);

        // Tania Soledad baca romero II: 72
        DB::table('resolution_associations')->insert([
            'resolution_id' => 182,
            'association_id' => 15,
        ]);
        DB::table('resolution_associations')->insert([
            'resolution_id' => 165,
            'association_id' => 15,
        ]);

        // Unidas en un solo corazon: 73
        DB::table('resolution_associations')->insert([
            'resolution_id' => 182,
            'association_id' => 15,
        ]);
        DB::table('resolution_associations')->insert([
            'resolution_id' => 165,
            'association_id' => 15,
        ]);

        // Unidas por la familia: 74
        DB::table('resolution_associations')->insert([
            'resolution_id' => 182,
            'association_id' => 15,
        ]);
        DB::table('resolution_associations')->insert([
            'resolution_id' => 165,
            'association_id' => 15,
        ]);

        // Victor Raul: 75
        DB::table('resolution_associations')->insert([
            'resolution_id' => 182,
            'association_id' => 15,
        ]);
        DB::table('resolution_associations')->insert([
            'resolution_id' => 165,
            'association_id' => 15,
        ]);

        // Virgen de guadalupe: 76
        DB::table('resolution_associations')->insert([
            'resolution_id' => 182,
            'association_id' => 15,
        ]);
        DB::table('resolution_associations')->insert([
            'resolution_id' => 165,
            'association_id' => 15,
        ]);

        // Virgen de la puerta: 77
        DB::table('resolution_associations')->insert([
            'resolution_id' => 182,
            'association_id' => 15,
        ]);
        DB::table('resolution_associations')->insert([
            'resolution_id' => 165,
            'association_id' => 15,
        ]);

        // Wilmer Sanchez: 78
        DB::table('resolution_associations')->insert([
            'resolution_id' => 182,
            'association_id' => 15,
        ]);
        DB::table('resolution_associations')->insert([
            'resolution_id' => 165,
            'association_id' => 15,
        ]);
*/

    }
}
