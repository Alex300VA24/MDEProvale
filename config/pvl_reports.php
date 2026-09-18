<?php

return [
    'municipality' => [
        'name' => 'MUNICIPALIDAD DISTRITAL DE LA ESPERANZA',
        'type' => 'MUNICIPALIDAD DISTRITAL',
        'department' => 'LA LIBERTAD',
        'province' => 'TRUJILLO',
    ],

    'prompt_version' => 'pvl-report-v2',
    'composition_tolerance' => 0.5,
    'rag_limit' => 12,
    'chunk_size' => 1200,
    'chunk_overlap' => 200,
    'max_document_kb' => 10240,
    'embedding_model' => env('GOOGLE_EMBEDDING_MODEL', 'gemini-embedding-001'),
];
