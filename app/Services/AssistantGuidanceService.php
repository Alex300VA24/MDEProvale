<?php

namespace App\Services;

use App\Support\AssistantAccess;
use App\Support\ModuleCatalog;
use Illuminate\Support\Str;

class AssistantGuidanceService
{
    private const DOMAIN_TERMS = [
        'beneficiario', 'beneficiarios', 'comite', 'consultar', 'ingreso', 'kardex',
        'movimiento', 'notificacion', 'padron', 'pecosa', 'pecosas',
        'presidenta', 'producto', 'productos', 'racion', 'reconocimiento',
        'reparticion', 'reporte', 'reportes', 'responsable', 'socia', 'socias',
        'socio', 'socios', 'stock', 'usuario',
    ];

    /**
     * Guía local de navegación. Cada flujo exige el módulo correspondiente: sin
     * acceso, devuelve el aviso del rol en lugar de los pasos.
     */
    public function answer(string $question, ?AssistantAccess $access = null): ?string
    {
        $access ??= AssistantAccess::unrestricted();
        $question = $this->normalizeQuestion($question);

        // El tema de reportes se resuelve antes que cualquier flujo por entidad:
        // "creame un reporte de pecosas" NO es la guía para registrar una pecosa.
        if ($this->hasAny($question, ['reporte', 'informe', 'descargar pdf', 'en pdf', 'exportar', 'imprimir'])) {
            if ($denied = $access->denial(ModuleCatalog::REPORTES)) {
                return $denied;
            }

            return "Sobre los reportes en PDF:\n"
                . "- Para el informe trimestral de Contraloría, abre Reportes, elige trimestre y año y revisa los gráficos.\n"
                . "- Pulsa Analizar con IA para obtener análisis, conclusiones y recomendaciones, y descarga el informe en Word o PDF.\n"
                . "- El informe solo incluye lo que el sistema registra; financiamiento, gastos operativos, certificados y código de envío no están disponibles.\n"
                . "- Para un reporte genérico, pídemelo con una sola entidad y un periodo simple, por ejemplo: crea un reporte de pecosas de este mes.\n"
                . "- Te dejaré el botón Ver reporte para abrirlo en una pestaña nueva.";
        }

        if ($this->hasAll($question, ['comite', 'benefici']) && $this->hasAny($question, ['mas', 'mayor', 'top'])) {
            return "Para consultar el comité con más beneficiarios:\n"
                . "1. Abre Inicio en el menú lateral.\n"
                . "2. Busca el gráfico \"Top Comités\".\n"
                . "3. Compara las barras: están ordenadas por cantidad de beneficiarios.\n"
                . "Si no aparecen datos, aún no existen comités con beneficiarios registrados.";
        }

        if (str_contains($question, 'padron') && $this->hasAny($question, ['club de madre', 'comite'])) {
            return $access->denial(ModuleCatalog::COMITES)
                ?? "Para generar el padrón de Clubes de Madres por comité:\n"
                . "1. Abre Comités y Resoluciones > Comités.\n"
                . "2. Abre el menú Más acciones y pulsa Padrón.\n"
                . "3. Selecciona el mes y el año que deseas consultar.\n"
                . "4. Pulsa Generar Padrón para abrir el PDF.\n"
                . "También puedes usar Inicio > Acciones Rápidas > Padrón Comité.";
        }

        if (str_contains($question, 'padron') && $this->hasAny($question, ['socio', 'benefici'])) {
            return $access->denial(ModuleCatalog::SOCIOS_BENEFICIARIOS)
                ?? "Para ver el padrón de beneficiarios:\n"
                . "1. Abre Socios y Beneficiarios.\n"
                . "2. En la parte superior, pulsa Generar Padrón.\n"
                . "3. Selecciona los filtros solicitados.\n"
                . "4. Genera el documento para revisarlo, imprimirlo o descargarlo.\n"
                . "También puedes usar Inicio > Acciones Rápidas > Padrón Beneficiarios.";
        }

        if ($this->hasAny($question, ['registrar socio', 'crear socio', 'registrar benefici', 'crear benefici', 'registrar persona'])) {
            return $access->denial(ModuleCatalog::SOCIOS_BENEFICIARIOS)
                ?? "Para registrar una familia correctamente:\n"
                . "1. Abre Socios y Beneficiarios > Personas y registra los datos personales.\n"
                . "2. Abre la pestaña Socios y crea al representante usando esa persona.\n"
                . "3. Abre Beneficiarios, registra cada beneficiario y vincúlalo con el socio.\n"
                . "4. Revisa la ficha antes de generar el padrón.";
        }

        if ($this->hasAny($question, ['pecosa', 'entrega de producto', 'comprobante de salida'])) {
            return $access->denial(ModuleCatalog::PECOSAS)
                ?? "Para registrar una pecosa:\n"
                . "1. Abre Pecosas y Repartición > Pecosas.\n"
                . "2. Pulsa Registrar Pecosa.\n"
                . "3. Selecciona comité, responsables, fecha y productos.\n"
                . "4. Revisa cantidades y stock antes de guardar.\n"
                . "5. Genera el comprobante de la pecosa.";
        }

        if ($this->hasAny($question, ['programacion de entrega', 'programacion'])) {
            return $access->denial(ModuleCatalog::PROGRAMACION_ENTREGA)
                ?? "Para consultar la programación de entrega:\n"
                . "1. Abre Productos y Programación de Entrega > Programación de Entrega.\n"
                . "2. Selecciona el año y el mes.\n"
                . "3. Revisa las cantidades programadas por comité y producto.";
        }

        // Los movimientos ya no existen como módulo: el kardex vive en Registro de Ingreso.
        if ($this->hasAny($question, ['kardex', 'movimiento', 'ingreso', 'ficha de control'])) {
            return $access->denial(ModuleCatalog::REGISTRO_INGRESO)
                ?? "Para registrar o revisar ingresos de productos:\n"
                . "1. Abre Productos y Programación de Entrega > Registro de Ingreso.\n"
                . "2. Pulsa Registrar Ingreso e indica producto, cantidad y fecha.\n"
                . "3. Consulta el kardex para revisar el saldo de cada producto.\n"
                . "4. Usa Ficha de Control Interno si necesitas el respaldo impreso.";
        }

        if ($this->hasAny($question, ['reparticion', 'distribucion'])) {
            return $access->denial(ModuleCatalog::REPARTICION)
                ?? "Para revisar la repartición:\n"
                . "1. Abre Pecosas y Repartición > Repartición.\n"
                . "2. Selecciona año y mes.\n"
                . "3. Revisa el cálculo basado en la ración vigente y descarga el PDF.";
        }

        if ($this->hasAny($question, ['stock', 'producto', 'leche', 'hojuela'])) {
            return $access->denial(ModuleCatalog::PRODUCTOS)
                ?? "Para consultar productos y stock:\n"
                . "1. Abre Productos y Programación de Entrega > Productos.\n"
                . "2. Busca el producto por nombre.\n"
                . "3. Revisa su stock.\n"
                . "Para crear uno, pulsa Registrar Producto y completa nombre, unidad de medida y presentación.";
        }

        if ($this->hasAny($question, ['racion', 'responsable', 'subgerente', 'encargado'])) {
            return $access->denial(ModuleCatalog::RESPONSABLES_RACIONES)
                ?? "Para configurar responsables o raciones:\n"
                . "1. Abre Responsables y Raciones.\n"
                . "2. En Responsables, registra o actualiza al encargado correspondiente.\n"
                . "3. En Raciones, crea la ración del año.\n"
                . "4. Indica gramos de hojuelas y mililitros de leche por beneficiario.";
        }

        if ($this->hasAny($question, ['supervision', 'supervisar'])) {
            return $access->denial(ModuleCatalog::SUPERVISION_COMITES)
                ?? "Para registrar una supervisión:\n"
                . "1. Abre Supervisión de Comités.\n"
                . "2. Elige el mes y programa la visita del comité.\n"
                . "3. Adjunta el acta o los documentos de la visita.\n"
                . "4. Usa Acta de Supervisión si necesitas el formato impreso.";
        }

        if ($this->hasAny($question, ['evaluacion nutricional', 'nutricional'])) {
            return $access->denial(ModuleCatalog::EVALUACION_NUTRICIONAL)
                ?? "Para la evaluación nutricional:\n"
                . "1. Abre Evaluación Nutricional.\n"
                . "2. Elige el comité.\n"
                . "3. Revisa la clasificación de sus beneficiarios y descarga el acta.";
        }

        if ($this->hasAny($question, ['reconocimiento', 'resolucion'])) {
            return $access->denial(ModuleCatalog::RESOLUCIONES)
                ?? "Para gestionar una resolución de reconocimiento:\n"
                . "1. Abre Comités y Resoluciones > Resoluciones.\n"
                . "2. Registra la resolución y el comité reconocido.\n"
                . "3. Valida los datos mediante la búsqueda externa.\n"
                . "4. Descarga el documento o abre la vista previa.";
        }

        if ($this->hasAny($question, ['comite', 'club de madre', 'presidenta'])) {
            return $access->denial(ModuleCatalog::COMITES)
                ?? "Para gestionar un comité:\n"
                . "1. Abre Comités y Resoluciones > Comités.\n"
                . "2. Registra nombre, zona y demás datos solicitados.\n"
                . "3. Usa Asignar Cargo y selecciona PRESIDENTA para designar a la responsable.\n"
                . "4. Pulsa Generar Padrón para consultar sus beneficiarios.";
        }

        if ($this->hasAny($question, ['perfil', 'contrasena', 'mi cuenta'])) {
            return $access->denial(ModuleCatalog::PERFIL)
                ?? "Para actualizar tu perfil:\n"
                . "1. Abre el menú de tu usuario, arriba a la derecha.\n"
                . "2. Pulsa Perfil.\n"
                . "3. Actualiza tus datos o tu contraseña y guarda los cambios.";
        }

        if ($this->hasAny($question, ['usuario', 'rol', 'permiso', 'modulo', 'notificacion'])) {
            return $access->denial(ModuleCatalog::SISTEMA)
                ?? 'Estas opciones están en Sistema. Abre Sistema y elige Usuarios, Roles, Módulos o Notificaciones. Si la opción no aparece, solicita acceso al administrador.';
        }

        return null;
    }

    /**
     * Devuelve alternativas accionables cuando no existe una intención segura.
     * El cliente las muestra como respuestas rápidas y espera la elección del
     * usuario antes de intentar resolver la consulta.
     *
     * @return array{respuesta:string,sugerencias:array<int,array{label:string,prompt:string}>}
     */
    public function clarification(string $question): array
    {
        $question = $this->normalizeQuestion($question);

        if ($this->hasAny($question, ['reporte', 'informe', 'pdf', 'exportar'])) {
            $suggestions = [
                ['label' => 'Reporte de beneficiarios', 'prompt' => 'Quiero un reporte de beneficiarios.'],
                ['label' => 'Reporte de pecosas', 'prompt' => 'Quiero un reporte de pecosas.'],
                ['label' => 'Reporte de productos', 'prompt' => 'Quiero un reporte de productos.'],
            ];
        } elseif ($this->hasAny($question, ['registrar', 'crear', 'nuevo', 'nueva', 'agregar'])) {
            $suggestions = [
                ['label' => 'Registrar una socia', 'prompt' => '¿Cómo registro una nueva socia?'],
                ['label' => 'Registrar un beneficiario', 'prompt' => '¿Cómo registro un beneficiario?'],
                ['label' => 'Registrar una pecosa', 'prompt' => '¿Cómo registro una nueva pecosa?'],
            ];
        } else {
            $suggestions = [
                ['label' => 'Consultar un comité', 'prompt' => 'Quiero consultar los datos de un comité.'],
                ['label' => 'Consultar beneficiarios', 'prompt' => 'Quiero consultar beneficiarios.'],
                ['label' => 'Consultar pecosas', 'prompt' => 'Quiero consultar pecosas de un periodo.'],
            ];
        }

        return [
            'respuesta' => "Necesito confirmar tu consulta:\n\n¿Quisiste decir alguna de estas opciones?",
            'sugerencias' => $suggestions,
        ];
    }

    /** Resumen de lo que el usuario puede hacer según los módulos de su rol. */
    public function overview(?AssistantAccess $access = null): string
    {
        $access ??= AssistantAccess::unrestricted();

        $tasks = array_values(array_filter([
            $access->allows(ModuleCatalog::SOCIOS_BENEFICIARIOS) ? 'registrar socios o beneficiarios y generar padrones' : null,
            $access->allowsAny([ModuleCatalog::COMITES, ModuleCatalog::RESOLUCIONES]) ? 'consultar comités y resoluciones' : null,
            $access->allows(ModuleCatalog::PRODUCTOS) ? 'consultar productos y stock' : null,
            $access->allows(ModuleCatalog::REGISTRO_INGRESO) ? 'registrar ingresos de productos' : null,
            $access->allows(ModuleCatalog::PECOSAS) ? 'registrar pecosas' : null,
            $access->allows(ModuleCatalog::REPARTICION) ? 'revisar la repartición mensual' : null,
            $access->allows(ModuleCatalog::RESPONSABLES_RACIONES) ? 'configurar raciones' : null,
        ]));

        if ($tasks === []) {
            return "Aún no tienes módulos habilitados.\nSolicita acceso al administrador para poder recibir guías.";
        }

        return "Puedo guiarte paso a paso en estas tareas:\n"
            . implode('', array_map(fn (string $task) => "- {$task};\n", $tasks))
            . "- consultar datos y generar reportes desde el Asistente PROVALE (según tus permisos).\n"
            . "Escribe qué deseas hacer, por ejemplo: \"¿Cuántos comités activos hay este mes?\"";
    }

    private function hasAny(string $question, array $terms): bool
    {
        foreach ($terms as $term) {
            if (str_contains($question, $term)) {
                return true;
            }
        }

        return false;
    }

    private function hasAll(string $question, array $terms): bool
    {
        foreach ($terms as $term) {
            if (! str_contains($question, $term)) {
                return false;
            }
        }

        return true;
    }

    private function normalizeQuestion(string $question): string
    {
        $question = Str::lower(Str::ascii(trim($question)));

        return (string) preg_replace_callback('/\b[a-z]{5,}\b/', function (array $match) {
            $word = $match[0];
            $best = $word;
            $bestDistance = PHP_INT_MAX;

            foreach (self::DOMAIN_TERMS as $term) {
                $distance = levenshtein($word, $term);
                $allowed = strlen($term) >= 9 ? 2 : 1;

                if ($distance <= $allowed && $distance < $bestDistance) {
                    $best = $term;
                    $bestDistance = $distance;
                }
            }

            return $best;
        }, $question);
    }
}
