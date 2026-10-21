<?php

namespace App\Services;

use App\Models\DetailProduct;
use App\Models\Product;
use App\Models\State;
use App\Repositories\Contracts\CatalogRepositoryInterface;
use App\Repositories\Contracts\ProductRepositoryInterface;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Str;

class ProductService
{
    public function __construct(
        private ProductRepositoryInterface $productRepo,
        private CatalogRepositoryInterface $catalogs
    ) {
    }

    /** Datos del listado de productos y sus lotes. */
    public function indexData(array $productFilters, array $lotFilters): array
    {
        // El listado de lotes de este módulo no distingue lotes futuros.
        if (($lotFilters['periodo'] ?? null) === 'futuro') {
            unset($lotFilters['periodo']);
        }

        return [
            'products' => $this->productRepo->searchWithFilters($productFilters),
            'states' => $this->catalogs->temporalStates(['id', 'title', 'abbreviation']),
            'uoms' => $this->catalogs->uoms(['id', 'title']),
            'detailProducts' => $this->productRepo->paginateDetailProducts($lotFilters),
        ];
    }

    /** Estados y UOM para los formularios de producto. */
    public function formCatalogs(): array
    {
        return [
            'states' => $this->catalogs->temporalStates(),
            'uoms' => $this->catalogs->uoms(),
        ];
    }

    public function kardexData(array $filters): array
    {
        return [
            'detailProducts' => $this->productRepo->paginateDetailProducts($filters),
            'products' => $this->catalogs->products(),
        ];
    }

    public function search(array $filters, int $perPage = 10): LengthAwarePaginator
    {
        return $this->productRepo->searchWithFilters($filters, $perPage);
    }

    /** Estados y UOM para los filtros del módulo API. */
    public function optionsData(): array
    {
        return [
            'states' => $this->catalogs->temporalStates(['id', 'title', 'abbreviation']),
            'uoms' => $this->catalogs->uoms(['id', 'title']),
        ];
    }

    public function lotsByPeriod(array $filters, int $perPage = 15): LengthAwarePaginator
    {
        return $this->productRepo->paginateLotsByPeriod($filters, $perPage);
    }

    /**
     * Registra un producto vigente; la abreviatura se deriva del título si no viene.
     *
     * @throws \DomainException si no existe el estado Vigente
     */
    public function register(array $data): Product
    {
        $data['state_id'] = State::idFor(State::CURRENT);
        if (! $data['state_id']) {
            throw new \DomainException('No se encontró el estado Vigente para crear el producto.');
        }
        $data['abbreviation'] = ($data['abbreviation'] ?? null)
            ?: Str::upper(Str::substr(Str::ascii($data['title']), 0, 5));
        $data['abbreviation'] = $data['abbreviation'] ?: 'PROD';

        return $this->productRepo->loadStateAndUom($this->productRepo->createProduct($data));
    }

    public function updateProduct(Product $product, array $data): Product
    {
        if (array_key_exists('abbreviation', $data) && ! $data['abbreviation']) {
            $title = $data['title'] ?? $product->title;
            $data['abbreviation'] = Str::upper(Str::substr(Str::ascii($title), 0, 5)) ?: 'PROD';
        }
        $this->productRepo->updateProduct($product, $data);

        return $this->productRepo->loadStateAndUom($product);
    }

    public function create(array $data): Product
    {
        return $this->productRepo->createProduct($data);
    }

    public function update(Product $product, array $data): bool
    {
        return $this->productRepo->updateProduct($product, $data);
    }

    public function show(Product $product): Product
    {
        return $this->productRepo->loadLots($product);
    }

    /**
     * @throws \DomainException si el producto tiene lotes en PECOSAs o stock consumido
     */
    public function delete(Product $product): void
    {
        if ($this->productRepo->isReferenced($product)) {
            throw new \DomainException('No se puede eliminar: el producto tiene detalles/stock asociado');
        }

        $this->productRepo->deleteWithLots($product);
    }

    public function generateReport(string $tipo, array $filters = []): array
    {
        if ($tipo === 'general') {
            return ['products' => $this->productRepo->inventoryForReport(), 'titulo' => 'Inventario General de Productos'];
        } elseif ($tipo === 'stock-bajo') {
            return [
                'products' => $this->productRepo->inventoryForReport()->filter(fn($p) => $p->stock <= ($filters['stock_minimo'] ?? 10)),
                'titulo' => 'Productos con Stock Bajo',
            ];
        } elseif ($tipo === 'valorizacion') {
            return ['products' => $this->productRepo->inventoryForReport(), 'titulo' => 'Valorización de Inventario'];
        } elseif ($tipo === 'movimientos') {
            return [
                'products' => $this->productRepo->inventoryForReport(['transactions']),
                'titulo' => 'Productos con Movimientos',
            ];
        } elseif ($tipo === 'top') {
            return [
                'products' => $this->productRepo->inventoryForReport([], true),
                'titulo' => 'Top 10 Productos Más Utilizados',
            ];
        } else {
            return ['products' => $this->productRepo->inventoryForReport(), 'titulo' => 'Reporte de Productos'];
        }
    }
}
