<?php

use App\Http\Controllers\Api\ComitesController;
use App\Http\Controllers\Api\EvaluacionNutricionalController;
use App\Http\Controllers\Api\InicioController;
use App\Http\Controllers\Api\SupervisionComitesController;
use App\Http\Controllers\Api\KnowledgeBaseController;
use App\Http\Controllers\Api\ResponsablesRacionesController;
use App\Http\Controllers\Api\MovimientosController;
use App\Http\Controllers\ControlInternoController;
use App\Http\Controllers\Api\ProductosPecosasController;
use App\Http\Controllers\Api\SistemaController;
use App\Http\Controllers\Api\SociosBeneficiariosController;
use App\Http\Controllers\Api\PvlReportsController;
use App\Http\Controllers\Api\MonthlyRosterController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Dashboard API Routes
|--------------------------------------------------------------------------
|
| Endpoints JSON consumidos por el dashboard SPA. Todas las rutas viven bajo
| /api/dashboard/{modulo}, protegidas por auth:sanctum (grupo api.php) y por
| el middleware de módulo correspondiente.
|
*/

// ==================== INICIO (panel de KPIs) ====================
// Sin middleware de módulo: la sección "Inicio" es visible para cualquier
// usuario autenticado (ver NAV_ITEMS en Dashboard.jsx, modules: []).
Route::get('dashboard/inicio/panel', [InicioController::class, 'panel'])->name('api.inicio.panel');

// ==================== REPORTES (informe trimestral) ====================
Route::prefix('dashboard/reportes-pvl')->middleware(['admin', 'module:reportes'])->name('api.reportes-pvl.')->group(function () {
    Route::get('trimestral', [PvlReportsController::class, 'quarterly'])->name('quarterly');
    Route::post('trimestral/analisis', [PvlReportsController::class, 'analysis'])->name('analysis');
    Route::post('trimestral/documento', [PvlReportsController::class, 'document'])->name('document');
    Route::post('trimestral/exportar/{format}', [PvlReportsController::class, 'export'])->where('format', 'pdf|docx')->name('export');
});

// ==================== MÓDULO: BASE DE CONOCIMIENTO IA (RAG) ====================
Route::prefix('dashboard/base-conocimiento')->middleware(['admin', 'module:base-conocimiento'])->name('api.base-conocimiento.')->group(function () {
    Route::get('/', [KnowledgeBaseController::class, 'index'])->name('index');
    Route::post('documents', [KnowledgeBaseController::class, 'store'])->name('documents.store');
    Route::post('normativa/importar', [KnowledgeBaseController::class, 'importNormativa'])->name('normativa.store');
    Route::post('normativa/importar-documento', [KnowledgeBaseController::class, 'importNormativaDocument'])->name('normativa-document.store');
    Route::get('normativa/{normativaDocument}/ver', [KnowledgeBaseController::class, 'previewNormativaDocument'])->name('normativa.preview');
    Route::get('normativa/{normativaDocument}/descargar', [KnowledgeBaseController::class, 'downloadNormativaDocument'])->name('normativa.download');
    Route::get('documents/{knowledgeBaseDocument}/download', [KnowledgeBaseController::class, 'download'])->name('documents.download');
    Route::delete('documents/{knowledgeBaseDocument}', [KnowledgeBaseController::class, 'destroy'])->name('documents.destroy');
    Route::post('preguntar', [KnowledgeBaseController::class, 'ask'])->name('ask');
    Route::get('historial', [KnowledgeBaseController::class, 'history'])->name('history');
});

// ==================== MÓDULO: SOCIOS Y BENEFICIARIOS ====================
Route::prefix('dashboard/socios-beneficiarios')->middleware('module:socios-beneficiarios')->name('api.socios-beneficiarios.')->group(function () {
    Route::middleware('module:padrones')->group(function () {
        Route::get('monthly-rosters', [MonthlyRosterController::class, 'index'])->name('monthly-rosters.index');
        Route::get('monthly-rosters/{monthlyRoster}', [MonthlyRosterController::class, 'show'])->name('monthly-rosters.show');
        Route::post('monthly-rosters', [MonthlyRosterController::class, 'store'])->middleware('module:padrones,create')->name('monthly-rosters.store');
        Route::post('monthly-rosters/{monthlyRoster}/sync', [MonthlyRosterController::class, 'sync'])->middleware('module:padrones,edit')->name('monthly-rosters.sync');
        Route::post('monthly-rosters/{monthlyRoster}/close', [MonthlyRosterController::class, 'close'])->middleware('module:padrones,edit')->name('monthly-rosters.close');
        Route::post('monthly-rosters/{monthlyRoster}/revise', [MonthlyRosterController::class, 'revise'])->middleware('module:padrones,edit')->name('monthly-rosters.revise');
        Route::delete('monthly-rosters/{monthlyRoster}', [MonthlyRosterController::class, 'destroy'])->middleware('module:padrones,delete')->name('monthly-rosters.destroy');
    });

    // Las opciones de comité son necesarias tanto en el registro como al emitir padrones.
    Route::get('partners/options', [SociosBeneficiariosController::class, 'partnersOptions'])
        ->middleware('module:registro-socios-beneficiarios|padrones')
        ->name('partners.options');

    Route::middleware('module:registro-socios-beneficiarios')->group(function () {
        // Socios
        Route::get('partners', [SociosBeneficiariosController::class, 'partners'])->name('partners');
        Route::post('partners', [SociosBeneficiariosController::class, 'storePartner'])->name('partners.store');
        Route::put('partners/{partner}', [SociosBeneficiariosController::class, 'updatePartner'])->name('partners.update');
        Route::delete('partners/{partner}', [SociosBeneficiariosController::class, 'destroyPartner'])->name('partners.destroy');

        // Personas
        Route::get('personas', [SociosBeneficiariosController::class, 'personas'])->name('personas');
        Route::get('personas/options', [SociosBeneficiariosController::class, 'personasOptions'])->name('personas.options');
        Route::post('personas/reniec', [SociosBeneficiariosController::class, 'consultarReniec'])
            ->middleware('throttle:10,1')
            ->name('personas.reniec');
        Route::get('personas/{person}/reniec-photo', [SociosBeneficiariosController::class, 'fotoReniec'])
            ->middleware('throttle:10,1')
            ->name('personas.reniec-photo');
        Route::get('personas/{person}', [SociosBeneficiariosController::class, 'showPersona'])->name('personas.show');
        Route::post('personas', [SociosBeneficiariosController::class, 'storePersona'])->name('personas.store');
        Route::put('personas/{person}', [SociosBeneficiariosController::class, 'updatePersona'])->name('personas.update');
        Route::delete('personas/{person}', [SociosBeneficiariosController::class, 'destroyPersona'])->name('personas.destroy');

        // Beneficiarios
        Route::get('beneficiarios', [SociosBeneficiariosController::class, 'beneficiarios'])->name('beneficiarios');
        Route::get('beneficiarios/options', [SociosBeneficiariosController::class, 'beneficiariosOptions'])->name('beneficiarios.options');
        Route::get('beneficiarios/{beneficiarie}', [SociosBeneficiariosController::class, 'showBeneficiario'])->name('beneficiarios.show');
        Route::post('beneficiarios', [SociosBeneficiariosController::class, 'storeBeneficiario'])->name('beneficiarios.store');
        Route::put('beneficiarios/{beneficiarie}', [SociosBeneficiariosController::class, 'updateBeneficiario'])->name('beneficiarios.update');
        Route::delete('beneficiarios/{beneficiarie}', [SociosBeneficiariosController::class, 'destroyBeneficiario'])->name('beneficiarios.destroy');

        // Documentos adjuntos
        Route::post('documents', [SociosBeneficiariosController::class, 'uploadDocument'])->name('documents.upload');
        Route::get('documents/{documentAttachment}', [SociosBeneficiariosController::class, 'downloadDocument'])->name('documents.show');
        Route::delete('documents/{documentAttachment}', [SociosBeneficiariosController::class, 'destroyDocument'])->name('documents.destroy');
    });
});

// ==================== MÓDULO: COMITÉS Y RESOLUCIONES ====================
// Grupo "comites-resoluciones": cada submódulo tiene su propio permiso.
// Club de Madres = Comité (slug club-madres); Reconocimiento = Resolución (slug reconocimientos).
Route::prefix('dashboard/club-madres')->name('api.club-madres.')->group(function () {
    // Comités (slug de submódulo: club-madres)
    Route::middleware('module:club-madres')->group(function () {
        Route::get('clubs', [ComitesController::class, 'clubs'])->name('clubs');
        Route::get('clubs/options', [ComitesController::class, 'clubsOptions'])->name('clubs.options');
        Route::get('clubs/{association}', [ComitesController::class, 'club'])->name('clubs.show');
        Route::post('clubs', [ComitesController::class, 'storeClub'])->name('clubs.store');
        Route::put('clubs/{association}', [ComitesController::class, 'updateClub'])->name('clubs.update');
        Route::post('clubs/{association}/status-change', [ComitesController::class, 'changeClubStatus'])->middleware('module:club-madres,edit')->name('clubs.status-change');
        Route::post('clubs/{association}/restore-automatic-status', [ComitesController::class, 'restoreAutomaticClubStatus'])->middleware('module:club-madres,edit')->name('clubs.restore-automatic-status');
        Route::delete('clubs/{association}', [ComitesController::class, 'destroyClub'])->name('clubs.destroy');
        Route::post('clubs/{association}/asignar-cargo', [ComitesController::class, 'asignarCargo'])->name('clubs.asignar-cargo');
        Route::post('clubs/{association}/asignar-cargos', [ComitesController::class, 'asignarCargos'])->name('clubs.asignar-cargos');
    });

    // Resoluciones / Reconocimientos (slug de submódulo: reconocimientos)
    Route::middleware('module:reconocimientos')->group(function () {
        Route::get('reconocimientos', [ComitesController::class, 'reconocimientos'])->name('reconocimientos');
        Route::get('reconocimientos/options', [ComitesController::class, 'reconocimientosOptions'])->name('reconocimientos.options');
        Route::post('reconocimientos', [ComitesController::class, 'storeReconocimiento'])->name('reconocimientos.store');
        Route::put('reconocimientos/{resolution}', [ComitesController::class, 'updateReconocimiento'])->name('reconocimientos.update');
        Route::delete('reconocimientos/{resolution}', [ComitesController::class, 'destroyReconocimiento'])->name('reconocimientos.destroy');
        Route::post('reconocimientos/{resolution}/regularizar', [ComitesController::class, 'regularizeReconocimiento'])->name('reconocimientos.regularize');

        // Portal municipal (proxy de resoluciones externas)
        Route::get('reconocimientos/verificar-externa', [ComitesController::class, 'buscarResolucionExternaPorNumero'])->name('reconocimientos.externa.verificar');
        Route::get('reconocimientos/{resolution}/buscar-externa', [ComitesController::class, 'buscarResolucionExterna'])->name('reconocimientos.externa.buscar');
        Route::get('reconocimientos/{resolution}/preview-externa', [ComitesController::class, 'previewResolucionExterna'])->name('reconocimientos.externa.preview');
        Route::get('reconocimientos/{resolution}/descargar-externa', [ComitesController::class, 'descargarResolucionExterna'])->name('reconocimientos.externa.descargar');
    });
});

// ==================== SUPERVISIÓN DE COMITÉS (slug de módulo: supervision-comites) ====================
Route::prefix('dashboard/supervision-comites')->middleware('module:supervision-comites')->name('api.supervision-comites.')->group(function () {
    Route::get('/', [SupervisionComitesController::class, 'index'])->name('index');
    Route::put('dia-sin-supervision', [SupervisionComitesController::class, 'setOffDay'])->middleware('module:supervision-comites,edit')->name('dia-sin-supervision');
    Route::post('visits/{supervisionVisit}/attachments', [SupervisionComitesController::class, 'uploadAttachment'])->middleware('module:supervision-comites,edit')->name('attachments.store');
    Route::get('attachments/{documentAttachment}', [SupervisionComitesController::class, 'downloadAttachment'])->name('attachments.show');
    Route::delete('attachments/{documentAttachment}', [SupervisionComitesController::class, 'destroyAttachment'])->middleware('module:supervision-comites,edit')->name('attachments.destroy');
    Route::put('{association}/visita', [SupervisionComitesController::class, 'setVisit'])->middleware('module:supervision-comites,edit')->name('visita');
});

// ==================== EVALUACIÓN NUTRICIONAL (slug de módulo: evaluacion-nutricional) ====================
Route::prefix('dashboard/evaluacion-nutricional')->middleware('module:evaluacion-nutricional')->name('api.evaluacion-nutricional.')->group(function () {
    Route::get('options', [EvaluacionNutricionalController::class, 'options'])->name('options');
    Route::get('{association}/beneficiarios', [EvaluacionNutricionalController::class, 'beneficiarios'])->name('beneficiarios');
});

// ==================== MÓDULO: PRODUCTOS Y PROGRAMACIÓN / PECOSAS Y REPARTICIÓN ====================
Route::prefix('dashboard/productos-pecosas')->name('api.productos-pecosas.')->group(function () {
    // Productos (slug de submódulo: productos)
    Route::middleware('module:productos')->group(function () {
        Route::get('products', [ProductosPecosasController::class, 'products'])->name('products');
        Route::get('products/options', [ProductosPecosasController::class, 'productsOptions'])->name('products.options');
        Route::get('products/detail-products', [ProductosPecosasController::class, 'detailProducts'])->name('products.detail-products');
        Route::post('products', [ProductosPecosasController::class, 'storeProduct'])->name('products.store');
        Route::put('products/{product}', [ProductosPecosasController::class, 'updateProduct'])->name('products.update');
        Route::delete('products/{product}', [ProductosPecosasController::class, 'destroyProduct'])->name('products.destroy');
    });

    // Programación de Entrega (slug de submódulo: programacion-entrega)
    Route::middleware('module:programacion-entrega')->group(function () {
        Route::get('programacion-entrega', [ProductosPecosasController::class, 'programacionEntrega'])->name('programacion-entrega');
        Route::put('programacion-entrega', [ProductosPecosasController::class, 'updateProgramacionEntrega'])
            ->middleware('module:programacion-entrega,edit')
            ->name('programacion-entrega.update');
    });

    // Pecosas (slug de submódulo: pecosas)
    Route::middleware('module:pecosas')->group(function () {
        Route::get('pecosas', [ProductosPecosasController::class, 'pecosas'])->name('pecosas');
        Route::get('pecosas/options', [ProductosPecosasController::class, 'pecosasOptions'])->name('pecosas.options');
        Route::get('pecosas/next-number', [ProductosPecosasController::class, 'nextPecosaNumber'])->name('pecosas.next-number');
        Route::post('pecosas', [ProductosPecosasController::class, 'storePecosa'])->name('pecosas.store');
        Route::put('pecosas/{pecosa}', [ProductosPecosasController::class, 'updatePecosa'])->name('pecosas.update');
        Route::delete('pecosas/{pecosa}', [ProductosPecosasController::class, 'destroyPecosa'])->name('pecosas.destroy');
    });
});

// ==================== REGISTRO DE INGRESO Y REPARTICIÓN ====================
// El antiguo módulo plano "movimientos" se dividió en dos submódulos con permisos propios.
Route::prefix('dashboard/movimientos')->name('api.movimientos.')->group(function () {
    // Registro de Ingreso (slug de submódulo: registro-ingreso)
    Route::middleware('module:registro-ingreso')->group(function () {
        Route::get('transactions', [MovimientosController::class, 'index'])->name('transactions');
        Route::get('transactions/options', [MovimientosController::class, 'options'])->name('transactions.options');
        Route::post('transactions', [MovimientosController::class, 'store'])->name('transactions.store');
        Route::put('transactions/{transaction}', [MovimientosController::class, 'update'])->name('transactions.update');
        Route::delete('transactions/{transaction}', [MovimientosController::class, 'destroy'])->name('transactions.destroy');
        Route::get('tarjeta-control', [ControlInternoController::class, 'tarjetaDatos'])->name('tarjeta-control');
    });

    // Repartición mensual (slug de submódulo: reparticion): reporte de raciones por comité + enlace al PDF
    Route::middleware('module:reparticion')->group(function () {
        Route::get('reparticion', [MovimientosController::class, 'reparticion'])->name('reparticion');
        Route::put('reparticion', [MovimientosController::class, 'saveReparticion'])
            ->middleware('module:reparticion,edit')
            ->name('reparticion.update');
    });
});

// ==================== MÓDULO: RESPONSABLES Y RACIONES ====================
Route::prefix('dashboard/responsables-raciones')->middleware('module:responsables-raciones')->name('api.responsables-raciones.')->group(function () {
    Route::get('responsibles', [ResponsablesRacionesController::class, 'responsibles'])->name('responsibles');
    Route::get('responsibles/history', [ResponsablesRacionesController::class, 'responsiblesHistory'])->name('responsibles.history');
    Route::get('responsibles/detail/{responsible}', [ResponsablesRacionesController::class, 'showResponsible'])->name('responsibles.show');
    Route::post('responsibles/{responsible}/end', [ResponsablesRacionesController::class, 'endResponsiblePeriod'])
        ->where('responsible', '[0-9]+')
        ->name('responsibles.end');
    Route::put('responsibles/{type}', [ResponsablesRacionesController::class, 'updateResponsible'])
        ->where('type', 'chief|storekeeper')
        ->name('responsibles.update');

    Route::get('raciones', [ResponsablesRacionesController::class, 'raciones'])->name('raciones');
    Route::post('raciones', [ResponsablesRacionesController::class, 'storeRacion'])->name('raciones.store');
    Route::put('raciones/{racion}', [ResponsablesRacionesController::class, 'updateRacion'])->name('raciones.update');
    Route::delete('raciones/{racion}', [ResponsablesRacionesController::class, 'destroyRacion'])->name('raciones.destroy');
});

// ==================== MÓDULO: SISTEMA ====================
Route::prefix('dashboard/sistema')->name('api.sistema.')->group(function () {
    // Campanita de notificaciones: disponible para cualquier usuario autenticado,
    // sin exigir acceso al módulo 'sistema' (mismo comportamiento que las vistas
    // Blade originales, que exponían el contador/lista propia a todos).
    Route::get('notifications', [SistemaController::class, 'notifications'])->name('notifications');
    Route::get('notifications/unread-count', [SistemaController::class, 'unreadNotificationsCount'])->name('notifications.unread-count');
    Route::post('notifications/mark-seen', [SistemaController::class, 'markNotificationsSeen'])->name('notifications.mark-seen');
    Route::post('notifications/{notification}/solve', [SistemaController::class, 'solveNotification'])->name('notifications.solve');
    // Sincronización en segundo plano con el portal, disparada tras el login.
    Route::post('knowledge-sync', [SistemaController::class, 'knowledgeSync'])->name('knowledge-sync');
    Route::get('current-modules', [SistemaController::class, 'currentModules'])->name('current-modules');

    Route::middleware('module:sistema')->group(function () {
        Route::get('usuarios', [SistemaController::class, 'usuarios'])->name('usuarios');
        Route::post('usuarios', [SistemaController::class, 'storeUsuario'])->name('usuarios.store');
        Route::put('usuarios/{usuario}', [SistemaController::class, 'updateUsuario'])->name('usuarios.update');
        Route::delete('usuarios/{usuario}', [SistemaController::class, 'destroyUsuario'])->name('usuarios.destroy');
        Route::post('usuarios/{usuario}/reset-password', [SistemaController::class, 'resetUserPassword'])->name('usuarios.reset-password');

        Route::get('roles', [SistemaController::class, 'roles'])->name('roles');
        Route::post('roles', [SistemaController::class, 'storeRol'])->name('roles.store');
        Route::put('roles/{rol}', [SistemaController::class, 'updateRol'])->name('roles.update');
        Route::delete('roles/{rol}', [SistemaController::class, 'destroyRol'])->name('roles.destroy');

        Route::get('modulos', [SistemaController::class, 'modulos'])->name('modulos');
        Route::get('modulos-options', [SistemaController::class, 'modulosOptions'])->name('modulos-options');
        Route::get('module-icons', [SistemaController::class, 'moduleIcons'])->name('module-icons');
        Route::post('modulos', [SistemaController::class, 'storeModulo'])->name('modulos.store');
        Route::put('modulos/{modulo}', [SistemaController::class, 'updateModulo'])->name('modulos.update');
        Route::delete('modulos/{modulo}', [SistemaController::class, 'destroyModulo'])->name('modulos.destroy');

        Route::post('notifications/{notification}/approve', [SistemaController::class, 'approveNotification'])->name('notifications.approve');
        Route::post('notifications/{notification}/reject', [SistemaController::class, 'rejectNotification'])->name('notifications.reject');

        // Parámetros del límite de consultas del asistente IA.
        Route::get('asistente-config', [SistemaController::class, 'asistenteConfig'])->name('asistente-config');
        Route::put('asistente-config', [SistemaController::class, 'updateAsistenteConfig'])->name('asistente-config.update');
        Route::get('upload-config', [SistemaController::class, 'uploadConfig'])->name('upload-config');
        Route::put('upload-config', [SistemaController::class, 'updateUploadConfig'])
            ->middleware('module:sistema,edit')
            ->name('upload-config.update');
        Route::get('audit-events', [SistemaController::class, 'auditEvents'])->name('audit-events');

        // Historial de Cambios (trazabilidad por usuario).
        Route::get('change-history', [SistemaController::class, 'changeHistory'])->name('change-history');
        Route::get('change-history/options', [SistemaController::class, 'changeHistoryOptions'])->name('change-history.options');
        Route::get('change-history/{auditLog}', [SistemaController::class, 'changeHistoryShow'])->whereNumber('auditLog')->name('change-history.show');
    });
});
