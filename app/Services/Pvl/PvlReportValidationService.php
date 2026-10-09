<?php

namespace App\Services\Pvl;

use App\Models\PvlReportRun;
use Carbon\Carbon;

class PvlReportValidationService
{
    /**
     * @return array{data:array,findings:array,status:string}
     */
    public function validate(array $data, array $aiOutput, array $context, ?PvlReportRun $previousRun = null): array
    {
        $findings = [];

        foreach ($aiOutput['conflictos'] ?? [] as $conflict) {
            $this->finding(
                $findings,
                'CRITICO',
                'CONFLICTO_FUENTES',
                (string) ($conflict['campo'] ?? 'desconocido'),
                (string) ($conflict['descripcion'] ?? 'Dos fuentes presentan valores distintos para el mismo campo.'),
                ['opciones' => $conflict['opciones'] ?? []]
            );
        }

        foreach ($aiOutput['datos_faltantes'] ?? [] as $missing) {
            $field = (string) ($missing['campo'] ?? 'desconocido');
            if ($this->hasConfirmedValue($data, $field)) {
                continue;
            }

            $this->finding(
                $findings,
                ! empty($missing['obligatorio']) ? 'CRITICO' : 'INFORMATIVO',
                'DATO_FALTANTE',
                $field,
                (string) ($missing['motivo'] ?? 'Dato no encontrado en las fuentes.')
            );
        }

        if (isset($data['pvl'])) {
            $this->validatePvl($data['pvl'], $findings, $previousRun);
        }
        if (isset($data['racion_a'])) {
            $this->validateRation($data['racion_a'], $findings);
        }

        if (($context['meta']['beneficiarios_sin_zona'] ?? 0) > 0) {
            $this->finding(
                $findings,
                'ADVERTENCIA',
                'BENEFICIARIOS_SIN_ZONA',
                'beneficiarios',
                $context['meta']['beneficiarios_sin_zona'].' beneficiarios activos no pudieron clasificarse como zona rural o urbana.'
            );
        }

        $period = $context['period'] ?? null;
        foreach ($aiOutput['trazabilidad'] ?? [] as $source) {
            $sourcePeriod = $source['periodo'] ?? null;
            if ($sourcePeriod && $period && $sourcePeriod !== $period) {
                $this->finding(
                    $findings,
                    'CRITICO',
                    'PERIODO_INCORRECTO',
                    (string) ($source['campo'] ?? 'trazabilidad'),
                    "Una fuente del periodo {$sourcePeriod} fue usada para el reporte {$period}."
                );
            }
        }

        $status = collect($findings)->contains(fn (array $finding) => $finding['severity'] === 'CRITICO')
            ? PvlReportRun::REQUIERE_REVISION
            : PvlReportRun::LISTO_PARA_GENERAR;

        return ['data' => $data, 'findings' => $findings, 'status' => $status];
    }

    private function validatePvl(array &$data, array &$findings, ?PvlReportRun $previousRun): void
    {
        $this->requireValues($data, [
            'municipalidad', 'tipo_municipalidad', 'departamento', 'provincia',
            'mes_reportado', 'anio_reportado', 'fecha_reporte',
        ], 'pvl', $findings);

        foreach (['compras_alimentos', 'compras_insumos'] as $section) {
            foreach ($data[$section] ?? [] as $index => $purchase) {
                $this->requireValues($purchase, [
                    'producto', 'proveedor', 'ruc', 'tipo_comprobante',
                    'numero_comprobante', 'fecha_emision', 'importe',
                ], "pvl.{$section}.{$index}", $findings);
            }
        }

        $foodTotal = $this->sumLines($data['compras_alimentos'] ?? []);
        $supplyTotal = $this->sumLines($data['compras_insumos'] ?? []);
        $donationTotal = ($data['donaciones'] ?? []) !== []
            ? $this->sumLines($data['donaciones'])
            : $data['total_donaciones'];

        $this->replaceCalculated($data, 'total_compras_alimentos', $foodTotal, 'pvl.total_compras_alimentos', $findings);
        $this->replaceCalculated($data, 'total_compras_insumos', $supplyTotal, 'pvl.total_compras_insumos', $findings);
        if ($donationTotal !== null) {
            $this->replaceCalculated($data, 'total_donaciones', $donationTotal, 'pvl.total_donaciones', $findings);
        }

        if ($data['total_gastos_operativos'] === null) {
            $this->finding($findings, 'CRITICO', 'DATO_FALTANTE', 'pvl.total_gastos_operativos', 'No existe fuente para los gastos operativos del periodo.');
            $data['total_gastos'] = null;
        } else {
            $totalExpenses = round($foodTotal + $supplyTotal + (float) $data['total_gastos_operativos'], 2);
            $this->replaceCalculated($data, 'total_gastos', $totalExpenses, 'pvl.total_gastos', $findings);
        }

        $financeFields = [
            'saldo_inicial_tesoro',
            'transferencia_tesoro',
            'recursos_directamente_recaudados',
            'foncomun',
            'donaciones',
            'intereses',
        ];
        $finance = &$data['financiamiento'];
        foreach ($financeFields as $field) {
            if ($finance[$field] === null) {
                $this->finding($findings, 'CRITICO', 'DATO_FALTANTE', 'pvl.financiamiento.'.$field, 'No existe fuente confirmada para este componente financiero.');
            }
        }

        if (collect($financeFields)->every(fn (string $field) => $finance[$field] !== null)) {
            $resources = round(array_sum(array_map(fn (string $field) => (float) $finance[$field], $financeFields)), 2);
            $this->replaceCalculated($finance, 'total_recursos', $resources, 'pvl.financiamiento.total_recursos', $findings);
            if ($data['total_gastos'] !== null) {
                $balance = round($resources - (float) $data['total_gastos'], 2);
                $this->replaceCalculated($finance, 'saldo_final', $balance, 'pvl.financiamiento.saldo_final', $findings);
            } else {
                $finance['saldo_final'] = null;
            }
        } else {
            $finance['total_recursos'] = null;
            $finance['saldo_final'] = null;
        }

        $seenInvoices = [];
        foreach (array_merge($data['compras_alimentos'] ?? [], $data['compras_insumos'] ?? []) as $index => $purchase) {
            $parts = array_map(static fn ($value) => mb_strtoupper(trim((string) $value)), [
                $purchase['ruc'] ?? null,
                $purchase['tipo_comprobante'] ?? null,
                $purchase['serie'] ?? null,
                $purchase['numero_comprobante'] ?? null,
            ]);
            if (in_array('', $parts, true)) {
                continue;
            }

            $key = implode('|', $parts);
            if (isset($seenInvoices[$key])) {
                $this->finding($findings, 'CRITICO', 'COMPROBANTE_DUPLICADO', 'pvl.compras.'.$index, 'Comprobante duplicado por RUC, tipo, serie y número.');
            }
            $seenInvoices[$key] = true;
        }

        if ($previousRun) {
            $previousBalance = data_get($previousRun->validated_data_json, 'pvl.financiamiento.saldo_final');
            $currentBalance = $finance['saldo_inicial_tesoro'] ?? null;
            if ($previousBalance !== null && $currentBalance !== null && abs((float) $previousBalance - (float) $currentBalance) > 0.01) {
                $this->finding($findings, 'ADVERTENCIA', 'CONTINUIDAD_SALDO', 'pvl.financiamiento.saldo_inicial_tesoro', 'El saldo inicial no coincide con el saldo final del mes anterior.');
            }
        }

        foreach (['numero_expediente', 'codigo_envio', 'presidente_comite_administracion', 'director_administracion'] as $field) {
            if (empty($data[$field])) {
                $this->finding($findings, 'ADVERTENCIA', 'DATO_ADMINISTRATIVO_FALTANTE', 'pvl.'.$field, 'Campo administrativo pendiente de revisión.');
            }
        }
    }

    private function validateRation(array &$data, array &$findings): void
    {
        $this->requireValues($data, [
            'municipalidad', 'tipo_municipalidad', 'departamento', 'provincia',
            'mes_reportado', 'anio_reportado', 'fecha_reporte',
        ], 'racion_a', $findings);

        if (($data['raciones_un_alimento'] ?? []) === [] && ($data['raciones_compuestas'] ?? []) === []) {
            $this->finding($findings, 'CRITICO', 'RACION_FALTANTE', 'racion_a.raciones', 'No existe una ración vigente ni evidencia documental para el periodo.');
        }
        if (($data['distribuciones'] ?? []) === []) {
            $this->finding($findings, 'CRITICO', 'DISTRIBUCION_FALTANTE', 'racion_a.distribuciones', 'No existen distribuciones confirmadas para el periodo.');
        }
        if (($data['certificados'] ?? []) === []) {
            $this->finding($findings, 'CRITICO', 'CERTIFICADO_FALTANTE', 'racion_a.certificados', 'No se encontró certificado de calidad para los productos distribuidos.');
        }
        if (($data['composicion'] ?? []) === []) {
            $this->finding($findings, 'CRITICO', 'COMPOSICION_FALTANTE', 'racion_a.composicion', 'No se encontró composición respaldada para los productos distribuidos.');
        }

        foreach ($data['raciones_un_alimento'] ?? [] as $index => $ration) {
            $path = 'racion_a.raciones_un_alimento.'.$index;
            $this->requireValues($ration, ['alimento', 'dias_prioridad_1', 'dias_prioridad_2', 'tipo_racion'], $path, $findings);
            $this->requireOneOf($ration, ['gramos', 'cc'], $path, $findings, 'La ración no tiene cantidad confirmada.');
        }

        foreach ($data['raciones_compuestas'] ?? [] as $index => $ration) {
            $path = 'racion_a.raciones_compuestas.'.$index;
            $this->requireValues($ration, ['alimento1', 'alimento2', 'dias_prioridad_1', 'dias_prioridad_2', 'tipo_racion'], $path, $findings);
            $this->requireOneOf($ration, ['alimento1_gramos', 'alimento1_cc'], $path, $findings, 'El primer alimento no tiene cantidad confirmada.');
            $this->requireOneOf($ration, ['alimento2_gramos', 'alimento2_cc'], $path, $findings, 'El segundo alimento no tiene cantidad confirmada.');
        }

        foreach ($data['distribuciones'] ?? [] as $index => $distribution) {
            $path = 'racion_a.distribuciones.'.$index;
            $this->requireValues($distribution, ['producto', 'fecha_distribucion', 'fecha_inicio_atencion', 'fecha_fin_atencion'], $path, $findings);
            $this->requireOneOf($distribution, ['cantidad_kg', 'cantidad_litros'], $path, $findings, 'La distribución no tiene cantidad confirmada.');
        }

        foreach ($data['certificados'] ?? [] as $index => $certificate) {
            $path = 'racion_a.certificados.'.$index;
            $this->requireValues($certificate, ['producto', 'laboratorio', 'numero_certificado', 'fecha_emision', 'numero_lote', 'fecha_vencimiento'], $path, $findings);
            if (! array_key_exists('certificado_microbiologico', $certificate) || $certificate['certificado_microbiologico'] === null) {
                $this->finding($findings, 'CRITICO', 'DATO_FALTANTE', $path.'.certificado_microbiologico', 'Campo obligatorio sin fuente confirmada.');
            }
        }

        foreach ($data['composicion'] ?? [] as $index => $composition) {
            $this->requireValues($composition, ['producto', 'insumo', 'porcentaje'], 'racion_a.composicion.'.$index, $findings);
        }

        foreach (['rural', 'urbana'] as $zone) {
            $beneficiaries = &$data['beneficiarios'][$zone];
            $fields = [
                'menores_1_anio', 'ninos_1_a_6', 'madres_gestantes', 'madres_lactantes',
                'personas_7_a_13', 'personas_tbc', 'ancianos', 'discapacitados',
            ];
            $missingFields = collect($fields)->filter(fn (string $field) => $beneficiaries[$field] === null);
            if ($missingFields->isNotEmpty()) {
                foreach ($missingFields as $field) {
                    $this->finding(
                        $findings,
                        'CRITICO',
                        'DATO_FALTANTE',
                        'racion_a.beneficiarios.'.$zone.'.'.$field,
                        'Categoría de beneficiarios sin valor confirmado para la zona '.$zone.'.'
                    );
                }
                $beneficiaries['total'] = null;
            } else {
                $total = array_sum(array_map(fn (string $field) => (int) $beneficiaries[$field], $fields));
                $this->replaceCalculated($beneficiaries, 'total', $total, 'racion_a.beneficiarios.'.$zone.'.total', $findings);
            }
        }

        $compositionGroups = collect($data['composicion'] ?? [])->groupBy(fn (array $item) => mb_strtoupper(trim((string) ($item['producto'] ?? ''))));
        foreach ($compositionGroups as $product => $items) {
            if ($product === '' || $items->contains(fn (array $item) => $item['porcentaje'] === null)) {
                $this->finding($findings, 'CRITICO', 'COMPOSICION_INCOMPLETA', 'racion_a.composicion', 'La composición contiene producto o porcentaje sin fuente.');
                continue;
            }

            $total = round((float) $items->sum('porcentaje'), 2);
            if (abs($total - 100.0) > (float) config('pvl_reports.composition_tolerance', 0.5)) {
                $this->finding($findings, 'CRITICO', 'COMPOSICION_INVALIDA', 'racion_a.composicion.'.$product, "La composición suma {$total} %, fuera de la tolerancia permitida.");
            }
        }

        $distributions = collect($data['distribuciones'] ?? []);
        foreach ($data['certificados'] ?? [] as $index => $certificate) {
            $expiration = $this->parseDate($certificate['fecha_vencimiento'] ?? null);
            if (! $expiration) {
                continue;
            }
            $product = mb_strtoupper(trim((string) ($certificate['producto'] ?? '')));
            $productDistributions = $distributions->filter(fn (array $distribution) => mb_strtoupper(trim((string) ($distribution['producto'] ?? ''))) === $product);
            foreach ($productDistributions as $distribution) {
                $date = $this->parseDate($distribution['fecha_distribucion'] ?? null);
                if ($date && $expiration->lt($date)) {
                    $this->finding($findings, 'CRITICO', 'LOTE_VENCIDO', 'racion_a.certificados.'.$index, 'Producto distribuido después de la fecha de vencimiento del lote.');
                    break;
                }
                if ($date && $expiration->diffInDays($date, false) >= -30 && $expiration->gte($date)) {
                    $this->finding($findings, 'ADVERTENCIA', 'LOTE_PROXIMO_A_VENCER', 'racion_a.certificados.'.$index, 'El lote vencía dentro de los 30 días posteriores a la distribución.');
                }
            }
        }

        foreach (['numero_expediente', 'codigo_envio', 'presidente_comite_administracion', 'representante_ministerio_salud', 'profesion_representante_salud'] as $field) {
            if (empty($data[$field])) {
                $this->finding($findings, 'ADVERTENCIA', 'DATO_ADMINISTRATIVO_FALTANTE', 'racion_a.'.$field, 'Campo administrativo pendiente de revisión.');
            }
        }
    }

    private function requireValues(array $data, array $fields, string $prefix, array &$findings): void
    {
        foreach ($fields as $field) {
            if (! array_key_exists($field, $data) || $data[$field] === null || $data[$field] === '') {
                $this->finding($findings, 'CRITICO', 'DATO_FALTANTE', $prefix.'.'.$field, 'Campo obligatorio sin fuente confirmada.');
            }
        }
    }

    private function requireOneOf(array $data, array $fields, string $prefix, array &$findings, string $message): void
    {
        $hasValue = collect($fields)->contains(fn (string $field) => array_key_exists($field, $data)
            && $data[$field] !== null
            && $data[$field] !== '');

        if (! $hasValue) {
            $this->finding($findings, 'CRITICO', 'DATO_FALTANTE', $prefix.'.'.implode('|', $fields), $message);
        }
    }

    private function sumLines(array $lines): float
    {
        return round(array_sum(array_map(static fn (array $line) => (float) ($line['importe'] ?? 0), $lines)), 2);
    }

    private function replaceCalculated(array &$data, string $field, $calculated, string $path, array &$findings): void
    {
        $current = $data[$field] ?? null;
        if ($current !== null && abs((float) $current - (float) $calculated) > 0.01) {
            $this->finding($findings, 'CRITICO', 'TOTAL_INCONSISTENTE', $path, 'El valor recibido no coincide con el cálculo determinístico del backend.');
        }
        $data[$field] = $calculated;
    }

    private function finding(array &$findings, string $severity, string $code, string $field, string $message, array $extra = []): void
    {
        $signature = $severity.'|'.$code.'|'.$field.'|'.$message;
        foreach ($findings as $existing) {
            if (($existing['_signature'] ?? null) === $signature) {
                return;
            }
        }

        $findings[] = array_merge([
            'severity' => $severity,
            'code' => $code,
            'field' => $field,
            'message' => $message,
            '_signature' => $signature,
        ], $extra);
    }

    private function parseDate($value): ?Carbon
    {
        $value = trim((string) $value);
        if ($value === '') {
            return null;
        }

        foreach (['d/m/Y', 'Y-m-d'] as $format) {
            try {
                $date = Carbon::createFromFormat($format, $value);
                if ($date !== false && $date->format($format) === $value) {
                    return $date->startOfDay();
                }
            } catch (\Throwable) {
                // Prueba siguiente formato.
            }
        }

        return null;
    }

    private function hasConfirmedValue(array $data, string $field): bool
    {
        $paths = [$field];
        if (! str_starts_with($field, 'pvl.') && ! str_starts_with($field, 'racion_a.')) {
            $paths[] = 'pvl.'.$field;
            $paths[] = 'racion_a.'.$field;
        }

        foreach ($paths as $path) {
            $value = data_get($data, $path);
            if ($value !== null && $value !== '' && $value !== []) {
                return true;
            }
        }

        return false;
    }
}
