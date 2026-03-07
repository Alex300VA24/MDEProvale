<?php

namespace App\Http\Controllers;

use App\Models\Product;
use Illuminate\Http\Request;

class ProductController extends Controller
{
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index()
    {
        $products = Product::all();
        return view('productos.index', compact('products'));
    }

    public function create()
    {
        return view('productos.create');
    }

    public function store(Request $request)
    {
        Product::create($request->all());
        return redirect()->route('productos.index');
    }

    public function show(Product $product)
    {
        return view('productos.show', compact('product'));
    }

    public function edit(Product $product)
    {
        return view('productos.edit', compact('product'));
    }

    public function update(Request $request, Product $product)
    {
        $product->update($request->all());
        return redirect()->route('productos.index');
    }

    public function destroy(Product $product)
    {
        $product->delete();
        return redirect()->route('productos.index');
    }
}
