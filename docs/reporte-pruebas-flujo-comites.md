# Reporte de pruebas: Resoluciones, Comités, Socios/Beneficiarios, Cargos y Padrones

Fecha de ejecución: 2026-11-12 · PHP 8.4.14 · PHPUnit 10.5 · BD de pruebas `dbsysprovale_test` (MySQL).

## 1. Resumen

| Métrica | Resultado |
|---|---|
| Pruebas nuevas del flujo | **236** (1 prueba añadida tras las correcciones) |
| Primera ejecución (antes de corregir) | 224 pasaban, **11 fallaban** (hallazgos reales) |
| **Después de corregir `app/`** | **236 de 236 pasan** |
| Suite completa del proyecto | **684 de 684 pasan** (incluidas las 2 pruebas antiguas de `ComitesApiTest` que ya fallaban) |

Los 11 hallazgos, las observaciones adicionales y las 2 pruebas antiguas rotas se corrigieron. La sección 5 conserva cada hallazgo con su causa y la solución aplicada. Las pruebas siguen marcadas `@group hallazgos` como protección contra regresiones.

## 2. Cómo ejecutarlas

```bash
php artisan config:clear          # obligatorio: evita que los tests toquen la BD de desarrollo
php vendor/bin/phpunit tests/Feature/FlujoComites                           # todo
php vendor/bin/phpunit tests/Feature/FlujoComites --exclude-group hallazgos # solo lo que hoy debe pasar
php vendor/bin/phpunit tests/Feature/FlujoComites --group hallazgos        # solo los hallazgos
```

## 3. Archivos creados

| Archivo | Pruebas | Cubre |
|---|---|---|
| `tests/Traits/FlujoComiteFixtures.php` | — | Datos base (estados, módulos, roles lector/sin acceso, zonas, tipos de beneficio, parentescos), simulación del portal municipal (publica / caído / error 500) y fábricas de resolución, comité, persona, socio. Fechas relativas a "hoy". |
| `tests/Feature/FlujoComites/ResolucionesFlujoTest.php` | 58 | Paso 1 |
| `tests/Feature/FlujoComites/ComitesCargosFlujoTest.php` | 50 | Pasos 2 y 4 |
| `tests/Feature/FlujoComites/SociosBeneficiariosFlujoTest.php` | 95 | Paso 3 |
| `tests/Feature/FlujoComites/PadronesYFlujoCompletoTest.php` | 31 | Paso 5 y recorridos completos |

## 4. Cobertura por paso

### Paso 1 — Resoluciones
- **Vigente**: se valida contra el portal municipal; nace `VIG`, o `VEN` si la fecha fin ya pasó; termina hoy = sigue vigente; descarta la observación enviada.
- **Rechazos**: número no publicado (`not_found`), 6 formatos inválidos (`invalid_format`), portal caído y portal en error 500 (no guarda y **no** dice "no existe"), campos obligatorios, fecha fin < inicio, fecha inválida, documento > 100 caracteres.
- **Pendiente**: exige observación (también rechaza solo espacios), no consulta el portal (acepta cualquier formato aunque el portal esté caído), crea notificación de regularización, no vence aunque la fecha fin sea pasada, `pending=false` se trata como vigente.
- **Regularizar**: pasa a `VIG` o `VEN` según fechas, corrige fechas, resuelve la notificación, sincroniza el estado del comité vinculado; rechaza no pendiente, formato inválido, no publicada, portal caído (sigue `PEN`), fechas inválidas.
- **Editar**: acortar fecha fin vence la resolución e inactiva el comité; renovar reactiva ambos; pendiente conserva estado; 404; documento vacío.
- **Eliminar**: libre; arrastra comité vacío; se bloquea con socios o historial; borra su notificación.
- **Listado / opciones / filtros** (estado, búsqueda, año, mes) y sincronización automática de vencimiento.
- **Portal externo**: verificar, buscar, previsualizar y descargar PDF; no publicada = 404; archivo que no es PDF = 502; portal caído.
- **Permisos**: 401 sin sesión, 403 sin módulo, lector (solo vista) no puede crear/editar/eliminar/regularizar.

### Paso 2 — Comité
- Estado inicial según resolución (vigente → `VIG`, vencida → `INA`, pendiente → `VIG`).
- `options`: incluye vigentes y pendientes, excluye vencidas (salvo `include_resolution_id`), `next_code`.
- Validaciones: 7 campos obligatorios, referencias inexistentes, longitudes, código duplicado exacto y numéricamente equivalente (`7`, `007`, ` 7 `).
- Edición: conserva su código y rechaza el de otro; cambiar resolución recalcula estado.
- Cambio manual de estado: renuncia exige acta PDF, otro motivo no; validaciones; estado manual no lo pisa el sync automático; restaurar automático; auditoría.
- Eliminar (vacío sí, con socios no), listado, filtros, detalle, permisos de todas las rutas.

### Paso 3 — Personas, Socios y Beneficiarios
- **Personas**: alta/edición/baja, 7 validaciones, DNI duplicado (alta y edición, permitiendo conservar el propio), bloqueo de baja si está vinculada, RENIEC con DNI inválido sin llamar al servicio.
- **Condiciones socioeconómicas** (todas): sin condición (exige beneficiario), `GESTANTE` (FUM obligatoria, no futura, beneficio GES, fecha fin FUM+9 meses, parto estimado FUM+280 días), `LACTANTE` (exige beneficiario, LAC +2 años, `benefit_child_person_id` debe estar en la ficha), `ADULTO_MAYOR` (edades 40/64/65/66/90/sin fecha: solo >65), `DISCAPACITADO` (CONADIS PDF obligatorio, rechaza imagen), `OTROS` (sin tuberculosis no genera fila; con "TBC/tuberculosis" genera beneficio TBC), condición inválida.
- **Beneficiarios**: prioridad y tipo NI0/NI7 corregidos según edad, niño de 6a11m vs 7a, varios parentescos, sin tipo (sin historial), 7 validaciones, estado de historial inactivo rechazado.
- **Documentos**: expediente único PDF, rechazo de imagen, documento por beneficiario, API de documentos (tipo/entidad inválidos).
- **Edición de socio**: campos simples, no borra beneficiarios si no se envían, agrega/conserva/elimina, cambio a gestante, discapacitado con/sin CONADIS previo, adulto mayor <65, traslado de comité, baja con fecha fin.
- **Eliminación**: cascada de beneficiarios/historial; con cargo asignado (nunca 500); bloqueo si es presidenta en una PECOSA.
- **API de beneficiarios**: alta, sin beneficio, validaciones, edición, baja (cierra periodo / elimina / rechaza histórico), detalle, listado por periodo.
- **Rutas web** (formularios clásicos) y fichas PDF; permisos y 401.

### Paso 4 — Cargos
- Los 7 cargos (uno por uno y los 7 de golpe), presidenta con nombre y cuenta del portal, varios cargos a una misma socia, rechazo de socia de otro comité, cargo desconocido o en minúsculas, socia inexistente, comité inexistente, duplicados/vacío/más de 7 en asignación múltiple, **atomicidad** (si una falla no se guarda ninguna), resolución pendiente, vínculo de la directiva con la resolución del comité.

### Paso 5 — Padrones
- **Beneficiarios**: PDF con datos; sin comité (422); comité inexistente (404); sin socios; sin historial vigente; periodo anterior; socia dada de baja; conteo por edad/sexo/tipo; gestante, lactante, adulto mayor, TBC; bajas por edad (≥14) y gestación (>9 meses); presidenta o "SIN ASIGNAR"; **bloqueo por resolución pendiente y liberación al regularizar**; comité inactivo no se bloquea.
- **Club**: incluye el comité nuevo, cuenta sus beneficiarios, excluye resolución que aún no empieza o vencida antes del mes, muestra presidenta y resoluciones, bloqueo/liberación por pendiente, actas PDF, permisos.

### Recorridos completos
1. **Resolución vigente** → comité → 3 socias (sin condición ×2 y gestante) → cargos en lote → padrón de beneficiarios y del club.
2. **Resolución pendiente** → comité → socia y presidenta → padrones bloqueados → regularización → padrones habilitados.
3. **Renovación** de resolución con historial (la anterior no se puede borrar).
4. **Vencimiento**: comité pasa a inactivo y sale del padrón actual pero aparece en periodos anteriores.
5. **Renuncia** con acta (estado manual).
6. **Cambio de directiva** + nuevas socias aumentan el padrón.
7. **Eliminar socio** la quita del padrón.

## 5. Hallazgos y correcciones aplicadas

| # | Hallazgo | Corrección |
|---|---|---|
| 1 | Reasignar la presidenta el mismo día dejaba a la anterior como presidenta (la vencida quedaba con rango invertido y la nueva con `date_end = NULL` no cumplía el filtro de vigencia). | `DirectiveRepository::expireCurrent` ya no deja `date_end` menor que `date_start`. `Association::getPresidenta`, `getPresidentaAt` y `hydratePresidents` aceptan `date_end` nulo como vigente y desempatan por `date_start` e `id` descendentes. |
| 2 | La misma persona podía ser socia dos veces en un comité. | `PartnerService::assertNoDuplicates`: rechaza (422) si la persona ya tiene una ficha vigente (sin `date_end` o no vencida). |
| 3 | Una persona podía ser socia vigente de dos comités. | Misma validación que el punto 2 (vale para cualquier comité). |
| 4 | El mismo niño podía ser beneficiario vigente de dos socias. | Misma validación: rechaza si ya es beneficiario con historial vigente de otra socia vigente. |
| 5 | El mismo niño repetido dentro de una ficha. | Misma validación: rechaza personas repetidas en `beneficiaries`. |
| 6 | La socia podía ser su propia beneficiaria "Hijo". | Misma validación: rechaza si el `person_id` coincide con el de la socia. Al editar se ignora la fila propia de la socia. |
| 7 | Resolución con número duplicado. | `ResolutionService::assertDocumentAvailable` al crear una resolución vigente, al regularizar y al cambiar el número en la edición. Las pendientes pueden repetir un marcador temporal. |
| 8 | Cargo a una socia con ficha vencida. | `CommitteeService` valida con `partnerIsActive` en asignación simple y múltiple. |
| 9 | La API de beneficiarios no derivaba la prioridad por edad. | `BeneficiaryService::register/modify` calculan prioridad por edad (≤6 años = 1, mayor = 2). |
| 10 | La API permitía el mismo beneficiario dos veces con la misma socia. | `BeneficiaryService::register` rechaza el duplicado (422). |
| 11 | DNI aceptaba letras y menos de 8 dígitos. | `digits:8` en `StorePersonaRequest` y `UpdatePersonaRequest`. |

> Las reglas 2 a 6 son reglas de negocio asumidas, ahora aplicadas. Si en la práctica una persona puede estar en dos comités a la vez, hay que retirar la validación de `personHasActivePartner` en `PartnerService::assertNoDuplicates` y su prueba.

## 6. Otras observaciones (corregidas)

- **`POST /clubs` devolvía `state_source` nulo**: `CommitteeService::create` refresca el modelo. Prueba actualizada.
- **Rango invertido en directivas**: resuelto con el hallazgo 1.
- **`PartnerController::store/update` (ruta web) no guardaba documentos**: ahora usan `register()` / `modify()` igual que la API. Prueba nueva `test_web_crear_socio_guarda_el_expediente_adjunto`.
- **Padrón y beneficios vencidos**: es el comportamiento diseñado (un beneficio NI7 o GES vencido sale del padrón; la baja depende de que corra `BenefitExpiryService`). Sin cambio.
- **Resolución pendiente**: permite crear comités y cargos, solo bloquea padrones. Sin cambio, es correcto.

## 7. Pruebas antiguas corregidas

`tests/Feature/Api/ComitesApiTest.php`: `test_store_reconocimiento_creates` y `test_destroy_reconocimiento_without_references_deletes` fallaban porque crear una resolución vigente ahora valida contra el portal. El payload de prueba ahora usa `pending: true` con observación.

## 8. Nota de arquitectura

Las consultas nuevas viven en los repositorios (`PartnerRepository`, `BeneficiaryRepository`, `ResolutionRepository`, `AssociationRepository`), porque `ArchitectureTest` prohíbe el acceso directo a la BD desde `app/Services`.

## 9. Datos históricos (cargas desde Excel)

Los registros históricos no deben bloquearse por las reglas nuevas, aunque no las cumplan. Las cargas desde Excel (seeders y JSON en `database/seeders/data`) escriben directo en la BD y no pasan por estas validaciones. Para los registros históricos que se editan o registran desde la aplicación:

- **Ficha de socia con `date_end` pasada:** se registra o edita sin revisar duplicados (socia repetida, niño repetido o ya con otra socia).
- **Edición de una socia:** solo se revisan los beneficiarios nuevos o cambiados. Los ya guardados no se revalidan.
- **Resolución con `date_end` pasada:** puede repetir un número de documento.
- **Edición de una persona:** el DNI solo se valida (8 dígitos) si se cambia. Una persona con DNI histórico inválido sigue siendo editable.
- **Beneficiario por API:** no se revisa el duplicado si la socia tiene la ficha vencida.

En la BD de desarrollo hay datos que hoy incumplen las reglas: 2 socias activas repetidas, 4 pares socia-beneficiario repetidos y 2 DNI que no tienen 8 dígitos. Siguen intactos y editables.
Pruebas nuevas: `test_resolucion_historica_con_documento_repetido_se_admite`, `test_ficha_historica_vencida_se_admite_aunque_repita_socia_y_nino` y `test_editar_persona_con_dni_historico_invalido_no_se_bloquea`.
