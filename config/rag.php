<?php

return [
    // Peso semántico del score híbrido (resto = peso léxico).
    'semantic_weight' => (float) env('RAG_SEMANTIC_WEIGHT', 0.85),

    // Dimensión esperada del embedding. 0 = no validar.
    // gemini-embedding-001 devuelve 3072 por defecto (o 768 con outputDimensionality).
    'embedding_dim' => (int) env('RAG_EMBEDDING_DIM', 0),

    // Modelo de embedding usado al indexar (para detectar cambios y reindexar).
    'embedding_model' => env('GOOGLE_EMBEDDING_MODEL', 'gemini-embedding-001'),

    // Candidatos máximos cargados de MySQL antes del score en PHP.
    'candidate_limit' => (int) env('RAG_CANDIDATE_LIMIT', 500),

    // Límite de candidatos cuando no hay embeddings (solo léxico).
    'lexical_candidate_limit' => (int) env('RAG_LEXICAL_CANDIDATE_LIMIT', 200),

    // Tope de chunks por documento (antes se truncaba silenciosamente).
    'max_chunks_kb' => (int) env('RAG_MAX_CHUNKS_KB', 200),
    'max_chunks_pvl' => (int) env('RAG_MAX_CHUNKS_PVL', 120),
    'max_chunks_normativa' => (int) env('RAG_MAX_CHUNKS_NORMATIVA', 60),
];
