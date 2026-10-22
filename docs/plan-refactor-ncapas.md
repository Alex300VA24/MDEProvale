# Plan de refactorización a Arquitectura N Capas (4 capas)

Stack: Laravel 10 / PHP 8.1, Inertia + React (JSX), Sanctum, DomPDF, PhpSpreadsheet.
Rama de trabajo: `estructura`.

**Estado: completado (T0–T11).** Allowlist de arquitectura vacía; descripción final del sistema en [manual-arquitectura.md](manual-arquitectura.md).

## 1. Diagnóstico (estado actual)

Ya existe un esqueleto parcial de capas, pero solo para 5 dominios.

| Capa | Ubicación actual | Estado |
|---|---|---|
| Presentación | `app/Http/Controllers` (+`Api/`), `Requests`, `Resources`, `Middleware`, `routes/*.php`, `Console/Commands`, `resources/js` | 31 controladores, 10.023 líneas. 29 de 30 controladores consultan Eloquent/`DB::` directamente. |
| Negocio | `app/Services` (+`Contracts`, `Pvl`, `Rag`, `Normativa`, `Alertas`) | 25 de ~48 servicios acceden a modelos/`DB::` sin pasar por repositorio. Solo 4 servicios tienen interfaz. |
| Datos | `app/Repositories` | Solo 5 repositorios (Association, Partner, Pecosa, Product, Transaction) + `BaseRepository`. |
| Entidades | `app/Models` (~55), `app/DTOs` (5) | Correcto. Falta reglas de modelo mínimas (casts/enums) y DTOs de entrada/salida. |

Mayores violaciones de la regla de dependencia (conteo de accesos directos a BD en el controlador):

| Archivo | Líneas | Accesos BD |
|---|---:|---:|
| `ProductosPecosasController` (web) | 1229 | 68 |
| `Api/ComitesController` | 687 | 33 |
| `ClubReconocimientosController` | 699 | 31 |
| `Api/InicioController` | 456 | 25 |
| `SistemaController` (web) | 405 | 17 |
| `Api/SociosBeneficiariosController` | 638 | 16 |
| `Api/ProductosPecosasController` | 502 | 16 |
| `Api/SistemaController` | 487 | 13 |
| resto (21 controladores) | | 1–11 c/u |

Servicios que mezclan negocio con acceso a datos: `NormativaScraperService` (19), `PecosaService` (19), `TransactionService` (15), `PvlReportContextService` (8), `PvlRagService` (8), `PartnerService` (8), `KnowledgeBaseRagService` (7), entre otros.

Comandos Artisan con consultas directas: `RebuildMonthlyData` (35), `RellenarDetailPecosas` (11), `MigrarViaServicios` (9), `ReindexRagEmbeddings` (8), `BackfillLegacyPartnerDates` (7).

Duplicación detectada: pares web/API del mismo dominio (`ProductosPecosas`, `Sistema`, `SociosBeneficiarios`, `ResponsablesRaciones`) con lógica repetida. Al extraer a servicios, ambos controladores deben consumir el mismo servicio.

## 2. Estructura objetivo

Se respetan las convenciones de Laravel (namespace `App\`), sin renombrar directorios existentes, para no romper autoload ni tests.

```
app/
├── Http/                              # CAPA 1 · PRESENTACIÓN
│   ├── Controllers/                   #   delgados: validan vía FormRequest, llaman a 1 servicio, devuelven Inertia/JSON/PDF
│   │   └── Api/
│   ├── Requests/                      #   validación de formato de entrada
│   ├── Resources/                     #   transformación de salida (JSON)
│   └── Middleware/
├── Console/Commands/                  #   presentación CLI: solo parsean opciones y llaman a servicios
├── Policies/                          #   autorización (usada desde controladores)
│
├── Services/                          # CAPA 2 · NEGOCIO
│   ├── Contracts/                     #   interfaces por servicio
│   ├── <Dominio>/                     #   Pecosas, Productos, Socios, Comites, Reportes, Pvl, Rag, Normativa, Sistema, ...
│   └── Support/                       #   PDF, Excel, Reniec (adaptadores externos)
├── DTOs/                              #   objetos de transferencia entre capas 1↔2↔3
│
├── Repositories/                      # CAPA 3 · ACCESO A DATOS
│   ├── Contracts/                     #   interfaces (los servicios dependen de ellas)
│   ├── BaseRepository.php
│   └── <Dominio>Repository.php        #   única capa con Eloquent queries / DB::
│
├── Models/                            # CAPA 4 · ENTIDADES (transversal)
│   ├── (Eloquent: relaciones, casts, scopes simples)
│   └── Enums/                         #   estados y tipos como enums PHP 8.1
└── Providers/AppServiceProvider.php   #   bindings Interface → Implementación
```

### Reglas de dependencia (se verifican al cierre de cada tarea)

1. Controlador / Comando / Middleware → solo `Services\Contracts`, `DTOs`, `Requests`, `Resources`, `Models` como tipo de dato.
2. Servicio → solo `Repositories\Contracts`, otros servicios por contrato, `DTOs`, `Models`. Prohibido: `DB::`, `Model::query()/where()/create()`, `Request`, `response()`, `Inertia`.
3. Repositorio → solo `Models`, `DB`, Eloquent. Prohibido: reglas de negocio, `Request`, servicios.
4. Modelo → no depende de ninguna capa superior.
5. Las capas no se saltan: Presentación nunca llama a Repositorio ni ejecuta Eloquent.

Comprobación automática (se añade en la Tarea 0): script/test que hace grep de violaciones.

## 3. Estrategia

- Una tarea = un dominio vertical, entregable y revertible por separado (un commit por tarea en la rama `estructura`).
- Refactor sin cambio de comportamiento: la respuesta HTTP/JSON y las vistas Inertia deben quedar idénticas.
- Antes de tocar un dominio: correr su test existente (`tests/Feature/Api/*`). Después: mismos tests en verde.
- Extraer en este orden dentro de cada tarea: Repositorio (+interfaz) → Servicio (+interfaz) → adelgazar Controlador → registrar binding.
- Los controladores web y API de un mismo dominio comparten el servicio nuevo (se elimina la duplicación).
- Llamadas a IA, PDF, Excel, scraping y RENIEC se tratan como adaptadores en `Services` (infraestructura externa), no como repositorios.

## 4. Tareas

### Tarea 0 · Línea base y andamiaje
- Correr suite actual (`php artisan test`) y registrar resultado de referencia.
- Completar `BaseRepository` / `BaseService` y definir convención de nombres, DTOs y enums.
- Crear test de arquitectura (`tests/Unit/ArchitectureTest.php`) que falle ante: `DB::`/Eloquent estático en Controllers y Services, `Illuminate\Http\Request` en Services/Repositories. Se ejecuta con lista de excepciones (allowlist) que se va vaciando tarea a tarea.
- Agrupar bindings en `RepositoryServiceProvider` y `ServiceServiceProvider` (o secciones claras en `AppServiceProvider`).
- Archivos: `tests/Unit/ArchitectureTest.php`, `app/Providers/*`, `app/Repositories/BaseRepository.php`.

### Tarea 1 · Dominios ya iniciados: cerrar el patrón (Partner, Product, Pecosa, Transaction, Association)
- Eliminar acceso directo a modelos en `PartnerService`, `PecosaService`, `TransactionService`, `StockService`, `ProductService`, `AssociationStateService`; moverlo a sus repositorios.
- Crear interfaces faltantes de servicios (`StockService`, `AssociationStateService`, …).
- Adelgazar `PartnerController`, `PecosaController`, `ProductController`, `TransactionController`, `AssociationController`, `KardexController`, `ResolutionController`, `PersonaController`, `BeneficiarieController`.
- Nuevos repos: `Resolution`, `Persona`, `Beneficiarie`, `ProductStock`.

### Tarea 2 · Productos y PECOSAS (mayor deuda)
- `Http/Controllers/ProductosPecosasController` (1229 líneas, 68 accesos BD) y `Api/ProductosPecosasController` (502).
- Crear `ProductosPecosasService` dividido por casos de uso: listado de productos, opciones, detalle de producto, programación de entrega, PECOSAS, stock.
- Repos: `DetailProductRepository`, `DetailPecosaRepository`, `DistributionRepository` (Assignment/Period), `UomRepository`.
- Servicios afectados: `ReparticionService`, `SchedulingService`, `DistributionExcelService`, `ReparticionController`.

### Tarea 3 · Socios y Beneficiarios
- `SociosBeneficiariosController` (web 352) y `Api/SociosBeneficiariosController` (638).
- Servicios: `SocioBeneficiarioQueryService`, reglas de períodos y vigencia (`BenefitPeriodCalculator`, `BenefitExpiryService`, `BeneficiaryReportService`).
- Repos: `BeneficiaryRepository`, `PartnerRosterPeriodRepository`, `BeneficiaryHistoryRepository`, `ObstetricDataRepository`.

### Tarea 4 · Comités y Supervisión
- `Api/ComitesController` (687), `Api/SupervisionComitesController` (198), `ControlInternoController` (286), `PresidentPortalController` (358).
- Servicios: `ComiteService`, `SupervisionService`, `PresidentPortalService`; absorber `PresidentAccountService`, `PresidentCommitteeResolver`, `MunicipalResolutionService`, `ResolutionStateService`.
- Repos: `DirectiveRepository`, `SupervisionVisitRepository`, `SupervisionOffDayRepository`, `PlaceRepository`, `ResolutionRepository`, `RosterPeriodRepository`.

### Tarea 5 · Club de Reconocimientos y Responsables/Raciones
- `ClubReconocimientosController` (699), `ResponsablesRacionesController` (web + API), `Api/EvaluacionNutricionalController`.
- Servicios: `ClubReconocimientoService`, `ResponsableService`, `RacionService`, `EvaluacionNutricionalService`.
- Repos: `ResponsibleRepository`, `RacionRepository`, `ReconocimientoRepository`, `PositionRepository`.

### Tarea 6 · Inicio, Dashboard, Búsqueda, Movimientos
- `Api/InicioController` (456), `DashboardController`, `SearchController`, `Api/MovimientosController` (250).
- Servicios de lectura/estadística: `InicioMetricsService`, `GlobalSearchService`, `MovimientosService`, `MonthClosureService`.
- Repos de consulta: `DashboardQueryRepository`, `SearchRepository`.

### Tarea 7 · Sistema, Usuarios, Roles, Módulos, Autenticación
- `SistemaController` (web 405) y `Api/SistemaController` (487); controladores `Auth/*` y `PresidentPasswordController`.
- Servicios: `UserService`, `RolService`, `ModuleService`, `SettingsService`, `PasswordResetService`, `AuthService`.
- Repos: `UserRepository`, `RolRepository`, `ModuleRepository`, `SettingRepository`, `PasswordResetRequestRepository`, `SystemAuditRepository`, `NotificationRepository`.
- Middleware `CheckModuleAccess`, `EnsurePlatformAccess`: que consuman servicio, no Eloquent.

### Tarea 8 · Reportes, PDF y verificación de documentos
- `ReportController`, `ReportGeneratorController` (390), `Api/PvlReportsController` (475), `PvlReportPdfController`, `DocumentVerificationController`.
- Servicios `Pvl/*` (Context, DataMapper, Generator, Validation, Supporting, AiReport, Gemini) sin acceso a BD: leer datos vía repos (`PvlReportRunRepository`, `PvlDocumentRepository`, `VerifiedDocumentRepository`, `DocumentAttachmentRepository`).
- Separar generación de PDF (`PDFService`, `ReportePvlPdfService`) como adaptador.

### Tarea 9 · Asistente IA, Base de conocimiento y RAG, Normativa
- `Api/AssistantChatController`, `Api/KnowledgeBaseController` (321).
- Servicios: `Assistant*Service`, `KnowledgeBaseRagService`, `Rag/BaseRagService`, `Normativa*`, `Pvl/PvlRagService`.
- Repos: `KnowledgeBaseRepository` (documentos + chunks), `NormativaRepository`, `PvlDocumentRepository` (chunks). La búsqueda vectorial/embeddings queda en el repositorio; el cálculo de score y armado de prompt queda en el servicio.
- `NormativaScraperService` (880 líneas, 19 accesos BD): separar scraper (adaptador HTTP) de la persistencia.

### Tarea 10 · Comandos Artisan y Jobs programados
- `RebuildMonthlyData` (533 líneas), `RellenarDetailPecosas`, `MigrarViaServicios`, `ReindexRagEmbeddings`, `BackfillLegacyPartnerDates`.
- Los comandos solo parsean opciones y llaman a servicios; la lógica pasa a servicios + repos ya creados.
- Eventos/Listeners/Observers (`PecosaObserver`, `ResponsiblePeriodEnded`): confirmar que no tengan queries propias.

### Tarea 11 · Entidades y cierre
- Enums PHP 8.1 para estados/tipos repetidos como strings; casts y scopes en modelos (sin lógica de negocio pesada ni acceso a otras capas).
- Vaciar allowlist del test de arquitectura (debe pasar sin excepciones o con excepciones justificadas).
- Ejecutar suite completa, revisar rutas (`php artisan route:list`) sin cambios de contrato.
- Actualizar `docs/arquitectura.html` y este documento con la estructura final; entrega del árbol de carpetas final.

## 5. Orden y dependencias

```
T0 ─► T1 ─► T2 ─► T3 ─► T4 ─► T5 ─► T6 ─► T7 ─► T8 ─► T9 ─► T10 ─► T11
            (T2–T9 son independientes entre sí tras T1; el orden va de mayor a menor deuda)
```

T1 primero porque fija el patrón y los repos base que reutilizan las demás.

## 6. Criterios de aceptación (por tarea)

- Tests del dominio en verde antes y después.
- Controladores del dominio: sin `DB::` ni Eloquent; cada acción ≤ ~25 líneas.
- Servicios del dominio: sin `DB::` ni Eloquent; sin `Request`/`Response`.
- Repos con interfaz y binding registrado.
- Entradas de allowlist del test de arquitectura removidas.
- Un commit por tarea.

## 7. Riesgos

- Controladores gigantes (`ProductosPecosasController`) con queries inline complejas: extraer por método, no reescribir.
- Consultas con `DB::raw`/joins pesados: moverlas literalmente al repositorio para preservar rendimiento.
- Transacciones (`DB::transaction`) en servicios: decisión de diseño — se permite en la capa de negocio como unidad de trabajo; las consultas dentro van por repositorio.
- Pruebas existentes cubren sobre todo API; web/Inertia tiene poca cobertura → añadir smoke tests antes de refactorizar esos controladores.
- Árbol de trabajo hermano `C:\xampp8.1\htdocs\publicar\SCPIDE` es otro proyecto (Livewire); queda fuera de alcance salvo que se indique.
