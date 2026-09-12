<?php

namespace App\Services;

use Illuminate\Support\Str;

class AssistantGuidanceService
{
    private const DOMAIN_TERMS = [
        'beneficiario', 'beneficiarios', 'comite', 'consultar', 'kardex',
        'movimiento', 'notificacion', 'padron', 'pecosa', 'pecosas',
        'presidenta', 'producto', 'productos', 'racion', 'reconocimiento',
        'reparticion', 'reporte', 'reportes', 'responsable', 'socia', 'socias',
        'socio', 'socios', 'stock', 'usuario',
    ];

    public function answer(string $question): ?string
    {
        $question = $this->normalizeQuestion($question);

        // El tema de reportes se resuelve antes que cualquier flujo por entidad:
        // "creame un reporte de pecosas" NO es la guía para registrar una pecosa.
        if ($this->hasAny($question, ['reporte', 'informe', 'descargar pdf', 'en pdf', 'exportar', 'imprimir'])) {
            return "Sobre los reportes en PDF:\n"
                . "- No existe una pantalla para armar reportes a mano: los genero yo cuando me lo pides de forma explícita.\n"
                . "- Pídelo con una sola entidad y un periodo simple, por ejemplo: crea un reporte de pecosas de este mes, o crea un reporte de beneficiarios del comité (nombre).\n"
                . "- Te dejaré el botón Ver reporte para abrirlo en una pestaña nueva; desde ahí lo imprimes o lo guardas en PDF.\n"
                . "- Si necesitas cruzar varios datos o columnas especiales, dime primero qué dato principal y qué periodo necesitas.";
        }

        if ($this->hasAll($question, ['comite', 'benefici']) && $this->hasAny($question, ['mas', 'mayor', 'top'])) {
            return "Para consultar el comité con más beneficiarios:\n"
                . "1. Abre Inicio en el menú lateral.\n"
                . "2. Busca el gráfico \"Top Comités\".\n"
                . "3. Compara las barras: están ordenadas por cantidad de beneficiarios.\n"
                . "Si no aparecen datos, aún no existen comités con beneficiarios registrados.";
        }

        if (str_contains($question, 'padron') && $this->hasAny($question, ['club de madre', 'comite'])) {
            return "Para generar el padrón de Clubes de Madres por comité:\n"
                . "1. Abre Comités y Reconocimientos > Comités.\n"
                . "2. Abre el menú Más acciones y pulsa Padrón.\n"
                . "3. Selecciona el mes y el año que deseas consultar.\n"
                . "4. Pulsa Generar Padrón para abrir el PDF.\n"
                . "También puedes usar Inicio > Acciones Rápidas > Padrón Comité.";
        }

        if (str_contains($question, 'padron') && $this->hasAny($question, ['socio', 'benefici'])) {
            return "Para ver el padrón de beneficiarios:\n"
                . "1. Abre Socios y Beneficiarios.\n"
                . "2. En la parte superior, pulsa Generar Padrón.\n"
                . "3. Selecciona los filtros solicitados.\n"
                . "4. Genera el documento para revisarlo, imprimirlo o descargarlo.\n"
                . "También puedes usar Inicio > Acciones Rápidas > Padrón Beneficiarios.";
        }

        if ($this->hasAny($question, ['registrar socio', 'crear socio', 'registrar benefici', 'crear benefici', 'registrar persona'])) {
            return "Para registrar una familia correctamente:\n"
                . "1. Abre Socios y Beneficiarios > Personas y registra los datos personales.\n"
                . "2. Abre la pestaña Socios y crea al representante usando esa persona.\n"
                . "3. Abre Beneficiarios, registra cada beneficiario y vincúlalo con el socio.\n"
                . "4. Revisa la ficha antes de generar el padrón.";
        }

        if ($this->hasAny($question, ['pecosa', 'entrega de producto', 'comprobante de salida'])) {
            return "Para registrar una pecosa:\n"
                . "1. Abre Productos y Pecosas > Pecosas.\n"
                . "2. Pulsa Nueva Pecosa.\n"
                . "3. Selecciona comité, responsables, fecha y productos.\n"
                . "4. Revisa cantidades y stock antes de guardar.\n"
                . "5. Genera el comprobante o la programación de entrega.";
        }

        if ($this->hasAny($question, ['stock', 'producto', 'leche', 'hojuela'])) {
            return "Para consultar productos y stock:\n"
                . "1. Abre Productos y Pecosas > Productos.\n"
                . "2. Busca el producto por nombre.\n"
                . "3. Revisa su stock y sus entradas o salidas.\n"
                . "Para crear uno, pulsa Nuevo Producto y completa nombre, unidad de medida y presentación.";
        }

        if ($this->hasAny($question, ['kardex', 'movimiento', 'reparticion', 'distribucion'])) {
            return "Para revisar movimientos o repartición:\n"
                . "1. Abre Movimientos y Repartición.\n"
                . "2. Usa Kardex para consultar o registrar entradas y salidas.\n"
                . "3. En Repartición, selecciona año y mes.\n"
                . "4. Revisa el cálculo basado en la ración vigente y descarga el PDF.";
        }

        if ($this->hasAny($question, ['racion', 'responsable', 'subgerente', 'encargado'])) {
            return "Para configurar responsables o raciones:\n"
                . "1. Abre Responsables y Raciones.\n"
                . "2. En Responsables, registra o actualiza al encargado correspondiente.\n"
                . "3. En Raciones, crea la ración del año.\n"
                . "4. Indica gramos de hojuelas y mililitros de leche por beneficiario.";
        }

        if ($this->hasAny($question, ['reconocimiento', 'resolucion'])) {
            return "Para gestionar un reconocimiento:\n"
                . "1. Abre Comités y Reconocimientos > Reconocimientos.\n"
                . "2. Registra la resolución y el comité reconocido.\n"
                . "3. Valida los datos mediante la búsqueda externa.\n"
                . "4. Descarga el documento o abre la vista previa.";
        }

        if ($this->hasAny($question, ['comite', 'club de madre', 'presidenta'])) {
            return "Para gestionar un comité:\n"
                . "1. Abre Comités y Reconocimientos > Comités.\n"
                . "2. Registra nombre, zona y demás datos solicitados.\n"
                . "3. Usa Asignar Presidenta para designar a la responsable.\n"
                . "4. Pulsa Generar Padrón para consultar sus beneficiarios.";
        }

        if ($this->hasAny($question, ['usuario', 'rol', 'permiso', 'modulo', 'notificacion'])) {
            return "Estas opciones están en Sistema y requieren permisos administrativos. Abre Sistema y elige Usuarios, Roles, Módulos o Notificaciones. Si la opción no aparece, solicita acceso al administrador.";
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

    public function overview(): string
    {
        return "Puedo guiarte paso a paso en estas tareas:\n"
            . "- registrar socios o beneficiarios;\n"
            . "- generar padrones;\n"
            . "- consultar comités y stock;\n"
            . "- registrar pecosas;\n"
            . "- revisar movimientos y reparticiones;\n"
            . "- configurar raciones;\n"
            . "- consultar datos y generar reportes desde el Asistente PROVALE.\n"
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
