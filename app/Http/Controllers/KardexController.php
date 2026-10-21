<?php

namespace App\Http\Controllers;

use App\Services\ProductService;
use Illuminate\Http\Request;

class KardexController extends Controller
{
    public function __construct(private ProductService $productService)
    {
    }

    public function index(Request $request)
    {
        $filters = $request->only(['product_id', 'periodo', 'stock_status', 'search']);

        return view('productos-pecosas.kardex.index', $this->productService->kardexData($filters));
    }
}
