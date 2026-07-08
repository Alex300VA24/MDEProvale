<?php

namespace App\Services;

use App\Models\Product;
use App\Repositories\ProductRepository;

class ProductService
{
    private ProductRepository $productRepo;

    public function __construct(ProductRepository $productRepo)
    {
        $this->productRepo = $productRepo;
    }

    public function generateReport(string $tipo, array $filters = []): array
    {
        $query = Product::with(['state', 'uom', 'detailProducts' => function ($q) {
            $q->withSum('stocks as used_quantity', 'quantity');
        }]);

        if ($tipo === 'general') {
            return ['products' => $query->get(), 'titulo' => 'Inventario General de Productos'];
        } elseif ($tipo === 'stock-bajo') {
            return [
                'products' => $query->get()->filter(fn($p) => $p->stock <= ($filters['stock_minimo'] ?? 10)),
                'titulo' => 'Productos con Stock Bajo',
            ];
        } elseif ($tipo === 'valorizacion') {
            return ['products' => $query->get(), 'titulo' => 'Valorización de Inventario'];
        } elseif ($tipo === 'movimientos') {
            return [
                'products' => $query->with('transactions')->get(),
                'titulo' => 'Productos con Movimientos',
            ];
        } elseif ($tipo === 'top') {
            return [
                'products' => $query->withCount('transactions')->orderBy('transactions_count', 'desc')->limit(10)->get(),
                'titulo' => 'Top 10 Productos Más Utilizados',
            ];
        } else {
            return ['products' => $query->get(), 'titulo' => 'Reporte de Productos'];
        }
    }
}