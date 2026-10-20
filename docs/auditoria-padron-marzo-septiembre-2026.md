# Auditoría del padrón histórico: marzo-setiembre 2026

Fecha de procesamiento: 19 de octubre de 2026.

## Criterio aplicado

Los cards y los resúmenes mensuales muestran las relaciones que existen en el
archivo fuente y en la base de datos, sin reemplazarlas por un total depurado.
La cantidad de personas únicas y las anomalías se presentan únicamente como
información de auditoría.

No se inventan registros para alcanzar 3,872. Las filas duplicadas que existen
literalmente en el Excel se conservan; las filas vacías y los bloques ausentes
se documentan, pero no se completan con datos de otro registro o mes.

## Resultado final en base de datos

| Periodo | Comités | Socias registradas | Socias únicas | Beneficiarios registrados | Beneficiarios únicos |
|---|---:|---:|---:|---:|---:|
| Marzo 2026 | 77 | 2,598 | 2,596 | 3,872 | 3,867 |
| Abril 2026 | 77 | 2,597 | 2,595 | 3,872 | 3,870 |
| Mayo 2026 | 77 | 2,617 | 2,615 | 3,862 | 3,861 |
| Junio 2026 | 77 | 2,635 | 2,635 | 3,871 | 3,871 |
| Julio 2026 | 77 | 2,681 | 2,680 | 3,872 | 3,870 |
| Agosto 2026 | 76 | 2,653 | 2,652 | 3,822 | 3,820 |
| Setiembre 2026 | 77 | 2,714 | 2,712 | 3,870 | 3,868 |

## Observaciones por mes

### Marzo

El Excel contiene 3,872 filas. Cuatro claves
`beneficiario|socia|comité` aparecen dos veces y el seeder conserva ambas
ocurrencias:

- `93775401|43843452|030`
- `93824304|71836488|093`
- `94210807|48294572|170`
- `91938806|42011201|302`

El DNI beneficiario `92526981` también aparece en los comités 036 y 068. Por
eso el card muestra 3,872 relaciones y la auditoría informa 3,867 personas
únicas: hay cinco DNI duplicados en total.

### Abril

La clave `93824304|71836488|093` está repetida literalmente y se conservó su
segunda ocurrencia. El DNI `92526981` aparece en los comités 036 y 068. El card
muestra 3,872 relaciones y la auditoría, 3,870 personas únicas.

### Mayo

El archivo tiene diez filas numeradas sin DNI ni identidad, que no pueden
convertirse en personas o relaciones válidas. La BD contiene 3,862 relaciones.
El DNI `92317941` figura en los comités 256 y 258, por lo que el total depurado
es 3,861. En socias se conservaron 2,617 relaciones y hay dos DNI presentes en
más de un comité.

### Junio

Una fila numerada no contiene DNI ni identidad. No se creó una persona ficticia
para reemplazarla: quedaron 3,871 relaciones y 3,871 personas únicas. Las 2,635
socias no presentan duplicidad por DNI.

### Julio

No hay faltantes: contiene 3,872 relaciones. Los DNI `92396005` (comités 265 y
270) y `91879690` (comités 375 y 380) aparecen dos veces, de modo que la
auditoría informa 3,870 personas únicas.

### Agosto

El archivo fuente contiene 76 comités y 3,822 beneficiarios. Falta el bloque
completo del comité 019, que en julio tenía 50 beneficiarios, pero agosto se
conserva tal como fue entregado: **no se copiaron registros desde julio**.

Dentro de las 3,822 relaciones hay dos DNI repetidos: `92396005` (comités 265 y
270) y `91879690` (comités 375 y 380). Por eso el card muestra 3,822 y la nota
de auditoría indica dos duplicados y 3,820 personas únicas.

### Setiembre

El archivo contiene 2,714 relaciones de socias y 3,870 relaciones de
beneficiarios. Se conservaron las relaciones repetidas entre comités:

- Socia `76446533`, comités 075 y 078; beneficiario `94427855` en ambos.
- Socia `47210495`, comités 305 y 360; beneficiario `92763772` en ambos.

Los cards muestran 2,714 y 3,870; la auditoría informa 2,712 socias únicas y
3,868 beneficiarios únicos. El resumen del comité 040 declara 39 beneficiarios,
pero el bloque contiene 40 filas; el historial respeta las 40 filas efectivas.

## Comités con nombre compartido

Los siguientes comités no se fusionan: se identifican por código y dirección.
Los selectores muestran los tres datos y el gráfico de comités conserva el
código en la etiqueta y la dirección en el detalle.

| Códigos | Nombre compartido | Direcciones |
|---|---|---|
| 265 / 270 | CLEMENTINA PERALTA DE ACUÑA | Mz.I Lt.38 / Mz.K Lt.36, II Sector de Nuevo Jerusalén |
| 059 / 280 | VIRGEN DE LA PUERTA | Mz.25 Lt.5A, III Sector de Nuevo Jerusalén / Girardot N° 1543, Santa Verónica |
| 300 / 325 | ZOILA DE LA TORRE DE HAYA | Calle Esmeralda N° 350, Santa Verónica / Calle San Pedro N° 199-A, Jerusalén |

## Controles de reconstrucción

- Los JSON mensuales fueron generados con las herramientas existentes del
  proyecto.
- `TemporalBeneficiarieHistorySeeder` valida el total real esperado para cada
  mes y conserva las ocurrencias duplicadas literales de marzo y abril.
- `AssociationRosterPeriodSeeder` recalcula los totales desde las relaciones
  sembradas y valida los siete periodos.
- `FullDatabaseSeederTest` compara cada total mensual contra la fuente.
- La reconstrucción completa se ejecuta con `php artisan migrate:fresh --seed`.
