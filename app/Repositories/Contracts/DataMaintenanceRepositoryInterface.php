<?php

namespace App\Repositories\Contracts;

use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Collection as SupportCollection;

/**
 * Consultas de los procesos de mantenimiento y migración de datos
 * (correcciones puntuales, reindexado RAG y reconstrucción mensual).
 */
interface DataMaintenanceRepositoryInterface
{
    // --- Fechas de socios heredadas de la resolución del comité ---

    public function associationsWithResolutions(): Collection;

    public function partnerIdsWithoutBeginDate(int $associationId): SupportCollection;

    public function setPartnersBeginDate(iterable $partnerIds, string $date): void;

    public function placeholderHistoryIds(iterable $partnerIds, string $placeholderDate): SupportCollection;

    public function setHistoriesBeginDate(iterable $historyIds, string $date): void;

    // --- Reindexado RAG (pipelines: kb, pvl, normativa) ---

    public function chunksForReindex(string $pipeline, bool $onlyMissing, int $budget): Collection;

    public function saveEmbedding(Model $chunk, array $embedding): void;

    public function indexedDocumentCount(string $pipeline): int;

    public function chunksMissingEmbeddingCount(string $pipeline): int;

    /** Documentos con binario aún guardado en la BD (pipelines: kb, pvl). */
    public function documentsWithLegacyBinary(string $pipeline, int $limit): Collection;

    public function storeBinaryPath(Model $document, string $path): void;

    // --- Migración vía servicios ---

    public function ingresoExists(array $row): bool;

    public function pecosaNumberExists(string $pecosaNumber): bool;

    /** Lotes del producto con cantidad usada, más antiguos primero. */
    public function lotsWithUsage(int $productId): Collection;

    // --- Relleno de detail_pecosas ---

    public function lotsGroupedByProduct(): SupportCollection;

    public function stocksLinkedToPecosasGrouped(): SupportCollection;

    public function pecosasByNumber(SupportCollection $pecosaNumbers): SupportCollection;

    public function productsMeta(): SupportCollection;

    public function uomTitles(): SupportCollection;

    public function pecosaHasDetails(int $pecosaId): bool;

    public function createDetailPecosa(array $attributes): void;

    // --- Reconstrucción mensual desde Excel ---

    public function transactionTypeId(string $title): ?int;

    public function associationIdsByCode(): array;

    public function detailProductRows(): SupportCollection;

    public function productTitles(): SupportCollection;

    public function pecosaIdsByNumbers(array $pecosaNumbers): SupportCollection;

    public function deleteMonthRecords(SupportCollection $pecosaIds, array $pecosaNumbers, int $salidaTypeId): void;

    public function upsertPecosas(array $rows): void;

    /** @return array<string,int> pecosa_number => id */
    public function pecosaIdMapByNumber(array $pecosaNumbers): array;

    public function pecosaDatesByNumber(array $pecosaNumbers): array;

    public function insertDetailPecosas(array $rows): void;

    public function insertTransactions(array $rows): void;

    public function insertProductStocks(array $rows): void;

    public function pecosaIdsDeliveredBetween(string $from, string $to): SupportCollection;

    public function unlinkedStocksOfPecosas(SupportCollection $pecosaIds): SupportCollection;

    public function pecosaNumberOf(int $pecosaId): ?string;

    public function salidaTransactionId(int $salidaTypeId, int $detailProductId, string $documentNumber): ?int;

    public function linkStockToTransaction(int $stockId, int $transactionId): void;

    public function pecosasDeliveredBetweenCount(string $from, string $to): int;

    public function detailPecosasDeliveredBetweenCount(string $from, string $to): int;

    public function salidasBetweenCount(string $from, string $to): int;

    public function rosterPartnersBetweenCount(string $from, string $to): int;
}
