<?php

namespace App\Services\Pvl;

use Carbon\Carbon;

class PvlReportDataMapper
{
    public function map(array $aiOutput, array $context, string $reportType): array
    {
        $aiData = is_array($aiOutput['data'] ?? null) ? $aiOutput['data'] : [];
        $mapped = [];

        if (in_array($reportType, ['PVL', 'AMBOS'], true)) {
            $candidate = is_array($aiData['pvl'] ?? null) ? $aiData['pvl'] : $aiData;
            $merged = $this->mergeTrusted($candidate, $context['pvl'] ?? []);
            $mapped['pvl'] = $this->project($this->pvlShape(), $merged);
            $mapped['pvl'] = $this->normalizePvl($mapped['pvl']);
        }

        if (in_array($reportType, ['RACION_A', 'AMBOS'], true)) {
            $candidate = is_array($aiData['racion_a'] ?? null) ? $aiData['racion_a'] : $aiData;
            $merged = $this->mergeTrusted($candidate, $context['racion_a'] ?? []);
            $mapped['racion_a'] = $this->project($this->rationShape(), $merged);
            $mapped['racion_a'] = $this->normalizeRation($mapped['racion_a']);
        }

        return $mapped;
    }

    private function mergeTrusted($ai, $trusted)
    {
        if ($trusted === null || $trusted === '') {
            return $ai;
        }
        if (! is_array($trusted)) {
            return $trusted;
        }
        if ($trusted === []) {
            return is_array($ai) ? $ai : [];
        }

        $ai = is_array($ai) ? $ai : [];
        $result = $ai;
        foreach ($trusted as $key => $value) {
            $result[$key] = $this->mergeTrusted($ai[$key] ?? null, $value);
        }

        return $result;
    }

    private function project(array $shape, array $data): array
    {
        $result = [];
        foreach ($shape as $key => $default) {
            $value = array_key_exists($key, $data) ? $data[$key] : $default;

            if (is_array($default) && $this->isList($default)) {
                $itemShape = $default[0] ?? null;
                $result[$key] = is_array($value)
                    ? array_values(array_map(fn ($item) => is_array($itemShape) && is_array($item)
                        ? $this->project($itemShape, $item)
                        : $item, $value))
                    : [];
            } elseif (is_array($default)) {
                $result[$key] = $this->project($default, is_array($value) ? $value : []);
            } else {
                $result[$key] = $value;
            }
        }

        return $result;
    }

    private function normalizePvl(array $data): array
    {
        foreach (['compras_alimentos', 'compras_insumos'] as $section) {
            foreach ($data[$section] as &$line) {
                $line['fecha_emision'] = $this->date($line['fecha_emision']);
                $line['ruc'] = $this->identifier($line['ruc']);
                $line['serie'] = $this->identifier($line['serie']);
                $line['numero_comprobante'] = $this->identifier($line['numero_comprobante']);
                foreach (['cantidad_kg', 'cantidad_litros', 'importe'] as $field) {
                    $line[$field] = $this->number($line[$field]);
                }
            }
            unset($line);
        }
        foreach ($data['donaciones'] as &$line) {
            foreach (['cantidad_kg', 'cantidad_litros', 'importe'] as $field) {
                $line[$field] = $this->number($line[$field]);
            }
        }
        unset($line);

        foreach (['total_compras_alimentos', 'total_compras_insumos', 'total_donaciones', 'total_gastos_operativos', 'total_gastos'] as $field) {
            $data[$field] = $this->number($data[$field]);
        }
        foreach ($data['financiamiento'] as $field => $value) {
            $data['financiamiento'][$field] = $this->number($value);
        }
        $data['fecha_reporte'] = $this->date($data['fecha_reporte']);

        return $data;
    }

    private function normalizeRation(array $data): array
    {
        foreach ($data['raciones_un_alimento'] as &$ration) {
            foreach (['gramos', 'cc'] as $field) {
                $ration[$field] = $this->number($ration[$field]);
            }
            foreach (['dias_prioridad_1', 'dias_prioridad_2'] as $field) {
                $ration[$field] = $this->integer($ration[$field]);
            }
        }
        unset($ration);
        foreach ($data['raciones_compuestas'] as &$ration) {
            foreach (['alimento1_gramos', 'alimento1_cc', 'alimento2_gramos', 'alimento2_cc', 'alimento3_gramos', 'alimento3_cc'] as $field) {
                $ration[$field] = $this->number($ration[$field]);
            }
            foreach (['dias_prioridad_1', 'dias_prioridad_2'] as $field) {
                $ration[$field] = $this->integer($ration[$field]);
            }
        }
        unset($ration);
        foreach ($data['distribuciones'] as &$distribution) {
            $distribution['cantidad_kg'] = $this->number($distribution['cantidad_kg']);
            $distribution['cantidad_litros'] = $this->number($distribution['cantidad_litros']);
            foreach (['fecha_distribucion', 'fecha_inicio_atencion', 'fecha_fin_atencion'] as $field) {
                $distribution[$field] = $this->date($distribution[$field]);
            }
        }
        unset($distribution);
        foreach ($data['certificados'] as &$certificate) {
            $certificate['fecha_emision'] = $this->date($certificate['fecha_emision']);
            $certificate['fecha_vencimiento'] = $this->date($certificate['fecha_vencimiento']);
            $certificate['numero_certificado'] = $this->identifier($certificate['numero_certificado']);
            $certificate['numero_lote'] = $this->identifier($certificate['numero_lote']);
            if ($certificate['certificado_microbiologico'] !== null) {
                $certificate['certificado_microbiologico'] = filter_var($certificate['certificado_microbiologico'], FILTER_VALIDATE_BOOL, FILTER_NULL_ON_FAILURE);
            }
        }
        unset($certificate);
        foreach ($data['composicion'] as &$composition) {
            $composition['porcentaje'] = $this->number($composition['porcentaje']);
        }
        unset($composition);
        foreach (['rural', 'urbana'] as $zone) {
            foreach ($data['beneficiarios'][$zone] as $field => $value) {
                $data['beneficiarios'][$zone][$field] = $this->integer($value);
            }
        }
        $data['cantidad_comites_atendidos'] = $this->integer($data['cantidad_comites_atendidos']);
        $data['fecha_reporte'] = $this->date($data['fecha_reporte']);

        return $data;
    }

    private function date($value): ?string
    {
        $value = trim((string) $value);
        if ($value === '') {
            return null;
        }

        foreach (['d/m/Y', 'Y-m-d', 'd-m-Y'] as $format) {
            try {
                $date = Carbon::createFromFormat($format, $value);
                if ($date !== false && $date->format($format) === $value) {
                    return $date->format('d/m/Y');
                }
            } catch (\Throwable) {
                // Prueba siguiente formato.
            }
        }

        return null;
    }

    private function number($value): ?float
    {
        return $value === null || $value === '' || ! is_numeric($value)
            ? null
            : round((float) $value, 2);
    }

    private function integer($value): ?int
    {
        if ($value === null || $value === '' || filter_var($value, FILTER_VALIDATE_INT) === false) {
            return null;
        }

        return (int) $value;
    }

    private function identifier($value): ?string
    {
        $value = $value === null ? '' : trim((string) $value);

        return $value === '' ? null : $value;
    }

    private function isList(array $value): bool
    {
        return array_keys($value) === range(0, count($value) - 1);
    }

    public function pvlShape(): array
    {
        $purchase = [
            'clasificacion' => null,
            'producto' => null,
            'marca' => null,
            'origen' => null,
            'proveedor' => null,
            'ruc' => null,
            'tipo_comprobante' => null,
            'serie' => null,
            'numero_comprobante' => null,
            'fecha_emision' => null,
            'cantidad_kg' => null,
            'cantidad_litros' => null,
            'importe' => null,
        ];

        return [
            'fecha_hora_impresion' => null,
            'municipalidad' => null,
            'tipo_municipalidad' => null,
            'departamento' => null,
            'provincia' => null,
            'mes_reportado' => null,
            'anio_reportado' => null,
            'fecha_reporte' => null,
            'numero_expediente' => null,
            'codigo_envio' => null,
            'compras_alimentos' => [$purchase],
            'total_compras_alimentos' => null,
            'compras_insumos' => [$purchase],
            'total_compras_insumos' => null,
            'donaciones' => [[
                'producto' => null,
                'marca' => null,
                'origen' => null,
                'donante' => null,
                'cantidad_kg' => null,
                'cantidad_litros' => null,
                'importe' => null,
            ]],
            'total_donaciones' => null,
            'total_gastos_operativos' => null,
            'total_gastos' => null,
            'financiamiento' => [
                'saldo_inicial_tesoro' => null,
                'transferencia_tesoro' => null,
                'recursos_directamente_recaudados' => null,
                'foncomun' => null,
                'donaciones' => null,
                'intereses' => null,
                'total_recursos' => null,
                'saldo_final' => null,
            ],
            'presidente_comite_administracion' => null,
            'director_administracion' => null,
        ];
    }

    public function rationShape(): array
    {
        $beneficiaries = [
            'menores_1_anio' => null,
            'ninos_1_a_6' => null,
            'madres_gestantes' => null,
            'madres_lactantes' => null,
            'personas_7_a_13' => null,
            'personas_tbc' => null,
            'ancianos' => null,
            'discapacitados' => null,
            'total' => null,
        ];

        return [
            'municipalidad' => null,
            'tipo_municipalidad' => null,
            'departamento' => null,
            'provincia' => null,
            'mes_reportado' => null,
            'anio_reportado' => null,
            'fecha_reporte' => null,
            'numero_expediente' => null,
            'codigo_envio' => null,
            'raciones_un_alimento' => [[
                'alimento' => null,
                'gramos' => null,
                'cc' => null,
                'dias_prioridad_1' => null,
                'dias_prioridad_2' => null,
                'tipo_racion' => null,
            ]],
            'raciones_compuestas' => [[
                'alimento1' => null,
                'alimento1_gramos' => null,
                'alimento1_cc' => null,
                'alimento2' => null,
                'alimento2_gramos' => null,
                'alimento2_cc' => null,
                'alimento3' => null,
                'alimento3_gramos' => null,
                'alimento3_cc' => null,
                'dias_prioridad_1' => null,
                'dias_prioridad_2' => null,
                'tipo_racion' => null,
            ]],
            'distribuciones' => [[
                'producto' => null,
                'cantidad_kg' => null,
                'cantidad_litros' => null,
                'fecha_distribucion' => null,
                'fecha_inicio_atencion' => null,
                'fecha_fin_atencion' => null,
            ]],
            'certificados' => [[
                'producto' => null,
                'laboratorio' => null,
                'numero_certificado' => null,
                'fecha_emision' => null,
                'certificado_microbiologico' => null,
                'numero_lote' => null,
                'fecha_vencimiento' => null,
            ]],
            'composicion' => [[
                'producto' => null,
                'insumo' => null,
                'porcentaje' => null,
            ]],
            'beneficiarios' => ['rural' => $beneficiaries, 'urbana' => $beneficiaries],
            'cantidad_comites_atendidos' => null,
            'presidente_comite_administracion' => null,
            'representante_ministerio_salud' => null,
            'profesion_representante_salud' => null,
        ];
    }
}
