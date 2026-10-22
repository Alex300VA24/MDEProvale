# MDEProvale — Manual de arquitectura

Documento de referencia del sistema de gestión del Programa del Vaso de Leche (PROVALE). Tiene dos partes: la **Parte A** es un resumen ejecutivo para quien decide o supervisa; la **Parte B** es el manual técnico para quien mantiene o extiende el código.

Estado documentado: rama `estructura`, refactorización a 4 capas completada (tareas T0–T11 del [plan](plan-refactor-ncapas.md)).

---

# Parte A — Resumen ejecutivo

## A.1 Qué es el sistema

MDEProvale administra la operación del Programa del Vaso de Leche: padrón de comités (asociaciones), socios y beneficiarios, resoluciones municipales, productos y stock, entrega de raciones mediante PECOSAs, movimientos de almacén, supervisión, reportes oficiales (formatos PVL) y un portal para las presidentas de comité. Incluye un asistente de IA que responde sobre la normativa y los datos del programa.

## A.2 Qué cambió con la refactorización

Antes, la lógica de negocio y las consultas a la base de datos estaban mezcladas dentro de los controladores web (algunos superaban las 1.200 líneas). Cualquier cambio de regla obligaba a tocar pantallas, y era difícil probar o reutilizar algo.

Ahora el código está dividido en **cuatro capas con responsabilidades únicas**, y una regla automática impide que vuelvan a mezclarse:

| Capa | Responsabilidad en una frase |
|---|---|
| Presentación | Recibe la petición y entrega la respuesta (pantalla, JSON, PDF). No decide nada. |
| Negocio | Contiene las reglas del programa (qué es válido, cómo se calcula, qué se registra). |
| Acceso a datos | Es la única que habla con la base de datos. |
| Entidades | Representan las tablas y sus relaciones. |

## A.3 Beneficios para la institución

- **Cambios más baratos y seguros.** Una regla de negocio vive en un solo lugar; modificarla no rompe las pantallas.
- **Calidad verificable.** La suite automática (391 pruebas, incluida una de arquitectura) corre antes de cada entrega. Si alguien vuelve a poner una consulta dentro de un controlador, la prueba falla.
- **Menor dependencia de personas.** Estructura uniforme: quien conoce un módulo entiende los demás.
- **Evolución posible.** La base de datos, el generador de PDF o el proveedor de IA pueden cambiarse tocando una sola capa.
- **Trazabilidad.** Operaciones sensibles (cierres de mes, cambios de responsables, documentos verificados) pasan por servicios con registro de auditoría.

## A.4 Indicadores del estado actual

| Elemento | Cantidad |
|---|---:|
| Controladores (web, API y autenticación) | 39 |
| Servicios de negocio | 48 + subdominios (Pvl, Rag, Normativa, Alertas, Maintenance, KnowledgeBase) |
| Repositorios con interfaz | 31 |
| Modelos (entidades) | 52 |
| Rutas | 223 |
| Comandos programados / de mantenimiento | 10 |
| Pruebas automáticas | 391 |
| Excepciones a la regla de capas | **0** |

## A.5 Riesgos y puntos de atención

- El sistema depende de servicios externos: **RENIEC (vía PIDE)** para validar DNI y **Gemini** para el asistente y los reportes asistidos. Si están caídos, el resto funciona; esas funciones se degradan.
- Las tareas diarias (estados de comités, vigencia de PECOSAs, vencimientos, normativa, alertas) requieren que el planificador de Laravel esté configurado en el servidor (ver B.9).
- Cobertura de pruebas de pantallas (Inertia/React) menor que la del backend; ver B.11.

---

# Parte B — Manual técnico

## B.1 Stack

| Pieza | Tecnología |
|---|---|
| Backend | Laravel 10, PHP 8.1 (rango fijado en `composer.json`) |
| Frontend | React + Inertia (SPA dentro de Laravel), Vite; vistas Blade con Laravel Mix para módulos heredados |
| Autenticación | Sesión web + Laravel Sanctum para la API |
| Base de datos | MySQL / MariaDB |
| PDF / Excel / QR | DomPDF, PhpSpreadsheet, endroid/qr-code |
| IA | Gemini (asistente, reportes PVL, embeddings RAG) |
| Integraciones | RENIEC WS2 vía PIDE |

Puesta en marcha, variables de entorno y modos de despliegue (artisan serve o XAMPP): ver [README.md](../README.md).

## B.2 Las cuatro capas

```
 Petición HTTP / comando CLI
           │
           ▼
┌──────────────────────────────────────────────┐
│ 1. PRESENTACIÓN                              │
│    Http/Controllers (+Api), Requests,        │
│    Resources, Middleware, Policies,          │
│    routes/*.php, Console/Commands,           │
│    resources/js (Inertia/React)              │
└──────────────────────────────────────────────┘
           │ llama a
           ▼
┌──────────────────────────────────────────────┐
│ 2. NEGOCIO              app/Services         │
│    reglas, orquestación, transacciones,      │
│    adaptadores externos (PDF, RENIEC, IA)    │
└──────────────────────────────────────────────┘
           │ depende de interfaces
           ▼
┌──────────────────────────────────────────────┐
│ 3. ACCESO A DATOS       app/Repositories     │
│    únicas clases que ejecutan consultas      │
└──────────────────────────────────────────────┘
           │ usa
           ▼
┌──────────────────────────────────────────────┐
│ 4. ENTIDADES            app/Models, DTOs,    │
│    Enums                                     │
└──────────────────────────────────────────────┘
```

### Regla de dependencia (unidireccional)

Cada capa solo conoce a la inmediata inferior. Concretamente:

| Capa | Puede | No puede |
|---|---|---|
| Presentación | Validar con `FormRequest`, autorizar con Policies, llamar a **un** servicio, devolver Inertia/JSON/PDF | `DB::`, `Modelo::where(...)`, lógica de negocio, llamar repositorios para lógica de dominio |
| Negocio | Orquestar repositorios, abrir `DB::transaction`, lanzar excepciones de dominio, usar DTOs | Importar `Request`, `Inertia`, `JsonResponse`; ejecutar consultas |
| Datos | Construir y ejecutar consultas Eloquent / `DB::table` | Importar `App\Services` o `App\Http`; contener reglas de negocio |
| Entidades | Definir relaciones, casts, scopes y accesores simples | Llamar a servicios o repositorios |

Excepciones consentidas: `DB::transaction/beginTransaction/commit/rollBack` en servicios (unidad de trabajo) y helpers estáticos de modelo como `Pecosa::currentDeliveryPeriod`.

### Cómo se verifica

`tests/Unit/ArchitectureTest.php` recorre el código y falla si:

1. Un controlador, middleware o comando contiene `DB::` o una llamada estática de consulta a un modelo.
2. Un servicio hace lo mismo.
3. Un servicio o repositorio importa `Request`, `Inertia` o `JsonResponse`.
4. Un repositorio importa `App\Services` o `App\Http`.
5. La lista de excepciones (`tests/Unit/architecture-allowlist.php`) contiene archivos ya limpios.

La lista de excepciones está **vacía** y no debe crecer: si el test falla, se corrige el código, no la lista.

## B.3 Mapa del directorio

```
app/
├── Console/
│   ├── Commands/        # 10 comandos Artisan (solo parsean opciones y llaman a servicios)
│   └── Support/         # CommandReporter: adapta un Command a ProgressReporter
├── DTOs/                # objetos de transferencia entre capas
├── Enums/               # tipos cerrados (ResponsibleType)
├── Events/ Listeners/   # eventos de dominio (cierre de periodo de responsable)
├── Http/
│   ├── Controllers/     # web (Inertia/PDF) y Api/ (JSON)
│   ├── Middleware/      # acceso por rol y por módulo, sesión, Inertia
│   ├── Requests/        # validación de entrada (25)
│   └── Resources/       # transformación de salida (18)
├── Models/              # 52 entidades Eloquent
├── Policies/            # autorización por recurso
├── Providers/           # RepositoryServiceProvider enlaza interfaz → implementación
├── Repositories/
│   ├── Contracts/       # 31 interfaces
│   ├── Concerns/        # comportamiento compartido
│   └── *.php            # implementaciones
└── Services/
    ├── Contracts/       # interfaces de servicios clave
    ├── Alertas/ KnowledgeBase/ Maintenance/ Normativa/ Pvl/ Rag/
    └── *.php            # servicios por dominio
config/                  # alertas, rag, knowledge_base, normativa, pvl_reports, distribution…
database/                # migraciones (84), seeders y JSON de catálogos
resources/js/            # Pages/, Sections/ (una por módulo), Components/, Layouts/
routes/                  # web, api, auth, dashboard-api, movimientos, reportes, search…
tests/                   # Unit/ (arquitectura, enums) y Feature/
docs/                    # esta documentación, plan de refactor, prototipos HTML
scripts/                 # utilidades one-shot (generador de seeders de resoluciones)
```

## B.4 Anatomía de una petición

Ejemplo real: **crear una PECOSA** (`POST` en la API de productos y pecosas).

1. `routes/productos-pecosas.php` enruta a `Api\ProductosPecosasController@storePecosa`.
2. `StorePecosaRequest` valida y normaliza la entrada; una Policy autoriza.
3. El controlador llama `PecosaService::createPecosa($data)`.
4. El servicio, dentro de `DB::transaction`:
   - resuelve la presidenta vigente en el periodo de entrega (`resolvePresidentForDeliveryPeriod`),
   - construye el `PecosaSnapshotDTO` con datos congelados de comité y responsables,
   - genera el correlativo (`nextPecosaNumber`, con bloqueo `lockForUpdate`),
   - guarda cabecera y detalle mediante `PecosaRepositoryInterface`.
5. El repositorio ejecuta las consultas sobre `Pecosa` / `DetailPecosa`.
6. El controlador devuelve un `PecosaResource` (JSON). Una pantalla Inertia devolvería `Inertia::render(...)` con los datos del servicio.

Los controladores resultantes tienen pocas líneas por acción; si una acción crece, la lógica sobrante pertenece a un servicio.

## B.5 Módulos funcionales

| Módulo | Presentación (resumen) | Negocio |
|---|---|---|
| Inicio / dashboard | `InicioController`, `Pages/Dashboard.jsx` | `InicioService` |
| Comités y resoluciones | `ComitesController` | `CommitteeService`, `ResolutionService`, `MunicipalResolutionService`, `AssociationStateService` |
| Socios y beneficiarios | `SociosBeneficiariosController`, `PartnerController`, `BeneficiarieController` | `PartnerService`, `BeneficiaryService`, `BenefitExpiryService`, `BenefitPeriodCalculator`, `PersonService` |
| Productos y PECOSAs | `ProductosPecosasController` (web y API) | `ProductService`, `PecosaService`, `ProgramacionEntregaService`, `StockService`, `SchedulingService` |
| Movimientos / kárdex | `MovimientosController`, `KardexController`, `TransactionController` | `TransactionService`, `MonthClosureService` |
| Raciones y responsables | `ResponsablesRacionesController` | `RacionService`, `ResponsibleService` |
| Reparto | `ReparticionController` | `ReparticionService`, `DistributionExcelService` |
| Supervisión y evaluación nutricional | `SupervisionComitesController`, `EvaluacionNutricionalController` | `SupervisionService`, `NutritionalEvaluationService` |
| Club de reconocimientos | `ClubReconocimientosController` | `ClubReportService` |
| Reportes PVL | `PvlReportsController`, `PvlReportPdfController`, `ReportController` | `Pvl/*`, `ReportGeneratorService`, `ReportePvlPdfService`, `PDFService` |
| Verificación de documentos (QR) | `DocumentVerificationController` | `VerifiedDocumentService`, `DocumentAttachmentService` |
| Control interno | `ControlInternoController` | `ControlInternoService` |
| Portal de presidentas | `PresidentPortalController`, `PresidentPasswordController` | `PresidentAccountService`, `PresidentCommitteeResolver` |
| Sistema (usuarios, roles, módulos, auditoría) | `SistemaController` | `UserService`, `RolService`, `ModuleService`, `SystemAuditService`, `UploadSettingsService` |
| Asistente IA y base de conocimiento | `AssistantChatController`, `KnowledgeBaseController` | `Assistant*Service`, `KnowledgeBase/*`, `Rag/*`, `Normativa/*` |
| Búsqueda global | `SearchController` | `SearchService` |

Documentación específica de dominios complejos: [base-conocimiento-rag.md](base-conocimiento-rag.md), [verificacion-documentos-qr.md](verificacion-documentos-qr.md), [knowledge-graph.md](knowledge-graph.md).

## B.6 Seguridad y control de acceso

- **Autenticación**: sesión para la web, Sanctum para la API (`AlwaysStatefulApi`). `CheckSessionExpired` cierra sesiones vencidas.
- **Dos poblaciones de usuarios**: personal de la plataforma (`platform.user`) y presidentas de comité (`role.president`), con grupos de rutas separados en `routes/web.php`. Las presidentas solo ven datos de su comité (`PresidentCommitteeResolver`).
- **Acceso por módulo**: `CheckModuleAccess` consulta qué módulos tiene el rol; `ModuleService` y `RolService` los administran.
- **Autorización fina**: 11 Policies (Association, Partner, Pecosa, Product, Resolution, Rol, Transaction, User, Module, Persona, Racion) invocadas desde controladores.
- **Límite de peticiones**: `throttle` en las rutas públicas y de acceso.
- **Secretos**: solo en `.env` (nunca versionado); las credenciales de RENIEC y Gemini se usan únicamente desde el backend.
- **Auditoría**: `SystemAuditService` y `AuditRepository` registran operaciones sensibles.

## B.7 Datos y entidades

- 52 modelos; la persona se modela como `People` y se relaciona con socios, responsables y directivas.
- Los estados de negocio se identifican por código de catálogo (`State::ACTIVE` `ACT`, `INACTIVE` `INA`, `CURRENT` `VIG`, `EXPIRED` `VEN`, `PENDING` `PEN`).
- Tipos cerrados nuevos deben ser **enums** en `app/Enums` (ejemplo: `ResponsibleType` con `Chief` y `Storekeeper`, y su etiqueta legible). PHP 8.1 no permite `->value` en constantes de clase; usar el enum directamente en código.
- Las PECOSAs guardan una **copia congelada** (snapshot) de nombres y DNI de comité y responsables, de modo que cambios posteriores del padrón no alteren documentos ya emitidos.
- Migraciones en `database/migrations`; catálogos y datos históricos se cargan con seeders (`database/seeders`, JSON en `database/seeders/data`).

## B.8 Servicios transversales

| Pieza | Función |
|---|---|
| `PDFService`, `ReportePvlPdfService` | Generación de PDF con DomPDF |
| `ReniecService` | Consulta de DNI vía PIDE |
| `Pvl/*` | Reportes PVL: contexto, validación, mapeo de datos, generación asistida por Gemini |
| `Rag/BaseRagService`, `KnowledgeBase/*`, `Normativa/*`, `Pvl/PvlRagService` | Indexación de documentos en chunks con embeddings y recuperación para el asistente |
| `NotificationService` + `Alertas/VencimientoAlertService` | Notificaciones internas y alertas de vencimiento con deduplicación |
| `VerifiedDocumentService` | Documentos con QR verificables públicamente |
| `Maintenance/*` | Procesos de mantenimiento de datos (ver B.9) |

## B.9 Comandos y tareas programadas

Planificador (`app/Console/Kernel.php`; requiere la entrada de cron `* * * * * php artisan schedule:run`):

| Hora | Comando | Efecto |
|---|---|---|
| 00:05 | `states:sync-associations` | Sincroniza estados de comités |
| 00:10 | `pecosas:sync-vigencia` | Actualiza vigencia de PECOSAs |
| 00:20 | `beneficios:procesar-vencimientos` | Procesa beneficios vencidos |
| 06:30 | `normativa:scan-pvl` | Busca normativa nueva |
| 06:45 | `alertas:vencimientos` | Genera alertas de vencimiento |

Comandos de mantenimiento manual (delgados; la lógica está en `Services/Maintenance`): `data:backfill-legacy-dates`, `data:rebuild`, `migrar:via-servicios`, `migrar:rellenar-detail-pecosas`, `rag:reindex`. Los servicios reportan avance mediante la interfaz `ProgressReporter`; `Console\Support\CommandReporter` la implementa sobre la consola, de modo que la capa de negocio no conoce Artisan.

## B.10 Guía para extender el sistema

**Agregar una funcionalidad a un dominio existente**

1. Consulta nueva: método en la interfaz de `Repositories/Contracts` y su implementación en `Repositories`.
2. Regla o flujo: método en el servicio del dominio, inyectando solo interfaces de repositorio.
3. Entrada/salida: `FormRequest` y `Resource` en `Http`.
4. Acción en el controlador: validar, llamar al servicio, responder. Registrar la ruta en el archivo `routes/` del dominio.
5. Prueba Feature del flujo; el test de arquitectura debe seguir en verde.

**Crear un dominio nuevo**

1. Modelo(s) y migración.
2. `XRepositoryInterface` + `XRepository`; registrar el par en `RepositoryServiceProvider::$bindings`.
3. `XService` (si hay riesgo de intercambio o varias implementaciones, con interfaz en `Services/Contracts`).
4. Controlador, Requests, Resource, Policy y ruta.
5. Si el módulo es visible en el menú, registrarlo como módulo del sistema (`ModuleSeeder`).

**Checklist de revisión de código**

- El controlador no importa modelos para consultar.
- El servicio no importa `Request`/`Inertia`.
- El repositorio no contiene `if` de negocio.
- Las cadenas mágicas repetidas (tipos, estados) se convierten en enum o constante.
- Las operaciones multi-tabla van dentro de `DB::transaction` en el servicio.

**Errores frecuentes**

- Olvidar el binding en `RepositoryServiceProvider`: el contenedor falla solo al resolver la clase, y las pruebas no lo notan si nada la ejercita. Después de agregar un repositorio, ejecutar `php artisan route:list` y una prueba que lo resuelva.
- Olvidar el `use` del modelo en un repositorio nuevo (ocurrió con `People`): igualmente solo falla al ejecutar.

## B.11 Pruebas

- Base de datos de pruebas MySQL `dbsysprovale_test`, con `RefreshDatabase`.
- Ejecución: `vendor/bin/phpunit` (suite completa: 391 pruebas, entre 1 y 3 minutos).
- `tests/Unit/ArchitectureTest.php` protege la regla de capas.
- `tests/Feature/` cubre la API por dominio, los servicios de mantenimiento y control interno, y la generación de PDF PVL.
- Pendiente: más pruebas de humo para las pantallas Inertia (poca cobertura relativa).

## B.12 Organización del repositorio

| Ruta | Contenido |
|---|---|
| `docs/` | Documentación viva: este manual, plan de refactor, auditoría de padrón, RAG, verificación QR, `cambios.md` (historial) |
| `docs/prototipos/` | Prototipos HTML de formatos (acta, reparto, fichas, tarjetas) ya migrados a Blade; solo referencia |
| `docs/arquitectura.html` | Diagrama interactivo de alto nivel generado con archify |
| `scripts/` | Utilidades puntuales y su insumo (`generate_resolution_seeders.php`, `resoluciones_comites.xlsx`) |
| `plv_blade_templates/` | Plantillas maestras y ejemplos JSON de los reportes PVL (los usa una prueba) |
| `migracion_*` | Scripts Python y datos de migración histórica; datos personales fuera de git |
| `DESIGN.md`, `PRODUCT.md` | Guía de diseño y de producto |
| `.claude/`, `.agents/`, `.cursor/`, `.gemini/`, `.kiro/`, `.opencode/`, `.agent/`, `.codex/` | Configuración y skills locales de cada asistente de IA. Cada herramienta exige su propia carpeta en la raíz; están ignoradas por git |
| `tmp/` | Salidas locales (p. ej. `repomix-output.md`), ignorado por git |

## B.13 Decisiones de diseño

- **Se mantuvieron los namespaces de Laravel** (`App\Http`, `App\Services`, `App\Repositories`) en vez de renombrar carpetas por capa: evita romper autoload y pruebas, y las capas se identifican por su directorio.
- **Transacciones en servicios**: la unidad de trabajo pertenece al negocio; las consultas de dentro siguen yendo por repositorio.
- **Una interfaz por repositorio, no por servicio**: los servicios solo tienen interfaz cuando hay una razón real (PECOSA, productos, socios, transacciones).
- **Pares web/API del mismo dominio** consumen el mismo servicio; no se duplican reglas.
- **El test de arquitectura es la fuente de verdad** de la regla; este manual la describe, el test la hace cumplir.
