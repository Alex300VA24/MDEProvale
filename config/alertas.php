<?php

return [

    // Avisos de vencimiento: la detección es determinista (fechas y reglas de
    // la BD). La IA solo redacta/prioriza el texto; si falla o está apagada,
    // el aviso se emite igual con el texto base.
    'ai_enabled' => (bool) env('ALERTAS_AI_ENABLED', true),

    // Beneficiarios que superan el límite de edad de su tipo de beneficio.
    'edad' => [
        'dias_anticipacion' => (int) env('ALERTAS_EDAD_DIAS', 60),
        // Días posteriores al cumpleaños límite en que aún se avisa si el
        // registro sigue vigente (no se dio de baja ni se reclasificó).
        'dias_gracia' => (int) env('ALERTAS_EDAD_GRACIA', 30),
    ],

    'beneficios' => [
        'umbrales_dias' => array_map('intval', array_filter(
            explode(',', (string) env('ALERTAS_BENEFICIOS_UMBRALES', '60,30,7'))
        )),
    ],

    // Resoluciones de comités por vencer. Un aviso por cada umbral cruzado.
    'resoluciones' => [
        'umbrales_dias' => array_map('intval', array_filter(
            explode(',', (string) env('ALERTAS_RESOLUCION_UMBRALES', '60,30,7'))
        )),
        'dias_gracia' => (int) env('ALERTAS_RESOLUCION_GRACIA', 30),
    ],

    // Máximo de ítems detallados por aviso resumen (el resto va como conteo).
    'max_items_por_aviso' => 25,
];
