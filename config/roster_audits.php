<?php

$homonymousClubs = [
    'Los códigos 265 y 270 comparten el nombre CLEMENTINA PERALTA DE ACUÑA, pero se distinguen por sus direcciones: Mz.I Lt.38 y Mz.K Lt.36 del II Sector de Nuevo Jerusalén.',
    'Los códigos 059 y 280 comparten el nombre VIRGEN DE LA PUERTA, pero corresponden a direcciones distintas: Mz.25 Lt.5A III Sector de Nuevo Jerusalén y Girardot N° 1543, Santa Verónica.',
    'Los códigos 300 y 325 comparten el nombre ZOILA DE LA TORRE DE HAYA, pero corresponden a Calle Esmeralda N° 350, Santa Verónica y Calle San Pedro N° 199-A, Jerusalén.',
    'El conteo identifica cada comité por código y dirección; el nombre compartido no los fusiona.',
];

$pendingResolution = 'El club 256 (NIÑOS DEL TRIUNFO) no existía al migrar este padrón y se registró inicialmente con una resolución pendiente de regularización.';

return [
    '2026-03' => [
        'source' => 'BASE DE DATOS PROVALE - MARZO III.xlsx',
        'metrics' => [
            'socios' => [
                'excel' => 2598,
                'migrated' => 2598,
                'corrected' => 2596,
                'observations' => [
                    'Dos socias aparecían en más de un club: VÁSQUEZ GARCÍA EINMA (DNI 72416206, clubes 350 y 360) y SILVA SILVA VALERIA (DNI 74969054, clubes 036 y 068).',
                    'El padrón conserva las 2,598 relaciones socia–club, pero el total corregido es 2,596 personas únicas.',
                    'Se corrigió el DNI de GUTIÉRREZ CULQUICHICÓN YENIFER ANALÍ a 75397239.',
                ],
            ],
            'beneficiarios' => [
                'excel' => 3872,
                'migrated' => 3872,
                'corrected' => 3867,
                'observations' => [
                    'Se detectaron cinco DNI repetidos en el Excel: 91938806, 92526981, 93775401, 93824304 y 94210807.',
                    'La BD conserva las 3,872 filas del padrón, incluidos los duplicados; al consolidar por DNI corresponden 3,867 personas únicas.',
                ],
            ],
            'clubes' => [
                'excel' => 77,
                'migrated' => 77,
                'corrected' => 77,
                'observations' => array_merge($homonymousClubs, [$pendingResolution]),
            ],
        ],
        'dual_role' => ['LAC' => 492, 'GES' => 41, 'DIS' => 10, 'total' => 543],
    ],
    '2026-04' => [
        'source' => 'BASE DE DATOS PROVALE - ABRIL.xlsx',
        'metrics' => [
            'socios' => [
                'excel' => 2597,
                'migrated' => 2597,
                'corrected' => 2595,
                'observations' => [
                    'Dos socias aparecían en más de un club: VÁSQUEZ GARCÍA EINMA (DNI 72416206, clubes 350 y 360) y SILVA SILVA VALERIA (DNI 74969054, clubes 036 y 068).',
                    'El padrón conserva las 2,597 relaciones socia–club, pero el total corregido es 2,595 personas únicas.',
                ],
            ],
            'beneficiarios' => [
                'excel' => 3872,
                'migrated' => 3872,
                'corrected' => 3870,
                'observations' => [
                    'Se detectaron dos DNI repetidos en el Excel: 92526981 y 93824304.',
                    'La BD conserva las 3,872 filas del padrón, incluidos los duplicados; al consolidar por DNI corresponden 3,870 personas únicas.',
                ],
            ],
            'clubes' => [
                'excel' => 77,
                'migrated' => 77,
                'corrected' => 77,
                'observations' => array_merge($homonymousClubs, [$pendingResolution]),
            ],
        ],
        'dual_role' => ['LAC' => 492, 'GES' => 41, 'DIS' => 10, 'total' => 543],
    ],
    '2026-05' => [
        'source' => 'BASE DE DATOS PROVALE - MAYO II.xlsx',
        'metrics' => [
            'socios' => [
                'excel' => 2623,
                'migrated' => 2617,
                'corrected' => 2615,
                'observations' => [
                    'Diez filas del Excel no tenían DNI y fueron ignoradas durante la migración; seis afectaban relaciones de socias.',
                    'MOYA RAMOS JANET YOVANA (DNI 43153448) y ROJAS MOYA JHARUMI NICOLL (DNI 78119578) aparecían en los clubes 256 y 258.',
                    'La BD conserva 2,617 relaciones válidas; el total corregido es 2,615 personas únicas.',
                ],
            ],
            'beneficiarios' => [
                'excel' => 3872,
                'migrated' => 3862,
                'corrected' => 3861,
                'observations' => [
                    'Diez filas sin DNI fueron ignoradas durante la migración.',
                    'ROJAS MOYA IAN ASIEL (DNI 92317941) estaba repetido en los clubes 256 y 258.',
                    'Un registro no tenía edad ni tipo de beneficio y no pudo clasificarse automáticamente.',
                ],
            ],
            'clubes' => [
                'excel' => 77,
                'migrated' => 77,
                'corrected' => 77,
                'observations' => array_merge($homonymousClubs, [$pendingResolution]),
            ],
        ],
        'dual_role' => ['LAC' => 459, 'GES' => 27, 'DIS' => 11, 'total' => 497],
    ],
    '2026-06' => [
        'source' => 'BASE DE DATOS PROVALE - JUNIO II.xlsx',
        'metrics' => [
            'socios' => [
                'excel' => 2636,
                'migrated' => 2635,
                'corrected' => 2635,
                'observations' => [
                    'Una fila sin DNI fue ignorada durante la migración.',
                    'Luego de excluir esa fila, no quedaron socias duplicadas entre clubes.',
                ],
            ],
            'beneficiarios' => [
                'excel' => 3872,
                'migrated' => 3871,
                'corrected' => 3871,
                'observations' => [
                    'Una fila sin DNI fue ignorada durante la migración.',
                    'No se encontraron beneficiarios duplicados después de validar el DNI.',
                ],
            ],
            'clubes' => [
                'excel' => 77,
                'migrated' => 77,
                'corrected' => 77,
                'observations' => $homonymousClubs,
            ],
        ],
        'dual_role' => ['LAC' => 448, 'GES' => 21, 'DIS' => 11, 'total' => 480],
    ],
    '2026-07' => [
        'source' => 'BASE DE DATOS PROVALE - JULIO.xlsx',
        'metrics' => [
            'socios' => [
                'excel' => 2681,
                'migrated' => 2681,
                'corrected' => 2680,
                'observations' => [
                    'GONZALES CABANILLAS JUAN VICTORIA CLOTILDE (DNI 47488826) aparecía en los clubes 375 y 380.',
                    'Se conservaron ambas relaciones históricas; el total corregido cuenta a la persona una sola vez.',
                ],
            ],
            'beneficiarios' => [
                'excel' => 3872,
                'migrated' => 3872,
                'corrected' => 3870,
                'observations' => [
                    'Dos beneficiarios aparecían repetidos entre clubes: DNI 91879690 (375 y 380) y DNI 92396005 (265 y 270).',
                    'El archivo de agosto contiene 3,822 filas: son 50 menos que el patrón de 3,872 de otros meses. La BD conserva 3,822 y no completa registros con datos de julio.',
                    'Al depurar los dos DNI repetidos, corresponden 3,820 personas únicas.',
                ],
            ],
            'clubes' => [
                'excel' => 77,
                'migrated' => 77,
                'corrected' => 77,
                'observations' => $homonymousClubs,
            ],
        ],
        'dual_role' => ['LAC' => 396, 'GES' => 19, 'DIS' => 13, 'total' => 428],
    ],
    '2026-08' => [
        'source' => 'BASE DE DATOS PROVALE - AGOSTO.xlsx',
        'metrics' => [
            'socios' => [
                'excel' => 2653,
                'migrated' => 2653,
                'corrected' => 2652,
                'observations' => [
                    'GONZALES CABANILLAS JUAN VICTORIA CLOTILDE (DNI 47488826) aparecía en los clubes 375 y 380.',
                    'Se conservaron ambas relaciones históricas; el total corregido cuenta a la persona una sola vez.',
                ],
            ],
            'beneficiarios' => [
                'excel' => 3822,
                'migrated' => 3822,
                'corrected' => 3820,
                'observations' => [
                    'Dos beneficiarios aparecían repetidos entre clubes: DNI 91879690 (375 y 380) y DNI 92396005 (265 y 270).',
                    'Un registro no tenía parentesco mapeable; se mantuvo la relación histórica con esa observación.',
                ],
            ],
            'clubes' => [
                'excel' => 76,
                'migrated' => 76,
                'corrected' => 76,
                'observations' => array_merge($homonymousClubs, [
                    'Este padrón contiene 76 clubes; la disminución frente a julio pertenece al archivo histórico y no se completó de forma artificial.',
                ]),
            ],
        ],
        'dual_role' => ['LAC' => 389, 'GES' => 18, 'DIS' => 13, 'total' => 420],
    ],
    '2026-09' => [
        'source' => 'BASE DE DATOS PROVALE - SETIEMBRE.xlsx',
        'metrics' => [
            'socios' => [
                'excel' => 2714,
                'migrated' => 2714,
                'corrected' => 2712,
                'observations' => [
                    'JANELA ABIGAIL VARGAS RUMAY (DNI 76446533) aparece en los clubes 075 y 078.',
                    'VANNESA JACKELINE JULCAMORO GALVEZ (DNI 47210495) aparece en los clubes 305 y 360.',
                    'Se conservaron las relaciones históricas y el total corregido cuenta a cada persona una sola vez.',
                ],
            ],
            'beneficiarios' => [
                'excel' => 3870,
                'migrated' => 3870,
                'corrected' => 3868,
                'observations' => [
                    'Se detectaron dos DNI repetidos: 94427855 (clubes 075 y 078) y 92763772 (clubes 305 y 360).',
                    'La BD conserva las 3,870 relaciones del archivo; el total depurado por DNI es 3,868.',
                ],
            ],
            'clubes' => [
                'excel' => 77,
                'migrated' => 77,
                'corrected' => 77,
                'observations' => $homonymousClubs,
            ],
        ],
        'dual_role' => ['LAC' => 376, 'GES' => 20, 'DIS' => 13, 'total' => 409],
    ],
];
