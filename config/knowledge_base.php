<?php

return [
    'chunk_size' => 1200,
    'chunk_overlap' => 200,
    'max_document_kb' => 20480,
    'rag_limit' => 8,
    'min_score' => 0.05,
    // Si una página tiene menos caracteres, el PDF completo pasa por OCR visual con Gemini.
    'ocr_min_page_characters' => (int) env('KB_OCR_MIN_PAGE_CHARACTERS', 200),
];
