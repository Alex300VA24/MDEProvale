<?php

// Excepciones temporales a la regla de capas. Solo se pueden retirar entradas, nunca agregar.
return [
    'presentation' => [
        'app/Console/Commands/BackfillLegacyPartnerDates.php',
        'app/Console/Commands/MigrarViaServicios.php',
        'app/Console/Commands/RebuildMonthlyData.php',
        'app/Console/Commands/ReindexRagEmbeddings.php',
        'app/Console/Commands/RellenarDetailPecosas.php',
        'app/Http/Controllers/Api/KnowledgeBaseController.php',
        'app/Http/Controllers/Api/PvlReportsController.php',
        'app/Http/Controllers/Api/SistemaController.php',
        'app/Http/Controllers/Auth/PasswordResetLinkController.php',
        'app/Http/Controllers/Auth/PresidentAuthenticatedSessionController.php',
        'app/Http/Controllers/Auth/RegisteredUserController.php',
        'app/Http/Controllers/ControlInternoController.php',
        'app/Http/Controllers/DocumentVerificationController.php',
        'app/Http/Controllers/PresidentPortalController.php',
        'app/Http/Controllers/ReportGeneratorController.php',
        'app/Http/Controllers/SistemaController.php',
        'app/Http/Middleware/HandleInertiaRequests.php',
    ],
    'business' => [
        'app/Services/Alertas/VencimientoAlertService.php',
        'app/Services/AssistantDataService.php',
        'app/Services/KnowledgeBase/KnowledgeBaseRagService.php',
        'app/Services/Normativa/NormativaRagService.php',
        'app/Services/Normativa/NormativaScraperService.php',
        'app/Services/PresidentAccountService.php',
        'app/Services/PresidentCommitteeResolver.php',
        'app/Services/Pvl/PvlRagService.php',
        'app/Services/Pvl/PvlReportContextService.php',
        'app/Services/Pvl/PvlReportGeneratorService.php',
        'app/Services/VerifiedDocumentService.php',
    ],
    'http_in_lower_layers' => [
    ],
];
