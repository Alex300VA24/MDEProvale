# Razón de la ausencia de presidentas y beneficiarios en PECOSAs

## Resumen

La ausencia de presidenta y total de beneficiarios a partir de la PECOSA
`260771` no se origina en la generación del PDF ni en la migración de ingresos.
El problema ocurre durante la migración de las PECOSAs: el sistema intenta
obtener esos datos desde un padrón mensual que no existe para los períodos de
octubre y noviembre de 2026.

Como resultado, las PECOSAs afectadas se guardaron con los campos
`president_name`, `managing_partner_name` y `beneficiaries_count` en `NULL`.
Posteriormente, el PDF utiliza esos mismos valores históricos y por eso muestra
vacíos tanto la presidenta como el número de beneficiarios.

## Corte exacto del problema

La PECOSA `260770` fue emitida el 16 de septiembre de 2026. Al no encontrarse en
los últimos siete días del mes, pertenece al período efectivo de septiembre.
El padrón de septiembre sí fue migrado, por lo que esta PECOSA conserva:

- Presidenta: Carmen Maura Benavides Villanueva.
- Total de beneficiarios: 55.

La PECOSA `260771`, en cambio, fue emitida el 29 de septiembre de 2026.
La aplicación considera que las PECOSAs emitidas en los últimos siete días de
un mes corresponden al reparto del mes siguiente. Por ello, esta PECOSA y las
siguientes emitidas el 29 de septiembre buscan el padrón de octubre.

El corte encontrado en la base de datos es el siguiente:

| PECOSAs | Fecha de emisión | Período efectivo buscado | Registros sin presidenta ni beneficiarios |
|---|---:|---:|---:|
| `260771` a `260846` | 29/09/2026 | Octubre de 2026 | 76 de 76 |
| `260847` a `260923` | 29/10/2026 | Noviembre de 2026 | 77 de 77 |

En total, las 153 PECOSAs comprendidas entre `260771` y `260923` tienen
`president_name` y `beneficiaries_count` en `NULL`.

## Regla del período efectivo

La regla se encuentra en `app/Models/Pecosa.php`, dentro del método
`effectiveDeliveryDate()`.

Cuando el día de emisión es posterior a `días del mes - 7`, la fecha se desplaza
al mes siguiente para determinar el período de reparto:

```php
return $date->day > $date->daysInMonth - 7
    ? $date->copy()->addMonthNoOverflow()->startOfDay()
    : $date->startOfDay();
```

Por esta regla:

- 16/09/2026 pertenece a septiembre.
- 29/09/2026 pertenece a octubre.
- 29/10/2026 pertenece a noviembre.

## Padrones mensuales disponibles

La tabla `association_roster_periods` contiene información desde marzo hasta
septiembre de 2026:

- Período mínimo: 01/03/2026.
- Período máximo: 01/09/2026.
- Total de registros mensuales: 538.

No se migraron padrones de octubre ni noviembre de 2026. Por eso no existe una
coincidencia exacta para las PECOSAs emitidas el 29 de septiembre y el 29 de
octubre.

## Funcionamiento de la migración de PECOSAs

El flujo vigente se ejecuta mediante `DatabaseSeeder`, que primero importa los
padrones mensuales y posteriormente ejecuta `PecosaSeeder`:

```php
$this->call(AssociationRosterPeriodSeeder::class);
// ...
$this->call(PecosaSeeder::class);
```

`PecosaSeeder` construye una colección de presidentas indexada por asociación y
período. Para cada PECOSA calcula el período efectivo y realiza una búsqueda
exacta:

```php
$effectivePeriod = Pecosa::effectiveDeliveryDate($row['delivery_date'])
    ->startOfMonth()
    ->toDateString();

$president = $presidentsByPeriod->get(
    $associationId . '|' . $effectivePeriod
);
```

Cuando no existe el padrón correspondiente, los datos se guardan como nulos:

```php
'managing_partner_name' => $president->president_name ?? null,
'president_name' => $president->president_name ?? null,
'beneficiaries_count' => $president->beneficiary_count ?? null,
```

Este seeder no utiliza las directivas como fuente alternativa ni calcula el
número de beneficiarios vigentes cuando falta el padrón mensual.

## Diferencia con el registro normal de PECOSAs

El servicio utilizado por la aplicación para registrar una PECOSA normalmente,
`PecosaService`, tiene una lógica más completa:

1. Busca la presidenta en el padrón del período efectivo.
2. Si no encuentra padrón, consulta la directiva vigente mediante
   `getPresidentaAt()`.
3. Calcula el número de beneficiarios de la asociación para la fecha de entrega.

Las directivas sí conservan presidentas vigentes después de septiembre. Por
ejemplo, el comité `005`, correspondiente a la PECOSA `260771`, conserva como
presidenta a Giovanna Elizabeth Paredes Esquivel con `date_end = NULL`.

El inconveniente es que `PecosaSeeder` inserta las PECOSAs directamente y no
reutiliza el mecanismo de respaldo de `PecosaService`.

## Por qué el backfill no corrigió los registros

La migración
`2026_09_13_000001_backfill_pecosa_presidents_from_roster_periods.php` fue
ejecutada, pero también exige una coincidencia exacta con
`association_roster_periods`.

Cuando no encuentra un padrón para el período calculado, simplemente omite la
PECOSA:

```php
if (! $president) {
    continue;
}
```

Por tanto, esta migración tampoco podía reparar las PECOSAs de octubre y
noviembre mientras esos padrones no existieran.

## Por qué el PDF muestra los campos vacíos

El PDF no vuelve a consultar la presidenta ni recalcula los beneficiarios. El
comprobante utiliza el snapshot histórico almacenado en la PECOSA:

```php
'num_mes' => $pecosa->beneficiaries_count ?? '',
'solicitante_nombre' => $pecosa->managing_partner_name
    ?: ($pecosa->president_name ?? ''),
```

Como esos campos se guardaron en `NULL`, el PDF imprime cadenas vacías. En
consecuencia, modificar únicamente la plantilla del PDF no solucionaría el dato
histórico faltante.

## Revisión de ingresos y movimientos de stock

La revisión de la base de datos encontró:

- 18 movimientos de ingreso.
- 1,384 movimientos de salida.
- 0 enlaces inconsistentes entre PECOSA, movimiento de salida y stock.

La migración de ingresos y los enlaces de inventario no son la causa de la
ausencia de presidentas o beneficiarios. El problema está limitado a los
snapshots históricos construidos al migrar las PECOSAs.

## Casos aislados anteriores

Además del corte masivo desde `260771`, existen cuatro PECOSAs anteriores con
presidenta vacía:

| PECOSA | Fecha | Comité |
|---|---:|---:|
| `260329` | 28/04/2026 | `080` |
| `260407` | 06/05/2026 | `080` |
| `260619` | 30/07/2026 | `019` |
| `260696` | 28/08/2026 | `390` |

Estos son casos puntuales de ausencia de una coincidencia válida en el padrón.
No explican el corte general desde `260771`, pero deben incluirse en una futura
regularización.

## Brecha en las pruebas automatizadas

La prueba `FullDatabaseSeederTest` compara la presidenta de una PECOSA con el
padrón mensual solamente cuando encuentra una coincidencia. Cuando el padrón no
existe, ejecuta `continue` y no reporta el problema.

Además, los totales mensuales esperados en esa prueba solamente cubren de marzo
a septiembre de 2026. Por esa razón, la migración pudo finalizar correctamente
aunque las 153 PECOSAs posteriores quedaran sin presidenta y sin beneficiarios.

## Corrección recomendada

La solución recomendada es:

1. Importar los padrones reales de octubre y noviembre de 2026.
2. Ejecutar un backfill sobre todas las PECOSAs con presidenta o beneficiarios
   nulos, incluyendo los cuatro casos aislados anteriores.
3. Centralizar la resolución de presidenta y beneficiarios para que
   `PecosaSeeder` y `PecosaService` utilicen la misma lógica.
4. Si no existe un padrón mensual, utilizar como respaldo la directiva vigente y
   calcular los beneficiarios activos para la fecha efectiva, siempre que esta
   regla sea aprobada funcionalmente.
5. Hacer que la migración falle o emita un error obligatorio cuando una PECOSA
   vaya a guardarse sin presidenta o sin total de beneficiarios.
6. Agregar pruebas específicas para las PECOSAs `260771` y `260847`, además de
   una validación que impida valores nulos en estos snapshots.

No se recomienda corregir únicamente el PDF. Primero deben repararse los datos
históricos almacenados en las PECOSAs y luego conservar el PDF como consumidor
del snapshot correspondiente al momento de la entrega.

## Corrección aplicada el 14 de noviembre de 2026

Se aplicó la migración
`2026_11_14_000002_separate_reused_019_committee_identity.php` con los
siguientes resultados:

- `SOL Y ARENA DE LAS PALMERITAS` conserva una identidad histórica interna
  `019-H` y el código oficial `019` hasta agosto de 2026.
- `ISABEL CRUZ DE MARCELO` es una identidad independiente y usa el código
  oficial `019` desde septiembre de 2026.
- El padrón de septiembre fue trasladado a Isabel: 30 socias, 40 beneficiarios
  y la presidenta Joselyn Farelly Rodríguez Saavedra.
- Las PECOSAS `260696`, `260773` y `260849` quedaron vinculadas a Isabel con
  código mostrado `019`, presidenta y 40 beneficiarios.
- El seeder de PECOSAS ahora usa como respaldo las altas, bajas y directivas
  vigentes cuando no existe un snapshot mensual importado.
- Después del último padrón importado, los socios y beneficiarios con vigencia
  abierta continúan activos hasta que el sistema registre expresamente su baja.

La PECOSA `260619` de Sol y Arena continúa sin snapshot porque el padrón de
agosto no existe. `260850 - LOS ANGELITOS` también permanece sin presidenta ni
beneficiarios porque no existe padrón ni altas registradas para ese comité.
