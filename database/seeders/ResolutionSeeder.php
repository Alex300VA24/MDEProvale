<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use RuntimeException;

class ResolutionSeeder extends Seeder
{
    /**
     * Resoluciones reconstruidas desde Padron_Vaso_de_Leche_La_Esperanza.xlsx.
     * La resolución 1 crea el comité. Las resoluciones 2 y 3 son posteriores.
     * La vigencia del padrón pertenece a la última resolución registrada.
     */
    public function run(): void
    {
        $currentStateId = DB::table('states')->where('abbreviation', 'VIG')->value('id');
        $expiredStateId = DB::table('states')->where('abbreviation', 'VEN')->value('id');

        if (!$currentStateId || !$expiredStateId) {
            throw new RuntimeException('Faltan los estados VIG o VEN.');
        }

        $resolutions = [
            // Comité 170 - NUESTRA SEÑORA AUXILIO DE LOS CRISTIANOS | Resolución 2 (posterior)
            [
                'document' => '0006-2024',
                'date_start' => '2026-03-02',
                'date_end' => '2028-03-02',
            ],
            // Comité 305 - VICTOR RAUL | Resolución 2 (posterior)
            [
                'document' => '0015-2024',
                'date_start' => '2026-02-24',
                'date_end' => '2028-02-24',
            ],
            // Comité 175 - VIRGENES DEL SOL | Resolución 2 (posterior)
            [
                'document' => '0023-2024',
                'date_start' => '2026-02-24',
                'date_end' => '2028-02-24',
            ],
            // Comité 075 - LAS DALIAS | Resolución 2 (posterior)
            [
                'document' => '0024-2024',
                'date_start' => '2026-02-24',
                'date_end' => '2028-02-24',
            ],
            // Comité 145 - LOS NIÑOS DE BELEN | Resolución 2 (posterior)
            [
                'document' => '0025-2024',
                'date_start' => '2026-03-13',
                'date_end' => '2028-03-13',
            ],
            // Comité 325 - ZOILA DE LATORRE DE HAYA (JERUSALEN) | Resolución 2 (posterior)
            [
                'document' => '0035-2024',
                'date_start' => '2026-03-20',
                'date_end' => '2028-03-20',
            ],
            // Comité 130 - CLEMENTINA PERALTA DE ACUÑA | Resolución 2 (posterior)
            [
                'document' => '0040-2024',
                'date_start' => '2026-03-18',
                'date_end' => '2028-03-18',
            ],
            // Comité 380 - TANIA SOLEDAD BACA ROMERO | Resolución 2 (posterior)
            [
                'document' => '0041-2024',
                'date_start' => '2026-03-13',
                'date_end' => '2028-03-13',
            ],
            // Comité 155 - SANTA CATALINA | Resolución 2 (posterior)
            [
                'document' => '0044-2024',
                'date_start' => '2026-03-13',
                'date_end' => '2028-03-13',
            ],
            // Comité 082 - BODAS DEL CORDERO | Resolución 2 (posterior)
            [
                'document' => '0044-2026',
                'date_start' => '2026-01-16',
                'date_end' => '2028-01-16',
            ],
            // Comité 375 - JESUS ME GUIA | Resolución 2 (posterior)
            [
                'document' => '0046-2024',
                'date_start' => '2026-04-09',
                'date_end' => '2028-04-08',
            ],
            // Comité 350 - SAN JOSE | Resolución 2 (posterior)
            [
                'document' => '0048-2024',
                'date_start' => '2026-03-13',
                'date_end' => '2028-03-13',
            ],
            // Comité 095 - CLEMENTINA ACUÑA DE PERALTA Nº 2 | Resolución 2 (posterior)
            [
                'document' => '0053-2024',
                'date_start' => '2026-03-30',
                'date_end' => '2028-03-30',
            ],
            // Comité 111 - MADRE JOSEFINA POTEL | Resolución 1 (creación)
            [
                'document' => '0057-2021',
                'date_start' => '2024-11-05',
                'date_end' => '2026-11-05',
            ],
            // Comité 058 - JUAN AMADOR DE GARRIDO | Resolución 1 (creación)
            [
                'document' => '0058-2020',
                'date_start' => '2024-06-21',
                'date_end' => '2026-06-21',
            ],
            // Comité 230 - SANTISIMO SACRAMENTO | Resolución 3 (posterior)
            [
                'document' => '0082-2026',
                'date_start' => '2026-01-23',
                'date_end' => '2028-01-23',
            ],
            // Comité 265 - CLEMENTINA PERALTA DE ACUÑA | Resolución 1 (creación)
            [
                'document' => '0120-2021',
                'date_start' => '2025-03-24',
                'date_end' => '2027-03-24',
            ],
            // Comité 070 - SAN PEDRO | Resolución 3 (posterior)
            [
                'document' => '0126-2025',
                'date_start' => '2025-02-13',
                'date_end' => '2027-02-13',
            ],
            // Comité 037 - WILMER SANCHEZ | Resolución 1 (creación)
            // Comité 313 - DOMITILA CHINGANA | Resolución 1 (creación)
            [
                'document' => '0146-2023',
                'date_start' => '2026-08-19',
                'date_end' => '2028-08-19',
            ],
            // Comité 258 - MANOS SOLIDARIAS | Resolución 2 (posterior)
            [
                'document' => '0147-2023',
                'date_start' => '2025-02-18',
                'date_end' => '2027-02-18',
            ],
            // Comité 058 - JUAN AMADOR DE GARRIDO | Resolución 2 (posterior)
            [
                'document' => '0148-2022',
                'date_start' => '2024-06-21',
                'date_end' => '2026-06-21',
            ],
            // Comité 253 - NUEVO PARAISO | Resolución 2 (posterior)
            [
                'document' => '0151-2023',
                'date_start' => '2025-03-07',
                'date_end' => '2027-03-07',
            ],
            // Comité 273 - EDITH SONRISAS DE NIÑOS | Resolución 1 (creación)
            [
                'document' => '0152-2023',
                'date_start' => '2025-03-05',
                'date_end' => '2027-03-05',
            ],
            // Comité 275 - BUEN SOCORRO | Resolución 3 (posterior)
            [
                'document' => '0154-2025',
                'date_start' => '2025-02-12',
                'date_end' => '2027-02-12',
            ],
            // Comité 045 - MADRES TRABAJANDO POR AMPLIACION | Resolución 2 (posterior)
            [
                'document' => '0155-2023',
                'date_start' => '2025-02-28',
                'date_end' => '2027-02-28',
            ],
            // Comité 274 - MARTIN MAMAY | Resolución 3 (posterior)
            [
                'document' => '0155-2025',
                'date_start' => '2025-02-12',
                'date_end' => '2027-02-12',
            ],
            // Comité 295 - NIÑO MANUELITO | Resolución 1 (creación)
            [
                'document' => '0156-2022',
                'date_start' => '2026-04-08',
                'date_end' => '2028-04-08',
            ],
            // Comité 240 - VIRGEN DE LA PUERTA (SAN MARTIN) | Resolución 1 (creación)
            [
                'document' => '0158-2022',
                'date_start' => '2026-07-14',
                'date_end' => '2028-07-14',
            ],
            // Comité 200 - MICAELA BASTIDAS | Resolución 1 (creación)
            [
                'document' => '0159-2022',
                'date_start' => '2026-08-11',
                'date_end' => '2028-08-11',
            ],
            // Comité 060 - SILOE | Resolución 2 (posterior)
            [
                'document' => '0164-2024',
                'date_start' => '2026-04-23',
                'date_end' => '2028-04-23',
            ],
            // Comité 258 - MANOS SOLIDARIAS | Resolución 3 (posterior)
            [
                'document' => '0172-2025',
                'date_start' => '2025-02-18',
                'date_end' => '2027-02-18',
            ],
            // Comité 295 - NIÑO MANUELITO | Resolución 2 (posterior)
            [
                'document' => '0173-2024',
                'date_start' => '2026-04-08',
                'date_end' => '2028-04-08',
            ],
            // Comité 060 - SILOE | Resolución 1 (creación)
            [
                'document' => '0176-2022',
                'date_start' => '2026-04-23',
                'date_end' => '2028-04-23',
            ],
            // Comité 253 - NUEVO PARAISO | Resolución 3 (posterior)
            [
                'document' => '0200-2025',
                'date_start' => '2025-03-07',
                'date_end' => '2027-03-07',
            ],
            // Comité 075 - LAS DALIAS | Resolución 3 (posterior)
            [
                'document' => '0203-2026',
                'date_start' => '2026-02-24',
                'date_end' => '2028-02-24',
            ],
            // Comité 175 - VIRGENES DEL SOL | Resolución 3 (posterior)
            [
                'document' => '0204-2026',
                'date_start' => '2026-02-24',
                'date_end' => '2028-02-24',
            ],
            // Comité 305 - VICTOR RAUL | Resolución 3 (posterior)
            [
                'document' => '0205-2026',
                'date_start' => '2026-02-24',
                'date_end' => '2028-02-24',
            ],
            // Comité 145 - LOS NIÑOS DE BELEN | Resolución 3 (posterior)
            [
                'document' => '0205-2026',
                'date_start' => '2026-03-13',
                'date_end' => '2028-03-13',
            ],
            // Comité 093 - HILO ROJO | Resolución 1 (creación)
            [
                'document' => '0206-2023',
                'date_start' => '2025-03-07',
                'date_end' => '2027-03-07',
            ],
            // Comité 246 - LOS GERANIOS | Resolución 1 (creación)
            [
                'document' => '0207-2021',
                'date_start' => '2025-05-12',
                'date_end' => '2027-05-12',
            ],
            // Comité 350 - SAN JOSE | Resolución 3 (posterior)
            [
                'document' => '0207-2026',
                'date_start' => '2026-03-13',
                'date_end' => '2028-03-13',
            ],
            // Comité 160 - NUESTRA SEÑORA DE FATIMA (FRATERNIDAD) | Resolución 1 (creación)
            [
                'document' => '0215-2022',
                'date_start' => '2026-06-09',
                'date_end' => '2028-06-09',
            ],
            // Comité 045 - MADRES TRABAJANDO POR AMPLIACION | Resolución 3 (posterior)
            [
                'document' => '0215-2025',
                'date_start' => '2025-02-28',
                'date_end' => '2027-02-28',
            ],
            // Comité 150 - ESTRELLA DE BELEN | Resolución 1 (creación)
            [
                'document' => '0216-2021',
                'date_start' => '2025-05-22',
                'date_end' => '2027-05-22',
            ],
            // Comité 170 - NUESTRA SEÑORA AUXILIO DE LOS CRISTIANOS | Resolución 3 (posterior)
            [
                'document' => '0219-2026',
                'date_start' => '2026-03-02',
                'date_end' => '2028-03-02',
            ],
            // Comité 037 - WILMER SANCHEZ | Resolución 2 (posterior)
            [
                'document' => '0220-2025',
                'date_start' => '2025-03-03',
                'date_end' => '2027-03-03',
            ],
            // Comité 270 - CLEMENTINA PERALTA DE ACUÑA | Resolución 2 (posterior)
            [
                'document' => '0240-2023',
                'date_start' => '2025-04-04',
                'date_end' => '2027-04-04',
            ],
            // Comité 375 - JESUS ME GUIA | Resolución 3 (posterior)
            [
                'document' => '0240-2026',
                'date_start' => '2026-04-09',
                'date_end' => '2028-04-08',
            ],
            // Comité 265 - CLEMENTINA PERALTA DE ACUÑA | Resolución 2 (posterior)
            [
                'document' => '0244-2023',
                'date_start' => '2025-03-24',
                'date_end' => '2027-03-24',
            ],
            // Comité 302 - MUJERES LUCHANDO POR UN FUTURO MEJOR | Resolución 1 (creación)
            [
                'document' => '0252-2023',
                'date_start' => '2025-03-28',
                'date_end' => '2027-03-28',
            ],
            // Comité 265 - CLEMENTINA PERALTA DE ACUÑA | Resolución 3 (posterior)
            [
                'document' => '0255-2025',
                'date_start' => '2025-03-24',
                'date_end' => '2027-03-24',
            ],
            // Comité 273 - EDITH SONRISAS DE NIÑOS | Resolución 2 (posterior)
            [
                'document' => '0257-2025',
                'date_start' => '2025-03-05',
                'date_end' => '2027-03-05',
            ],
            // Comité 380 - TANIA SOLEDAD BACA ROMERO | Resolución 3 (posterior)
            [
                'document' => '0266-2026',
                'date_start' => '2026-03-13',
                'date_end' => '2028-03-13',
            ],
            // Comité 155 - SANTA CATALINA | Resolución 3 (posterior)
            [
                'document' => '0268-2026',
                'date_start' => '2026-03-13',
                'date_end' => '2028-03-13',
            ],
            // Comité 235 - LA CARIDAD | Resolución 1 (creación)
            [
                'document' => '0283-2025',
                'date_start' => '2025-03-17',
                'date_end' => '2027-03-17',
            ],
            // Comité 130 - CLEMENTINA PERALTA DE ACUÑA | Resolución 3 (posterior)
            [
                'document' => '0287-2026',
                'date_start' => '2026-03-18',
                'date_end' => '2028-03-18',
            ],
            // Comité 025 - NUEVA ESPERANZA | Resolución 1 (creación)
            [
                'document' => '0295-2022',
                'date_start' => '2026-08-05',
                'date_end' => '2028-08-05',
            ],
            // Comité 325 - ZOILA DE LATORRE DE HAYA (JERUSALEN) | Resolución 3 (posterior)
            [
                'document' => '0295-2026',
                'date_start' => '2026-03-20',
                'date_end' => '2028-03-20',
            ],
            // Comité 105 - INDOAMERICA | Resolución 1 (creación)
            [
                'document' => '0310-2021',
                'date_start' => '2025-05-30',
                'date_end' => '2027-05-30',
            ],
            // Comité 093 - HILO ROJO | Resolución 2 (posterior)
            [
                'document' => '0312-2025',
                'date_start' => '2025-03-07',
                'date_end' => '2027-03-07',
            ],
            // Comité 095 - CLEMENTINA ACUÑA DE PERALTA Nº 2 | Resolución 3 (posterior)
            [
                'document' => '0315-2026',
                'date_start' => '2026-03-30',
                'date_end' => '2028-03-30',
            ],
            // Comité 059 - VIRGEN DE LA PUERTA | Resolución 1 (creación)
            [
                'document' => '0316-2025',
                'date_start' => '2025-03-27',
                'date_end' => '2027-03-27',
            ],
            // Comité 302 - MUJERES LUCHANDO POR UN FUTURO MEJOR | Resolución 2 (posterior)
            [
                'document' => '0317-2025',
                'date_start' => '2025-03-28',
                'date_end' => '2027-03-28',
            ],
            // Comité 160 - NUESTRA SEÑORA DE FATIMA (FRATERNIDAD) | Resolución 2 (posterior)
            [
                'document' => '0319-2024',
                'date_start' => '2026-06-09',
                'date_end' => '2028-06-09',
            ],
            // Comité 295 - NIÑO MANUELITO | Resolución 3 (posterior)
            [
                'document' => '0333-2026',
                'date_start' => '2026-04-08',
                'date_end' => '2028-04-08',
            ],
            // Comité 270 - CLEMENTINA PERALTA DE ACUÑA | Resolución 3 (posterior)
            [
                'document' => '0334-2025',
                'date_start' => '2025-04-04',
                'date_end' => '2027-04-04',
            ],
            // Comité 240 - VIRGEN DE LA PUERTA (SAN MARTIN) | Resolución 2 (posterior)
            [
                'document' => '0356-2024',
                'date_start' => '2026-07-14',
                'date_end' => '2028-07-14',
            ],
            // Comité 060 - SILOE | Resolución 3 (posterior)
            [
                'document' => '0378-2026',
                'date_start' => '2026-04-23',
                'date_end' => '2028-04-23',
            ],
            // Comité 200 - MICAELA BASTIDAS | Resolución 2 (posterior)
            [
                'document' => '0383-2024',
                'date_start' => '2026-08-11',
                'date_end' => '2028-08-11',
            ],
            // Comité 078 - NUEVO EDEN | Resolución 1 (creación)
            [
                'document' => '0444-2022',
                'date_start' => '2026-08-17',
                'date_end' => '2028-08-17',
            ],
            // Comité 140 - JESUS MI SALVADOR | Resolución 1 (creación)
            [
                'document' => '0451-2022',
                'date_start' => '2026-08-03',
                'date_end' => '2028-08-03',
            ],
            // Comité 036 - CORAZON DE JESUS | Resolución 1 (creación)
            [
                'document' => '0465-2023',
                'date_start' => '2025-05-27',
                'date_end' => '2027-05-27',
            ],
            // Comité 150 - ESTRELLA DE BELEN | Resolución 2 (posterior)
            [
                'document' => '0472-2023',
                'date_start' => '2025-05-22',
                'date_end' => '2027-05-22',
            ],
            // Comité 040 - LAS PALMERAS III | Resolución 3 (posterior)
            [
                'document' => '0475-2026',
                'date_start' => '2026-05-28',
                'date_end' => '2028-05-28',
            ],
            // Comité 078 - NUEVO EDEN | Resolución 2 (posterior)
            [
                'document' => '0501-2024',
                'date_start' => '2026-08-17',
                'date_end' => '2028-08-17',
            ],
            // Comité 058 - JUAN AMADOR DE GARRIDO | Resolución 3 (posterior)
            [
                'document' => '0505-2024',
                'date_start' => '2024-06-21',
                'date_end' => '2026-06-21',
            ],
            // Comité 140 - JESUS MI SALVADOR | Resolución 2 (posterior)
            [
                'document' => '0510-2024',
                'date_start' => '2026-08-03',
                'date_end' => '2028-08-03',
            ],
            // Comité 313 - DOMITILA CHINGANA | Resolución 2 (posterior)
            [
                'document' => '0512-2024',
                'date_start' => '2026-08-19',
                'date_end' => '2028-08-19',
            ],
            // Comité 105 - INDOAMERICA | Resolución 2 (posterior)
            [
                'document' => '0514-2023',
                'date_start' => '2025-05-30',
                'date_end' => '2027-05-30',
            ],
            // Comité 160 - NUESTRA SEÑORA DE FATIMA (FRATERNIDAD) | Resolución 3 (posterior)
            [
                'document' => '0514-2026',
                'date_start' => '2026-06-09',
                'date_end' => '2028-06-09',
            ],
            // Comité 230 - SANTISIMO SACRAMENTO | Resolución 1 (creación)
            [
                'document' => '0520-2021',
                'date_start' => '2026-01-23',
                'date_end' => '2028-01-23',
            ],
            // Comité 019 - SOL Y ARENA DE LAS PALMERITAS | Resolución 1 (creación), conservada porque el comité no figura en el padrón
            [
                'document' => '0535-2024',
                'date_start' => '2024-06-28',
                'date_end' => '2026-06-28',
            ],
            // Comité 036 - CORAZON DE JESUS | Resolución 2 (posterior)
            [
                'document' => '0541-2025',
                'date_start' => '2025-05-27',
                'date_end' => '2027-05-27',
            ],
            // Comité 246 - LOS GERANIOS | Resolución 2 (posterior)
            [
                'document' => '0551-2023',
                'date_start' => '2025-05-12',
                'date_end' => '2027-05-12',
            ],
            // Comité 250 - HIJAS DE SION | Resolución 1 (creación)
            [
                'document' => '0587-2020',
                'date_start' => '2024-11-08',
                'date_end' => '2026-11-08',
            ],
            // Comité 025 - NUEVA ESPERANZA | Resolución 2 (posterior)
            [
                'document' => '0598-2024',
                'date_start' => '2026-08-05',
                'date_end' => '2028-08-05',
            ],
            // Comité 246 - LOS GERANIOS | Resolución 3 (posterior)
            [
                'document' => '0605-2025',
                'date_start' => '2025-05-12',
                'date_end' => '2027-05-12',
            ],
            // Comité 365 - RAMIRO PRIALE PRIALE | Resolución 3 (posterior)
            [
                'document' => '0625-2025',
                'date_start' => '2025-07-22',
                'date_end' => '2027-07-22',
            ],
            // Comité 150 - ESTRELLA DE BELEN | Resolución 3 (posterior)
            [
                'document' => '0626-2025',
                'date_start' => '2025-05-22',
                'date_end' => '2027-05-22',
            ],
            // Comité 240 - VIRGEN DE LA PUERTA (SAN MARTIN) | Resolución 3 (posterior)
            [
                'document' => '0641-2026',
                'date_start' => '2026-07-14',
                'date_end' => '2028-07-14',
            ],
            // Comité 365 - RAMIRO PRIALE PRIALE | Resolución 1 (creación)
            [
                'document' => '0643-2021',
                'date_start' => '2025-07-22',
                'date_end' => '2027-07-22',
            ],
            // Comité 220 - RIOS DE AGUA VIVA | Resolución 1 (creación)
            [
                'document' => '0683-2021',
                'date_start' => '2025-10-28',
                'date_end' => '2027-10-28',
            ],
            // Comité 140 - JESUS MI SALVADOR | Resolución 3 (posterior)
            [
                'document' => '0686-2026',
                'date_start' => '2026-08-03',
                'date_end' => '2028-08-03',
            ],
            // Comité 110 - CESAR ACUÑA PERALTA | Resolución 1 (creación)
            [
                'document' => '0688-2021',
                'date_start' => '2025-09-05',
                'date_end' => '2027-09-05',
            ],
            // Comité 105 - INDOAMERICA | Resolución 3 (posterior)
            [
                'document' => '0688-2025',
                'date_start' => '2025-05-30',
                'date_end' => '2027-05-30',
            ],
            // Comité 080 - AMIGAS UNIDAS | Resolución 1 (creación)
            [
                'document' => '0703-2021',
                'date_start' => '2025-12-04',
                'date_end' => '2027-12-04',
            ],
            // Comité 025 - NUEVA ESPERANZA | Resolución 3 (posterior)
            [
                'document' => '0713-2026',
                'date_start' => '2026-08-05',
                'date_end' => '2028-08-05',
            ],
            // Comité 200 - MICAELA BASTIDAS | Resolución 3 (posterior)
            [
                'document' => '0725-2026',
                'date_start' => '2026-08-11',
                'date_end' => '2028-08-11',
            ],
            // Comité 385 - TANIA SOLEDAD BACA ROMERO II | Resolución 1 (creación)
            [
                'document' => '0744-2020',
                'date_start' => '2024-12-02',
                'date_end' => '2026-12-02',
            ],
            // Comité 370 - JEHOVA ES MI PASTOR | Resolución 1 (creación)
            [
                'document' => '0746-2020',
                'date_start' => '2024-12-13',
                'date_end' => '2026-12-13',
            ],
            // Comité 078 - NUEVO EDEN | Resolución 3 (posterior)
            // Comité 378 - EL ANGEL | Resolución 2 (posterior)
            [
                'document' => '0748-2026',
                'date_start' => '2026-08-17',
                'date_end' => '2028-08-17',
            ],
            // Comité 313 - DOMITILA CHINGANA | Resolución 3 (posterior)
            [
                'document' => '0750-2026',
                'date_start' => '2026-08-19',
                'date_end' => '2028-08-19',
            ],
            // Comité 274 - MARTIN MAMAY | Resolución 2 (posterior)
            [
                'document' => '0770-2023',
                'date_start' => '2025-02-12',
                'date_end' => '2027-02-12',
            ],
            // Comité 360 - MANUEL AREVALO | Resolución 1 (creación)
            [
                'document' => '0774-2020',
                'date_start' => '2024-11-20',
                'date_end' => '2026-11-20',
            ],
            // Comité 280 - VIRGEN DE LA PUERTA (SANTA VERONICA) | Resolución 1 (creación)
            [
                'document' => '0785-2020',
                'date_start' => '2024-11-22',
                'date_end' => '2026-11-22',
            ],
            // Comité 090 - VIRGEN DE GUADALUPE | Resolución 1 (creación)
            [
                'document' => '0790-2020',
                'date_start' => '2024-12-31',
                'date_end' => '2026-12-31',
            ],
            // Comité 365 - RAMIRO PRIALE PRIALE | Resolución 2 (posterior)
            [
                'document' => '0793-2023',
                'date_start' => '2025-07-22',
                'date_end' => '2027-07-22',
            ],
            // Comité 118 - UNIDAS EN UN SOLO CORAZON | Resolución 1 (creación)
            [
                'document' => '0795-2024',
                'date_start' => '2024-08-28',
                'date_end' => '2026-08-28',
            ],
            // Comité 125 - CORAZON DE JESUS Nº 08 | Resolución 1 (creación)
            [
                'document' => '0800-2020',
                'date_start' => '2024-12-31',
                'date_end' => '2026-12-31',
            ],
            // Comité 255 - UNIDAS | Resolución 1 (creación)
            [
                'document' => '0801-2020',
                'date_start' => '2024-12-04',
                'date_end' => '2026-12-04',
            ],
            // Comité 378 - EL ANGEL | Resolución 1 (creación)
            [
                'document' => '0830-2024',
                'date_start' => '2026-08-17',
                'date_end' => '2028-08-17',
            ],
            // Comité 032 - LOS ANGELES DE JESUS | Resolución 1 (creación)
            [
                'document' => '0871-2020',
                'date_start' => '2024-12-02',
                'date_end' => '2026-12-02',
            ],
            // Comité 030 - AMIGOS DE JESUS | Resolución 1 (creación)
            // Comité 050 - MADRES TRABAJANDO POR EL GRAN CAMBIO | Resolución 1 (creación)
            [
                'document' => '0890-2021',
                'date_start' => '2025-10-17',
                'date_end' => '2027-10-17',
            ],
            // Comité 300 - ZOILA DE LATORRE DE HAYA (SANTA VERONICA) | Resolución 1 (creación)
            [
                'document' => '0891-2021',
                'date_start' => '2025-11-17',
                'date_end' => '2027-11-17',
            ],
            // Comité 110 - CESAR ACUÑA PERALTA | Resolución 3 (posterior)
            [
                'document' => '0906-2025',
                'date_start' => '2025-09-05',
                'date_end' => '2027-09-05',
            ],
            // Comité 040 - LAS PALMERAS III | Resolución 2 (posterior)
            [
                'document' => '0907-2025',
                'date_start' => '2026-05-28',
                'date_end' => '2028-05-28',
            ],
            // Comité 005 - SANTA RITA DE CASIA | Resolución 1 (creación)
            [
                'document' => '0921-2021',
                'date_start' => '2025-10-26',
                'date_end' => '2027-10-26',
            ],
            // Comité 040 - LAS PALMERAS III | Resolución 1 (creación)
            [
                'document' => '0948-2023',
                'date_start' => '2026-05-28',
                'date_end' => '2028-05-28',
            ],
            // Comité 268 - SEMBRANDO ESPERANZA | Resolución 1 (creación)
            [
                'document' => '0993-2025',
                'date_start' => '2025-09-17',
                'date_end' => '2027-09-17',
            ],
            // Comité 120 - GLORIA VALDERRAMA GARCIA | Resolución 3 (posterior)
            [
                'document' => '0997-2024',
                'date_start' => '2024-10-10',
                'date_end' => '2026-10-10',
            ],
            // Comité 015 - JESUS ES MI SALVACION | Resolución 1 (creación)
            [
                'document' => '1000-2025',
                'date_start' => '2025-09-19',
                'date_end' => '2027-09-19',
            ],
            // Comité 022 - MADRE DE CRISTO | Resolución 1 (creación)
            [
                'document' => '1001-2025',
                'date_start' => '2025-09-19',
                'date_end' => '2027-09-19',
            ],
            // Comité 110 - CESAR ACUÑA PERALTA | Resolución 2 (posterior)
            [
                'document' => '1014-2023',
                'date_start' => '2025-09-05',
                'date_end' => '2027-09-05',
            ],
            // Comité 340 - NTRA SRA PERPETUO SOCORRO | Resolución 1 (creación)
            [
                'document' => '1019-2021',
                'date_start' => '2025-11-07',
                'date_end' => '2027-11-07',
            ],
            // Comité 253 - NUEVO PARAISO | Resolución 1 (creación)
            [
                'document' => '1048-2020',
                'date_start' => '2025-03-07',
                'date_end' => '2027-03-07',
            ],
            // Comité 275 - BUEN SOCORRO | Resolución 1 (creación)
            [
                'document' => '1050-2020',
                'date_start' => '2025-02-12',
                'date_end' => '2027-02-12',
            ],
            // Comité 120 - GLORIA VALDERRAMA GARCIA | Resolución 1 (creación)
            [
                'document' => '1056-2022',
                'date_start' => '2024-10-10',
                'date_end' => '2026-10-10',
            ],
            // Comité 111 - MADRE JOSEFINA POTEL | Resolución 2 (posterior)
            [
                'document' => '1062-2022',
                'date_start' => '2024-11-05',
                'date_end' => '2026-11-05',
            ],
            // Comité 111 - MADRE JOSEFINA POTEL | Resolución 3 (posterior)
            [
                'document' => '1081-2024',
                'date_start' => '2024-11-05',
                'date_end' => '2026-11-05',
            ],
            // Comité 050 - MADRES TRABAJANDO POR EL GRAN CAMBIO | Resolución 3 (posterior)
            [
                'document' => '1083-2025',
                'date_start' => '2025-10-17',
                'date_end' => '2027-10-17',
            ],
            // Comité 068 - LA FUERZA DEL PUEBLO | Resolución 2 (posterior)
            [
                'document' => '1084-2025',
                'date_start' => '2025-10-17',
                'date_end' => '2027-10-17',
            ],
            // Comité 005 - SANTA RITA DE CASIA | Resolución 2 (posterior)
            [
                'document' => '1096-2023',
                'date_start' => '2025-10-26',
                'date_end' => '2027-10-26',
            ],
            // Comité 070 - SAN PEDRO | Resolución 1 (creación)
            [
                'document' => '1101-2020',
                'date_start' => '2025-02-13',
                'date_end' => '2027-02-13',
            ],
            // Comité 258 - MANOS SOLIDARIAS | Resolución 1 (creación)
            [
                'document' => '1104-2020',
                'date_start' => '2025-02-18',
                'date_end' => '2027-02-18',
            ],
            // Comité 045 - MADRES TRABAJANDO POR AMPLIACION | Resolución 1 (creación)
            [
                'document' => '1105-2020',
                'date_start' => '2025-02-28',
                'date_end' => '2027-02-28',
            ],
            // Comité 250 - HIJAS DE SION | Resolución 3 (posterior)
            [
                'document' => '1118-2024',
                'date_start' => '2024-11-08',
                'date_end' => '2026-11-08',
            ],
            // Comité 220 - RIOS DE AGUA VIVA | Resolución 3 (posterior)
            [
                'document' => '1145-2025',
                'date_start' => '2025-10-28',
                'date_end' => '2027-10-28',
            ],
            // Comité 005 - SANTA RITA DE CASIA | Resolución 3 (posterior)
            [
                'document' => '1146-2025',
                'date_start' => '2025-10-26',
                'date_end' => '2027-10-26',
            ],
            // Comité 034 - MUJERES LUCHADORAS | Resolución 2 (posterior)
            [
                'document' => '1157-2024',
                'date_start' => '2024-11-13',
                'date_end' => '2026-11-13',
            ],
            // Comité 360 - MANUEL AREVALO | Resolución 3 (posterior)
            [
                'document' => '1162-2024',
                'date_start' => '2024-11-20',
                'date_end' => '2026-11-20',
            ],
            // Comité 068 - LA FUERZA DEL PUEBLO | Resolución 1 (creación)
            [
                'document' => '1165-2023',
                'date_start' => '2025-10-17',
                'date_end' => '2027-10-17',
            ],
            // Comité 050 - MADRES TRABAJANDO POR EL GRAN CAMBIO | Resolución 2 (posterior)
            [
                'document' => '1166-2023',
                'date_start' => '2025-10-17',
                'date_end' => '2027-10-17',
            ],
            // Comité 320 - JERUSALEN MADRES UNIDAS | Resolución 1 (creación)
            [
                'document' => '1168-2023',
                'date_start' => '2025-11-07',
                'date_end' => '2027-11-07',
            ],
            // Comité 220 - RIOS DE AGUA VIVA | Resolución 2 (posterior)
            [
                'document' => '1170-2023',
                'date_start' => '2025-10-28',
                'date_end' => '2027-10-28',
            ],
            // Comité 278 - NVO. JERUSALEN LA ALEGRIA DE LOS NIÑOS | Resolución 1 (creación)
            [
                'document' => '1173-2024',
                'date_start' => '2024-11-22',
                'date_end' => '2026-11-22',
            ],
            // Comité 280 - VIRGEN DE LA PUERTA (SANTA VERONICA) | Resolución 3 (posterior)
            [
                'document' => '1174-2024',
                'date_start' => '2024-11-22',
                'date_end' => '2026-11-22',
            ],
            // Comité 271 - ROSITA DE AMOR | Resolución 2 (posterior)
            [
                'document' => '1175-2025',
                'date_start' => '2025-11-07',
                'date_end' => '2027-11-07',
            ],
            // Comité 340 - NTRA SRA PERPETUO SOCORRO | Resolución 3 (posterior)
            [
                'document' => '1180-2025',
                'date_start' => '2025-11-07',
                'date_end' => '2027-11-07',
            ],
            // Comité 320 - JERUSALEN MADRES UNIDAS | Resolución 2 (posterior)
            [
                'document' => '1181-2025',
                'date_start' => '2025-11-07',
                'date_end' => '2027-11-07',
            ],
            // Comité 280 - VIRGEN DE LA PUERTA (SANTA VERONICA) | Resolución 2 (posterior)
            [
                'document' => '1204-2022',
                'date_start' => '2024-11-22',
                'date_end' => '2026-11-22',
            ],
            // Comité 030 - AMIGOS DE JESUS | Resolución 2 (posterior)
            [
                'document' => '1205-2023',
                'date_start' => '2025-11-20',
                'date_end' => '2027-11-20',
            ],
            // Comité 250 - HIJAS DE SION | Resolución 2 (posterior)
            [
                'document' => '1206-2022',
                'date_start' => '2024-11-08',
                'date_end' => '2026-11-08',
            ],
            // Comité 340 - NTRA SRA PERPETUO SOCORRO | Resolución 2 (posterior)
            [
                'document' => '1208-2023',
                'date_start' => '2025-11-07',
                'date_end' => '2027-11-07',
            ],
            // Comité 032 - LOS ANGELES DE JESUS | Resolución 2 (posterior)
            [
                'document' => '1215-2022',
                'date_start' => '2024-12-02',
                'date_end' => '2026-12-02',
            ],
            // Comité 300 - ZOILA DE LATORRE DE HAYA (SANTA VERONICA) | Resolución 3 (posterior)
            [
                'document' => '1217-2025',
                'date_start' => '2025-11-17',
                'date_end' => '2027-11-17',
            ],
            // Comité 034 - MUJERES LUCHADORAS | Resolución 1 (creación)
            [
                'document' => '1232-2022',
                'date_start' => '2024-11-13',
                'date_end' => '2026-11-13',
            ],
            // Comité 125 - CORAZON DE JESUS Nº 08 | Resolución 2 (posterior)
            [
                'document' => '1233-2022',
                'date_start' => '2024-12-31',
                'date_end' => '2026-12-31',
            ],
            // Comité 030 - AMIGOS DE JESUS | Resolución 3 (posterior)
            [
                'document' => '1233-2025',
                'date_start' => '2025-11-20',
                'date_end' => '2027-11-20',
            ],
            // Comité 388 - SUPERMAMAS Nº 1 | Resolución 1 (creación)
            [
                'document' => '1236-2025',
                'date_start' => '2025-11-20',
                'date_end' => '2027-11-20',
            ],
            // Comité 032 - LOS ANGELES DE JESUS | Resolución 3 (posterior)
            [
                'document' => '1240-2024',
                'date_start' => '2024-12-02',
                'date_end' => '2026-12-02',
            ],
            // Comité 385 - TANIA SOLEDAD BACA ROMERO II | Resolución 3 (posterior)
            [
                'document' => '1243-2024',
                'date_start' => '2024-12-02',
                'date_end' => '2026-12-02',
            ],
            // Comité 370 - JEHOVA ES MI PASTOR | Resolución 2 (posterior)
            [
                'document' => '1244-2022',
                'date_start' => '2024-12-13',
                'date_end' => '2026-12-13',
            ],
            // Comité 385 - TANIA SOLEDAD BACA ROMERO II | Resolución 2 (posterior)
            [
                'document' => '1245-2022',
                'date_start' => '2024-12-02',
                'date_end' => '2026-12-02',
            ],
            // Comité 255 - UNIDAS | Resolución 3 (posterior)
            [
                'document' => '1249-2024',
                'date_start' => '2024-12-04',
                'date_end' => '2026-12-04',
            ],
            // Comité 360 - MANUEL AREVALO | Resolución 2 (posterior)
            [
                'document' => '1256-2022',
                'date_start' => '2024-11-20',
                'date_end' => '2026-11-20',
            ],
            // Comité 270 - CLEMENTINA PERALTA DE ACUÑA | Resolución 1 (creación)
            [
                'document' => '1259-2020',
                'date_start' => '2025-04-04',
                'date_end' => '2027-04-04',
            ],
            // Comité 370 - JEHOVA ES MI PASTOR | Resolución 3 (posterior)
            [
                'document' => '1288-2024',
                'date_start' => '2024-12-13',
                'date_end' => '2026-12-13',
            ],
            // Comité 080 - AMIGAS UNIDAS | Resolución 3 (posterior)
            [
                'document' => '1290-2025',
                'date_start' => '2025-12-04',
                'date_end' => '2027-12-04',
            ],
            // Comité 255 - UNIDAS | Resolución 2 (posterior)
            [
                'document' => '1312-2022',
                'date_start' => '2024-12-04',
                'date_end' => '2026-12-04',
            ],
            // Comité 028 - UNIDAS POR LA FAMILIA | Resolución 1 (creación)
            [
                'document' => '1322-2024',
                'date_start' => '2024-12-23',
                'date_end' => '2026-12-23',
            ],
            // Comité 120 - GLORIA VALDERRAMA GARCIA | Resolución 2 (posterior)
            [
                'document' => '1332-2022',
                'date_start' => '2024-10-10',
                'date_end' => '2026-10-10',
            ],
            // Comité 090 - VIRGEN DE GUADALUPE | Resolución 2 (posterior)
            [
                'document' => '1333-2022',
                'date_start' => '2024-12-31',
                'date_end' => '2026-12-31',
            ],
            // Comité 145 - LOS NIÑOS DE BELEN | Resolución 1 (creación)
            [
                'document' => '1336-2022',
                'date_start' => '2026-03-13',
                'date_end' => '2028-03-13',
            ],
            // Comité 125 - CORAZON DE JESUS Nº 08 | Resolución 3 (posterior)
            [
                'document' => '1340-2024',
                'date_start' => '2024-12-31',
                'date_end' => '2026-12-31',
            ],
            // Comité 090 - VIRGEN DE GUADALUPE | Resolución 3 (posterior)
            [
                'document' => '1343-2024',
                'date_start' => '2024-12-31',
                'date_end' => '2026-12-31',
            ],
            // Comité 195 - ESTRELLA DE LA ESPERANZA | Resolución 3 (posterior)
            [
                'document' => '1345-2025',
                'date_start' => '2025-12-23',
                'date_end' => '2027-12-23',
            ],
            // Comité 195 - ESTRELLA DE LA ESPERANZA | Resolución 1 (creación)
            [
                'document' => '1355-2021',
                'date_start' => '2025-12-23',
                'date_end' => '2027-12-23',
            ],
            // Comité 080 - AMIGAS UNIDAS | Resolución 2 (posterior)
            [
                'document' => '1380-2023',
                'date_start' => '2025-12-04',
                'date_end' => '2027-12-04',
            ],
            // Comité 300 - ZOILA DE LATORRE DE HAYA (SANTA VERONICA) | Resolución 2 (posterior)
            [
                'document' => '1432-2023',
                'date_start' => '2025-11-17',
                'date_end' => '2027-11-17',
            ],
            // Comité 271 - ROSITA DE AMOR | Resolución 1 (creación)
            [
                'document' => '1443-2023',
                'date_start' => '2025-11-07',
                'date_end' => '2027-11-07',
            ],
            // Comité 230 - SANTISIMO SACRAMENTO | Resolución 2 (posterior)
            [
                'document' => '1546-2023',
                'date_start' => '2026-01-23',
                'date_end' => '2028-01-23',
            ],
            // Comité 305 - VICTOR RAUL | Resolución 1 (creación)
            [
                'document' => '1595-2021',
                'date_start' => '2026-02-24',
                'date_end' => '2028-02-24',
            ],
            // Comité 195 - ESTRELLA DE LA ESPERANZA | Resolución 2 (posterior)
            [
                'document' => '1618-2023',
                'date_start' => '2025-12-23',
                'date_end' => '2027-12-23',
            ],
            // Comité 380 - TANIA SOLEDAD BACA ROMERO | Resolución 1 (creación)
            [
                'document' => '1704-2021',
                'date_start' => '2026-03-13',
                'date_end' => '2028-03-13',
            ],
            // Comité 082 - BODAS DEL CORDERO | Resolución 1 (creación)
            [
                'document' => '1704-2023',
                'date_start' => '2026-01-16',
                'date_end' => '2028-01-16',
            ],
            // Comité 070 - SAN PEDRO | Resolución 2 (posterior)
            [
                'document' => '1712-2022',
                'date_start' => '2025-02-13',
                'date_end' => '2027-02-13',
            ],
            // Comité 275 - BUEN SOCORRO | Resolución 2 (posterior)
            [
                'document' => '1713-2022',
                'date_start' => '2025-02-12',
                'date_end' => '2027-02-12',
            ],
            // Comité 274 - MARTIN MAMAY | Resolución 1 (creación)
            [
                'document' => '1714-2022',
                'date_start' => '2025-02-12',
                'date_end' => '2027-02-12',
            ],
            // Comité 375 - JESUS ME GUIA | Resolución 1 (creación)
            [
                'document' => '1733-2021',
                'date_start' => '2026-04-09',
                'date_end' => '2028-04-08',
            ],
            // Comité 325 - ZOILA DE LATORRE DE HAYA (JERUSALEN) | Resolución 1 (creación)
            [
                'document' => '1734-2021',
                'date_start' => '2026-03-20',
                'date_end' => '2028-03-20',
            ],
            // Comité 170 - NUESTRA SEÑORA AUXILIO DE LOS CRISTIANOS | Resolución 1 (creación)
            [
                'document' => '1735-2021',
                'date_start' => '2026-03-02',
                'date_end' => '2028-03-02',
            ],
            // Comité 155 - SANTA CATALINA | Resolución 1 (creación)
            [
                'document' => '1747-2021',
                'date_start' => '2026-03-13',
                'date_end' => '2028-03-13',
            ],
            // Comité 075 - LAS DALIAS | Resolución 1 (creación)
            [
                'document' => '1770-2021',
                'date_start' => '2026-02-24',
                'date_end' => '2028-02-24',
            ],
            // Comité 095 - CLEMENTINA ACUÑA DE PERALTA Nº 2 | Resolución 1 (creación)
            [
                'document' => '1771-2021',
                'date_start' => '2026-03-30',
                'date_end' => '2028-03-30',
            ],
            // Comité 175 - VIRGENES DEL SOL | Resolución 1 (creación)
            [
                'document' => '1775-2021',
                'date_start' => '2026-02-24',
                'date_end' => '2028-02-24',
            ],
            // Comité 350 - SAN JOSE | Resolución 1 (creación)
            [
                'document' => '1812-2021',
                'date_start' => '2026-03-13',
                'date_end' => '2028-03-13',
            ],
            // Comité 130 - CLEMENTINA PERALTA DE ACUÑA | Resolución 1 (creación)
            [
                'document' => '1897-2021',
                'date_start' => '2026-03-18',
                'date_end' => '2028-03-18',
            ],
        ];

        $today = now()->toDateString();
        $now = now();

        foreach ($resolutions as $resolution) {
            $resolution['state_id'] = $resolution['date_end'] && $resolution['date_end'] >= $today
                ? $currentStateId
                : $expiredStateId;
            $resolution['created_at'] = $now;
            $resolution['updated_at'] = $now;
            DB::table('resolutions')->insert($resolution);
        }
    }
}
