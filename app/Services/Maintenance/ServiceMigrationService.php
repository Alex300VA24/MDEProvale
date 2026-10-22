<?php

namespace App\Services\Maintenance;

use App\Repositories\Contracts\DataMaintenanceRepositoryInterface;
use App\Services\PecosaService;
use App\Services\TransactionService;
use Illuminate\Support\Facades\DB;
use Throwable;

/**
 * Migra Ingresos y Pecosas llamando a los métodos reales del sistema
 * (TransactionService::registerIngreso, PecosaService::createPecosa) en vez
 * de INSERT directo, para que corran las mismas reglas de negocio que usa
 * la app (descuento de stock, snapshot de asociación/socia, etc).
 */
class ServiceMigrationService
{
    public function __construct(
        private TransactionService $transactions,
        private PecosaService $pecosas,
        private DataMaintenanceRepositoryInterface $data,
    ) {
    }

    /**
     * Sin $execute se hace rollback al final (dry-run). Ante un error fatal revierte todo y relanza.
     *
     * @return array{ingresos:array{ok:int,skip:int,fail:int}, pecosas:array{ok:int,skip:int,fail:int}, pendientes:array}
     *
     * @throws Throwable
     */
    public function migrate(array $ingresos, array $pecosas, bool $execute, ProgressReporter $out): array
    {
        $pendientes = [];

        DB::beginTransaction();

        try {
            $ingresosResult = $this->migrateIngresos($ingresos, $pendientes, $out);
            $pecosasResult = $this->migratePecosas($pecosas, $pendientes, $out);

            if ($execute) {
                DB::commit();
            } else {
                DB::rollBack();
            }
        } catch (Throwable $e) {
            DB::rollBack();

            throw $e;
        }

        return ['ingresos' => $ingresosResult, 'pecosas' => $pecosasResult, 'pendientes' => $pendientes];
    }

    private function migrateIngresos(array $ingresos, array &$pendientes, ProgressReporter $out): array
    {
        $result = ['ok' => 0, 'skip' => 0, 'fail' => 0];

        $out->progressStart(count($ingresos));
        foreach ($ingresos as $row) {
            if ($this->data->ingresoExists($row)) {
                $result['skip']++;
                $out->progressAdvance();
                continue;
            }

            try {
                $this->transactions->registerIngreso([
                    'product_id' => $row['product_id'],
                    'quantity' => $row['quantity'],
                    'unit_price' => $row['unit_price'],
                    'start_date' => $row['start_date'],
                    'end_date' => $row['end_date'],
                    'transaction_date' => $row['start_date'],
                    'document_number' => null,
                ]);
                $result['ok']++;
            } catch (Throwable $e) {
                $result['fail']++;
                $pendientes[] = ['ingreso', $row['_producto'] . ' (' . $row['start_date'] . ')', $e->getMessage()];
            }
            $out->progressAdvance();
        }
        $out->progressFinish();

        return $result;
    }

    private function migratePecosas(array $pecosas, array &$pendientes, ProgressReporter $out): array
    {
        $result = ['ok' => 0, 'skip' => 0, 'fail' => 0];

        $out->progressStart(count($pecosas));
        foreach ($pecosas as $row) {
            if ($this->data->pecosaNumberExists($row['pecosa_number'])) {
                $result['skip']++;
                $out->progressAdvance();
                continue;
            }

            $details = [];
            $motivoFalloLote = null;
            foreach ($row['details'] as $detail) {
                $lot = $this->pickFifoLot($detail['product_id'], (float) $detail['quantity']);
                if ($lot === null) {
                    $motivoFalloLote = "Sin lote con stock suficiente para {$detail['_producto']} (cant. {$detail['quantity']})";
                    break;
                }
                $details[] = [
                    'detail_product_id' => $lot,
                    'quantity' => $detail['quantity'],
                ];
            }

            if ($motivoFalloLote !== null) {
                $result['fail']++;
                $pendientes[] = ['pecosa', $row['pecosa_number'], $motivoFalloLote];
                $out->progressAdvance();
                continue;
            }

            try {
                $this->pecosas->createPecosa([
                    'pecosa_number' => $row['pecosa_number'],
                    'observation' => $row['observation'],
                    'delivery_date' => $row['delivery_date'],
                    'state_id' => $row['state_id'],
                    'association_id' => $row['association_id'],
                    'managing_partner_id' => $row['managing_partner_id'],
                    'details' => $details,
                ]);
                $result['ok']++;
            } catch (Throwable $e) {
                $result['fail']++;
                $pendientes[] = ['pecosa', $row['pecosa_number'], $e->getMessage()];
            }
            $out->progressAdvance();
        }
        $out->progressFinish();

        return $result;
    }

    private function pickFifoLot(int $productId, float $needed): ?int
    {
        foreach ($this->data->lotsWithUsage($productId) as $lot) {
            $available = (float) $lot->quantity - (float) ($lot->used_quantity ?? 0);
            if ($available >= $needed) {
                return $lot->id;
            }
        }

        return null;
    }
}
