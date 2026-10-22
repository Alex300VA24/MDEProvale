<?php

namespace App\Services\Maintenance;

use App\Repositories\Contracts\DataMaintenanceRepositoryInterface;
use Carbon\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use PhpOffice\PhpSpreadsheet\IOFactory;
use Throwable;

/**
 * Reconstruye PECOSAs, detail_pecosas, transactions y product_stocks de un mes
 * desde el Excel fuente. Sin $execute solo reporta lo que se crearía (dry-run).
 */
class MonthlyDataRebuildService
{
    private ProgressReporter $out;

    private bool $dryRun;

    private int $typeSalidaId;

    private int $typeIngresoId;

    private array $associations = [];

    private Collection $detailProducts;

    private array $dpByProductName = [];

    public function __construct(private DataMaintenanceRepositoryInterface $data)
    {
    }

    /**
     * @param  string[]  $months  YYYY-MM
     * @return bool false si faltan los tipos de transacción Salida/Ingreso
     *
     * @throws Throwable
     */
    public function rebuild(string $excelPath, array $months, bool $dryRun, ProgressReporter $out): bool
    {
        $this->out = $out;
        $this->dryRun = $dryRun;

        $salida = $this->data->transactionTypeId('salida');
        $ingreso = $this->data->transactionTypeId('ingreso');

        if (!$salida || !$ingreso) {
            $out->error('TypeTransactions "Salida" o "Ingreso" no encontrados');
            return false;
        }

        $this->typeSalidaId = $salida;
        $this->typeIngresoId = $ingreso;
        $this->associations = $this->data->associationIdsByCode();
        $this->detailProducts = $this->data->detailProductRows();

        $titles = $this->data->productTitles();
        $this->dpByProductName = [];
        foreach ($this->detailProducts as $dp) {
            if (!$titles->has($dp->product_id)) continue;
            $this->dpByProductName[$titles->get($dp->product_id)][] = $dp;
        }

        $out->info('Modo: ' . ($dryRun ? 'DRY-RUN (solo lectura)' : 'EJECUTAR'));
        $out->info('Excel: ' . $excelPath);
        $out->info('Meses: ' . implode(', ', $months));
        $out->newLine();

        ini_set('memory_limit', '1024M');
        $reader = IOFactory::createReaderForFile($excelPath);
        $reader->setReadDataOnly(true);
        $reader->setLoadSheetsOnly(['pecosas', 'detail_pecosas', 'transactions']);
        $spreadsheet = $reader->load($excelPath);

        foreach ($months as $month) {
            $this->processMonth($spreadsheet, $month);
        }

        $spreadsheet->disconnectWorksheets();
        $out->newLine();
        $out->info('Proceso completado.');

        return true;
    }

    private function excelSerialToDate(float $serial): ?string
    {
        if ($serial < 1) return null;
        $unixEpoch = Carbon::create(1899, 12, 30);
        return $unixEpoch->copy()->addDays((int) $serial)->toDateString();
    }

    private function processMonth($spreadsheet, string $month): void
    {
        $this->out->info("=== {$month} ===");

        $start = Carbon::parse($month . '-01');
        $end = $start->copy()->endOfMonth();

        $pecosasExcel = $this->readPecosasMonth($spreadsheet, $start, $end);
        $detailsExcel = $this->readDetailPecosasMonth($spreadsheet, $pecosasExcel);
        $ingresosExcel = $this->readIngresosMonth($spreadsheet, $start, $end);

        $this->out->info('  PECOSAs en Excel: ' . count($pecosasExcel));
        $this->out->info('  Detalles en Excel: ' . count($detailsExcel));
        $this->out->info('  Ingresos en Excel: ' . count($ingresosExcel));

        if ($this->dryRun) {
            $this->dryRunReport($month, $pecosasExcel, $detailsExcel, $ingresosExcel);
            return;
        }

        DB::beginTransaction();
        try {
            $this->deleteMonthData(array_column($pecosasExcel, 'pecosa_number'));

            $pecosaIdMap = $this->createPecosasMonth($pecosasExcel);
            $counts = $this->createDetailsTransactionsStocks($detailsExcel, $pecosaIdMap, $ingresosExcel, $start);

            DB::commit();
            $this->out->info("  Creados: " . count($pecosaIdMap) . " PECOSAs, {$counts['details']} detalles, {$counts['tx']} transacciones, {$counts['stocks']} stocks");
        } catch (Throwable $e) {
            DB::rollBack();
            $this->out->error('  ROLLBACK: ' . $e->getMessage());
            throw $e;
        }

        $this->out->newLine();
    }

    private function readPecosasMonth($spreadsheet, Carbon $start, Carbon $end): array
    {
        $sheet = $spreadsheet->getSheetByName('pecosas');
        if (!$sheet) return [];

        $results = [];
        $highestRow = $sheet->getHighestRow();

        for ($row = 2; $row <= $highestRow; $row++) {
            $migrar = (string) $sheet->getCell("AD{$row}")->getValue();
            if ($migrar !== 'SI') continue;

            $deliveryDateSerial = (float) $sheet->getCell("D{$row}")->getValue();
            $deliveryDate = $this->excelSerialToDate($deliveryDateSerial);
            if (!$deliveryDate || $deliveryDate < $start->toDateString() || $deliveryDate > $end->toDateString()) {
                continue;
            }

            $assocCode = (string) $sheet->getCell("T{$row}")->getValue();

            $results[] = [
                'pecosa_number'    => (string) $sheet->getCell("B{$row}")->getValue(),
                'observation'      => (string) $sheet->getCell("C{$row}")->getValue(),
                'delivery_date'    => $deliveryDate,
                'state_id'         => (int) ($sheet->getCell("I{$row}")->getValue() ?? 4),
                'association_code' => $assocCode,
                'association_name' => (string) $sheet->getCell("S{$row}")->getValue(),
                'beneficiaries_count' => (int) ($sheet->getCell("Y{$row}")->getValue() ?? 0),
                'origin_pec_id'    => (string) $sheet->getCell("AE{$row}")->getValue(),
            ];
        }

        return $results;
    }

    private function readDetailPecosasMonth($spreadsheet, array $pecosasExcel): array
    {
        $sheet = $spreadsheet->getSheetByName('detail_pecosas');
        if (!$sheet) return [];

        $pecNumbers = array_column($pecosasExcel, 'pecosa_number');
        $pecNumSet = array_flip($pecNumbers);

        $results = [];
        $highestRow = $sheet->getHighestRow();

        for ($row = 2; $row <= $highestRow; $row++) {
            $pecNum = (string) $sheet->getCell("Q{$row}")->getValue();
            if (!isset($pecNumSet[$pecNum])) continue;

            $results[] = [
                'priority'             => (int) ($sheet->getCell("B{$row}")->getValue() ?? 1),
                'quantity'             => (int) $sheet->getCell("C{$row}")->getValue(),
                'delivered_quantity'   => (int) ($sheet->getCell("D{$row}")->getValue() ?: $sheet->getCell("C{$row}")->getValue()),
                'unit_price'           => (float) $sheet->getCell("E{$row}")->getValue(),
                'subtotal'             => (float) $sheet->getCell("F{$row}")->getValue(),
                'pecosa_number'        => $pecNum,
                'product_name'         => (string) $sheet->getCell("I{$row}")->getValue(),
                'product_abbreviation' => (string) $sheet->getCell("J{$row}")->getValue(),
                'uom_title'            => (string) $sheet->getCell("K{$row}")->getValue(),
                'origin_pro_id'        => (int) ($sheet->getCell("P{$row}")->getValue() ?? 0),
            ];
        }

        return $results;
    }

    private function readIngresosMonth($spreadsheet, Carbon $start, Carbon $end): array
    {
        $sheet = $spreadsheet->getSheetByName('transactions');
        if (!$sheet) return [];

        $results = [];
        $highestRow = $sheet->getHighestRow();

        for ($row = 2; $row <= $highestRow; $row++) {
            $typeId = (int) $sheet->getCell("I{$row}")->getValue();
            if ($typeId !== 1) continue;

            $txDateSerial = (float) $sheet->getCell("G{$row}")->getValue();
            $txDate = $this->excelSerialToDate($txDateSerial);
            if (!$txDate || $txDate < $start->toDateString() || $txDate > $end->toDateString()) {
                continue;
            }

            $originProId = (int) ($sheet->getCell("O{$row}")->getValue() ?? 0);
            $productName = (string) $sheet->getCell("J{$row}")->getValue();
            $dpId = $this->resolveDetailProductIdByOrigin($originProId, $txDate, $productName);

            $results[] = [
                'quantity'            => (int) $sheet->getCell("B{$row}")->getValue(),
                'unit_price'          => (float) $sheet->getCell("C{$row}")->getValue(),
                'total_price'         => (float) $sheet->getCell("D{$row}")->getValue(),
                'document_number'     => (string) $sheet->getCell("E{$row}")->getValue(),
                'transaction_date'    => $txDate,
                'detail_product_id'   => $dpId,
                'type_transaction_id' => $typeId,
                'product_name'        => (string) $sheet->getCell("J{$row}")->getValue(),
                'uom_title'           => (string) $sheet->getCell("K{$row}")->getValue(),
            ];
        }

        return $results;
    }

    private function deleteMonthData(array $pecNumbers): void
    {
        $pecosaIds = $this->data->pecosaIdsByNumbers($pecNumbers);

        if ($pecosaIds->isEmpty()) {
            $this->out->info('  No hay datos existentes para eliminar');
            return;
        }

        $this->data->deleteMonthRecords($pecosaIds, $pecNumbers, $this->typeSalidaId);

        $this->out->info('  Datos eliminados para el mes (' . $pecosaIds->count() . ' PECOSAs)');
    }

    private function createPecosasMonth(array $pecosasExcel): array
    {
        $idMap = [];
        $ahora = now();
        $lote = [];
        $skipped = 0;

        foreach ($pecosasExcel as $row) {
            $assocId = $this->associations[$row['association_code']] ?? null;
            if (!$assocId) {
                $this->out->warn("  Asociacion no encontrada: {$row['association_code']} (PEC {$row['pecosa_number']})");
                $skipped++;
                continue;
            }

            $lote[] = [
                'pecosa_number'       => $row['pecosa_number'],
                'observation'         => $row['observation'],
                'delivery_date'       => $row['delivery_date'] . ' 00:00:00',
                'state_id'            => $row['state_id'],
                'association_id'      => $assocId,
                'association_name'    => $row['association_name'],
                'association_code'    => $row['association_code'],
                'beneficiaries_count' => $row['beneficiaries_count'],
                'created_at'          => $ahora,
                'updated_at'          => $ahora,
            ];

            if (count($lote) >= 500) {
                $this->data->upsertPecosas($lote);
                $lote = [];
            }
        }

        if ($lote) {
            $this->data->upsertPecosas($lote);
        }

        if ($skipped > 0) {
            $this->out->warn("  {$skipped} PECOSAs omitidas (asociacion no encontrada)");
        }

        $allPecNumbers = array_column($pecosasExcel, 'pecosa_number');
        $insertedPecNumbers = $this->data->pecosaIdMapByNumber($allPecNumbers);
        $insertedPecDates = $this->data->pecosaDatesByNumber($allPecNumbers);

        foreach ($pecosasExcel as $row) {
            if (isset($insertedPecNumbers[$row['pecosa_number']])) {
                $idMap[$row['pecosa_number']] = [
                    'id'            => $insertedPecNumbers[$row['pecosa_number']],
                    'delivery_date' => $insertedPecDates[$row['pecosa_number']] ?? $row['delivery_date']->format('Y-m-d') . ' 00:00:00',
                ];
            }
        }

        return $idMap;
    }

    private function createDetailsTransactionsStocks(array $detailsExcel, array $pecosaIdMap, array $ingresosExcel, Carbon $monthStart): array
    {
        $detailsCount = 0;
        $txCount = 0;
        $stocksCount = 0;
        $ahora = now();
        $loteDetails = [];
        $loteTx = [];
        $loteStocks = [];

        foreach ($detailsExcel as $row) {
            $pecEntry = $pecosaIdMap[$row['pecosa_number']] ?? null;
            if (!$pecEntry) continue;
            $pecosaId = $pecEntry['id'];
            $txDateStr = ($pecEntry['delivery_date'] instanceof Carbon)
                ? $pecEntry['delivery_date']->format('Y-m-d') . ' 00:00:00'
                : $pecEntry['delivery_date'];

            $dpId = $this->resolveDetailProductIdByOrigin($row['origin_pro_id'], $monthStart, $row['product_name']);
            if (!$dpId) {
                $this->out->warn("  Lote no resuelto: origin_pro_id={$row['origin_pro_id']} (PEC {$row['pecosa_number']})");
                continue;
            }

            $dpInfo = $this->detailProducts->firstWhere('id', $dpId);
            $unitPrice = $dpInfo ? (float) $dpInfo->unit_price : $row['unit_price'];
            $subtotal = $row['quantity'] * $unitPrice;

            $loteDetails[] = [
                'priority'             => $row['priority'],
                'quantity'             => $row['quantity'],
                'delivered_quantity'   => $row['delivered_quantity'] ?: $row['quantity'],
                'unit_price'           => $unitPrice,
                'subtotal'             => $subtotal,
                'detail_product_id'    => $dpId,
                'pecosa_id'            => $pecosaId,
                'product_name'         => $row['product_name'],
                'product_abbreviation' => $row['product_abbreviation'],
                'uom_title'            => $row['uom_title'],
                'created_at'           => $ahora,
                'updated_at'           => $ahora,
            ];
            $detailsCount++;

            $loteTx[] = [
                'quantity'            => $row['quantity'],
                'unit_price'          => $unitPrice,
                'total_price'         => $subtotal,
                'document_number'     => $row['pecosa_number'],
                'transaction_date'    => $txDateStr,
                'detail_product_id'   => $dpId,
                'type_transaction_id' => $this->typeSalidaId,
                'product_name'        => $row['product_name'],
                'uom_title'           => $row['uom_title'],
                'created_at'          => $ahora,
                'updated_at'          => $ahora,
            ];
            $txCount++;

            $loteStocks[] = [
                'detail_product_id' => $dpId,
                'pecosa_id'         => $pecosaId,
                'transaction_id'    => null,
                'quantity'          => $row['quantity'],
                'observation'       => "Salida por Pecosa #{$pecosaId}",
                'created_at'        => $ahora,
                'updated_at'        => $ahora,
            ];
            $stocksCount++;

            if (count($loteDetails) >= 500) {
                $this->data->insertDetailPecosas($loteDetails);
                $this->data->insertTransactions($loteTx);
                $this->data->insertProductStocks($loteStocks);
                $loteDetails = [];
                $loteTx = [];
                $loteStocks = [];
            }
        }

        if ($loteDetails) {
            $this->data->insertDetailPecosas($loteDetails);
            $this->data->insertTransactions($loteTx);
            $this->data->insertProductStocks($loteStocks);
        }

        foreach ($ingresosExcel as $row) {
            if (!$row['detail_product_id']) continue;
            $this->data->insertTransactions([[
                'quantity'            => $row['quantity'],
                'unit_price'          => $row['unit_price'],
                'total_price'         => $row['total_price'],
                'document_number'     => $row['document_number'],
                'transaction_date'    => $row['transaction_date'] . ' 00:00:00',
                'detail_product_id'   => $row['detail_product_id'],
                'type_transaction_id' => $row['type_transaction_id'],
                'product_name'        => $row['product_name'],
                'uom_title'           => $row['uom_title'],
                'created_at'          => $ahora,
                'updated_at'          => $ahora,
            ]]);
        }

        $this->linkStocksToTransactions($monthStart);

        return ['details' => $detailsCount, 'tx' => $txCount, 'stocks' => $stocksCount];
    }

    private function resolveDetailProductIdByOrigin(int $originProId, $monthDate, string $productName): ?int
    {
        if (!$originProId) return null;

        $monthStr = $monthDate instanceof Carbon ? $monthDate->toDateString() : $monthDate;

        $candidates = $this->dpByProductName[$productName] ?? [];
        foreach ($candidates as $dp) {
            if ($dp->start_date <= $monthStr && $dp->end_date >= $monthStr) {
                return (int) $dp->id;
            }
        }

        return null;
    }

    private function linkStocksToTransactions(Carbon $monthStart): void
    {
        $monthEnd = $monthStart->copy()->addMonth();

        $pecosaIds = $this->data->pecosaIdsDeliveredBetween($monthStart->toDateString(), $monthEnd->toDateString());
        $stocks = $this->data->unlinkedStocksOfPecosas($pecosaIds);

        $linked = 0;
        foreach ($stocks as $stock) {
            $pecNumber = $this->data->pecosaNumberOf($stock->pecosa_id);
            if (!$pecNumber) continue;

            $tx = $this->data->salidaTransactionId($this->typeSalidaId, $stock->detail_product_id, $pecNumber);

            if ($tx) {
                $this->data->linkStockToTransaction($stock->id, $tx);
                $linked++;
            }
        }

        $this->out->info("  Stocks vinculados a transacciones: {$linked}/" . $stocks->count());
    }

    private function dryRunReport(string $month, array $pecosas, array $details, array $ingresos): void
    {
        $this->out->info("  [DRY-RUN] Se crearian:");

        $from = $month . '-01';
        $nextMonth = Carbon::parse($from)->addMonth()->toDateString();

        $this->out->info("    PECOSAs: " . count($pecosas) . " (existentes: {$this->data->pecosasDeliveredBetweenCount($from, $nextMonth)})");
        $this->out->info("    Detalles: " . count($details) . " (existentes: {$this->data->detailPecosasDeliveredBetweenCount($from, $nextMonth)})");
        $this->out->info("    Transacciones SALIDA: " . count($details) . " (existentes: {$this->data->salidasBetweenCount($from, $nextMonth)})");
        $this->out->info("    Product stocks: " . count($details));
        $this->out->info("    Ingresos: " . count($ingresos));

        $clubsWithPecosa = count(array_unique(array_column($pecosas, 'association_code')));
        $this->out->info("    Socias en padron: {$this->data->rosterPartnersBetweenCount($from, $nextMonth)}");
        $this->out->info("    Clubes con PECOSA en Excel: {$clubsWithPecosa}");
    }
}
