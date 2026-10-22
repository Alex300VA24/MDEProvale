<?php

namespace App\Repositories;

use App\Models\Association;
use App\Models\BeneficiaryHistory;
use App\Models\DetailPecosa;
use App\Models\DetailProduct;
use App\Models\KnowledgeBaseDocument;
use App\Models\KnowledgeBaseDocumentChunk;
use App\Models\NormativaDocument;
use App\Models\NormativaDocumentChunk;
use App\Models\Partner;
use App\Models\Pecosa;
use App\Models\ProductStock;
use App\Models\PvlDocument;
use App\Models\PvlDocumentChunk;
use App\Models\Transaction;
use App\Repositories\Contracts\DataMaintenanceRepositoryInterface;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Collection as SupportCollection;
use Illuminate\Support\Facades\DB;

class DataMaintenanceRepository implements DataMaintenanceRepositoryInterface
{
    private const CHUNK_MODELS = [
        'kb' => KnowledgeBaseDocumentChunk::class,
        'pvl' => PvlDocumentChunk::class,
        'normativa' => NormativaDocumentChunk::class,
    ];

    private const DOCUMENT_MODELS = [
        'kb' => KnowledgeBaseDocument::class,
        'pvl' => PvlDocument::class,
        'normativa' => NormativaDocument::class,
    ];

    // --- Fechas de socios heredadas de la resolución del comité ---

    public function associationsWithResolutions(): Collection
    {
        return Association::with(['resolution', 'resolutionsHistory'])->get();
    }

    public function partnerIdsWithoutBeginDate(int $associationId): SupportCollection
    {
        return Partner::where('association_id', $associationId)
            ->whereNull('date_begin')
            ->pluck('id');
    }

    public function setPartnersBeginDate(iterable $partnerIds, string $date): void
    {
        Partner::whereIn('id', $partnerIds)->update(['date_begin' => $date]);
    }

    public function placeholderHistoryIds(iterable $partnerIds, string $placeholderDate): SupportCollection
    {
        return BeneficiaryHistory::whereHas('beneficiary', fn ($q) => $q->whereIn('partner_id', $partnerIds))
            ->where('date_begin', $placeholderDate)
            ->pluck('id');
    }

    public function setHistoriesBeginDate(iterable $historyIds, string $date): void
    {
        BeneficiaryHistory::whereIn('id', $historyIds)->update(['date_begin' => $date]);
    }

    // --- Reindexado RAG ---

    public function chunksForReindex(string $pipeline, bool $onlyMissing, int $budget): Collection
    {
        $query = $this->chunkModel($pipeline)::query()->with('document');

        if ($onlyMissing) {
            $this->whereMissingEmbedding($query);
        }

        return $query->limit($budget)->get();
    }

    public function saveEmbedding(Model $chunk, array $embedding): void
    {
        $chunk->forceFill(['embedding' => $embedding])->save();
    }

    public function indexedDocumentCount(string $pipeline): int
    {
        return $this->documentModel($pipeline)::where('index_status', 'INDEXADO')->count();
    }

    public function chunksMissingEmbeddingCount(string $pipeline): int
    {
        return $this->whereMissingEmbedding($this->chunkModel($pipeline)::query())->count();
    }

    public function documentsWithLegacyBinary(string $pipeline, int $limit): Collection
    {
        return $this->documentModel($pipeline)::whereNull('file_path')->whereNotNull('file_data')->limit($limit)->get();
    }

    public function storeBinaryPath(Model $document, string $path): void
    {
        $document->forceFill(['file_path' => $path, 'file_data' => null])->save();
    }

    // --- Migración vía servicios ---

    public function ingresoExists(array $row): bool
    {
        return Transaction::whereHas('typeTransaction', fn ($q) => $q->whereRaw('LOWER(title) = ?', ['ingreso']))
            ->whereHas('detailProduct', function ($q) use ($row) {
                $q->where('product_id', $row['product_id'])
                    ->where('quantity', $row['quantity'])
                    ->where('unit_price', $row['unit_price'])
                    ->where('start_date', $row['start_date']);
            })
            ->exists();
    }

    public function pecosaNumberExists(string $pecosaNumber): bool
    {
        return Pecosa::where('pecosa_number', $pecosaNumber)->exists();
    }

    public function lotsWithUsage(int $productId): Collection
    {
        return DetailProduct::where('product_id', $productId)
            ->withSum('stocks as used_quantity', 'quantity')
            ->orderBy('start_date')
            ->orderBy('id')
            ->get();
    }

    // --- Relleno de detail_pecosas ---

    public function lotsGroupedByProduct(): SupportCollection
    {
        return DetailProduct::orderBy('start_date')->orderBy('id')->get()->groupBy('product_id');
    }

    public function stocksLinkedToPecosasGrouped(): SupportCollection
    {
        return ProductStock::whereNotNull('pecosa_id')
            ->with('detailProduct:id,product_id,unit_price')
            ->get()
            ->groupBy('pecosa_id');
    }

    public function pecosasByNumber(SupportCollection $pecosaNumbers): SupportCollection
    {
        return Pecosa::whereIn('pecosa_number', $pecosaNumbers)
            ->get(['id', 'pecosa_number', 'delivery_date'])
            ->keyBy('pecosa_number');
    }

    public function productsMeta(): SupportCollection
    {
        return DB::table('products')->select('id', 'title', 'abbreviation', 'uom_id')->get()->keyBy('id');
    }

    public function uomTitles(): SupportCollection
    {
        return DB::table('uoms')->pluck('title', 'id');
    }

    public function pecosaHasDetails(int $pecosaId): bool
    {
        return DetailPecosa::where('pecosa_id', $pecosaId)->exists();
    }

    public function createDetailPecosa(array $attributes): void
    {
        DetailPecosa::create($attributes);
    }

    // --- Reconstrucción mensual desde Excel ---

    public function transactionTypeId(string $title): ?int
    {
        $id = DB::table('type_transactions')->whereRaw('LOWER(title) = ?', [$title])->value('id');

        return $id === null ? null : (int) $id;
    }

    public function associationIdsByCode(): array
    {
        return DB::table('associations')->pluck('id', 'code')->toArray();
    }

    public function detailProductRows(): SupportCollection
    {
        return DB::table('detail_products')
            ->get(['id', 'product_id', 'unit_price', 'quantity', 'start_date', 'end_date']);
    }

    public function productTitles(): SupportCollection
    {
        return DB::table('products')->pluck('title', 'id');
    }

    public function pecosaIdsByNumbers(array $pecosaNumbers): SupportCollection
    {
        return DB::table('pecosas')->whereIn('pecosa_number', $pecosaNumbers)->pluck('id');
    }

    public function deleteMonthRecords(SupportCollection $pecosaIds, array $pecosaNumbers, int $salidaTypeId): void
    {
        DB::table('product_stocks')->whereIn('pecosa_id', $pecosaIds)->delete();
        DB::table('detail_pecosas')->whereIn('pecosa_id', $pecosaIds)->delete();

        DB::table('transactions')
            ->where('type_transaction_id', $salidaTypeId)
            ->whereIn('document_number', $pecosaNumbers)
            ->delete();

        DB::table('pecosas')->whereIn('id', $pecosaIds)->delete();
    }

    public function upsertPecosas(array $rows): void
    {
        DB::table('pecosas')->upsert($rows, ['pecosa_number'], [
            'observation', 'delivery_date', 'state_id', 'association_id',
            'association_name', 'association_code', 'beneficiaries_count', 'updated_at',
        ]);
    }

    public function pecosaIdMapByNumber(array $pecosaNumbers): array
    {
        return DB::table('pecosas')
            ->whereIn('pecosa_number', $pecosaNumbers)
            ->pluck('id', 'pecosa_number')
            ->toArray();
    }

    public function pecosaDatesByNumber(array $pecosaNumbers): array
    {
        return DB::table('pecosas')
            ->whereIn('pecosa_number', $pecosaNumbers)
            ->pluck('delivery_date', 'pecosa_number')
            ->toArray();
    }

    public function insertDetailPecosas(array $rows): void
    {
        DB::table('detail_pecosas')->insert($rows);
    }

    public function insertTransactions(array $rows): void
    {
        DB::table('transactions')->insert($rows);
    }

    public function insertProductStocks(array $rows): void
    {
        DB::table('product_stocks')->insert($rows);
    }

    public function pecosaIdsDeliveredBetween(string $from, string $to): SupportCollection
    {
        return DB::table('pecosas')->whereBetween('delivery_date', [$from, $to])->pluck('id');
    }

    public function unlinkedStocksOfPecosas(SupportCollection $pecosaIds): SupportCollection
    {
        return DB::table('product_stocks')
            ->whereIn('pecosa_id', $pecosaIds)
            ->whereNull('transaction_id')
            ->get();
    }

    public function pecosaNumberOf(int $pecosaId): ?string
    {
        return DB::table('pecosas')->where('id', $pecosaId)->value('pecosa_number');
    }

    public function salidaTransactionId(int $salidaTypeId, int $detailProductId, string $documentNumber): ?int
    {
        $id = DB::table('transactions')
            ->where('type_transaction_id', $salidaTypeId)
            ->where('detail_product_id', $detailProductId)
            ->where('document_number', $documentNumber)
            ->value('id');

        return $id === null ? null : (int) $id;
    }

    public function linkStockToTransaction(int $stockId, int $transactionId): void
    {
        DB::table('product_stocks')->where('id', $stockId)->update(['transaction_id' => $transactionId]);
    }

    public function pecosasDeliveredBetweenCount(string $from, string $to): int
    {
        return DB::table('pecosas')->whereBetween('delivery_date', [$from, $to])->count();
    }

    public function detailPecosasDeliveredBetweenCount(string $from, string $to): int
    {
        return DB::table('detail_pecosas')
            ->join('pecosas', 'pecosas.id', '=', 'detail_pecosas.pecosa_id')
            ->whereBetween('pecosas.delivery_date', [$from, $to])
            ->count();
    }

    public function salidasBetweenCount(string $from, string $to): int
    {
        return DB::table('transactions')
            ->join('type_transactions', 'type_transactions.id', '=', 'transactions.type_transaction_id')
            ->where('type_transactions.title', 'Salida')
            ->whereBetween('transactions.transaction_date', [$from, $to])
            ->count();
    }

    public function rosterPartnersBetweenCount(string $from, string $to): int
    {
        return DB::table('partner_roster_periods')
            ->whereBetween('period', [$from, $to])
            ->distinct()
            ->count('partner_id');
    }

    private function whereMissingEmbedding(Builder $query): Builder
    {
        return $query->where(function ($where) {
            $where->whereNull('embedding')->orWhere('embedding', 'like', '%null%');
        });
    }

    /** @return class-string<Model> */
    private function chunkModel(string $pipeline): string
    {
        return self::CHUNK_MODELS[$pipeline];
    }

    /** @return class-string<Model> */
    private function documentModel(string $pipeline): string
    {
        return self::DOCUMENT_MODELS[$pipeline];
    }
}
