<?php

namespace App\Services;

use App\Models\VerifiedDocument;
use App\Repositories\Contracts\ReportRepositoryInterface;
use Illuminate\Support\Str;
use InvalidArgumentException;

class ReportGeneratorService
{
    public function __construct(
        private ReportRepositoryInterface $reports,
        private PDFService $pdfService,
        private VerifiedDocumentService $verifiedDocuments
    ) {
    }

    /**
     * Definición de entidades disponibles para el generador de reportes.
     * Cada columna se resuelve sobre el modelo ya cargado con sus relaciones
     * (ver métodos build*Rows) mediante dot-notation simple o closures.
     */
    public function entidades(): array
    {
        return [
            'comites' => [
                'label' => 'Comités / Club de Madres',
                'icon' => 'fa-people-roof',
                'columns' => [
                    'code' => 'Código',
                    'company_name' => 'Razón Social',
                    'name' => 'Club de Madres',
                    'address' => 'Dirección',
                    'zona' => 'Zona',
                    'sector' => 'Sector',
                    'presidenta' => 'Presidenta',
                    'beneficiarios' => 'N° Beneficiarios',
                    'estado' => 'Estado',
                ],
                'default_columns' => ['code', 'name', 'zona', 'sector', 'presidenta', 'beneficiarios', 'estado'],
                'group_by' => ['zona' => 'Zona', 'sector' => 'Sector', 'estado' => 'Estado'],
                'filters' => [
                    'place_sector_id' => ['label' => 'Sector', 'type' => 'select', 'source' => 'place_sectors'],
                    'state_id' => ['label' => 'Estado', 'type' => 'select', 'source' => 'states_association'],
                ],
            ],
            'beneficiarios' => [
                'label' => 'Socios y Beneficiarios',
                'icon' => 'fa-users',
                'columns' => [
                    'dni' => 'DNI',
                    'nombre' => 'Nombres y Apellidos',
                    'parentesco' => 'Parentesco',
                    'socia' => 'Socia Titular',
                    'comite' => 'Comité',
                ],
                'default_columns' => ['dni', 'nombre', 'parentesco', 'socia', 'comite'],
                'group_by' => ['comite' => 'Comité', 'parentesco' => 'Parentesco'],
                'filters' => [
                    'association_id' => ['label' => 'Comité', 'type' => 'select', 'source' => 'associations'],
                    'relationship_id' => ['label' => 'Parentesco', 'type' => 'select', 'source' => 'relationships'],
                ],
            ],
            'productos' => [
                'label' => 'Productos',
                'icon' => 'fa-box',
                'columns' => [
                    'code' => 'Código',
                    'title' => 'Producto',
                    'abbreviation' => 'Abrev.',
                    'uom' => 'Unidad',
                    'stock' => 'Stock Disponible',
                    'estado' => 'Estado',
                ],
                'default_columns' => ['code', 'title', 'uom', 'stock', 'estado'],
                'group_by' => ['estado' => 'Estado'],
                'filters' => [
                    'state_id' => ['label' => 'Estado', 'type' => 'select', 'source' => 'states_product'],
                ],
            ],
            'pecosas' => [
                'label' => 'Pecosas',
                'icon' => 'fa-file-alt',
                'columns' => [
                    'pecosa_number' => 'N° Pecosa',
                    'delivery_date' => 'Fecha Entrega',
                    'comite' => 'Comité',
                    'codigo_comite' => 'Cód. Comité',
                    'presidenta' => 'Presidenta',
                    'beneficiarios' => 'N° Beneficiarios',
                    'estado' => 'Estado',
                ],
                'default_columns' => ['pecosa_number', 'delivery_date', 'comite', 'presidenta', 'beneficiarios', 'estado'],
                'group_by' => ['comite' => 'Comité', 'estado' => 'Estado'],
                'filters' => [
                    'association_id' => ['label' => 'Comité', 'type' => 'select', 'source' => 'associations'],
                    'date_from' => ['label' => 'Desde', 'type' => 'date'],
                    'date_to' => ['label' => 'Hasta', 'type' => 'date'],
                ],
            ],
            'movimientos' => [
                'label' => 'Movimientos / Kardex',
                'icon' => 'fa-exchange-alt',
                'columns' => [
                    'transaction_date' => 'Fecha',
                    'document_number' => 'N° Documento',
                    'producto' => 'Producto',
                    'tipo' => 'Tipo',
                    'quantity' => 'Cantidad',
                    'unit_price' => 'P. Unitario',
                    'total_price' => 'Total',
                ],
                'default_columns' => ['transaction_date', 'document_number', 'producto', 'tipo', 'quantity', 'total_price'],
                'group_by' => ['tipo' => 'Tipo', 'producto' => 'Producto'],
                'filters' => [
                    'type_transaction_id' => ['label' => 'Tipo de Movimiento', 'type' => 'select', 'source' => 'type_transactions'],
                    'date_from' => ['label' => 'Desde', 'type' => 'date'],
                    'date_to' => ['label' => 'Hasta', 'type' => 'date'],
                ],
            ],
        ];
    }

    public function config(): array
    {
        $sources = [
            'place_sectors' => $this->reports->placeSectors()
                ->map(fn ($ps) => ['id' => $ps->id, 'name' => trim(($ps->place->title ?? '') . ' - ' . ($ps->sector->title ?? ''))]),
            'states_association' => $this->reports->associationStates(),
            'states_product' => $this->reports->productStates(),
            'associations' => $this->reports->associations(),
            'relationships' => $this->reports->relationships(),
            'type_transactions' => $this->reports->typeTransactions(),
        ];

        return ['entidades' => $this->entidades(), 'sources' => $sources];
    }

    /**
     * Genera el PDF del reporte solicitado.
     *
     * @return array{0:string,1:string} contenido binario y nombre de archivo
     *
     * @throws InvalidArgumentException si no hay ninguna entidad válida seleccionada
     */
    public function generate(array $entidades, array $columnasInput, array $filtrosInput, array $agruparInput, ?int $userId): array
    {
        $entidadesDefs = $this->entidades();
        $seleccionadas = array_values(array_intersect($entidades, array_keys($entidadesDefs)));

        if (empty($seleccionadas)) {
            throw new InvalidArgumentException('Selecciona al menos una entidad para el reporte.');
        }

        $secciones = [];
        $resumen = [];

        foreach ($seleccionadas as $entidad) {
            $definicion = $entidadesDefs[$entidad];

            $columnasSolicitadas = array_values(array_intersect(
                (array) ($columnasInput[$entidad] ?? []),
                array_keys($definicion['columns'])
            ));
            if (empty($columnasSolicitadas)) {
                $columnasSolicitadas = $definicion['default_columns'];
            }

            $groupBy = $agruparInput[$entidad] ?? null;
            if ($groupBy && !isset($definicion['group_by'][$groupBy])) {
                $groupBy = null;
            }

            $filtros = (array) ($filtrosInput[$entidad] ?? []);

            $rows = match ($entidad) {
                'comites' => $this->buildComitesRows($filtros),
                'beneficiarios' => $this->buildBeneficiariosRows($filtros),
                'productos' => $this->buildProductosRows($filtros),
                'pecosas' => $this->buildPecosasRows($filtros),
                'movimientos' => $this->buildMovimientosRows($filtros),
                default => [],
            };

            if ($groupBy) {
                usort($rows, fn ($a, $b) => strcmp((string) ($a[$groupBy] ?? ''), (string) ($b[$groupBy] ?? '')));
            }

            $columnas = [];
            foreach ($columnasSolicitadas as $key) {
                $columnas[] = ['key' => $key, 'label' => $definicion['columns'][$key]];
            }

            $filtrosAplicados = [];
            foreach ($filtros as $key => $value) {
                if ($value === null || $value === '') continue;
                $label = $definicion['filters'][$key]['label'] ?? $key;
                $filtrosAplicados[] = "{$label}: {$value}";
            }

            $secciones[] = [
                'entidad' => $entidad,
                'titulo' => mb_strtoupper($definicion['label']),
                'columnas' => $columnas,
                'rows' => $rows,
                'group_by' => $groupBy,
                'group_label' => $groupBy ? $definicion['group_by'][$groupBy] : null,
                'filtros_aplicados' => $filtrosAplicados,
            ];

            $resumen[] = [
                'label' => $definicion['label'],
                'total' => count($rows),
            ];
        }

        $titulo = count($secciones) === 1
            ? 'REPORTE DE ' . $secciones[0]['titulo']
            : 'REPORTE GENERAL DE PADRONES';

        $data = [
            'titulo' => $titulo,
            'secciones' => $secciones,
            'resumen' => $resumen,
            'total_general' => array_sum(array_column($resumen, 'total')),
            'fecha' => date('d/m/Y'),
            'hora' => date('H:i:s'),
        ];

        $filename = 'reporte_' . implode('-', $seleccionadas) . '_' . date('Ymd_His') . '.pdf';

        if (count($seleccionadas) === 1 && $seleccionadas[0] === 'pecosas') {
            $identifier = 'PEC-' . now()->format('Ymd-His') . '-' . strtoupper(Str::random(6));
            [, $contents, $safeFilename] = $this->verifiedDocuments->issue(
                VerifiedDocument::TYPE_PECOSA_REGISTER,
                $identifier,
                [
                    'filtros' => $filtrosInput['pecosas'] ?? [],
                    'registros' => $data['total_general'],
                ],
                'reportes.padron_generico',
                $data,
                $filename,
                $userId,
                'a4',
                'landscape'
            );

            return [$contents, $safeFilename];
        }

        $contents = $this->pdfService->generate('reportes.padron_generico', $data, 'a4', 'landscape')->output();

        return [$contents, $filename];
    }

    private function buildComitesRows(array $filtros): array
    {
        return $this->reports->committees($filtros)->map(function ($a) {
            return [
                'code' => $a->code,
                'company_name' => $a->company_name,
                'name' => $a->name,
                'address' => $a->address,
                'zona' => $a->placeSector->place->title ?? '',
                'sector' => $a->placeSector->sector->title ?? '',
                'presidenta' => $a->president_name ?? $a->getPresidentName() ?? '',
                'beneficiarios' => $a->partners->count(),
                'estado' => $a->state->title ?? '',
            ];
        })->toArray();
    }

    private function buildBeneficiariosRows(array $filtros): array
    {
        return $this->reports->beneficiaries($filtros)->map(function ($b) {
            $person = $b->person;
            return [
                'dni' => $person->dni ?? '',
                'nombre' => $person ? trim($person->names . ' ' . $person->father_lastname . ' ' . $person->mother_lastname) : '',
                'parentesco' => $b->relationship->title ?? '',
                'socia' => $b->partner->name ?? '',
                'comite' => $b->partner->association->name ?? '',
            ];
        })->toArray();
    }

    private function buildProductosRows(array $filtros): array
    {
        return $this->reports->products($filtros)->map(function ($p) {
            return [
                'code' => $p->code,
                'title' => $p->title,
                'abbreviation' => $p->abbreviation,
                'uom' => $p->uom->title ?? '',
                'stock' => $p->stock,
                'estado' => $p->state->title ?? '',
            ];
        })->toArray();
    }

    private function buildPecosasRows(array $filtros): array
    {
        return $this->reports->pecosas($filtros)->map(function ($p) {
            return [
                'pecosa_number' => $p->pecosa_number,
                'delivery_date' => $p->delivery_date ? $p->delivery_date->format('d/m/Y') : '',
                'comite' => $p->association_name ?: ($p->association->name ?? ''),
                'codigo_comite' => $p->association_code ?: ($p->association->code ?? ''),
                'presidenta' => $p->president_name,
                'beneficiarios' => $p->beneficiaries_count,
                'estado' => $p->state->title ?? '',
            ];
        })->toArray();
    }

    private function buildMovimientosRows(array $filtros): array
    {
        return $this->reports->transactions($filtros)->map(function ($t) {
            return [
                'transaction_date' => $t->transaction_date ? $t->transaction_date->format('d/m/Y') : '',
                'document_number' => $t->document_number,
                'producto' => $t->product_name ?: ($t->detailProduct->product->title ?? ''),
                'tipo' => $t->typeTransaction->title ?? '',
                'quantity' => $t->quantity,
                'unit_price' => $t->unit_price,
                'total_price' => $t->total_price,
            ];
        })->toArray();
    }
}
