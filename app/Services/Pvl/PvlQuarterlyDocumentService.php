<?php

namespace App\Services\Pvl;

/**
 * Fuente única del contenido del informe trimestral. Devuelve bloques neutrales
 * que consumen la vista previa (React), el PDF (Blade/dompdf) y el Word (PhpWord).
 *
 * Tipos de bloque: title, meta, rule, h, p, ol, table, image, sign, note.
 */
class PvlQuarterlyDocumentService
{
    private const MONTHS = ['enero', 'febrero', 'marzo', 'abril', 'mayo', 'junio', 'julio', 'agosto', 'septiembre', 'octubre', 'noviembre', 'diciembre'];

    public const CHARTS = [
        'ingresos_mes' => 'Gráfico N.° 1. Ingresos por mes (S/)',
        'entregas_mes' => 'Gráfico N.° 2. Entregas por mes y producto',
        'distribucion_producto' => 'Gráfico N.° 3. Distribución por producto',
        'ingresos_producto' => 'Gráfico N.° 4. Ingresos por producto (S/)',
        'beneficiarios' => 'Gráfico N.° 5. Beneficiarios por categoría y zona',
    ];

    public function letterhead(): array
    {
        return [
            'municipalidad' => 'MUNICIPALIDAD DISTRITAL DE LA ESPERANZA',
            'creacion' => 'CREADO EL 29 DE ENERO DE 1965 - LEY N° 15418',
            'telefono' => 'Teléfono: 044 - 603501 Anexo 106',
            'area' => 'Alcaldía',
            'programa' => 'PROVALE',
        ];
    }

    public function build(array $report, ?array $analysis, array $meta): array
    {
        $meta = $this->meta($meta, $report);
        $number = $meta['number'] !== '' ? $meta['number'] : '____-'.$report['year'].'-MDE/GDS/SGPS';
        $subject = $meta['subject'] !== ''
            ? $meta['subject']
            : 'INFORMA REPORTES DEL SISTEMA DE CONTRALORÍA RESPECTO AL PROGRAMA VASO DE LECHE – '.$report['quarter_text'].' – '.$report['year'];
        $blank = '________________________';
        $t = $report['totals'];
        $qText = str_replace("TRIMESTRE", "trimestre", $report["quarter_text"]);

        $blocks = [
            ['type' => 'title', 'text' => 'INFORME N° '.$number],
            ['type' => 'meta', 'rows' => [
                ['A', [[$meta['to'] ?: $blank, true], [$meta['to_role'] ?: 'Cargo del destinatario', false]]],
                ['DE', [[$meta['from'] ?: $blank, true], [$meta['from_role'] ?: 'Programa Vaso de Leche', false]]],
                ['ASUNTO', [[$subject, true]]],
                ['FECHA', [[($meta['place'] ?: 'La Esperanza').', '.$meta['date'], false]]],
            ]],
            ['type' => 'rule'],
            ['type' => 'p', 'text' => "Por medio del presente me dirijo a usted, para saludarlo cordialmente y al mismo tiempo hacer de su conocimiento el resumen de la información trimestral de ingresos y de la ración distribuida por el Programa Vaso de Leche, correspondiente al {$qText} del año {$report['year']} ({$report['period_text']}), según lo registrado en el sistema PROVALE."],
        ];

        $section = 0;
        $figure = function (string $key) use (&$blocks, $analysis) {
            $blocks[] = ['type' => 'image', 'key' => $key, 'caption' => self::CHARTS[$key]];
            if (! empty($analysis['graficos'][$key])) {
                $blocks[] = ['type' => 'p', 'text' => $analysis['graficos'][$key]];
            }
        };
        $heading = function (string $title) use (&$blocks, &$section) {
            $section++;
            $blocks[] = ['type' => 'h', 'text' => $this->roman($section).'. '.$title];
        };

        $heading('Resumen del trimestre');
        if ($analysis && $analysis['resumen'] !== '') {
            $blocks[] = ['type' => 'p', 'text' => $analysis['resumen']];
        } else {
            $blocks[] = ['type' => 'p', 'text' => implode(' ', $report['considerandos'])];
        }
        $blocks[] = ['type' => 'table', 'caption' => 'Cuadro N.° 1. Resumen mensual',
            'head' => ['Mes', 'Ingresos', 'Valor (S/)', 'Líneas PECOSA', 'Cant. entregada', 'Comités', 'Beneficiarios'],
            'align' => ['l', 'r', 'r', 'r', 'r', 'r', 'r'],
            'rows' => array_map(fn (array $m) => [$m['name'], $this->n0($m['ingresos_registros']), $this->n2($m['ingresos_importe']), $this->n0($m['distribuciones']), $this->n0($m['unidades_distribuidas']), $this->n0($m['comites_atendidos']), $this->n0($m['beneficiarios'])], $report['months']),
            'total' => ['Total', $this->n0($t['ingresos_registros']), $this->n2($t['ingresos_importe']), $this->n0($t['distribuciones']), $this->n0($t['unidades_distribuidas']), $this->n0($t['comites_atendidos']), $this->n0($t['beneficiarios'])],
        ];
        $figure('ingresos_mes');

        $heading('Ingresos y distribución por producto');
        if ($report['ingresos_por_producto']) {
            $blocks[] = ['type' => 'table', 'caption' => 'Cuadro N.° 2. Ingresos por producto',
                'head' => ['Producto', 'Registros', 'Valor (S/)'], 'align' => ['l', 'r', 'r'],
                'rows' => array_map(fn (array $r) => [$r['producto'], $this->n0($r['registros']), $this->n2($r['importe'])], $report['ingresos_por_producto']),
            ];
        }
        if ($report['distribucion_por_producto']) {
            $blocks[] = ['type' => 'table', 'caption' => 'Cuadro N.° 3. Distribución por producto',
                'head' => ['Producto', 'Unidad', 'Líneas PECOSA', 'Cantidad'], 'align' => ['l', 'l', 'r', 'r'],
                'rows' => array_map(fn (array $r) => [$r['producto'], $r['unidad'], $this->n0($r['entregas']), $this->n0($r['cantidad'])], $report['distribucion_por_producto']),
            ];
        }
        $figure('entregas_mes');
        $figure('distribucion_producto');
        $figure('ingresos_producto');

        $heading('Beneficiarios');
        $blocks[] = ['type' => 'table', 'caption' => 'Cuadro N.° 4. Beneficiarios por categoría y zona',
            'head' => ['Categoría', 'Urbana', 'Rural', 'Total'], 'align' => ['l', 'r', 'r', 'r'],
            'rows' => array_map(fn (array $c) => [$c['label'], $this->n0($c['urbana']), $this->n0($c['rural']), $this->n0($c['urbana'] + $c['rural'])], $report['beneficiarios_categorias']),
            'total' => ['Total', $this->n0($report['beneficiarios_zonas']['urbana']), $this->n0($report['beneficiarios_zonas']['rural']), $this->n0($t['beneficiarios'])],
        ];
        $figure('beneficiarios');

        if ($analysis) {
            if ($analysis['analisis_mensual'] || $analysis['hallazgos']) {
                $heading('Análisis');
                foreach ($analysis['analisis_mensual'] as $line) {
                    $blocks[] = ['type' => 'p', 'text' => $line];
                }
                if ($analysis['hallazgos']) {
                    $blocks[] = ['type' => 'p', 'text' => 'Se identifican los siguientes hallazgos:'];
                    $blocks[] = ['type' => 'ol', 'items' => $analysis['hallazgos']];
                }
            }
            if ($analysis['conclusiones']) {
                $heading('Conclusiones');
                $blocks[] = ['type' => 'ol', 'items' => $analysis['conclusiones']];
            }
            if ($analysis['recomendaciones']) {
                $heading('Recomendaciones');
                $blocks[] = ['type' => 'ol', 'items' => $analysis['recomendaciones']];
            }
        }

        $blocks[] = ['type' => 'p', 'text' => 'Es todo cuanto informo a usted, para conocimiento y fines.'];
        $blocks[] = ['type' => 'p', 'text' => 'Atentamente,'];
        $blocks[] = ['type' => 'sign', 'name' => $meta['from'] ?: $blank, 'role' => $meta['from_role'] ?: 'Programa Vaso de Leche'];
        $blocks[] = ['type' => 'note', 'text' => 'cc. Archivo'];

        return $blocks;
    }

    public function meta(array $meta, array $report): array
    {
        $clean = fn (string $key, int $max) => mb_substr(trim((string) ($meta[$key] ?? '')), 0, $max);
        $now = now();

        return [
            'number' => $clean('number', 80),
            'to' => $clean('to', 160),
            'to_role' => $clean('to_role', 160),
            'from' => $clean('from', 160),
            'from_role' => $clean('from_role', 160),
            'subject' => $clean('subject', 300),
            'place' => $clean('place', 100),
            'date' => $clean('date', 60) ?: $now->day.' de '.self::MONTHS[$now->month - 1].' del '.$now->year,
        ];
    }

    private function roman(int $n): string
    {
        return ['', 'I', 'II', 'III', 'IV', 'V', 'VI', 'VII', 'VIII'][$n] ?? (string) $n;
    }

    private function n0($value): string
    {
        return number_format((float) $value, 0, '.', ',');
    }

    private function n2($value): string
    {
        return number_format((float) $value, 2, '.', ',');
    }
}
