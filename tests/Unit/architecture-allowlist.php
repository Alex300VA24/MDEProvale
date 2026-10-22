<?php

// Excepciones temporales a la regla de capas. Solo se pueden retirar entradas, nunca agregar.
return [
    'presentation' => [
        'app/Console/Commands/BackfillLegacyPartnerDates.php',
        'app/Console/Commands/MigrarViaServicios.php',
        'app/Console/Commands/RebuildMonthlyData.php',
        'app/Console/Commands/ReindexRagEmbeddings.php',
        'app/Console/Commands/RellenarDetailPecosas.php',
        'app/Http/Controllers/ControlInternoController.php',
        'app/Http/Controllers/PresidentPortalController.php',
    ],
    'business' => [
        'app/Services/Alertas/VencimientoAlertService.php',
    ],
    'http_in_lower_layers' => [
    ],
];
