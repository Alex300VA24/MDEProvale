<?php

return [
    'base_url' => 'https://www.muniesperanza.gob.pe',
    'listing_path' => '/website/mde2026/normativa.php',
    'download_path' => '/website/mde2026/norma_descargar.php',

    // tipo (query param de normativa.php) => etiqueta legible.
    'tipos' => [
        1 => 'ORDENANZA MUNICIPAL',
        2 => 'RESOLUCION DE ALCALDIA',
        3 => 'DECRETO DE ALCALDIA',
        4 => 'ACUERDO DE CONCEJO',
        12 => 'RESOLUCION GERENCIAL',
        13 => 'RESOLUCION JEFATURAL',
    ],

    // Coincidencia léxica (insensible a mayúsculas/tildes) sobre título+asunto+concepto
    // antes de gastar una llamada de IA. Debe matchear al menos una para clasificar.
    'pvl_keywords' => [
        'vaso de leche',
        'programa del vaso de leche',
        'pvl',
        'club de madres',
        'clubes de madres',
        'comite de vaso de leche',
        'comité de vaso de leche',
        'programa social alimentario',
        'programa alimentario',
        'alimentacion complementaria',
        'alimentación complementaria',
        'racion alimentaria',
        'ración alimentaria',
        'raciones alimentarias',
        'producto lacteo',
        'producto lácteo',
        'leche evaporada',
        'hojuela de avena',
        'hojuela de quinua',
        'hojuela de quinua avena',
        'quinua avena',
        'fortificada con vitaminas y minerales',
        'fortificado con vitaminas y minerales',
        'mezcla de hojuelas',
        'adquisicion de alimentos',
        'adquisición de alimentos',
        'comite de administracion',
        'comité de administración',
        'beneficiarios del vaso de leche',
        'contraloria vaso de leche',
        'contraloría vaso de leche',
    ],

    // Coincidencias solicitadas expresamente para la base PROVALE. Cuando una
    // norma contiene alguna de estas frases se importa directamente: la IA no
    // puede descartarla por considerar que la relación con el programa es
    // indirecta.
    'direct_import_keywords' => [
        'club de madres',
        'clubes de madres',
        'hojuela de quinua',
        'hojuela de quinua avena con azucar fortificada con vitaminas y minerales',
        'quinua avena',
        'fortificada con vitaminas y minerales',
        'fortificado con vitaminas y minerales',
    ],

    // Solo se clasifica y notifica lo publicado dentro de esta ventana en la primera
    // corrida (evita un aluvión de avisos retroactivos sobre el backlog histórico).
    'backfill_days' => 30,

    'chunk_size' => 1200,
    'chunk_overlap' => 200,
    'embedding_model' => env('GOOGLE_EMBEDDING_MODEL', 'gemini-embedding-001'),

    'request_timeout' => 20,
    'request_delay_ms' => 300,
    'max_document_kb' => 25600,

    'prompt_version' => 'normativa-pvl-v1',
];
