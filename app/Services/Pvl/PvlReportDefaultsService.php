<?php

namespace App\Services\Pvl;

use App\Models\Setting;

class PvlReportDefaultsService
{
    public const SETTING_KEY = 'pvl_report_defaults';

    public function definitions(): array
    {
        $definitions = [
            'municipality_name' => $this->definition('Identidad municipal', 'Nombre de la municipalidad', 'Se usa en ambos anexos cuando no existe otro valor estructurado.', ['pvl.municipalidad', 'racion_a.municipalidad'], (string) config('pvl_reports.municipality.name', '')),
            'municipality_type' => $this->definition('Identidad municipal', 'Tipo de municipalidad', 'Ejemplo: MUNICIPALIDAD DISTRITAL.', ['pvl.tipo_municipalidad', 'racion_a.tipo_municipalidad'], (string) config('pvl_reports.municipality.type', '')),
            'department' => $this->definition('Identidad municipal', 'Departamento', 'Departamento institucional usado en ambos anexos.', ['pvl.departamento', 'racion_a.departamento'], (string) config('pvl_reports.municipality.department', '')),
            'province' => $this->definition('Identidad municipal', 'Provincia', 'Provincia institucional usada en ambos anexos.', ['pvl.provincia', 'racion_a.provincia'], (string) config('pvl_reports.municipality.province', '')),
            'pvl_file_number' => $this->definition('Administración del Formato PVL', 'Número de expediente', 'Se reemplaza si el análisis encuentra el expediente real.', ['pvl.numero_expediente']),
            'pvl_submission_code' => $this->definition('Administración del Formato PVL', 'Código de envío', 'Se reemplaza si existe una constancia real.', ['pvl.codigo_envio']),
            'administration_committee_president' => $this->definition('Responsables', 'Presidente del comité de administración', 'Solo se aplica cuando la BD y los respaldos no identifican al responsable.', ['pvl.presidente_comite_administracion', 'racion_a.presidente_comite_administracion']),
            'administration_director' => $this->definition('Responsables', 'Director de administración', 'Valor de respaldo para el Formato PVL.', ['pvl.director_administracion']),
            'health_representative' => $this->definition('Responsables', 'Representante del Ministerio de Salud', 'Valor de respaldo para Ración A.', ['racion_a.representante_ministerio_salud']),
            'health_representative_profession' => $this->definition('Responsables', 'Profesión del representante de Salud', 'Valor de respaldo para Ración A.', ['racion_a.profesion_representante_salud']),
            'pvl_operating_expenses' => $this->definition('Gastos y financiamiento PVL', 'Gastos operativos', 'Importe usado solo si ninguna fuente real lo informa.', ['pvl.total_gastos_operativos'], null, 'number'),
            'pvl_opening_treasury_balance' => $this->definition('Gastos y financiamiento PVL', 'Saldo inicial del Tesoro', 'Componente de financiamiento.', ['pvl.financiamiento.saldo_inicial_tesoro'], null, 'number'),
            'pvl_treasury_transfer' => $this->definition('Gastos y financiamiento PVL', 'Transferencia del Tesoro', 'Componente de financiamiento.', ['pvl.financiamiento.transferencia_tesoro'], null, 'number'),
            'pvl_directly_collected_resources' => $this->definition('Gastos y financiamiento PVL', 'Recursos directamente recaudados', 'Componente de financiamiento.', ['pvl.financiamiento.recursos_directamente_recaudados'], null, 'number'),
            'pvl_foncomun' => $this->definition('Gastos y financiamiento PVL', 'FONCOMUN', 'Componente de financiamiento.', ['pvl.financiamiento.foncomun'], null, 'number'),
            'pvl_financing_donations' => $this->definition('Gastos y financiamiento PVL', 'Donaciones financieras', 'Componente de financiamiento.', ['pvl.financiamiento.donaciones'], null, 'number'),
            'pvl_interest' => $this->definition('Gastos y financiamiento PVL', 'Intereses', 'Componente de financiamiento.', ['pvl.financiamiento.intereses'], null, 'number'),
            'ration_file_number' => $this->definition('Administración de Ración A', 'Número de expediente', 'Se reemplaza si el análisis encuentra el expediente real.', ['racion_a.numero_expediente']),
            'ration_submission_code' => $this->definition('Administración de Ración A', 'Código de envío', 'Se reemplaza si existe una constancia real.', ['racion_a.codigo_envio']),
            'committees_served' => $this->definition('Resumen de atención', 'Comités atendidos', 'Cantidad usada solo si la BD no aporta el dato.', ['racion_a.cantidad_comites_atendidos'], null, 'number'),
            'supporting_recipient_name' => $this->definition('Informe sustentatorio', 'Destinatario predeterminado', 'Puede reemplazarse al preparar cada informe.', ['report_metadata.recipient_name']),
            'supporting_recipient_role' => $this->definition('Informe sustentatorio', 'Cargo del destinatario', 'Puede reemplazarse al preparar cada informe.', ['report_metadata.recipient_role']),
            'supporting_sender_name' => $this->definition('Informe sustentatorio', 'Remitente predeterminado', 'Puede reemplazarse al preparar cada informe.', ['report_metadata.sender_name']),
            'supporting_sender_role' => $this->definition('Informe sustentatorio', 'Cargo del remitente', 'Puede reemplazarse al preparar cada informe.', ['report_metadata.sender_role']),
            'supporting_place' => $this->definition('Informe sustentatorio', 'Lugar de emisión', 'Puede reemplazarse al preparar cada informe.', ['report_metadata.place'], 'La Esperanza'),
        ];

        $purchaseFields = [
            'classification' => ['clasificacion', 'Clasificación'],
            'product' => ['producto', 'Producto'],
            'brand' => ['marca', 'Marca'],
            'origin' => ['origen', 'Origen'],
            'supplier' => ['proveedor', 'Proveedor'],
            'supplier_ruc' => ['ruc', 'RUC del proveedor'],
            'receipt_type' => ['tipo_comprobante', 'Tipo de comprobante'],
            'receipt_series' => ['serie', 'Serie'],
            'receipt_number' => ['numero_comprobante', 'Número de comprobante'],
            'issue_date' => ['fecha_emision', 'Fecha de emisión', 'date'],
            'quantity_kg' => ['cantidad_kg', 'Cantidad en kg', 'number'],
            'quantity_liters' => ['cantidad_litros', 'Cantidad en litros', 'number'],
            'amount' => ['importe', 'Importe', 'number'],
        ];
        $definitions += $this->rowDefinitions('pvl_food', 'Compras de alimentos · plantilla', 'pvl.compras_alimentos', $purchaseFields);
        $definitions += $this->rowDefinitions('pvl_supply', 'Compras de insumos · plantilla', 'pvl.compras_insumos', $purchaseFields);
        $definitions += $this->rowDefinitions('pvl_donation', 'Donaciones · plantilla', 'pvl.donaciones', [
            'product' => ['producto', 'Producto'],
            'brand' => ['marca', 'Marca'],
            'origin' => ['origen', 'Origen'],
            'donor' => ['donante', 'Donante'],
            'quantity_kg' => ['cantidad_kg', 'Cantidad en kg', 'number'],
            'quantity_liters' => ['cantidad_litros', 'Cantidad en litros', 'number'],
            'amount' => ['importe', 'Importe', 'number'],
        ]);
        $definitions += $this->rowDefinitions('ration_single', 'Ración de un alimento · plantilla', 'racion_a.raciones_un_alimento', [
            'food' => ['alimento', 'Alimento'],
            'grams' => ['gramos', 'Gramos', 'number'],
            'cc' => ['cc', 'Centímetros cúbicos', 'number'],
            'priority_1_days' => ['dias_prioridad_1', 'Días prioridad 1', 'number'],
            'priority_2_days' => ['dias_prioridad_2', 'Días prioridad 2', 'number'],
            'type' => ['tipo_racion', 'Tipo de ración'],
        ]);
        $definitions += $this->rowDefinitions('ration_compound', 'Ración compuesta · plantilla', 'racion_a.raciones_compuestas', [
            'food_1' => ['alimento1', 'Alimento 1'],
            'food_1_grams' => ['alimento1_gramos', 'Alimento 1 · gramos', 'number'],
            'food_1_cc' => ['alimento1_cc', 'Alimento 1 · cc', 'number'],
            'food_2' => ['alimento2', 'Alimento 2'],
            'food_2_grams' => ['alimento2_gramos', 'Alimento 2 · gramos', 'number'],
            'food_2_cc' => ['alimento2_cc', 'Alimento 2 · cc', 'number'],
            'food_3' => ['alimento3', 'Alimento 3'],
            'food_3_grams' => ['alimento3_gramos', 'Alimento 3 · gramos', 'number'],
            'food_3_cc' => ['alimento3_cc', 'Alimento 3 · cc', 'number'],
            'priority_1_days' => ['dias_prioridad_1', 'Días prioridad 1', 'number'],
            'priority_2_days' => ['dias_prioridad_2', 'Días prioridad 2', 'number'],
            'type' => ['tipo_racion', 'Tipo de ración'],
        ]);
        $definitions += $this->rowDefinitions('distribution', 'Distribución · plantilla', 'racion_a.distribuciones', [
            'product' => ['producto', 'Producto'],
            'quantity_kg' => ['cantidad_kg', 'Cantidad en kg', 'number'],
            'quantity_liters' => ['cantidad_litros', 'Cantidad en litros', 'number'],
            'date' => ['fecha_distribucion', 'Fecha de distribución', 'date'],
            'attention_start' => ['fecha_inicio_atencion', 'Inicio de atención', 'date'],
            'attention_end' => ['fecha_fin_atencion', 'Fin de atención', 'date'],
        ]);
        $definitions += $this->rowDefinitions('certificate', 'Certificado de calidad · plantilla', 'racion_a.certificados', [
            'product' => ['producto', 'Producto'],
            'laboratory' => ['laboratorio', 'Laboratorio'],
            'number' => ['numero_certificado', 'Número de certificado'],
            'issue_date' => ['fecha_emision', 'Fecha de emisión', 'date'],
            'microbiological' => ['certificado_microbiologico', 'Certificado microbiológico', 'select', [['value' => '1', 'label' => 'Sí'], ['value' => '0', 'label' => 'No']]],
            'batch_number' => ['numero_lote', 'Número de lote'],
            'expiration_date' => ['fecha_vencimiento', 'Fecha de vencimiento', 'date'],
        ]);
        $definitions += $this->rowDefinitions('composition', 'Composición · plantilla', 'racion_a.composicion', [
            'product' => ['producto', 'Producto'],
            'ingredient' => ['insumo', 'Insumo'],
            'percentage' => ['porcentaje', 'Porcentaje', 'number'],
        ]);
        $definitions += $this->beneficiaryDefinitions('rural', 'Beneficiarios rurales');
        $definitions += $this->beneficiaryDefinitions('urbana', 'Beneficiarios urbanos');

        return $definitions;
    }

    public function values(): array
    {
        $stored = json_decode((string) Setting::get(self::SETTING_KEY, '{}'), true);
        $stored = is_array($stored) ? $stored : [];

        return collect($this->definitions())->mapWithKeys(function (array $definition, string $key) use ($stored) {
            $value = array_key_exists($key, $stored) ? $stored[$key] : $definition['fallback'];

            return [$key => $this->clean($value)];
        })->all();
    }

    public function resource(): array
    {
        $values = $this->values();

        return collect($this->definitions())->map(function (array $definition, string $key) use ($values) {
            return [
                'key' => $key,
                'group' => $definition['group'],
                'label' => $definition['label'],
                'description' => $definition['description'],
                'value' => $values[$key],
                'max_length' => $definition['max_length'],
                'input_type' => $definition['input_type'],
                'options' => $definition['options'],
                'is_template' => $definition['is_template'],
            ];
        })->values()->all();
    }

    public function update(array $values): array
    {
        $clean = [];
        foreach ($this->definitions() as $key => $definition) {
            $value = $this->clean($values[$key] ?? null);
            if ($value !== null) {
                $clean[$key] = mb_substr($value, 0, $definition['max_length']);
            }
        }

        Setting::put(self::SETTING_KEY, json_encode($clean, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES));

        return $this->resource();
    }

    public function apply(array $context, array $reportMetadata = []): array
    {
        $values = $this->values();
        $context['report_metadata'] = $reportMetadata;
        $applied = [];

        foreach ($this->definitions() as $key => $definition) {
            $value = $values[$key] ?? null;
            if ($value === null) {
                continue;
            }

            foreach ($definition['targets'] as $target) {
                $this->applyTarget($context, $target, $value, $definition['label'], $applied);
            }
        }

        if ($this->hasAppliedAmount($applied, 'pvl.compras_alimentos')) {
            data_set($context, 'pvl.total_compras_alimentos', null);
        }
        if ($this->hasAppliedAmount($applied, 'pvl.compras_insumos')) {
            data_set($context, 'pvl.total_compras_insumos', null);
        }

        $context['meta']['campos_predeterminados'] = array_values(array_unique($applied));

        return $context;
    }

    private function hasAppliedAmount(array $applied, string $collectionPath): bool
    {
        return collect($applied)->contains(fn (string $path) => str_starts_with($path, $collectionPath.'.')
            && str_ends_with($path, '.importe'));
    }

    private function applyTarget(array &$context, string $target, string $value, string $label, array &$applied): void
    {
        if (str_contains($target, '.*.')) {
            [$collectionPath, $fieldPath] = explode('.*.', $target, 2);
            $root = strtok($collectionPath, '.');
            if (! is_array($context[$root] ?? null)) {
                return;
            }

            $rows = data_get($context, $collectionPath);
            if (! is_array($rows)) {
                return;
            }
            if ($rows === []) {
                data_set($context, $collectionPath, [[]]);
                $rows = [[]];
            }

            foreach (array_keys($rows) as $index) {
                $this->applyExactTarget($context, $collectionPath.'.'.$index.'.'.$fieldPath, $value, $label, $applied);
            }

            return;
        }

        $this->applyExactTarget($context, $target, $value, $label, $applied);
    }

    private function applyExactTarget(array &$context, string $target, string $value, string $label, array &$applied): void
    {
        $root = strtok($target, '.');
        if ($root !== 'report_metadata' && ! is_array($context[$root] ?? null)) {
            return;
        }
        if (! $this->isMissing(data_get($context, $target))) {
            return;
        }

        data_set($context, $target, $value);
        $context['trazabilidad'][] = [
            'campo' => $target,
            'valor' => $value,
            'origen' => 'PREDETERMINADO',
            'entidad' => 'settings',
            'referencia' => $label,
        ];
        $applied[] = $target;
    }

    private function rowDefinitions(string $keyPrefix, string $group, string $collectionPath, array $fields): array
    {
        $definitions = [];
        foreach ($fields as $key => $field) {
            [$path, $label, $inputType, $options] = array_pad($field, 4, null);
            $definitions[$keyPrefix.'_'.$key] = $this->definition(
                $group,
                $label,
                'Completa campos vacíos de cada fila; una fuente real siempre lo reemplaza.',
                [$collectionPath.'.*.'.$path],
                null,
                $inputType ?: 'text',
                $options ?: [],
                true
            );
        }

        return $definitions;
    }

    private function beneficiaryDefinitions(string $zone, string $group): array
    {
        $labels = [
            'menores_1_anio' => 'Menores de 1 año',
            'ninos_1_a_6' => 'Niños de 1 a 6 años',
            'madres_gestantes' => 'Madres gestantes',
            'madres_lactantes' => 'Madres lactantes',
            'personas_7_a_13' => 'Personas de 7 a 13 años',
            'personas_tbc' => 'Personas con TBC',
            'ancianos' => 'Adultos mayores',
            'discapacitados' => 'Personas con discapacidad',
        ];

        $definitions = [];
        foreach ($labels as $field => $label) {
            $definitions['beneficiaries_'.$zone.'_'.$field] = $this->definition(
                $group,
                $label,
                'Cantidad usada solo cuando el padrón no aporta este valor.',
                ['racion_a.beneficiarios.'.$zone.'.'.$field],
                null,
                'number'
            );
        }

        return $definitions;
    }

    private function definition(
        string $group,
        string $label,
        string $description,
        array $targets,
        ?string $fallback = null,
        string $inputType = 'text',
        array $options = [],
        bool $isTemplate = false,
    ): array {
        return [
            'group' => $group,
            'label' => $label,
            'description' => $description,
            'targets' => $targets,
            'fallback' => $fallback,
            'max_length' => $inputType === 'number' ? 40 : 200,
            'input_type' => $inputType,
            'options' => $options,
            'is_template' => $isTemplate,
        ];
    }

    private function clean(mixed $value): ?string
    {
        if (! is_scalar($value)) {
            return null;
        }

        $value = trim((string) $value);

        return $value === '' ? null : $value;
    }

    private function isMissing(mixed $value): bool
    {
        return $value === null || (is_string($value) && trim($value) === '');
    }
}
