<?php

namespace App\Console\Commands;

use App\Models\Pecosa;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use PhpOffice\PhpSpreadsheet\IOFactory;
use Carbon\Carbon;

class RebuildMonthlyData extends Command
{
    protected $signature = 'data:rebuild
        {--month= : Mes a reconstruir (YYYY-MM)}
        {--all : Reconstruir todos los meses Mar-Ago 2026}
        {--execute : Ejecutar realmente (por defecto es dry-run)}
        {--excel= : Ruta al archivo Excel}';

    protected $description = 'Reconstruir PECOSAs, detail_pecosas, transactions y product_stocks desde Excel fuente';

    private string $excelPath;
    private bool $dryRun;
    private int $typeSalidaId;
    private int $typeIngresoId;
    private array $associations = [];
    private \Illuminate\Support\Collection $detailProducts;
    private array $dpByProductName = [];

    public function handle(): int
    {
        $this->dryRun = !$this->option('execute');
        $this->excelPath = $this->option('excel') ?? base_path('migracion_productos/datos_sqlserver_v5.xlsx');

        if (!file_exists($this->excelPath)) {
            $this->error("Excel no encontrado: {$this->excelPath}");
            return 1;
        }

        $this->typeSalidaId = DB::table('type_transactions')->whereRaw('LOWER(title) = ?', ['salida'])->value('id');
        $this->typeIngresoId = DB::table('type_transactions')->whereRaw('LOWER(title) = ?', ['ingreso'])->value('id');

        if (!$this->typeSalidaId || !$this->typeIngresoId) {
            $this->error('TypeTransactions "Salida" o "Ingreso" no encontrados');
            return 1;
        }

        $this->associations = DB::table('associations')->pluck('id', 'code')->toArray();
        $this->detailProducts = DB::table('detail_products')
            ->get(['id', 'product_id', 'unit_price', 'quantity', 'start_date', 'end_date']);

        $this->dpByProductName = [];
        foreach ($this->detailProducts as $dp) {
            $product = DB::table('products')->where('id', $dp->product_id)->first();
            if (!$product) continue;
            $name = $product->title;
            if (!isset($this->dpByProductName[$name])) {
                $this->dpByProductName[$name] = [];
            }
            $this->dpByProductName[$name][] = $dp;
        }

        $months = [];
        if ($this->option('all')) {
            $months = ['2026-03', '2026-04', '2026-05', '2026-06', '2026-07', '2026-08'];
        } elseif ($this->option('month')) {
            $months = [$this->option('month')];
        } else {
            $this->error('Especifique --month=YYYY-MM o --all');
            return 1;
        }

        $this->info('Modo: ' . ($this->dryRun ? 'DRY-RUN (solo lectura)' : 'EJECUTAR'));
        $this->info('Excel: ' . $this->excelPath);
        $this->info('Meses: ' . implode(', ', $months));
        $this->newLine();

        ini_set('memory_limit', '1024M');
        $reader = IOFactory::createReaderForFile($this->excelPath);
        $reader->setReadDataOnly(true);
        $reader->setLoadSheetsOnly(['pecosas', 'detail_pecosas', 'transactions']);
        $spreadsheet = $reader->load($this->excelPath);

        foreach ($months as $month) {
            $this->processMonth($spreadsheet, $month);
        }

        $spreadsheet->disconnectWorksheets();
        $this->newLine();
        $this->info('Proceso completado.');
        return 0;
    }

    private function excelSerialToDate(float $serial): ?string
    {
        if ($serial < 1) return null;
        $unixEpoch = Carbon::create(1899, 12, 30);
        return $unixEpoch->copy()->addDays((int) $serial)->toDateString();
    }

    private function processMonth($spreadsheet, string $month): void
    {
        $this->info("=== {$month} ===");

        $start = Carbon::parse($month . '-01');
        $end = $start->copy()->endOfMonth();

        $pecosasExcel = $this->readPecosasMonth($spreadsheet, $start, $end);
        $detailsExcel = $this->readDetailPecosasMonth($spreadsheet, $pecosasExcel);
        $ingresosExcel = $this->readIngresosMonth($spreadsheet, $start, $end);

        $this->info('  PECOSAs en Excel: ' . count($pecosasExcel));
        $this->info('  Detalles en Excel: ' . count($detailsExcel));
        $this->info('  Ingresos en Excel: ' . count($ingresosExcel));

        if ($this->dryRun) {
            $this->dryRunReport($month, $pecosasExcel, $detailsExcel, $ingresosExcel);
            return;
        }

        DB::beginTransaction();
        try {
            $this->deleteMonthData($start, $end, array_column($pecosasExcel, 'pecosa_number'));

            $pecosaIdMap = $this->createPecosasMonth($pecosasExcel);
            $counts = $this->createDetailsTransactionsStocks($detailsExcel, $pecosaIdMap, $ingresosExcel, $start);

            DB::commit();
            $this->info("  Creados: " . count($pecosaIdMap) . " PECOSAs, {$counts['details']} detalles, {$counts['tx']} transacciones, {$counts['stocks']} stocks");
        } catch (\Throwable $e) {
            DB::rollBack();
            $this->error('  ROLLBACK: ' . $e->getMessage());
            throw $e;
        }

        $this->newLine();
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

    private function deleteMonthData(Carbon $start, Carbon $end, array $pecNumbers): void
    {
        $pecosaIds = DB::table('pecosas')
            ->whereIn('pecosa_number', $pecNumbers)
            ->pluck('id');

        if ($pecosaIds->isEmpty()) {
            $this->info('  No hay datos existentes para eliminar');
            return;
        }

        DB::table('product_stocks')->whereIn('pecosa_id', $pecosaIds)->delete();
        DB::table('detail_pecosas')->whereIn('pecosa_id', $pecosaIds)->delete();

        DB::table('transactions')
            ->where('type_transaction_id', $this->typeSalidaId)
            ->whereIn('document_number', $pecNumbers)
            ->delete();

        DB::table('pecosas')->whereIn('id', $pecosaIds)->delete();

        $this->info('  Datos eliminados para el mes (' . $pecosaIds->count() . ' PECOSAs)');
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
                $this->warn("  Asociacion no encontrada: {$row['association_code']} (PEC {$row['pecosa_number']})");
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
                DB::table('pecosas')->upsert($lote, ['pecosa_number'], [
                    'observation', 'delivery_date', 'state_id', 'association_id',
                    'association_name', 'association_code', 'beneficiaries_count', 'updated_at',
                ]);
                $lote = [];
            }
        }

        if ($lote) {
            DB::table('pecosas')->upsert($lote, ['pecosa_number'], [
                'observation', 'delivery_date', 'state_id', 'association_id',
                'association_name', 'association_code', 'beneficiaries_count', 'updated_at',
            ]);
        }

        if ($skipped > 0) {
            $this->warn("  {$skipped} PECOSAs omitidas (asociacion no encontrada)");
        }

        $allPecNumbers = array_column($pecosasExcel, 'pecosa_number');
        $insertedPecNumbers = DB::table('pecosas')
            ->whereIn('pecosa_number', $allPecNumbers)
            ->pluck('id', 'pecosa_number')
            ->toArray();

        $insertedPecDates = DB::table('pecosas')
            ->whereIn('pecosa_number', $allPecNumbers)
            ->pluck('delivery_date', 'pecosa_number')
            ->toArray();

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
                $this->warn("  Lote no resuelto: origin_pro_id={$row['origin_pro_id']} (PEC {$row['pecosa_number']})");
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
                DB::table('detail_pecosas')->insert($loteDetails);
                DB::table('transactions')->insert($loteTx);
                DB::table('product_stocks')->insert($loteStocks);
                $loteDetails = [];
                $loteTx = [];
                $loteStocks = [];
            }
        }

        if ($loteDetails) {
            DB::table('detail_pecosas')->insert($loteDetails);
            DB::table('transactions')->insert($loteTx);
            DB::table('product_stocks')->insert($loteStocks);
        }

        foreach ($ingresosExcel as $row) {
            if (!$row['detail_product_id']) continue;
            DB::table('transactions')->insert([
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
            ]);
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

        $pecosaIds = DB::table('pecosas')
            ->whereBetween('delivery_date', [$monthStart->toDateString(), $monthEnd->toDateString()])
            ->pluck('id');

        $stocks = DB::table('product_stocks')
            ->whereIn('pecosa_id', $pecosaIds)
            ->whereNull('transaction_id')
            ->get();

        $linked = 0;
        foreach ($stocks as $stock) {
            $pecNumber = DB::table('pecosas')->where('id', $stock->pecosa_id)->value('pecosa_number');
            if (!$pecNumber) continue;

            $tx = DB::table('transactions')
                ->where('type_transaction_id', $this->typeSalidaId)
                ->where('detail_product_id', $stock->detail_product_id)
                ->where('document_number', $pecNumber)
                ->value('id');

            if ($tx) {
                DB::table('product_stocks')
                    ->where('id', $stock->id)
                    ->update(['transaction_id' => $tx]);
                $linked++;
            }
        }

        $this->info("  Stocks vinculados a transacciones: {$linked}/" . $stocks->count());
    }

    private function dryRunReport(string $month, array $pecosas, array $details, array $ingresos): void
    {
        $this->info("  [DRY-RUN] Se crearian:");

        $nextMonth = Carbon::parse($month . '-01')->addMonth()->toDateString();

        $existingPecosas = DB::table('pecosas')
            ->whereBetween('delivery_date', [$month . '-01', $nextMonth])
            ->count();

        $existingDetails = DB::table('detail_pecosas')
            ->join('pecosas', 'pecosas.id', '=', 'detail_pecosas.pecosa_id')
            ->whereBetween('pecosas.delivery_date', [$month . '-01', $nextMonth])
            ->count();

        $existingSalidas = DB::table('transactions')
            ->join('type_transactions', 'type_transactions.id', '=', 'transactions.type_transaction_id')
            ->where('type_transactions.title', 'Salida')
            ->whereBetween('transactions.transaction_date', [$month . '-01', $nextMonth])
            ->count();

        $this->info("    PECOSAs: " . count($pecosas) . " (existentes: {$existingPecosas})");
        $this->info("    Detalles: " . count($details) . " (existentes: {$existingDetails})");
        $this->info("    Transacciones SALIDA: " . count($details) . " (existentes: {$existingSalidas})");
        $this->info("    Product stocks: " . count($details));
        $this->info("    Ingresos: " . count($ingresos));

        $clubsInRoster = DB::table('partner_roster_periods')
            ->whereBetween('period', [$month . '-01', $nextMonth])
            ->distinct()
            ->count('partner_id');

        $clubsWithPecosa = count(array_unique(array_column($pecosas, 'association_code')));
        $this->info("    Socias en padron: {$clubsInRoster}");
        $this->info("    Clubes con PECOSA en Excel: {$clubsWithPecosa}");
    }
}
