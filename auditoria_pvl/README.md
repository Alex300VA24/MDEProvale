# Auditoría histórica del sistema PROVALE

**Fecha de revisión:** 2 de septiembre de 2026  
**Alcance:** padrones de socios/beneficiarios, padrón de comités, PECOSAs, repartición y tarjetas del Inicio.  
**Tipo de revisión:** código, esquema/migraciones, datos locales y suite automatizada. No se modificaron registros de negocio.

## Resumen ejecutivo

| Área | Veredicto | Resultado principal |
|---|---|---|
| Padrón de socios y beneficiarios | Parcialmente correcto | Filtra socios e historiales por solapamiento del mes/año. Solo es confiable desde enero de 2024, porque las fechas históricas disponibles empiezan allí. Tiene inconsistencias menores en reglas de estado y en la PECOSA asociada al padrón. |
| Padrón de comités | Incorrecto para historia completa | Usa mes/año, pero primero exige que la resolución guardada en `associations.resolution_id` cubra el período. No considera una resolución antigua del pivote para decidir si incluye el comité. |
| PECOSAs | Incompleto | Los detalles de producto están casi completos, pero ninguna de las 10 819 PECOSAs tiene snapshot de presidenta y ninguna tiene snapshot de solicitante. Hay 2 PECOSAs sin detalles. |
| Repartición | Parcialmente correcto | Calcula raciones por beneficiarios vigentes al último día y excluye comités con cero beneficiarios. No filtra vigencia/estado del comité. La programación con firma usa reglas distintas y no es histórica. |
| Tarjetas de Inicio | Incorrecto respecto a mes/año | Socios, beneficiarios y comités son totales globales. El stock también es saldo global/último lote y no responde al selector histórico. Solo las gráficas usan filtros de período. |
| Integridad de stock | Incorrecto en al menos un lote | El lote 16, HOJUELA DE QUINUA de 2019-08-01, tiene ingreso 4 020, salidas 4 076 y saldo -56. |

## Evidencia de la base revisada

- 77 comités.
- 2 656 socios.
- 3 872 beneficiarios y 3 872 historiales: no hay beneficiarios sin historial ni rangos invertidos.
- Vigencia disponible de socios y beneficiarios: desde 2024-01-09 hasta 2028-01-23.
- 10 819 PECOSAs y 13 946 filas de detalle.
- 10 819 PECOSAs sin `president_name` y sin `managing_partner_name`.
- 8 366 PECOSAs sin snapshot de jefe ni almacenero; esto afecta principalmente 2019-2024.
- 61 PECOSAs sin `beneficiaries_count`.
- 2 PECOSAs sin ninguna fila en `detail_pecosas`.
- Los 13 946 detalles sí tienen nombre de producto, unidad y cantidad positiva.
- Los 77 comités tienen alguna directiva de presidenta, pero solo 52 tienen una directiva que cubre la fecha actual según `date_start/date_end`.

## 1. Padrón de socios y beneficiarios

### Lo que funciona

`BeneficiaryReportService::generatePadronReport()` construye el inicio y fin del mes solicitado. `PartnerRepository::findActiveByAssociation()` selecciona socios cuyo rango incluye la fecha de corte, y `buildReportData()` exige un historial de beneficiario que se solape con el mes. La edad también se calcula al final del mes seleccionado y la presidenta se intenta resolver históricamente con `getPresidentNameAt()`.

Con los datos actuales, todos los socios y todos los historiales de beneficiario tienen fechas, ningún rango está invertido y ningún beneficiario carece de historial. Para períodos desde enero de 2024, la base necesaria para el filtrado existe.

### Lo que falla o puede producir diferencias

1. **No existe historia anterior a 2024 en socios/beneficiarios.** Las PECOSAs llegan hasta 2019, pero `partners.date_begin` y `beneficiary_histories.date_begin` empiezan el 2024-01-09. Un padrón 2019-2023 no puede reconstruirse con fidelidad aunque el PDF acepte esos años.
2. **La consulta del socio usa solo la fecha final del mes.** `findActiveByAssociation()` recibe `$endDate`, de modo que una socia que estuvo vigente al inicio del mes pero terminó antes del último día queda fuera. Los beneficiarios sí usan solapamiento de inicio y fin. Ambas reglas deberían ser iguales.
3. **No se exige estado vigente.** El padrón decide por fechas, pero no comprueba `partners.state_id` ni `beneficiary_histories.state_id`. Esto puede incluir registros con estado de baja si sus fechas todavía cubren el mes.
4. **La PECOSA mostrada en el padrón usa mes calendario.** Busca `delivery_date` dentro del mes natural, mientras el resto del sistema clasifica las PECOSAs de los últimos siete días en el mes siguiente mediante `Pecosa::deliveryPeriodRange()`. El padrón puede mostrar productos de otra repartición o ninguno.
5. **Si hubiera más de una PECOSA del comité en el período, toma solo la primera sin orden explícito.** En varios períodos existen dos PECOSAs por comité; esto puede ser válido si una contiene leche y otra hojuelas, pero el padrón solo presenta el detalle de una.

### Solución recomendada

- Cambiar `findActiveByAssociation()` para recibir inicio y fin, y aplicar solapamiento: `date_begin <= fin` y (`date_end IS NULL` o `date_end >= inicio`).
- Definir una única regla de vigencia: fechas más estado, o únicamente fechas; documentarla y aplicarla en padrón, Inicio y repartición.
- Consultar PECOSAs mediante `forDeliveryPeriod($year, $month)` y combinar todas las PECOSAs del comité del período, agrupando productos.
- Limitar el selector histórico del padrón al primer mes respaldado por datos, o importar las altas/bajas reales de 2019-2023.

## 2. Padrón histórico de comités

### Fallo principal

`ClubReconocimientosController::generarPadronClub()` hace `whereHas('resolution', ...)`. Esa relación representa solamente `associations.resolution_id`. Aunque después carga `resolutionsHistory`, un comité queda descartado antes si su resolución enlazada actualmente no cubre el mes consultado.

Ejemplo: si en 2026 el comité apunta a su resolución nueva y se solicita el padrón de 2024, la resolución nueva no cubre 2024; el comité se omite aunque `resolution_associations` contenga una resolución de 2024 válida.

Además, al formar las columnas de resolución se incluyen todas las resoluciones iniciadas antes del fin del período, pero no se exige que su fecha de término alcance el inicio del período. Puede imprimirse una resolución ya vencida.

### Solución recomendada

- Seleccionar comités con una condición agrupada: la resolución directa **o cualquiera de `resolutionsHistory`** debe solaparse con el mes.
- Tras reunir todas las resoluciones, filtrar cada una con la misma regla completa: `date_start <= fin` y (`date_end IS NULL` o `date_end >= inicio`).
- Agregar pruebas con un comité cuya resolución actual sea de 2026 y cuyo historial contenga una resolución de 2024; el padrón 2024 debe incluirlo.
- No usar `associations.state_id` actual para reconstruir un mes pasado; derivar el estado desde la resolución que cubría ese período.

## 3. PECOSAs y presidentas

### Estado de los datos

Los detalles de producto son razonablemente completos: todas las filas tienen producto, unidad y cantidad positiva. Sin embargo, hay 2 PECOSAs sin detalles, que deben identificarse y cotejarse contra el documento físico.

El problema crítico está en los snapshots históricos:

| Año | PECOSAs | Sin presidenta | Sin solicitante |
|---:|---:|---:|---:|
| 2019 | 1 399 | 1 399 | 1 399 |
| 2020 | 1 645 | 1 645 | 1 645 |
| 2021 | 1 585 | 1 585 | 1 585 |
| 2022 | 1 599 | 1 599 | 1 599 |
| 2023 | 1 539 | 1 539 | 1 539 |
| 2024 | 1 340 | 1 340 | 1 340 |
| 2025 | 1 023 | 1 023 | 1 023 |
| 2026 | 689 | 689 | 689 |

Por tanto, **las presidentas no están apareciendo desde el dato histórico de la PECOSA**. El modelo y el PDF ya admiten `president_name`/`president_dni`, pero los registros están vacíos.

### Problemas de implementación

- Al crear/actualizar una PECOSA, `PecosaService::buildPecosaSnapshotDTO()` llama `Association::getPresidenta()`, que resuelve la presidenta respecto de hoy, no respecto de `delivery_date`. Para historia debe usar la directiva que cubra la fecha de entrega efectiva.
- `president_id` existe en la tabla y el modelo, pero el snapshot no lo asigna.
- Los datos migrados necesitan backfill. El comando `RellenarDetailPecosas` atiende detalles, y `RellenarDetailPecosas`/`SyncPecosaVigencia` no resuelven presidentas históricas.
- La suite automatizada muestra que el flujo actual de creación de PECOSA devuelve 422 en varias pruebas y que `pecosas/options` no encuentra la presidenta esperada. Hay una desalineación entre los IDs/estados sembrados por las pruebas y las reglas actuales; debe corregirse antes de confiar en regresiones.

### Solución recomendada

1. Resolver presidenta por `association_id` y fecha de entrega: directiva con posición PRESIDENTA y rango que cubra la fecha; guardar `president_id`, nombre y DNI.
2. Crear un comando de backfill en modo simulación y ejecución. Solo completar valores nulos, nunca sobrescribir snapshots existentes.
3. Para cada PECOSA histórica sin coincidencia inequívoca, generar un CSV de excepciones para validación humana.
4. Bloquear nuevas PECOSAs sin presidenta si la regla institucional exige que todo comité tenga una; si se permiten excepciones, exigir una observación.
5. Identificar las 2 PECOSAs sin detalle y decidir si se completan desde el documento físico o se marcan como incompletas/anuladas.

## 4. Repartición

### Qué hace actualmente

`ReparticionService::buildReport()` carga todos los comités, conserva los socios y beneficiarios vigentes **al último día del mes**, calcula las cantidades y después elimina comités con cero beneficiarios. Por eso no tiene que devolver siempre los 77 comités existentes ni 76: devuelve únicamente comités con al menos un beneficiario que cumple esos rangos.

Con la ración activa 2026, el resultado observado fue:

| Período | Comités | Beneficiarios |
|---|---:|---:|
| 2026-01 | 65 | 3 247 |
| 2026-02 | 65 | 3 247 |
| 2026-03 | 63 | 3 165 |
| 2026-04 | 63 | 3 165 |
| 2026-05 | 60 | 3 026 |
| 2026-06 | 56 | 2 783 |
| 2026-07 | 53 | 2 600 |
| 2026-08 | 52 | 2 550 |
| 2026-09 | 52 | 2 550 |

Esto contradice la expectativa de “76 comités” para 2026. No demuestra por sí solo un error: significa que, según las fechas cargadas, 24 de los 76/77 comités no tienen beneficiarios vigentes al cierre de septiembre. Hay que contrastarlo con el padrón oficial.

### Fallos y diferencias

1. No filtra si el comité/resolución estaba vigente en el mes; un comité vencido puede entrar si tiene beneficiarios.
2. Usa vigencia puntual al último día, no solapamiento con el mes. Un beneficiario que recibió durante parte del mes y terminó antes del último día no cuenta.
3. La presidenta es la actual (`getPresidentName()`), no la histórica del período.
4. Solo hay ración activa para 2026; 2024 y 2025 no pueden regenerarse desde este servicio.
5. `SchedulingService` (repartición con firma/programación) usa comités con estado vigente **actual**, no el estado del mes solicitado. Además cuenta beneficiarios por edad sin filtrar sus historiales de vigencia. Sus totales pueden diferir del reporte de repartición normal.
6. La fórmula redondea tarros y kilogramos por comité antes de sumar. Se debe confirmar si la norma exige redondeo, techo (`ceil`) o conservar decimales; una diferencia aquí cambia el total físico.

### Solución recomendada

- Crear un servicio único `PeriodSnapshotService` usado por padrón, repartición, programación y dashboard.
- Para un mes dado, obtener comités cuya resolución se solape, socios e historiales que se solapen y presidenta cuya directiva se solape.
- Definir oficialmente si la cobertura es “vigente cualquier día del mes” o “vigente al cierre”; aplicar una sola regla.
- Guardar raciones por año/mes o con `date_start/date_end`, no solo una fila activa por año, si la ración puede cambiar durante el año.
- Agregar una vista de conciliación: comités esperados, incluidos, excluidos y motivo de exclusión.

## 5. Tarjetas y gráficas de Inicio

`InicioController::panel()` recibe selectores de año/mes, pero los cuatro cards se calculan así:

- `Partner::count()`: todos los socios.
- `Beneficiarie::count()`: todos los beneficiarios.
- `Association::count()`: todos los comités.
- stock total: todos los ingresos menos todas las salidas; los cards por producto muestran el saldo del lote más reciente.

Por tanto, **los cards no cambian correctamente según mes/año**. La sección `socios_vs_beneficiarios` sí usa rangos históricos, la gráfica de PECOSAs usa período efectivo y productos distribuidos usa el año del lote de ingreso. Son semánticas diferentes dentro del mismo panel.

Otros problemas:

- `top_comites` cuenta todos los beneficiarios actuales sin rango de fechas.
- `stock_total` puede resultar afectado por el lote negativo de -56.
- “Productos distribuidos por mes” asigna la salida al mes de `detail_products.start_date`, no a `transactions.transaction_date` ni a la fecha efectiva de la PECOSA. Esto puede ser una decisión de negocio, pero el título no la explica.
- Las PECOSAs de los últimos siete días cambian de período; los conteos observados pueden superar el número de comités porque existen múltiples PECOSAs del mismo comité en un período. Por ejemplo, 2026-02 contiene 152 PECOSAs para 76 comités y 2026-05 contiene 154 para 77.

### Solución recomendada

- Añadir `stats_anio` y `stats_mes` al API y calcular socios, beneficiarios y comités mediante el mismo snapshot del período.
- Definir el stock histórico. Para “stock al cierre” se deben sumar ingresos hasta fin del período y restar salidas hasta esa fecha; para “stock actual” el card debe rotularse explícitamente y no depender del selector.
- Hacer que `top_comites` respete el período seleccionado.
- Renombrar o recalcular “productos distribuidos”: usar la fecha real de salida si se pretende medir distribución mensual.

## 6. Integridad de stock

Se encontró un saldo negativo:

| Lote | Producto | Inicio | Ingreso | Salida | Saldo |
|---:|---|---|---:|---:|---:|
| 16 | HOJUELA DE QUINUA | 2019-08-01 | 4 020 | 4 076 | -56 |

Antes de corregirlo se debe conciliar el lote contra ingresos, PECOSAs, movimientos manuales y posibles duplicados. No debe ajustarse borrando movimientos. La corrección debe ser un movimiento de regularización auditado o la reparación del vínculo/cantidad incorrecta con evidencia documental.

## Información que se debe juntar

1. Padrón oficial mensual por comité, al menos una muestra de cierre por cada año 2019-2026.
2. Historial real de altas/bajas de socios y beneficiarios 2019-2023.
3. Resoluciones por comité con fecha exacta de inicio y término, incluyendo renovaciones.
4. Historial de presidentas por comité: DNI, resolución/directiva, fecha de inicio y fin.
5. PECOSAs físicas o archivos fuente, especialmente las 2 sin detalle y una muestra por mes con PECOSAs duplicadas.
6. Regla oficial del corte de últimos siete días: confirmar si aplica a todos los reportes.
7. Regla de ración y redondeo: gramos/ml diarios, días atendidos, conversión por tarro/caja/saco y método de redondeo.
8. Cantidad oficial de comités habilitados por mes y motivo de alta/baja.
9. Inventario inicial, ingresos y salidas del lote 16 para explicar la diferencia de 56.
10. Definición de los cards: “actual”, “vigente al cierre” o “vigente en cualquier momento del mes”.

## Plan de corrección priorizado

### Prioridad 1: evitar documentos nuevos incorrectos

- Corregir resolución histórica de presidenta en PECOSA.
- Unificar filtro mensual en padrón y repartición.
- Mostrar en repartición los comités excluidos y la razón.
- Corregir las pruebas de creación de PECOSA y presidenta hasta que pasen.

### Prioridad 2: reparar historia

- Importar vigencias 2019-2023.
- Corregir el padrón histórico de comités para consultar el pivote de resoluciones.
- Ejecutar backfill seguro de presidentas y solicitantes de PECOSAs.
- Conciliar las 2 PECOSAs sin detalle y el lote negativo.

### Prioridad 3: consistencia de indicadores

- Aplicar el período a cards y top de comités.
- Implementar stock a fecha de corte o rotular el stock como actual.
- Unificar la programación con firma con el mismo motor de snapshot.

## Pruebas y verificación ejecutadas

- Todas las migraciones del ambiente local figuran aplicadas.
- Suite: **180 pruebas aprobaron y 22 fallaron**.
- Fallos directamente relevantes: creación/actualización/eliminación de PECOSA, vínculo de salida con stock, carga de presidenta en opciones, creación de socio y algunos productos.
- Otros fallos son deuda de la suite (factories de autenticación usan la columna inexistente `users.name`, y una prueba intenta crear `positions` dos veces). Aunque no todos son fallos funcionales del PVL, impiden usar la suite completa como garantía de regresión.

## Criterios de aceptación propuestos

- Para cada mes de control: total del padrón = total de beneficiarios de repartición = suma de beneficiarios snapshot de los comités incluidos, salvo excepciones documentadas.
- Un comité se incluye únicamente si su resolución cubre el período y tiene población atendible según la regla oficial.
- Toda PECOSA nueva conserva nombres, DNI, comité, dirección, productos, unidades, precios y cantidad de beneficiarios como snapshot inmutable.
- Toda PECOSA nueva tiene presidenta histórica o una excepción explícita.
- Ningún lote puede terminar con saldo negativo.
- Los cards muestran el período aplicado o se rotulan inequívocamente como datos actuales/globales.

