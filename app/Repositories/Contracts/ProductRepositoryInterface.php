<?php

namespace App\Repositories\Contracts;

use App\Models\DetailProduct;
use App\Models\Product;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Pagination\LengthAwarePaginator;

interface ProductRepositoryInterface
{
    public function searchWithFilters(array $filters, int $perPage = 10): LengthAwarePaginator;
    public function getDetailProductsWithStock(?int $productId = null, ?string $periodo = null): Collection;
    public function getDetailProductsByIds(Collection $ids): Collection;

    /**
     * Lotes con consumo, paginados. Filtros: product_id, periodo (vigente|vencido|futuro),
     * stock_status (disponible|agotado), search (título o abreviatura del producto).
     */
    public function paginateDetailProducts(array $filters, int $perPage = 15): LengthAwarePaginator;

    /**
     * Lotes con consumo para el módulo API. Filtros: search, product_id, year, month,
     * periodo (vigente|vencido).
     */
    public function paginateLotsByPeriod(array $filters, int $perPage = 15): LengthAwarePaginator;

    public function loadStateAndUom(Product $product): Product;

    /** Todos los lotes con producto, UOM y cantidad consumida (más recientes primero). */
    public function lotsWithUsage(): Collection;

    public function createProduct(array $data): Product;

    public function updateProduct(Product $product, array $data): bool;

    public function createDetailProduct(array $data): DetailProduct;

    public function findDetailProductWithProductOrFail(int $id): DetailProduct;

    public function updateDetailProduct(DetailProduct $detailProduct, array $data): bool;

    public function deleteDetailProduct(int $id): void;

    /** Producto con lotes (más recientes primero). */
    public function loadLots(Product $product): Product;

    /** true si algún lote del producto está en una PECOSA o tiene stock consumido. */
    public function isReferenced(Product $product): bool;

    public function deleteWithLots(Product $product): void;

    /**
     * Inventario para reportes: productos con estado, UOM y lotes con consumo.
     *
     * @param string[] $with relaciones adicionales
     */
    public function inventoryForReport(array $with = [], bool $topByTransactions = false): Collection;

    /** Lotes con `available_stock` para el modal de PECOSAs (listado). */
    public function lotsForPecosaIndex(): Collection;

    /** Lotes no vencidos con `available_stock` para registrar PECOSAs. */
    public function lotsForPecosaCreate(): Collection;
}
