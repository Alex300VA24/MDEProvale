<?php

namespace App\Services\Pvl;

use App\Models\PvlReportRun;
use Illuminate\Support\Collection;

class PvlSupportingReportService
{
    public function build(PvlReportRun $run): array
    {
        $validated = $run->validated_data_json ?? [];
        $pvl = data_get($validated, 'pvl', []);
        $ration = data_get($validated, 'racion_a', []);
        $metadata = data_get($run->input_snapshot_json, 'report_metadata', []);
        $documents = collect(data_get($run->input_snapshot_json, 'documentos_respaldo', []));
        $findings = collect($run->warnings_json ?? []);
        $sources = collect($run->sources_json ?? [])->map(fn (array $source) => $this->source($source));

        $criticalCount = $findings->where('severity', 'CRITICO')->count();
        $warningCount = $findings->where('severity', 'ADVERTENCIA')->count();
        $indexedSubmission = $documents->contains(fn (array $document) => ($document['tipo_documento'] ?? null) === 'constancia_envio'
            && ($document['estado_indexacion'] ?? null) === 'INDEXADO');
        $shippingCodes = collect([
            data_get($pvl, 'codigo_envio'),
            data_get($ration, 'codigo_envio'),
        ])->filter(fn ($value) => filled($value))->unique()->values();
        $submissionConfirmed = $criticalCount === 0 && $indexedSubmission && $shippingCodes->isNotEmpty();

        $purchaseCount = count(data_get($pvl, 'compras_alimentos', [])) + count(data_get($pvl, 'compras_insumos', []));
        $distributionCount = count(data_get($ration, 'distribuciones', []));
        $certificateCount = count(data_get($ration, 'certificados', []));
        $beneficiaryTotal = $this->number(data_get($ration, 'beneficiarios.rural.total'))
            + $this->number(data_get($ration, 'beneficiarios.urbana.total'));

        $period = sprintf('%04d-%02d', $run->year, $run->month);
        $monthName = mb_strtoupper($this->monthName($run->month));
        $quarter = (int) ceil($run->month / 3);
        $municipality = data_get($pvl, 'municipalidad') ?: data_get($ration, 'municipalidad') ?: config('pvl_reports.municipality.name');

        $evidence = collect([
            $this->evidence(
                'Gastos e ingresos del PVL',
                $purchaseCount > 0 && data_get($pvl, 'total_gastos') !== null,
                sprintf('%d comprobante(s) o movimiento(s); gasto total S/ %s; recursos S/ %s; saldo S/ %s.',
                    $purchaseCount,
                    $this->money(data_get($pvl, 'total_gastos')),
                    $this->money(data_get($pvl, 'financiamiento.total_recursos')),
                    $this->money(data_get($pvl, 'financiamiento.saldo_final')),
                ),
                $this->referencesFor($sources, ['compras', 'transactions', 'financiamiento', 'comprobante', 'factura']),
            ),
            $this->evidence(
                'Ración y distribución',
                $distributionCount > 0 && (count(data_get($ration, 'raciones_compuestas', [])) + count(data_get($ration, 'raciones_un_alimento', []))) > 0,
                sprintf('%d distribución(es), %d comité(s) atendido(s) y %s beneficiario(s) registrados.',
                    $distributionCount,
                    (int) data_get($ration, 'cantidad_comites_atendidos', 0),
                    number_format($beneficiaryTotal),
                ),
                $this->referencesFor($sources, ['distribucion', 'pecosas', 'raciones', 'beneficiarios']),
            ),
            $this->evidence(
                'Calidad, lote y composición',
                $certificateCount > 0 && count(data_get($ration, 'composicion', [])) > 0,
                sprintf('%d certificado(s) y %d registro(s) de composición contrastados.',
                    $certificateCount,
                    count(data_get($ration, 'composicion', [])),
                ),
                $this->referencesFor($sources, ['certificado', 'lote', 'composicion', 'ficha']),
            ),
            $this->evidence(
                'Remisión a Contraloría',
                $submissionConfirmed,
                $submissionConfirmed
                    ? 'Existe constancia documental indexada y código(s) de envío: '.$shippingCodes->implode(', ').'.'
                    : 'No se afirma el envío: se requiere una constancia indexada y al menos un código de envío confirmado.',
                $documents
                    ->where('tipo_documento', 'constancia_envio')
                    ->pluck('archivo')
                    ->filter()
                    ->values()
                    ->all(),
            ),
        ])->all();

        return [
            'numero_informe' => $this->text($metadata['report_number'] ?? null),
            'destinatario_nombre' => $this->text($metadata['recipient_name'] ?? null),
            'destinatario_cargo' => $this->text($metadata['recipient_role'] ?? null),
            'remitente_nombre' => $this->text($metadata['sender_name'] ?? null),
            'remitente_cargo' => $this->text($metadata['sender_role'] ?? null) ?: 'Programa del Vaso de Leche',
            'asunto' => $this->text($metadata['subject'] ?? null)
                ?: "Sustento de los reportes del Programa del Vaso de Leche - {$monthName} {$run->year}",
            'lugar' => $this->text($metadata['place'] ?? null) ?: 'La Esperanza',
            'fecha' => data_get($pvl, 'fecha_reporte') ?: data_get($ration, 'fecha_reporte') ?: now()->format('d/m/Y'),
            'municipalidad' => $municipality,
            'periodo' => $period,
            'periodo_texto' => "{$monthName} {$run->year}",
            'trimestre' => $this->roman($quarter).' TRIMESTRE',
            'estado_acreditacion' => $submissionConfirmed
                ? 'CUMPLIMIENTO Y ENVÍO ACREDITADOS'
                : 'PREPARACIÓN ACREDITADA / ENVÍO PENDIENTE DE ACREDITAR',
            'envio_acreditado' => $submissionConfirmed,
            'resumen' => [
                'compras_registradas' => $purchaseCount,
                'total_gastos' => data_get($pvl, 'total_gastos'),
                'total_recursos' => data_get($pvl, 'financiamiento.total_recursos'),
                'saldo_final' => data_get($pvl, 'financiamiento.saldo_final'),
                'distribuciones' => $distributionCount,
                'comites_atendidos' => data_get($ration, 'cantidad_comites_atendidos'),
                'beneficiarios' => $beneficiaryTotal,
                'certificados' => $certificateCount,
                'fuentes' => $sources->count(),
                'documentos' => $documents->where('estado_indexacion', 'INDEXADO')->count(),
            ],
            'evidencias' => $evidence,
            'advertencias' => $findings->where('severity', 'ADVERTENCIA')->values()->all(),
            'fuentes' => $sources->unique(fn (array $source) => implode('|', $source))->values()->all(),
            'documentos' => $documents->values()->all(),
            'anexos' => [
                'Anexo N.° 1 - Formato PVL del periodo '.$period,
                'Anexo N.° 2 - Formato Ración A del periodo '.$period,
            ],
            'conclusiones' => $this->conclusions($submissionConfirmed, $warningCount, $purchaseCount, $distributionCount, $beneficiaryTotal),
        ];
    }

    private function evidence(string $control, bool $supported, string $result, array $references): array
    {
        return [
            'control' => $control,
            'estado' => $supported ? 'ACREDITADO' : 'PENDIENTE DE ACREDITAR',
            'resultado' => $result,
            'referencias' => $references,
        ];
    }

    private function source(array $source): array
    {
        $reference = $source['archivo'] ?? $source['referencia'] ?? $source['entidad'] ?? 'Fuente registrada';
        $location = collect([
            isset($source['pagina']) ? 'pág. '.$source['pagina'] : null,
            isset($source['chunk']) ? 'frag. '.$source['chunk'] : null,
        ])->filter()->implode(', ');

        return [
            'campo' => (string) ($source['campo'] ?? 'Dato relacionado'),
            'origen' => (string) ($source['origen'] ?? 'RAG'),
            'referencia' => trim($reference.($location !== '' ? ' ('.$location.')' : '')),
        ];
    }

    private function referencesFor(Collection $sources, array $terms): array
    {
        $references = $sources->filter(function (array $source) use ($terms) {
            $haystack = mb_strtolower($source['campo'].' '.$source['referencia']);

            return collect($terms)->contains(fn (string $term) => str_contains($haystack, $term));
        })->pluck('referencia')->filter()->unique()->take(4)->values()->all();

        return $references ?: ['Datos validados del sistema para el periodo.'];
    }

    private function conclusions(bool $submissionConfirmed, int $warningCount, int $purchaseCount, int $distributionCount, int $beneficiaries): array
    {
        $conclusions = [
            "La información validada sustenta {$purchaseCount} registro(s) de compra/ingreso, {$distributionCount} distribución(es) y la atención de ".number_format($beneficiaries).' beneficiario(s) en el periodo evaluado.',
            'Los anexos PVL y Ración A se construyen con los mismos datos validados y conservan trazabilidad hacia la base de datos y los documentos indexados.',
        ];

        $conclusions[] = $submissionConfirmed
            ? 'La remisión a Contraloría se encuentra acreditada mediante constancia documental y código de envío registrados.'
            : 'La preparación de los anexos está sustentada; la remisión a Contraloría permanece pendiente de acreditar y no se declara como cumplida.';

        if ($warningCount > 0) {
            $conclusions[] = "Se mantienen {$warningCount} advertencia(s) no crítica(s), detalladas para seguimiento administrativo.";
        }

        return $conclusions;
    }

    private function monthName(int $month): string
    {
        return [1 => 'enero', 2 => 'febrero', 3 => 'marzo', 4 => 'abril', 5 => 'mayo', 6 => 'junio', 7 => 'julio', 8 => 'agosto', 9 => 'septiembre', 10 => 'octubre', 11 => 'noviembre', 12 => 'diciembre'][$month];
    }

    private function roman(int $number): string
    {
        return [1 => 'I', 2 => 'II', 3 => 'III', 4 => 'IV'][$number];
    }

    private function money($value): string
    {
        return $value === null ? 'sin dato confirmado' : number_format((float) $value, 2, '.', ',');
    }

    private function number($value): int
    {
        return is_numeric($value) ? (int) $value : 0;
    }

    private function text($value): ?string
    {
        $value = trim((string) $value);

        return $value === '' ? null : $value;
    }
}
