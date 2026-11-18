<?php

namespace App\Services;

use App\Models\Pecosa;
use App\Support\AssistantAccess;
use App\Support\ModuleCatalog;
use Illuminate\Support\Carbon;
use Illuminate\Support\Str;

/**
 * Decide si un mensaje del asistente es una solicitud explícita de reporte y,
 * en ese caso, construye la URL del generador (`reportes.generar`) con una sola
 * entidad y filtros simples. Si la solicitud es ambigua o combinada, devuelve
 * una guía para que el usuario llegue al resultado dentro del sistema.
 */
class AssistantReportService
{
    /** Columnas por defecto de cada entidad (espejo de ReportGeneratorController). */
    private const COLUMNAS = [
        'comites' => ['code', 'name', 'zona', 'sector', 'presidenta', 'beneficiarios', 'estado'],
        'beneficiarios' => ['dni', 'nombre', 'parentesco', 'socia', 'comite'],
        'productos' => ['code', 'title', 'uom', 'stock', 'estado'],
        'pecosas' => ['pecosa_number', 'delivery_date', 'comite', 'presidenta', 'beneficiarios', 'estado'],
        'movimientos' => ['transaction_date', 'document_number', 'producto', 'tipo', 'quantity', 'total_price'],
    ];

    private const ETIQUETA = [
        'comites' => 'comités / club de madres',
        'beneficiarios' => 'socios y beneficiarios',
        'productos' => 'productos',
        'pecosas' => 'pecosas',
        // El módulo "Movimientos" ya no existe: solo queda el Registro de Ingreso.
        'movimientos' => 'registro de ingreso',
    ];

    /** Submódulo cuyos datos expone cada entidad del reporte. */
    private const MODULO = [
        'comites' => ModuleCatalog::COMITES,
        'beneficiarios' => ModuleCatalog::SOCIOS_BENEFICIARIOS,
        'productos' => ModuleCatalog::PRODUCTOS,
        'pecosas' => ModuleCatalog::PECOSAS,
        'movimientos' => ModuleCatalog::REGISTRO_INGRESO,
    ];

    public function __construct(private AssistantDataService $datos)
    {
    }

    /**
     * @return array{tipo:string,respuesta:string,entidad?:string,accion?:array}|null
     */
    public function detect(string $question, ?AssistantAccess $access = null): ?array
    {
        $access ??= AssistantAccess::unrestricted();

        $q = Str::lower(Str::ascii(trim($question)));

        // Pregunta de navegación ("¿cómo genero...?", "¿dónde descargo...?"): no es
        // una orden de crear; que la resuelva la guía, no el generador.
        if (preg_match('/\b(como|donde|pasos para|se puede|puedo|explicame|ensename)\b/', $q)) {
            return null;
        }

        // Raíces de verbo (sin exigir límite final): cubre "creame", "generame",
        // "hazme", "elaborame", "muestrame", "pasame", etc.
        $pideCrear = (bool) preg_match('/\b(crea|gener|elabor|arma|haz|hag|prepar|export|descarg|emit|saca|sacar|imprim|list|muestr|dame|damelo|pas[ae]me|quiero|necesito|obten)/', $q);
        $mencionaReporte = (bool) preg_match('/\b(reporte|reportes|padron|padrones|listado|listados|informe|informes|pdf|documento)\b/', $q);

        if (! $pideCrear || ! $mencionaReporte) {
            return null;
        }

        $entidades = $this->entidadesMencionadas($q);

        // "beneficiarios del comité X" / "pecosas del comité X": el comité es un
        // filtro, no una segunda entidad del reporte.
        if (in_array('comites', $entidades, true)
            && array_intersect(['beneficiarios', 'pecosas'], $entidades)
            && preg_match('/\b(de|del|por|para|en)\s+(el\s+)?(club|comite)/', $q)) {
            $entidades = array_values(array_diff($entidades, ['comites']));
        }

        // El reporte se abre en /reportes/generar (módulo Reportes) y expone datos de la entidad:
        // el rol necesita ambos permisos.
        if ($denied = $access->denial(ModuleCatalog::REPORTES)) {
            return ['tipo' => 'acceso', 'respuesta' => $denied];
        }
        foreach ($entidades as $entidad) {
            if ($denied = $access->denial(self::MODULO[$entidad])) {
                return ['tipo' => 'acceso', 'respuesta' => $denied];
            }
        }

        $esComplejo = (bool) preg_match('/\b(columna|columnas|agrupar|agrupa|cruza|cruzar|combina|combinar|combinado|union|juntar|personalizad|comparar|comparativo|varios|multiples|todos los meses)\b/', $q);

        if ($esComplejo || count($entidades) !== 1) {
            return $this->guia($entidades, $access);
        }

        $entidad = $entidades[0];
        [$filtros, $descripcion] = $this->filtros($entidad, $q);

        $params = [
            'entidades' => [$entidad],
            'columnas' => [$entidad => self::COLUMNAS[$entidad]],
        ];
        foreach ($filtros as $clave => $valor) {
            $params['filtros'][$entidad][$clave] = $valor;
        }

        // Ruta relativa: el frontend antepone window.APP_URL al abrir la pestaña.
        $url = '/reportes/generar?'.http_build_query($params);
        $detalle = $descripcion ? ' ('.implode(', ', $descripcion).')' : '';

        return [
            'tipo' => 'reporte',
            'entidad' => $entidad,
            'respuesta' => 'Preparé el reporte de '.self::ETIQUETA[$entidad].$detalle.".\n\n"
                ."- Pulsa Ver reporte para abrirlo en una pestaña nueva.\n"
                .'- Desde esa vista puedes imprimirlo o guardarlo en PDF.',
            'accion' => ['tipo' => 'reporte', 'url' => $url, 'label' => 'Ver reporte'],
        ];
    }

    /**
     * @return list<string>
     */
    private function entidadesMencionadas(string $q): array
    {
        $mapa = [
            'comites' => ['comite', 'club de madres', 'clubes'],
            'beneficiarios' => ['beneficiari', 'socio', 'socia'],
            'productos' => ['producto', 'stock', 'alimento'],
            'pecosas' => ['pecosa'],
            'movimientos' => ['movimiento', 'kardex', 'transaccion', 'registro de ingreso', 'ingreso'],
        ];

        $encontradas = [];
        foreach ($mapa as $entidad => $terminos) {
            foreach ($terminos as $termino) {
                if (str_contains($q, $termino)) {
                    $encontradas[] = $entidad;
                    break;
                }
            }
        }

        return array_values(array_unique($encontradas));
    }

    /**
     * @return array{0:array<string,string>,1:list<string>}
     */
    private function filtros(string $entidad, string $q): array
    {
        $filtros = [];
        $descripcion = [];

        if (in_array($entidad, ['beneficiarios', 'pecosas'], true)) {
            $comite = $this->datos->buscarComitePublico($q);
            if ($comite) {
                $filtros['association_id'] = (string) $comite->id;
                $descripcion[] = "comité {$comite->name}";
            }
        }

        if ($entidad === 'movimientos') {
            // Registro de Ingreso: solo ingresos (tipo de transacción 1).
            $filtros['type_transaction_id'] = '1';
        }

        if (in_array($entidad, ['pecosas', 'movimientos'], true)) {
            [$anio, $mes, $etiqueta, $mencionado] = $this->datos->periodoPublico($q);

            if ($mencionado) {
                if ($entidad === 'pecosas') {
                    [$inicio, $fin] = Pecosa::deliveryPeriodRange($anio, $mes);
                } else {
                    $inicio = Carbon::create($anio, $mes, 1)->startOfMonth();
                    $fin = $inicio->copy()->endOfMonth();
                }
                $filtros['date_from'] = $inicio->toDateString();
                $filtros['date_to'] = $fin->toDateString();
                $descripcion[] = $etiqueta;
            } elseif (preg_match('/\b(20\d{2})\b/', $q, $coincide) || preg_match('/\b(ano|anio|anual)\b/', $q)) {
                // Año completo: "de todo el año", "del año 2026", "reporte anual".
                $anioObjetivo = isset($coincide[1]) ? (int) $coincide[1] : (int) now()->year;
                $inicio = Carbon::create($anioObjetivo, 1, 1)->startOfDay();
                $fin = Carbon::create($anioObjetivo, 12, 31)->endOfDay();
                $filtros['date_from'] = $inicio->toDateString();
                $filtros['date_to'] = $fin->toDateString();
                $descripcion[] = "año {$anioObjetivo}";
            }
        }

        return [$filtros, $descripcion];
    }

    /**
     * @param  list<string>  $entidades
     * @return array{tipo:string,respuesta:string,sugerencias?:array<int,array{label:string,prompt:string}>}
     */
    private function guia(array $entidades, AssistantAccess $access): array
    {
        $multiples = count($entidades) > 1;

        // Solo se ofrecen entidades cuyo submódulo puede ver el usuario.
        $permitidas = array_values(array_filter(
            array_keys(self::MODULO),
            fn (string $entidad) => $access->allows(self::MODULO[$entidad])
        ));

        if ($entidades === []) {
            return [
                'tipo' => 'guia',
                'respuesta' => $permitidas === []
                    ? "Tu rol no tiene acceso a los datos de ningún reporte.\nSolicita el permiso al administrador."
                    : "Necesito confirmar el reporte:\n\n¿Quisiste decir alguna de estas opciones?",
                'sugerencias' => $this->sugerenciasReporte(array_slice(
                    array_values(array_intersect(['beneficiarios', 'pecosas', 'productos', 'comites', 'movimientos'], $permitidas)),
                    0,
                    3
                )),
            ];
        }

        $motivo = $multiples
            ? 'Esa solicitud tiene varios pasos porque cruza más de un tipo de dato ('.$this->listarEtiquetas($entidades).') y el reporte automático trabaja con una sola entidad a la vez.'
            : 'Esa solicitud tiene varios pasos porque pide columnas, agrupaciones o cruces personalizados que el reporte automático no arma por sí solo.';

        $texto = $motivo."\n\n"
            ."Para generártelo al instante necesito dos datos:\n"
            ."- ¿Qué dato principal necesitas: ".$this->listarEtiquetas($permitidas, ' o ')."?\n"
            ."- ¿De qué comité y de qué periodo (un mes o un año)?\n\n"
            ."Con esos datos te devuelvo el reporte y el botón Ver reporte para abrirlo en una pestaña nueva. Ejemplos que puedo generar directamente:\n"
            ."- crea un reporte de pecosas de este mes\n"
            ."- crea un reporte de beneficiarios del comité (nombre)\n"
            .'- crea un reporte de '.($access->allows(ModuleCatalog::REGISTRO_INGRESO) ? 'registro de ingreso' : 'pecosas').' del año 2026';

        $result = ['tipo' => 'guia', 'respuesta' => $texto];

        if ($multiples) {
            $result['sugerencias'] = $this->sugerenciasReporte(array_slice($entidades, 0, 3));
        }

        return $result;
    }

    /**
     * @param  list<string>  $entidades
     * @return array<int,array{label:string,prompt:string}>
     */
    private function sugerenciasReporte(array $entidades): array
    {
        return array_map(fn (string $entidad) => [
            'label' => 'Reporte de '.self::ETIQUETA[$entidad],
            'prompt' => 'Quiero un reporte de '.self::ETIQUETA[$entidad].'.',
        ], $entidades);
    }

    /**
     * @param  list<string>  $entidades
     */
    private function listarEtiquetas(array $entidades, string $separador = ', '): string
    {
        return implode($separador, array_map(fn ($entidad) => self::ETIQUETA[$entidad] ?? $entidad, $entidades));
    }
}
