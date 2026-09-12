<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use RuntimeException;

class ResolutionAssociationSeeder extends Seeder
{
    /**
     * Resoluciones 2 y 3 registradas después de la creación del comité.
     * La resolución 1 se asigna en AssociationSeeder como resolution_id.
     */
    public function run(): void
    {
        $links = [
            // Comité 005 - SANTA RITA DE CASIA | Resolución 2 (posterior)
            [
                'code' => '005',
                'document' => '1096-2023',
                'date_start' => null,
                'date_end' => null,
            ],
            // Comité 005 - SANTA RITA DE CASIA | Resolución 3 (posterior)
            [
                'code' => '005',
                'document' => '1146-2025',
                'date_start' => '2025-10-26',
                'date_end' => '2027-10-26',
            ],
            // Comité 025 - NUEVA ESPERANZA | Resolución 2 (posterior)
            [
                'code' => '025',
                'document' => '0598-2024',
                'date_start' => null,
                'date_end' => null,
            ],
            // Comité 025 - NUEVA ESPERANZA | Resolución 3 (posterior)
            [
                'code' => '025',
                'document' => '0713-2026',
                'date_start' => '2026-08-05',
                'date_end' => '2028-08-05',
            ],
            // Comité 030 - AMIGOS DE JESUS | Resolución 2 (posterior)
            [
                'code' => '030',
                'document' => '1205-2023',
                'date_start' => null,
                'date_end' => null,
            ],
            // Comité 030 - AMIGOS DE JESUS | Resolución 3 (posterior)
            [
                'code' => '030',
                'document' => '1233-2025',
                'date_start' => '2025-11-20',
                'date_end' => '2027-11-20',
            ],
            // Comité 032 - LOS ANGELES DE JESUS | Resolución 2 (posterior)
            [
                'code' => '032',
                'document' => '1215-2022',
                'date_start' => null,
                'date_end' => null,
            ],
            // Comité 032 - LOS ANGELES DE JESUS | Resolución 3 (posterior)
            [
                'code' => '032',
                'document' => '1240-2024',
                'date_start' => '2024-12-02',
                'date_end' => '2026-12-02',
            ],
            // Comité 034 - MUJERES LUCHADORAS | Resolución 2 (posterior)
            [
                'code' => '034',
                'document' => '1157-2024',
                'date_start' => '2024-11-13',
                'date_end' => '2026-11-13',
            ],
            // Comité 036 - CORAZON DE JESUS | Resolución 2 (posterior)
            [
                'code' => '036',
                'document' => '0541-2025',
                'date_start' => '2025-05-27',
                'date_end' => '2027-05-27',
            ],
            // Comité 037 - WILMER SANCHEZ | Resolución 2 (posterior)
            [
                'code' => '037',
                'document' => '0220-2025',
                'date_start' => '2025-03-03',
                'date_end' => '2027-03-03',
            ],
            // Comité 040 - LAS PALMERAS III | Resolución 2 (posterior)
            [
                'code' => '040',
                'document' => '0907-2025',
                'date_start' => null,
                'date_end' => null,
            ],
            // Comité 040 - LAS PALMERAS III | Resolución 3 (posterior)
            [
                'code' => '040',
                'document' => '0475-2026',
                'date_start' => '2026-05-28',
                'date_end' => '2028-05-28',
            ],
            // Comité 045 - MADRES TRABAJANDO POR AMPLIACION | Resolución 2 (posterior)
            [
                'code' => '045',
                'document' => '0155-2023',
                'date_start' => null,
                'date_end' => null,
            ],
            // Comité 045 - MADRES TRABAJANDO POR AMPLIACION | Resolución 3 (posterior)
            [
                'code' => '045',
                'document' => '0215-2025',
                'date_start' => '2025-02-28',
                'date_end' => '2027-02-28',
            ],
            // Comité 050 - MADRES TRABAJANDO POR EL GRAN CAMBIO | Resolución 2 (posterior)
            [
                'code' => '050',
                'document' => '1166-2023',
                'date_start' => null,
                'date_end' => null,
            ],
            // Comité 050 - MADRES TRABAJANDO POR EL GRAN CAMBIO | Resolución 3 (posterior)
            [
                'code' => '050',
                'document' => '1083-2025',
                'date_start' => '2025-10-17',
                'date_end' => '2027-10-17',
            ],
            // Comité 058 - JUAN AMADOR DE GARRIDO | Resolución 2 (posterior)
            [
                'code' => '058',
                'document' => '0148-2022',
                'date_start' => null,
                'date_end' => null,
            ],
            // Comité 058 - JUAN AMADOR DE GARRIDO | Resolución 3 (posterior)
            [
                'code' => '058',
                'document' => '0505-2024',
                'date_start' => '2024-06-21',
                'date_end' => '2026-06-21',
            ],
            // Comité 060 - SILOE | Resolución 2 (posterior)
            [
                'code' => '060',
                'document' => '0164-2024',
                'date_start' => null,
                'date_end' => null,
            ],
            // Comité 060 - SILOE | Resolución 3 (posterior)
            [
                'code' => '060',
                'document' => '0378-2026',
                'date_start' => '2026-04-23',
                'date_end' => '2028-04-23',
            ],
            // Comité 068 - LA FUERZA DEL PUEBLO | Resolución 2 (posterior)
            [
                'code' => '068',
                'document' => '1084-2025',
                'date_start' => '2025-10-17',
                'date_end' => '2027-10-17',
            ],
            // Comité 070 - SAN PEDRO | Resolución 2 (posterior)
            [
                'code' => '070',
                'document' => '1712-2022',
                'date_start' => null,
                'date_end' => null,
            ],
            // Comité 070 - SAN PEDRO | Resolución 3 (posterior)
            [
                'code' => '070',
                'document' => '0126-2025',
                'date_start' => '2025-02-13',
                'date_end' => '2027-02-13',
            ],
            // Comité 075 - LAS DALIAS | Resolución 2 (posterior)
            [
                'code' => '075',
                'document' => '0024-2024',
                'date_start' => null,
                'date_end' => null,
            ],
            // Comité 075 - LAS DALIAS | Resolución 3 (posterior)
            [
                'code' => '075',
                'document' => '0203-2026',
                'date_start' => '2026-02-24',
                'date_end' => '2028-02-24',
            ],
            // Comité 078 - NUEVO EDEN | Resolución 2 (posterior)
            [
                'code' => '078',
                'document' => '0501-2024',
                'date_start' => null,
                'date_end' => null,
            ],
            // Comité 078 - NUEVO EDEN | Resolución 3 (posterior)
            [
                'code' => '078',
                'document' => '0748-2026',
                'date_start' => '2026-08-17',
                'date_end' => '2028-08-17',
            ],
            // Comité 080 - AMIGAS UNIDAS | Resolución 2 (posterior)
            [
                'code' => '080',
                'document' => '1380-2023',
                'date_start' => null,
                'date_end' => null,
            ],
            // Comité 080 - AMIGAS UNIDAS | Resolución 3 (posterior)
            [
                'code' => '080',
                'document' => '1290-2025',
                'date_start' => '2025-12-04',
                'date_end' => '2027-12-04',
            ],
            // Comité 082 - BODAS DEL CORDERO | Resolución 2 (posterior)
            [
                'code' => '082',
                'document' => '0044-2026',
                'date_start' => '2026-01-16',
                'date_end' => '2028-01-16',
            ],
            // Comité 090 - VIRGEN DE GUADALUPE | Resolución 2 (posterior)
            [
                'code' => '090',
                'document' => '1333-2022',
                'date_start' => null,
                'date_end' => null,
            ],
            // Comité 090 - VIRGEN DE GUADALUPE | Resolución 3 (posterior)
            [
                'code' => '090',
                'document' => '1343-2024',
                'date_start' => '2024-12-31',
                'date_end' => '2026-12-31',
            ],
            // Comité 093 - HILO ROJO | Resolución 2 (posterior)
            [
                'code' => '093',
                'document' => '0312-2025',
                'date_start' => '2025-03-07',
                'date_end' => '2027-03-07',
            ],
            // Comité 095 - CLEMENTINA ACUÑA DE PERALTA Nº 2 | Resolución 2 (posterior)
            [
                'code' => '095',
                'document' => '0053-2024',
                'date_start' => null,
                'date_end' => null,
            ],
            // Comité 095 - CLEMENTINA ACUÑA DE PERALTA Nº 2 | Resolución 3 (posterior)
            [
                'code' => '095',
                'document' => '0315-2026',
                'date_start' => '2026-03-30',
                'date_end' => '2028-03-30',
            ],
            // Comité 105 - INDOAMERICA | Resolución 2 (posterior)
            [
                'code' => '105',
                'document' => '0514-2023',
                'date_start' => null,
                'date_end' => null,
            ],
            // Comité 105 - INDOAMERICA | Resolución 3 (posterior)
            [
                'code' => '105',
                'document' => '0688-2025',
                'date_start' => '2025-05-30',
                'date_end' => '2027-05-30',
            ],
            // Comité 110 - CESAR ACUÑA PERALTA | Resolución 2 (posterior)
            [
                'code' => '110',
                'document' => '1014-2023',
                'date_start' => null,
                'date_end' => null,
            ],
            // Comité 110 - CESAR ACUÑA PERALTA | Resolución 3 (posterior)
            [
                'code' => '110',
                'document' => '0906-2025',
                'date_start' => '2025-09-05',
                'date_end' => '2027-09-05',
            ],
            // Comité 111 - MADRE JOSEFINA POTEL | Resolución 2 (posterior)
            [
                'code' => '111',
                'document' => '1062-2022',
                'date_start' => null,
                'date_end' => null,
            ],
            // Comité 111 - MADRE JOSEFINA POTEL | Resolución 3 (posterior)
            [
                'code' => '111',
                'document' => '1081-2024',
                'date_start' => '2024-11-05',
                'date_end' => '2026-11-05',
            ],
            // Comité 120 - GLORIA VALDERRAMA GARCIA | Resolución 2 (posterior)
            [
                'code' => '120',
                'document' => '1332-2022',
                'date_start' => null,
                'date_end' => null,
            ],
            // Comité 120 - GLORIA VALDERRAMA GARCIA | Resolución 3 (posterior)
            [
                'code' => '120',
                'document' => '0997-2024',
                'date_start' => '2024-10-10',
                'date_end' => '2026-10-10',
            ],
            // Comité 125 - CORAZON DE JESUS Nº 08 | Resolución 2 (posterior)
            [
                'code' => '125',
                'document' => '1233-2022',
                'date_start' => null,
                'date_end' => null,
            ],
            // Comité 125 - CORAZON DE JESUS Nº 08 | Resolución 3 (posterior)
            [
                'code' => '125',
                'document' => '1340-2024',
                'date_start' => '2024-12-31',
                'date_end' => '2026-12-31',
            ],
            // Comité 130 - CLEMENTINA PERALTA DE ACUÑA | Resolución 2 (posterior)
            [
                'code' => '130',
                'document' => '0040-2024',
                'date_start' => null,
                'date_end' => null,
            ],
            // Comité 130 - CLEMENTINA PERALTA DE ACUÑA | Resolución 3 (posterior)
            [
                'code' => '130',
                'document' => '0287-2026',
                'date_start' => '2026-03-18',
                'date_end' => '2028-03-18',
            ],
            // Comité 140 - JESUS MI SALVADOR | Resolución 2 (posterior)
            [
                'code' => '140',
                'document' => '0510-2024',
                'date_start' => null,
                'date_end' => null,
            ],
            // Comité 140 - JESUS MI SALVADOR | Resolución 3 (posterior)
            [
                'code' => '140',
                'document' => '0686-2026',
                'date_start' => '2026-08-03',
                'date_end' => '2028-08-03',
            ],
            // Comité 145 - LOS NIÑOS DE BELEN | Resolución 2 (posterior)
            [
                'code' => '145',
                'document' => '0025-2024',
                'date_start' => null,
                'date_end' => null,
            ],
            // Comité 145 - LOS NIÑOS DE BELEN | Resolución 3 (posterior)
            [
                'code' => '145',
                'document' => '0205-2026',
                'date_start' => '2026-03-13',
                'date_end' => '2028-03-13',
            ],
            // Comité 150 - ESTRELLA DE BELEN | Resolución 2 (posterior)
            [
                'code' => '150',
                'document' => '0472-2023',
                'date_start' => null,
                'date_end' => null,
            ],
            // Comité 150 - ESTRELLA DE BELEN | Resolución 3 (posterior)
            [
                'code' => '150',
                'document' => '0626-2025',
                'date_start' => '2025-05-22',
                'date_end' => '2027-05-22',
            ],
            // Comité 155 - SANTA CATALINA | Resolución 2 (posterior)
            [
                'code' => '155',
                'document' => '0044-2024',
                'date_start' => null,
                'date_end' => null,
            ],
            // Comité 155 - SANTA CATALINA | Resolución 3 (posterior)
            [
                'code' => '155',
                'document' => '0268-2026',
                'date_start' => '2026-03-13',
                'date_end' => '2028-03-13',
            ],
            // Comité 160 - NUESTRA SEÑORA DE FATIMA (FRATERNIDAD) | Resolución 2 (posterior)
            [
                'code' => '160',
                'document' => '0319-2024',
                'date_start' => null,
                'date_end' => null,
            ],
            // Comité 160 - NUESTRA SEÑORA DE FATIMA (FRATERNIDAD) | Resolución 3 (posterior)
            [
                'code' => '160',
                'document' => '0514-2026',
                'date_start' => '2026-06-09',
                'date_end' => '2028-06-09',
            ],
            // Comité 170 - NUESTRA SEÑORA AUXILIO DE LOS CRISTIANOS | Resolución 2 (posterior)
            [
                'code' => '170',
                'document' => '0006-2024',
                'date_start' => null,
                'date_end' => null,
            ],
            // Comité 170 - NUESTRA SEÑORA AUXILIO DE LOS CRISTIANOS | Resolución 3 (posterior)
            [
                'code' => '170',
                'document' => '0219-2026',
                'date_start' => '2026-03-02',
                'date_end' => '2028-03-02',
            ],
            // Comité 175 - VIRGENES DEL SOL | Resolución 2 (posterior)
            [
                'code' => '175',
                'document' => '0023-2024',
                'date_start' => null,
                'date_end' => null,
            ],
            // Comité 175 - VIRGENES DEL SOL | Resolución 3 (posterior)
            [
                'code' => '175',
                'document' => '0204-2026',
                'date_start' => '2026-02-24',
                'date_end' => '2028-02-24',
            ],
            // Comité 195 - ESTRELLA DE LA ESPERANZA | Resolución 2 (posterior)
            [
                'code' => '195',
                'document' => '1618-2023',
                'date_start' => null,
                'date_end' => null,
            ],
            // Comité 195 - ESTRELLA DE LA ESPERANZA | Resolución 3 (posterior)
            [
                'code' => '195',
                'document' => '1345-2025',
                'date_start' => '2025-12-23',
                'date_end' => '2027-12-23',
            ],
            // Comité 200 - MICAELA BASTIDAS | Resolución 2 (posterior)
            [
                'code' => '200',
                'document' => '0383-2024',
                'date_start' => null,
                'date_end' => null,
            ],
            // Comité 200 - MICAELA BASTIDAS | Resolución 3 (posterior)
            [
                'code' => '200',
                'document' => '0725-2026',
                'date_start' => '2026-08-11',
                'date_end' => '2028-08-11',
            ],
            // Comité 220 - RIOS DE AGUA VIVA | Resolución 2 (posterior)
            [
                'code' => '220',
                'document' => '1170-2023',
                'date_start' => null,
                'date_end' => null,
            ],
            // Comité 220 - RIOS DE AGUA VIVA | Resolución 3 (posterior)
            [
                'code' => '220',
                'document' => '1145-2025',
                'date_start' => '2025-10-28',
                'date_end' => '2027-10-28',
            ],
            // Comité 230 - SANTISIMO SACRAMENTO | Resolución 2 (posterior)
            [
                'code' => '230',
                'document' => '1546-2023',
                'date_start' => null,
                'date_end' => null,
            ],
            // Comité 230 - SANTISIMO SACRAMENTO | Resolución 3 (posterior)
            [
                'code' => '230',
                'document' => '0082-2026',
                'date_start' => '2026-01-23',
                'date_end' => '2028-01-23',
            ],
            // Comité 240 - VIRGEN DE LA PUERTA (SAN MARTIN) | Resolución 2 (posterior)
            [
                'code' => '240',
                'document' => '0356-2024',
                'date_start' => null,
                'date_end' => null,
            ],
            // Comité 240 - VIRGEN DE LA PUERTA (SAN MARTIN) | Resolución 3 (posterior)
            [
                'code' => '240',
                'document' => '0641-2026',
                'date_start' => '2026-07-14',
                'date_end' => '2028-07-14',
            ],
            // Comité 246 - LOS GERANIOS | Resolución 2 (posterior)
            [
                'code' => '246',
                'document' => '0551-2023',
                'date_start' => null,
                'date_end' => null,
            ],
            // Comité 246 - LOS GERANIOS | Resolución 3 (posterior)
            [
                'code' => '246',
                'document' => '0605-2025',
                'date_start' => '2025-05-12',
                'date_end' => '2027-05-12',
            ],
            // Comité 250 - HIJAS DE SION | Resolución 2 (posterior)
            [
                'code' => '250',
                'document' => '1206-2022',
                'date_start' => null,
                'date_end' => null,
            ],
            // Comité 250 - HIJAS DE SION | Resolución 3 (posterior)
            [
                'code' => '250',
                'document' => '1118-2024',
                'date_start' => '2024-11-08',
                'date_end' => '2026-11-08',
            ],
            // Comité 253 - NUEVO PARAISO | Resolución 2 (posterior)
            [
                'code' => '253',
                'document' => '0151-2023',
                'date_start' => null,
                'date_end' => null,
            ],
            // Comité 253 - NUEVO PARAISO | Resolución 3 (posterior)
            [
                'code' => '253',
                'document' => '0200-2025',
                'date_start' => '2025-03-07',
                'date_end' => '2027-03-07',
            ],
            // Comité 255 - UNIDAS | Resolución 2 (posterior)
            [
                'code' => '255',
                'document' => '1312-2022',
                'date_start' => null,
                'date_end' => null,
            ],
            // Comité 255 - UNIDAS | Resolución 3 (posterior)
            [
                'code' => '255',
                'document' => '1249-2024',
                'date_start' => '2024-12-04',
                'date_end' => '2026-12-04',
            ],
            // Comité 258 - MANOS SOLIDARIAS | Resolución 2 (posterior)
            [
                'code' => '258',
                'document' => '0147-2023',
                'date_start' => null,
                'date_end' => null,
            ],
            // Comité 258 - MANOS SOLIDARIAS | Resolución 3 (posterior)
            [
                'code' => '258',
                'document' => '0172-2025',
                'date_start' => '2025-02-18',
                'date_end' => '2027-02-18',
            ],
            // Comité 265 - CLEMENTINA PERALTA DE ACUÑA | Resolución 2 (posterior)
            [
                'code' => '265',
                'document' => '0244-2023',
                'date_start' => null,
                'date_end' => null,
            ],
            // Comité 265 - CLEMENTINA PERALTA DE ACUÑA | Resolución 3 (posterior)
            [
                'code' => '265',
                'document' => '0255-2025',
                'date_start' => '2025-03-24',
                'date_end' => '2027-03-24',
            ],
            // Comité 270 - CLEMENTINA PERALTA DE ACUÑA | Resolución 2 (posterior)
            [
                'code' => '270',
                'document' => '0240-2023',
                'date_start' => null,
                'date_end' => null,
            ],
            // Comité 270 - CLEMENTINA PERALTA DE ACUÑA | Resolución 3 (posterior)
            [
                'code' => '270',
                'document' => '0334-2025',
                'date_start' => '2025-04-04',
                'date_end' => '2027-04-04',
            ],
            // Comité 271 - ROSITA DE AMOR | Resolución 2 (posterior)
            [
                'code' => '271',
                'document' => '1175-2025',
                'date_start' => '2025-11-07',
                'date_end' => '2027-11-07',
            ],
            // Comité 273 - EDITH SONRISAS DE NIÑOS | Resolución 2 (posterior)
            [
                'code' => '273',
                'document' => '0257-2025',
                'date_start' => '2025-03-05',
                'date_end' => '2027-03-05',
            ],
            // Comité 274 - MARTIN MAMAY | Resolución 2 (posterior)
            [
                'code' => '274',
                'document' => '0770-2023',
                'date_start' => null,
                'date_end' => null,
            ],
            // Comité 274 - MARTIN MAMAY | Resolución 3 (posterior)
            [
                'code' => '274',
                'document' => '0155-2025',
                'date_start' => '2025-02-12',
                'date_end' => '2027-02-12',
            ],
            // Comité 275 - BUEN SOCORRO | Resolución 2 (posterior)
            [
                'code' => '275',
                'document' => '1713-2022',
                'date_start' => null,
                'date_end' => null,
            ],
            // Comité 275 - BUEN SOCORRO | Resolución 3 (posterior)
            [
                'code' => '275',
                'document' => '0154-2025',
                'date_start' => '2025-02-12',
                'date_end' => '2027-02-12',
            ],
            // Comité 280 - VIRGEN DE LA PUERTA (SANTA VERONICA) | Resolución 2 (posterior)
            [
                'code' => '280',
                'document' => '1204-2022',
                'date_start' => null,
                'date_end' => null,
            ],
            // Comité 280 - VIRGEN DE LA PUERTA (SANTA VERONICA) | Resolución 3 (posterior)
            [
                'code' => '280',
                'document' => '1174-2024',
                'date_start' => '2024-11-22',
                'date_end' => '2026-11-22',
            ],
            // Comité 295 - NIÑO MANUELITO | Resolución 2 (posterior)
            [
                'code' => '295',
                'document' => '0173-2024',
                'date_start' => null,
                'date_end' => null,
            ],
            // Comité 295 - NIÑO MANUELITO | Resolución 3 (posterior)
            [
                'code' => '295',
                'document' => '0333-2026',
                'date_start' => '2026-04-08',
                'date_end' => '2028-04-08',
            ],
            // Comité 300 - ZOILA DE LATORRE DE HAYA (SANTA VERONICA) | Resolución 2 (posterior)
            [
                'code' => '300',
                'document' => '1432-2023',
                'date_start' => null,
                'date_end' => null,
            ],
            // Comité 300 - ZOILA DE LATORRE DE HAYA (SANTA VERONICA) | Resolución 3 (posterior)
            [
                'code' => '300',
                'document' => '1217-2025',
                'date_start' => '2025-11-17',
                'date_end' => '2027-11-17',
            ],
            // Comité 302 - MUJERES LUCHANDO POR UN FUTURO MEJOR | Resolución 2 (posterior)
            [
                'code' => '302',
                'document' => '0317-2025',
                'date_start' => '2025-03-28',
                'date_end' => '2027-03-28',
            ],
            // Comité 305 - VICTOR RAUL | Resolución 2 (posterior)
            [
                'code' => '305',
                'document' => '0015-2024',
                'date_start' => null,
                'date_end' => null,
            ],
            // Comité 305 - VICTOR RAUL | Resolución 3 (posterior)
            [
                'code' => '305',
                'document' => '0205-2026',
                'date_start' => '2026-02-24',
                'date_end' => '2028-02-24',
            ],
            // Comité 313 - DOMITILA CHINGANA | Resolución 2 (posterior)
            [
                'code' => '313',
                'document' => '0512-2024',
                'date_start' => null,
                'date_end' => null,
            ],
            // Comité 313 - DOMITILA CHINGANA | Resolución 3 (posterior)
            [
                'code' => '313',
                'document' => '0750-2026',
                'date_start' => '2026-08-19',
                'date_end' => '2028-08-19',
            ],
            // Comité 320 - JERUSALEN MADRES UNIDAS | Resolución 2 (posterior)
            [
                'code' => '320',
                'document' => '1181-2025',
                'date_start' => '2025-11-07',
                'date_end' => '2027-11-07',
            ],
            // Comité 325 - ZOILA DE LATORRE DE HAYA (JERUSALEN) | Resolución 2 (posterior)
            [
                'code' => '325',
                'document' => '0035-2024',
                'date_start' => null,
                'date_end' => null,
            ],
            // Comité 325 - ZOILA DE LATORRE DE HAYA (JERUSALEN) | Resolución 3 (posterior)
            [
                'code' => '325',
                'document' => '0295-2026',
                'date_start' => '2026-03-20',
                'date_end' => '2028-03-20',
            ],
            // Comité 340 - NTRA SRA PERPETUO SOCORRO | Resolución 2 (posterior)
            [
                'code' => '340',
                'document' => '1208-2023',
                'date_start' => null,
                'date_end' => null,
            ],
            // Comité 340 - NTRA SRA PERPETUO SOCORRO | Resolución 3 (posterior)
            [
                'code' => '340',
                'document' => '1180-2025',
                'date_start' => '2025-11-07',
                'date_end' => '2027-11-07',
            ],
            // Comité 350 - SAN JOSE | Resolución 2 (posterior)
            [
                'code' => '350',
                'document' => '0048-2024',
                'date_start' => null,
                'date_end' => null,
            ],
            // Comité 350 - SAN JOSE | Resolución 3 (posterior)
            [
                'code' => '350',
                'document' => '0207-2026',
                'date_start' => '2026-03-13',
                'date_end' => '2028-03-13',
            ],
            // Comité 360 - MANUEL AREVALO | Resolución 2 (posterior)
            [
                'code' => '360',
                'document' => '1256-2022',
                'date_start' => null,
                'date_end' => null,
            ],
            // Comité 360 - MANUEL AREVALO | Resolución 3 (posterior)
            [
                'code' => '360',
                'document' => '1162-2024',
                'date_start' => '2024-11-20',
                'date_end' => '2026-11-20',
            ],
            // Comité 365 - RAMIRO PRIALE PRIALE | Resolución 2 (posterior)
            [
                'code' => '365',
                'document' => '0793-2023',
                'date_start' => null,
                'date_end' => null,
            ],
            // Comité 365 - RAMIRO PRIALE PRIALE | Resolución 3 (posterior)
            [
                'code' => '365',
                'document' => '0625-2025',
                'date_start' => '2025-07-22',
                'date_end' => '2027-07-22',
            ],
            // Comité 370 - JEHOVA ES MI PASTOR | Resolución 2 (posterior)
            [
                'code' => '370',
                'document' => '1244-2022',
                'date_start' => null,
                'date_end' => null,
            ],
            // Comité 370 - JEHOVA ES MI PASTOR | Resolución 3 (posterior)
            [
                'code' => '370',
                'document' => '1288-2024',
                'date_start' => '2024-12-13',
                'date_end' => '2026-12-13',
            ],
            // Comité 375 - JESUS ME GUIA | Resolución 2 (posterior)
            [
                'code' => '375',
                'document' => '0046-2024',
                'date_start' => null,
                'date_end' => null,
            ],
            // Comité 375 - JESUS ME GUIA | Resolución 3 (posterior)
            [
                'code' => '375',
                'document' => '0240-2026',
                'date_start' => '2026-04-09',
                'date_end' => '2028-04-08',
            ],
            // Comité 378 - EL ANGEL | Resolución 2 (posterior)
            [
                'code' => '378',
                'document' => '0748-2026',
                'date_start' => '2026-08-17',
                'date_end' => '2028-08-17',
            ],
            // Comité 380 - TANIA SOLEDAD BACA ROMERO | Resolución 2 (posterior)
            [
                'code' => '380',
                'document' => '0041-2024',
                'date_start' => null,
                'date_end' => null,
            ],
            // Comité 380 - TANIA SOLEDAD BACA ROMERO | Resolución 3 (posterior)
            [
                'code' => '380',
                'document' => '0266-2026',
                'date_start' => '2026-03-13',
                'date_end' => '2028-03-13',
            ],
            // Comité 385 - TANIA SOLEDAD BACA ROMERO II | Resolución 2 (posterior)
            [
                'code' => '385',
                'document' => '1245-2022',
                'date_start' => null,
                'date_end' => null,
            ],
            // Comité 385 - TANIA SOLEDAD BACA ROMERO II | Resolución 3 (posterior)
            [
                'code' => '385',
                'document' => '1243-2024',
                'date_start' => '2024-12-02',
                'date_end' => '2026-12-02',
            ],
        ];

        // Reemplaza el historial completo: aquí no debe quedar ninguna resolución 1.
        DB::table('resolution_associations')->delete();

        foreach ($links as $link) {
            $associationId = DB::table('associations')->where('code', $link['code'])->value('id');
            $resolutionQuery = DB::table('resolutions')->where('document', $link['document']);

            if ($link['date_start'] !== null) {
                $resolutionQuery
                    ->whereDate('date_start', $link['date_start'])
                    ->whereDate('date_end', $link['date_end']);
            }

            $resolutionIds = $resolutionQuery->pluck('id');
            if (!$associationId || $resolutionIds->count() !== 1) {
                throw new RuntimeException(
                    "No se pudo vincular el comité {$link['code']} con la resolución {$link['document']}."
                );
            }

            DB::table('resolution_associations')->updateOrInsert(
                [
                    'resolution_id' => $resolutionIds->first(),
                    'association_id' => $associationId,
                ],
                [
                    'created_at' => now(),
                    'updated_at' => now(),
                ]
            );
        }
    }
}
