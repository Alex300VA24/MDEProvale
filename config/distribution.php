<?php

return [
    'defaults' => [
        'milk_grams_per_beneficiary' => 44.0,
        'oat_grams_per_beneficiary' => 51.5,
        'milk_can_grams' => 410.0,
        'oat_bag_grams' => 1000.0,
        'milk_cans_per_box' => 48,
        'oat_kg_per_sack' => 30,
    ],

    // Vuelta por defecto de cada club (por código) mientras no exista una asignación guardada.
    'default_routes' => [
        1 => [5, 15, 19, 22, 25, 28, 30, 32, 34],
        2 => [36, 37, 40, 45, 50, 58, 59, 60],
        3 => [68, 70, 75, 78, 80, 82, 90, 93, 95],
        4 => [105, 110, 111, 118, 120, 125, 130],
        5 => [140, 145, 150, 155, 160],
        6 => [170, 175, 195, 200, 220, 230, 235, 240, 246],
        7 => [250, 253, 255, 258, 265, 268, 270, 271],
        8 => [273, 274, 275, 278, 280, 295, 300, 302],
        9 => [305, 313, 320, 325, 340, 350, 360],
        10 => [365, 370, 375, 378, 380, 385, 388],
    ],
];
