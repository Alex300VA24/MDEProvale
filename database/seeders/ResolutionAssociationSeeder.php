<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use RuntimeException;

class ResolutionAssociationSeeder extends Seeder
{
    /**
     * Resoluciones posteriores generadas desde resoluciones_comites.xlsx.
     * La resolución 1 se asigna en AssociationSeeder como resolution_id.
     */
    public function run(): void
    {
        $links = [
            // Comité 005 - SANTA RITA DE CASIA | Resolución 2
            ['code' => '005', 'document' => '1098-2023', 'date_start' => '2023-08-28', 'date_end' => '2025-08-28'],
            // Comité 005 - SANTA RITA DE CASIA | Resolución 3
            ['code' => '005', 'document' => '1148-2025', 'date_start' => '2025-10-28', 'date_end' => '2027-10-28'],
            // Comité 025 - NUEVA ESPERANZA | Resolución 2
            ['code' => '025', 'document' => '0536-2024', 'date_start' => '2024-06-28', 'date_end' => '2026-06-28'],
            // Comité 025 - NUEVA ESPERANZA | Resolución 3
            ['code' => '025', 'document' => '0713-2026', 'date_start' => '2026-08-05', 'date_end' => '2028-08-05'],
            // Comité 030 - AMIGOS DE JESUS | Resolución 2
            ['code' => '030', 'document' => '1209-2023', 'date_start' => '2023-09-25', 'date_end' => '2025-09-25'],
            // Comité 030 - AMIGOS DE JESUS | Resolución 3
            ['code' => '030', 'document' => '1233-2025', 'date_start' => '2025-11-20', 'date_end' => '2027-11-20'],
            // Comité 032 - LOS ANGELES DE JESUS | Resolución 2
            ['code' => '032', 'document' => '1215-2022', 'date_start' => '2022-10-12', 'date_end' => '2024-10-12'],
            // Comité 032 - LOS ANGELES DE JESUS | Resolución 3
            ['code' => '032', 'document' => '1240-2024', 'date_start' => '2024-12-02', 'date_end' => '2026-12-02'],
            // Comité 034 - MUJERES LUCHADORAS | Resolución 2
            ['code' => '034', 'document' => '1157-2024', 'date_start' => '2024-11-13', 'date_end' => '2026-11-13'],
            // Comité 036 - CORAZON DE JESUS | Resolución 2
            ['code' => '036', 'document' => '0541-2025', 'date_start' => '2025-05-27', 'date_end' => '2027-05-27'],
            // Comité 037 - WILMER SANCHEZ | Resolución 2
            ['code' => '037', 'document' => '0220-2025', 'date_start' => '2025-03-03', 'date_end' => '2027-03-03'],
            // Comité 040 - LAS PALMERAS III | Resolución 2
            ['code' => '040', 'document' => '0907-2025', 'date_start' => '2025-08-20', 'date_end' => '2027-08-20'],
            // Comité 040 - LAS PALMERAS III | Resolución 3
            ['code' => '040', 'document' => '0479-2026', 'date_start' => '2026-05-28', 'date_end' => '2028-05-28'],
            // Comité 045 - MADRES TRABAJANDO POR AMPLIACION | Resolución 2
            ['code' => '045', 'document' => '0155-2023', 'date_start' => '2023-01-27', 'date_end' => '2025-01-27'],
            // Comité 045 - MADRES TRABAJANDO POR AMPLIACION | Resolución 3
            ['code' => '045', 'document' => '0215-2025', 'date_start' => '2025-02-28', 'date_end' => '2027-02-28'],
            // Comité 050 - MADRES TRABAJANDO POR EL GRAN CAMBIO | Resolución 2
            ['code' => '050', 'document' => '1169-2023', 'date_start' => '2023-09-13', 'date_end' => '2025-09-13'],
            // Comité 050 - MADRES TRABAJANDO POR EL GRAN CAMBIO | Resolución 3
            ['code' => '050', 'document' => '1083-2025', 'date_start' => '2025-10-17', 'date_end' => '2027-10-17'],
            // Comité 058 - JUANA MALAVER DE GARRIDO | Resolución 2
            ['code' => '058', 'document' => '0148-2022', 'date_start' => '2022-02-21', 'date_end' => '2024-02-21'],
            // Comité 058 - JUANA MALAVER DE GARRIDO | Resolución 3
            ['code' => '058', 'document' => '0505-2024', 'date_start' => '2024-06-21', 'date_end' => '2026-06-21'],
            // Comité 060 - SILOE | Resolución 2
            ['code' => '060', 'document' => '0164-2024', 'date_start' => '2024-03-22', 'date_end' => '2026-03-22'],
            // Comité 060 - SILOE | Resolución 3
            ['code' => '060', 'document' => '0378-2026', 'date_start' => '2026-04-23', 'date_end' => '2028-04-23'],
            // Comité 068 - LA FUERZA DEL PUEBLO | Resolución 2
            ['code' => '068', 'document' => '1084-2025', 'date_start' => '2025-10-17', 'date_end' => '2027-10-17'],
            // Comité 070 - SAN PEDRO | Resolución 2
            ['code' => '070', 'document' => '1712-2022', 'date_start' => '2022-12-23', 'date_end' => '2024-12-23'],
            // Comité 070 - SAN PEDRO | Resolución 3
            ['code' => '070', 'document' => '0156-2025', 'date_start' => '2025-02-13', 'date_end' => '2027-02-13'],
            // Comité 075 - LAS DALIAS | Resolución 2
            ['code' => '075', 'document' => '0024-2024', 'date_start' => '2024-01-15', 'date_end' => '2026-01-15'],
            // Comité 075 - LAS DALIAS | Resolución 3
            ['code' => '075', 'document' => '0203-2026', 'date_start' => '2026-02-24', 'date_end' => '2028-02-24'],
            // Comité 078 - NUEVO EDEN | Resolución 2
            ['code' => '078', 'document' => '0601-2024', 'date_start' => '2024-07-10', 'date_end' => '2026-07-10'],
            // Comité 078 - NUEVO EDEN | Resolución 3
            ['code' => '078', 'document' => '0748-2026', 'date_start' => '2026-08-17', 'date_end' => '2028-08-17'],
            // Comité 080 - AMIGAS UNIDAS | Resolución 2
            ['code' => '080', 'document' => '1380-2023', 'date_start' => '2023-10-11', 'date_end' => '2025-10-11'],
            // Comité 080 - AMIGAS UNIDAS | Resolución 3
            ['code' => '080', 'document' => '1290-2025', 'date_start' => '2025-12-04', 'date_end' => '2027-12-04'],
            // Comité 082 - BODAS DEL CORDERO | Resolución 2
            ['code' => '082', 'document' => '0044-2026', 'date_start' => '2026-01-16', 'date_end' => '2028-01-16'],
            // Comité 090 - VIRGEN DE GUADALUPE | Resolución 2
            ['code' => '090', 'document' => '1333-2022', 'date_start' => '2022-11-23', 'date_end' => '2024-11-23'],
            // Comité 090 - VIRGEN DE GUADALUPE | Resolución 3
            ['code' => '090', 'document' => '1343-2024', 'date_start' => '2024-12-31', 'date_end' => '2026-12-31'],
            // Comité 093 - HILO ROJO | Resolución 2
            ['code' => '093', 'document' => '0312-2024', 'date_start' => '2024-05-17', 'date_end' => '2026-05-17'],
            // Comité 093 - HILO ROJO | Resolución 2
            ['code' => '093', 'document' => '0259-2025', 'date_start' => '2025-03-17', 'date_end' => '2027-03-17'],
            // Comité 095 - CLEMENTINA ACUÑA DE PERALTA Nº 2 | Resolución 2
            ['code' => '095', 'document' => '0039-2024', 'date_start' => '2024-01-22', 'date_end' => '2026-01-22'],
            // Comité 095 - CLEMENTINA ACUÑA DE PERALTA Nº 2 | Resolución 3
            ['code' => '095', 'document' => '0315-2026', 'date_start' => '2026-03-30', 'date_end' => '2028-03-30'],
            // Comité 105 - INDOAMERICA | Resolución 2
            ['code' => '105', 'document' => '0514-2023', 'date_start' => '2023-04-21', 'date_end' => '2025-04-21'],
            // Comité 105 - INDOAMERICA | Resolución 3
            ['code' => '105', 'document' => '0559-2025', 'date_start' => '2025-05-30', 'date_end' => '2027-05-30'],
            // Comité 110 - CESAR ACUÑA PERALTA | Resolución 2
            ['code' => '110', 'document' => '1014-2023', 'date_start' => '2023-08-11', 'date_end' => '2025-08-11'],
            // Comité 110 - CESAR ACUÑA PERALTA | Resolución 3
            ['code' => '110', 'document' => '0956-2025', 'date_start' => '2025-09-05', 'date_end' => '2027-09-05'],
            // Comité 111 - MADRE JOSEFINA POTEL | Resolución 2
            ['code' => '111', 'document' => '1062-2022', 'date_start' => '2022-09-14', 'date_end' => '2024-09-14'],
            // Comité 111 - MADRE JOSEFINA POTEL | Resolución 3
            ['code' => '111', 'document' => '1081-2024', 'date_start' => '2024-11-06', 'date_end' => '2026-11-06'],
            // Comité 120 - GLORIA VALDERRAMA GARCIA | Resolución 2
            ['code' => '120', 'document' => '1332-2022', 'date_start' => '2022-11-23', 'date_end' => '2024-11-23'],
            // Comité 120 - GLORIA VALDERRAMA GARCIA | Resolución 3
            ['code' => '120', 'document' => '0997-2024', 'date_start' => '2024-10-10', 'date_end' => '2026-10-10'],
            // Comité 125 - CORAZON DE JESUS Nº 08 | Resolución 2
            ['code' => '125', 'document' => '1233-2022', 'date_start' => '2022-10-17', 'date_end' => '2024-10-17'],
            // Comité 125 - CORAZON DE JESUS Nº 08 | Resolución 3
            ['code' => '125', 'document' => '1340-2024', 'date_start' => '2024-12-31', 'date_end' => '2026-12-31'],
            // Comité 130 - CLEMENTINA PERALTA DE ACUÑA | Resolución 2
            ['code' => '130', 'document' => '0040-2024', 'date_start' => '2024-01-22', 'date_end' => '2026-01-22'],
            // Comité 130 - CLEMENTINA PERALTA DE ACUÑA | Resolución 3
            ['code' => '130', 'document' => '0287-2026', 'date_start' => '2026-03-18', 'date_end' => '2028-03-18'],
            // Comité 140 - JESUS MI SALVADOR | Resolución 2
            ['code' => '140', 'document' => '0510-2024', 'date_start' => '2024-06-24', 'date_end' => '2026-06-24'],
            // Comité 140 - JESUS MI SALVADOR | Resolución 3
            ['code' => '140', 'document' => '0698-2026', 'date_start' => '2026-08-03', 'date_end' => '2028-08-03'],
            // Comité 145 - LOS NIÑOS DE BELEN | Resolución 2
            ['code' => '145', 'document' => '0025-2024', 'date_start' => '2024-01-16', 'date_end' => '2026-01-16'],
            // Comité 145 - LOS NIÑOS DE BELEN | Resolución 3
            ['code' => '145', 'document' => '0266-2026', 'date_start' => '2026-03-13', 'date_end' => '2028-03-13'],
            // Comité 150 - FRATERNIDAD | Resolución 2
            ['code' => '150', 'document' => '0472-2023', 'date_start' => '2023-04-12', 'date_end' => '2025-04-12'],
            // Comité 150 - FRATERNIDAD | Resolución 3
            ['code' => '150', 'document' => '0525-2025', 'date_start' => '2025-05-22', 'date_end' => '2027-05-22'],
            // Comité 155 - SANTA CATALINA | Resolución 2
            ['code' => '155', 'document' => '0044-2024', 'date_start' => '2024-01-23', 'date_end' => '2026-01-23'],
            // Comité 155 - SANTA CATALINA | Resolución 3
            ['code' => '155', 'document' => '0268-2026', 'date_start' => '2026-03-13', 'date_end' => '2028-03-13'],
            // Comité 160 - NUESTRA SEÑORA DE FATIMA (FRATERNIDAD) | Resolución 2
            ['code' => '160', 'document' => '0319-2024', 'date_start' => '2024-05-20', 'date_end' => '2026-05-20'],
            // Comité 160 - NUESTRA SEÑORA DE FATIMA (FRATERNIDAD) | Resolución 3
            ['code' => '160', 'document' => '0514-2026', 'date_start' => '2026-06-09', 'date_end' => '2028-06-09'],
            // Comité 170 - NUESTRA SEÑORA AUXILIO DE LOS CRISTIANOS | Resolución 2
            ['code' => '170', 'document' => '0008-2024', 'date_start' => '2024-01-09', 'date_end' => '2026-01-09'],
            // Comité 170 - NUESTRA SEÑORA AUXILIO DE LOS CRISTIANOS | Resolución 3
            ['code' => '170', 'document' => '0218-2026', 'date_start' => '2026-03-02', 'date_end' => '2028-03-02'],
            // Comité 175 - VIRGENES DEL SOL | Resolución 2
            ['code' => '175', 'document' => '0023-2024', 'date_start' => '2024-01-15', 'date_end' => '2026-01-15'],
            // Comité 175 - VIRGENES DEL SOL | Resolución 3
            ['code' => '175', 'document' => '0204-2026', 'date_start' => '2026-02-24', 'date_end' => '2028-02-24'],
            // Comité 195 - ESTRELLA DE LA ESPERANZA | Resolución 2
            ['code' => '195', 'document' => '1616-2023', 'date_start' => '2023-11-27', 'date_end' => '2025-11-27'],
            // Comité 195 - ESTRELLA DE LA ESPERANZA | Resolución 3
            ['code' => '195', 'document' => '1345-2025', 'date_start' => '2025-12-23', 'date_end' => '2027-12-23'],
            // Comité 200 - MICAELA BASTIDAS | Resolución 2
            ['code' => '200', 'document' => '0363-2024', 'date_start' => '2024-05-30', 'date_end' => '2026-05-30'],
            // Comité 200 - MICAELA BASTIDAS | Resolución 3
            ['code' => '200', 'document' => '0729-2026', 'date_start' => '2026-08-11', 'date_end' => '2028-08-11'],
            // Comité 220 - RIOS DE AGUA VIVA | Resolución 2
            ['code' => '220', 'document' => '1170-2023', 'date_start' => '2023-09-13', 'date_end' => '2025-09-13'],
            // Comité 220 - RIOS DE AGUA VIVA | Resolución 3
            ['code' => '220', 'document' => '1149-2025', 'date_start' => '2025-10-24', 'date_end' => '2027-10-24'],
            // Comité 230 - SANTISIMO SACRAMENTO | Resolución 2
            ['code' => '230', 'document' => '1546-2023', 'date_start' => '2023-11-13', 'date_end' => '2025-11-13'],
            // Comité 230 - SANTISIMO SACRAMENTO | Resolución 3
            ['code' => '230', 'document' => '0082-2026', 'date_start' => '2026-01-23', 'date_end' => '2028-01-23'],
            // Comité 240 - VIRGEN DE LA PUERTA (SAN MARTIN) | Resolución 2
            ['code' => '240', 'document' => '0356-2024', 'date_start' => '2026-07-14', 'date_end' => '2028-07-14'],
            // Comité 240 - VIRGEN DE LA PUERTA (SAN MARTIN) | Resolución 3
            ['code' => '240', 'document' => '0641-2026', 'date_start' => '2026-07-14', 'date_end' => '2028-07-14'],
            // Comité 246 - LOS GERANIOS | Resolución 2
            ['code' => '246', 'document' => '0551-2023', 'date_start' => '2025-05-12', 'date_end' => '2027-05-12'],
            // Comité 246 - LOS GERANIOS | Resolución 3
            ['code' => '246', 'document' => '0605-2025', 'date_start' => '2025-05-12', 'date_end' => '2027-05-12'],
            // Comité 250 - HIJAS DE SION | Resolución 2
            ['code' => '250', 'document' => '1206-2022', 'date_start' => '2024-11-08', 'date_end' => '2026-11-08'],
            // Comité 250 - HIJAS DE SION | Resolución 3
            ['code' => '250', 'document' => '1118-2024', 'date_start' => '2024-11-08', 'date_end' => '2026-11-08'],
            // Comité 253 - NUEVO PARAISO | Resolución 2
            ['code' => '253', 'document' => '0151-2023', 'date_start' => '2025-03-07', 'date_end' => '2027-03-07'],
            // Comité 253 - NUEVO PARAISO | Resolución 3
            ['code' => '253', 'document' => '0200-2025', 'date_start' => '2025-03-07', 'date_end' => '2027-03-07'],
            // Comité 255 - UNIDAS | Resolución 2
            ['code' => '255', 'document' => '1312-2022', 'date_start' => '2024-12-04', 'date_end' => '2026-12-04'],
            // Comité 255 - UNIDAS | Resolución 3
            ['code' => '255', 'document' => '1249-2024', 'date_start' => '2024-12-04', 'date_end' => '2026-12-04'],
            // Comité 258 - MANOS SOLIDARIAS | Resolución 2
            ['code' => '258', 'document' => '0147-2023', 'date_start' => '2025-02-18', 'date_end' => '2027-02-18'],
            // Comité 258 - MANOS SOLIDARIAS | Resolución 3
            ['code' => '258', 'document' => '0172-2025', 'date_start' => '2025-02-18', 'date_end' => '2027-02-18'],
            // Comité 265 - CLEMENTINA PERALTA DE ACUÑA | Resolución 2
            ['code' => '265', 'document' => '0244-2023', 'date_start' => '2025-03-24', 'date_end' => '2027-03-24'],
            // Comité 265 - CLEMENTINA PERALTA DE ACUÑA | Resolución 3
            ['code' => '265', 'document' => '0255-2025', 'date_start' => '2025-03-24', 'date_end' => '2027-03-24'],
            // Comité 270 - CLEMENTINA PERALTA DE ACUÑA | Resolución 2
            ['code' => '270', 'document' => '0240-2023', 'date_start' => '2025-04-04', 'date_end' => '2027-04-04'],
            // Comité 270 - CLEMENTINA PERALTA DE ACUÑA | Resolución 3
            ['code' => '270', 'document' => '0334-2025', 'date_start' => '2025-04-04', 'date_end' => '2027-04-04'],
            // Comité 271 - ROSITA DE AMOR | Resolución 2
            ['code' => '271', 'document' => '1175-2025', 'date_start' => '2025-11-07', 'date_end' => '2027-11-07'],
            // Comité 273 - EDITH SONRISAS DE NIÑOS | Resolución 2
            ['code' => '273', 'document' => '0257-2025', 'date_start' => '2025-03-05', 'date_end' => '2027-03-05'],
            // Comité 274 - MARTIN MAMAY | Resolución 2
            ['code' => '274', 'document' => '0770-2023', 'date_start' => '2025-02-12', 'date_end' => '2027-02-12'],
            // Comité 274 - MARTIN MAMAY | Resolución 3
            ['code' => '274', 'document' => '0155-2025', 'date_start' => '2025-02-12', 'date_end' => '2027-02-12'],
            // Comité 275 - BUEN SOCORRO | Resolución 2
            ['code' => '275', 'document' => '1713-2022', 'date_start' => '2025-02-12', 'date_end' => '2027-02-12'],
            // Comité 275 - BUEN SOCORRO | Resolución 3
            ['code' => '275', 'document' => '0154-2025', 'date_start' => '2025-02-12', 'date_end' => '2027-02-12'],
            // Comité 280 - VIRGEN DE LA PUERTA (SANTA VERONICA) | Resolución 2
            ['code' => '280', 'document' => '1204-2022', 'date_start' => '2024-11-22', 'date_end' => '2026-11-22'],
            // Comité 280 - VIRGEN DE LA PUERTA (SANTA VERONICA) | Resolución 3
            ['code' => '280', 'document' => '1174-2024', 'date_start' => '2024-11-22', 'date_end' => '2026-11-22'],
            // Comité 295 - NIÑO MANUELITO | Resolución 2
            ['code' => '295', 'document' => '0173-2024', 'date_start' => '2026-04-08', 'date_end' => '2028-04-08'],
            // Comité 295 - NIÑO MANUELITO | Resolución 3
            ['code' => '295', 'document' => '0333-2026', 'date_start' => '2026-04-08', 'date_end' => '2028-04-08'],
            // Comité 300 - ZOILA DE LATORRE DE HAYA (SANTA VERONICA) | Resolución 2
            ['code' => '300', 'document' => '1432-2023', 'date_start' => '2025-11-17', 'date_end' => '2027-11-17'],
            // Comité 300 - ZOILA DE LATORRE DE HAYA (SANTA VERONICA) | Resolución 3
            ['code' => '300', 'document' => '1217-2025', 'date_start' => '2025-11-17', 'date_end' => '2027-11-17'],
            // Comité 302 - MUJERES LUCHANDO POR UN FUTURO MEJOR | Resolución 2
            ['code' => '302', 'document' => '0317-2025', 'date_start' => '2025-03-28', 'date_end' => '2027-03-28'],
            // Comité 305 - VICTOR RAUL | Resolución 2
            ['code' => '305', 'document' => '0015-2024', 'date_start' => '2026-02-24', 'date_end' => '2028-02-24'],
            // Comité 305 - VICTOR RAUL | Resolución 3
            ['code' => '305', 'document' => '0205-2026', 'date_start' => '2026-02-24', 'date_end' => '2028-02-24'],
            // Comité 313 - DOMITILA CHINGANA | Resolución 2
            ['code' => '313', 'document' => '0512-2024', 'date_start' => '2026-08-19', 'date_end' => '2028-08-19'],
            // Comité 313 - DOMITILA CHINGANA | Resolución 3
            ['code' => '313', 'document' => '0750-2026', 'date_start' => '2026-08-19', 'date_end' => '2028-08-19'],
            // Comité 320 - JERUSALEN MADRES UNIDAS | Resolución 2
            ['code' => '320', 'document' => '1181-2025', 'date_start' => '2025-11-07', 'date_end' => '2027-11-07'],
            // Comité 325 - ZOILA DE LATORRE DE HAYA (JERUSALEN) | Resolución 2
            ['code' => '325', 'document' => '0035-2024', 'date_start' => '2026-03-20', 'date_end' => '2028-03-20'],
            // Comité 325 - ZOILA DE LATORRE DE HAYA (JERUSALEN) | Resolución 3
            ['code' => '325', 'document' => '0295-2026', 'date_start' => '2026-03-20', 'date_end' => '2028-03-20'],
            // Comité 340 - NTRA SRA PERPETUO SOCORRO | Resolución 2
            ['code' => '340', 'document' => '1208-2023', 'date_start' => '2025-11-07', 'date_end' => '2027-11-07'],
            // Comité 340 - NTRA SRA PERPETUO SOCORRO | Resolución 3
            ['code' => '340', 'document' => '1180-2025', 'date_start' => '2025-11-07', 'date_end' => '2027-11-07'],
            // Comité 350 - SAN JOSE | Resolución 2
            ['code' => '350', 'document' => '0048-2024', 'date_start' => '2026-03-13', 'date_end' => '2028-03-13'],
            // Comité 350 - SAN JOSE | Resolución 3
            ['code' => '350', 'document' => '0207-2026', 'date_start' => '2026-03-13', 'date_end' => '2028-03-13'],
            // Comité 360 - MANUEL AREVALO | Resolución 2
            ['code' => '360', 'document' => '1256-2022', 'date_start' => '2024-11-20', 'date_end' => '2026-11-20'],
            // Comité 360 - MANUEL AREVALO | Resolución 3
            ['code' => '360', 'document' => '1162-2024', 'date_start' => '2024-11-20', 'date_end' => '2026-11-20'],
            // Comité 365 - RAMIRO PRIALE PRIALE | Resolución 2
            ['code' => '365', 'document' => '0793-2023', 'date_start' => '2025-07-22', 'date_end' => '2027-07-22'],
            // Comité 365 - RAMIRO PRIALE PRIALE | Resolución 3
            ['code' => '365', 'document' => '0625-2025', 'date_start' => '2025-07-22', 'date_end' => '2027-07-22'],
            // Comité 370 - JEHOVA ES MI PASTOR | Resolución 2
            ['code' => '370', 'document' => '1244-2022', 'date_start' => '2024-12-13', 'date_end' => '2026-12-13'],
            // Comité 370 - JEHOVA ES MI PASTOR | Resolución 3
            ['code' => '370', 'document' => '1288-2024', 'date_start' => '2024-12-13', 'date_end' => '2026-12-13'],
            // Comité 375 - JESUS ME GUIA | Resolución 2
            ['code' => '375', 'document' => '0046-2024', 'date_start' => '2026-04-09', 'date_end' => '2028-04-08'],
            // Comité 375 - JESUS ME GUIA | Resolución 3
            ['code' => '375', 'document' => '0240-2026', 'date_start' => '2026-04-09', 'date_end' => '2028-04-08'],
            // Comité 378 - EL ANGEL | Resolución 2
            ['code' => '378', 'document' => '0748-2026', 'date_start' => '2026-08-17', 'date_end' => '2028-08-17'],
            // Comité 380 - TANIA SOLEDAD BACA ROMERO | Resolución 2
            ['code' => '380', 'document' => '0041-2024', 'date_start' => '2026-03-13', 'date_end' => '2028-03-13'],
            // Comité 380 - TANIA SOLEDAD BACA ROMERO | Resolución 3
            ['code' => '380', 'document' => '0266-2026', 'date_start' => '2026-03-13', 'date_end' => '2028-03-13'],
            // Comité 385 - TANIA SOLEDAD BACA ROMERO II | Resolución 2
            ['code' => '385', 'document' => '1245-2022', 'date_start' => '2024-12-02', 'date_end' => '2026-12-02'],
            // Comité 385 - TANIA SOLEDAD BACA ROMERO II | Resolución 3
            ['code' => '385', 'document' => '1243-2024', 'date_start' => '2024-12-02', 'date_end' => '2026-12-02'],
        ];

        DB::table('resolution_associations')->delete();

        foreach ($links as $link) {
            $associationId = DB::table('associations')->where('code', $link['code'])->value('id');
            $resolutionId = DB::table('resolutions')
                ->where('document', $link['document'])
                ->whereDate('date_start', $link['date_start'])
                ->whereDate('date_end', $link['date_end'])
                ->value('id');

            if (!$associationId || !$resolutionId) {
                throw new RuntimeException(
                    "No se pudo vincular el comité {$link['code']} con la resolución {$link['document']}.",
                );
            }

            DB::table('resolution_associations')->updateOrInsert(
                [
                    'resolution_id' => $resolutionId,
                    'association_id' => $associationId,
                ],
                [
                    'created_at' => now(),
                    'updated_at' => now(),
                ],
            );
        }
    }
}
