# Grafo de conocimiento — MDEProvale

Mapa de conexiones principales: Modelos → Controllers → Policies → Rutas → Frontend.
Generado 2026-08-20. Actualizar tras cambios grandes de estructura.

## 1. Modelos y relaciones (`app/Models`)

| Modelo | Relaciones |
|---|---|
| Association | belongsTo PlaceSector, TypePremises, State, Resolution; hasMany Partner, Pecosa; belongsToMany Resolution (resolution_associations) |
| Beneficiarie | belongsTo People, Partner, Relationship; hasMany BeneficiaryHistory |
| BeneficiaryHistory | belongsTo Beneficiarie, State, ReasonDisqualification, TypeBenefit; hasOne ObstetricData |
| DetailPecosa | belongsTo DetailProduct, Pecosa; hasOneThrough Product |
| DetailProduct | belongsTo Product; hasMany ProductStock |
| Directive | belongsTo Resolution, Partner, Position, State |
| Module | belongsToMany Rol (module_rol) |
| Notification | belongsTo User (x3: dueño, requested_by, processed_by) |
| ObstetricData | belongsTo BeneficiaryHistory |
| PasswordResetRequest | belongsTo User |
| People | belongsTo PlaceSector; hasMany Partner, Beneficiarie |
| Partner | belongsTo People, Association, State, Position; hasMany Directive, Beneficiarie |
| Pecosa | belongsTo State, Association, Partner(president), Responsible(chief/storekeeper), Partner(managing); hasMany DetailPecosa |
| Place | hasMany PlaceSector |
| PlaceSector | belongsTo Place, Sector; hasMany Association, People |
| Position | hasMany Directive |
| Product | belongsTo State, Uom; hasMany DetailPecosa, Transaction, ProductStock, DetailProduct |
| ProductStock | belongsTo DetailProduct, Pecosa, Transaction |
| ReasonDisqualification | hasMany BeneficiaryHistory |
| Relationship | hasMany Beneficiarie |
| Resolution | belongsTo State; hasMany Directive; belongsToMany Association |
| Responsible | belongsTo People |
| Rol | hasMany User; belongsToMany Module |
| Sector | hasMany PlaceSector |
| State | hasMany User, Association, Resolution, Partner, Directive, BeneficiaryHistory, Pecosa, Product |
| Transaction | belongsTo DetailProduct, TypeTransaction; hasOneThrough Product |
| TypeBenefit | hasMany BeneficiaryHistory |
| TypePremises | hasMany Association |
| TypeTransaction | hasMany Transaction |
| Uom | hasMany Product |
| User | belongsTo Rol, State |
| Racion, ResolutionAssociation(pivot) | no sampleados |

## 2. Controllers → Modelos / Policy / Requests

| Controller | Modelos | authorize() → Policy | FormRequests |
|---|---|---|---|
| AssociationController | Association | — | — |
| BeneficiarieController | Beneficiarie, Partner, Relationship | — | — |
| PartnerController | Partner, Association, People, Relationship, TypeBenefit, ReasonDisqualification, PlaceSector, State | — | StorePartnerRequest, UpdatePartnerRequest |
| PersonaController | People, PlaceSector | — | StorePersonaRequest, UpdatePersonaRequest |
| PecosaController | Association, DetailProduct, Directive, Partner, Pecosa, Position, ProductStock, Responsible, State, DetailPecosa, Transaction | — | StorePecosaRequest, UpdatePecosaRequest |
| ProductController | DetailPecosa, DetailProduct, Product, State, Uom | — | StoreProductRequest, UpdateProductRequest |
| ProductosPecosasController (web, legacy) | Product, Pecosa, DetailPecosa, DetailProduct, ProductStock, Transaction, TypeTransaction, Association, State, Uom, Partner, Directive, Position, Responsible, People | — | — |
| TransactionController | Product, Transaction, TypeTransaction | — | StoreTransactionRequest |
| KardexController | DetailProduct, Product | — | — |
| ReparticionController | (movimientos-reparticion) | — | — |
| ReportController | Association, Pecosa, Partner, Product | — | — |
| ResolutionController | Resolution | — | — |
| ResponsablesRacionesController (web) | Responsible, People, Racion | — | — |
| ClubReconocimientosController | Association, Resolution, Directive, Partner, Position, State, PlaceSector, TypePremises | — | AsignarPresidentaRequest y otras |
| SistemaController (web) | Module, Rol, User, State, Notification | — | — |
| SociosBeneficiariosController (web) | Partner, Beneficiarie, Association, People, Relationship, State, PlaceSector | — | — |
| SearchController | People, Partner | — | — |
| DashboardController | Partner, Beneficiarie, Association, Product, Pecosa, Transaction, DetailProduct, DetailPecosa, ProductStock | — | — |
| Api\ComitesController | Association, Directive, Partner, Pecosa, PlaceSector, Position, Resolution, State, TypePremises | — | AsignarPresidentaRequest, StoreClubRequest, StoreReconocimientoRequest, UpdateClubRequest, UpdateReconocimientoRequest |
| Api\InicioController | Association, Beneficiarie, DetailPecosa, Partner | — | — |
| Api\MovimientosController | DetailProduct, Product, Transaction, TypeTransaction | — | StoreTransactionRequest |
| Api\ProductosPecosasController | Association, DetailPecosa, DetailProduct, Pecosa, Position, Product, Responsible, State, Transaction, Uom | — | StorePecosaRequest, StoreProductRequest, UpdatePecosaRequest, UpdateProductRequest |
| Api\ResponsablesRacionesController | People, Racion, Responsible | delete→RacionPolicy | StoreRacionRequest, UpdateRacionRequest, UpdateResponsibleRequest |
| Api\SistemaController | Module, Notification, Rol, State, User | delete/update usuario→UserPolicy; delete rol→RolPolicy; delete modulo→ModulePolicy | StoreModuleRequest, StoreRolRequest, StoreUserRequest, UpdateModuleRequest, UpdateRolRequest, UpdateUserRequest |
| Api\SociosBeneficiariosController | Association, Beneficiarie, Partner, People, PlaceSector, ReasonDisqualification, Relationship, State, TypeBenefit | — | StorePartnerRequest, StorePersonaRequest, UpdatePartnerRequest, UpdatePersonaRequest |
| Auth\* | User, Notification | — | LoginRequest |

Nota (revisado, no es hueco de seguridad): policies AssociationPolicy, PartnerPolicy, PecosaPolicy, PersonaPolicy, ProductPolicy, TransactionPolicy existen pero sin `authorize()` en controllers muestreados. Middleware `CheckModuleAccess` ya aplica gate equivalente por verbo HTTP (view/create/edit/delete → `hasModuleAccess`/`canCreateModule`/`canEditModule`/`canDeleteModule`), misma lógica que replican las policies sin usar — código muerto, no falla de control de acceso. Ninguna de las dos capas hace chequeo a nivel de objeto (ownership); `state_id` en estos modelos es campo de estado del registro (activo/inactivo), no filtro multi-tenant, así que no hay fuga de datos entre sucursales.

## 3. Policies (`app/Policies`)

| Policy | Modelo | Abilities |
|---|---|---|
| AssociationPolicy | Association | viewAny, create, update, delete |
| ModulePolicy | Module | viewAny, create, update, delete |
| PartnerPolicy | Partner | viewAny, view, create, update, delete |
| PecosaPolicy | Pecosa | viewAny, create, update, delete |
| PersonaPolicy | People | create, update |
| ProductPolicy | Product | viewAny, create, update, delete |
| RacionPolicy | Racion | viewAny, create, update, delete |
| ResolutionPolicy | Resolution | viewAny, create, update, delete |
| RolPolicy | Rol | viewAny, create, update, delete |
| TransactionPolicy | Transaction | viewAny, create, update, delete |
| UserPolicy | User | viewAny, view, create, update, delete |

## 4. Rutas

`routes/web.php` → grupo `auth`, incluye sub-archivos. Middleware `module:<slug>` = `CheckModuleAccess` (Kernel.php:68).

| Archivo | Prefix | Controller | Middleware |
|---|---|---|---|
| search.php | /api/search/* | SearchController | (dentro grupo auth) |
| socios-beneficiarios.php | socios-beneficiarios | PartnerController, PersonaController, BeneficiarieController | module:socios-beneficiarios |
| club-reconocimientos.php | club-reconocimientos | ClubReconocimientosController | module:club-madres |
| productos-pecosas.php | productos-pecosas | ProductController, PecosaController, ReportController, KardexController | module:productos |
| movimientos.php | movimientos | TransactionController, ReparticionController | module:movimientos |
| responsables-raciones.php | responsables-raciones | ResponsablesRacionesController | module:responsables-raciones |
| sistema.php | sistema | SistemaController (web) | module:sistema |
| auth.php | — | Auth\* | guest / auth |

`routes/api.php` → `auth:sanctum` → `dashboard-api.php` (SPA JSON):

| Prefix | Controller | Middleware |
|---|---|---|
| dashboard/inicio/panel | Api\InicioController | ninguno |
| dashboard/socios-beneficiarios | Api\SociosBeneficiariosController | module:socios-beneficiarios |
| dashboard/club-madres | Api\ComitesController | module:club-madres |
| dashboard/productos-pecosas | Api\ProductosPecosasController | module:productos / module:pecosas |
| dashboard/movimientos | Api\MovimientosController | module:movimientos |
| dashboard/responsables-raciones | Api\ResponsablesRacionesController | module:responsables-raciones |
| dashboard/sistema | Api\SistemaController | notificaciones públicas; module:sistema para usuarios/roles/modulos |

## 5. Frontend (resources/js/Pages, Sections)

`Pages/Dashboard.jsx` = entry point Inertia único; lazy-load Sections por slug de módulo; usa wrapper `http` (axios) de `resources/js/http.js`.

| Section | Endpoint base | Controller destino |
|---|---|---|
| Inicio.jsx | /api/dashboard/inicio | Api\InicioController |
| SociosBeneficiarios.jsx + socios/*Tab.jsx | /api/dashboard/socios-beneficiarios | Api\SociosBeneficiariosController |
| ClubReconocimientos.jsx + comites/*Tab.jsx | /api/dashboard/club-madres | Api\ComitesController |
| ProductosPecosas.jsx + productos/*Tab.jsx | /api/dashboard/productos-pecosas | Api\ProductosPecosasController |
| Movimientos.jsx + movimientos/KardexTab.jsx | /api/dashboard/movimientos | Api\MovimientosController |
| ResponsablesRaciones.jsx + responsables-raciones/*Tab.jsx | /api/dashboard/responsables-raciones | Api\ResponsablesRacionesController |
| Sistema.jsx + sistema/*Tab.jsx | /api/dashboard/sistema | Api\SistemaController |
| comites/PadronModal.jsx | /club-reconocimientos/club-padron (web, PDF) | ClubReconocimientosController::generarPadronClub |
| comites/ResolucionExternaModal.jsx | /api/dashboard/club-madres/reconocimientos/{id}/buscar-externa | Api\ComitesController::buscarResolucionExterna |
| socios/PadronModal.jsx | /socios-beneficiarios/beneficiarios-padron (web, PDF) | PartnerController::reportePadronBeneficiarios |
| socios/SociosTab.jsx (typeahead) | /api/search/people | SearchController::people |
| Ayuda.jsx | sin llamadas API | — |
