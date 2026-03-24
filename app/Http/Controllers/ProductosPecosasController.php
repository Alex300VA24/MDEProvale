<?php

namespace App\Http\Controllers;

use App\Models\Product;
use App\Models\Pecosa;
use App\Models\DetailPecosa;
use App\Models\DetailProduct;
use App\Models\ProductStock;
use App\Models\Transaction;
use App\Models\TypeTransaction;
use App\Models\Association;
use App\Models\State;
use App\Models\Uom;
use App\Models\Partner;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class ProductosPecosasController extends Controller
{
    // ==================== ÍNDICE PRINCIPAL ====================

    public function index(Request $request)
    {
        $query = Product::with(['state', 'uom', 'detailProducts']);

        if ($request->has('search') && $request->search != '') {
            $search = $request->search;
            $query->where('title', 'like', "%{$search}%")
                ->orWhere('abbreviation', 'like', "%{$search}%")
                ->orWhere('code', 'like', "%{$search}%");
        }

        if ($request->has('state_id') && $request->state_id != '') {
            $query->where('state_id', $request->state_id);
        }

        $products = $query->orderBy('id', 'desc')->paginate(10);
        $states = State::all();
        $uoms = Uom::all();
        
        $estadoActivo = State::where('abbreviation', 'A')->first();
        $associationsForModal = $estadoActivo
            ? Association::where('state_id', $estadoActivo->id)->get()
            : Association::all();
        foreach ($associationsForModal as $association) {
            $president = $association->getPresidenta();
            $association->president_partner_id = $president ? $president->id : null;
            $association->president_name = $president && $president->people 
                ? $president->people->names . ' ' . $president->people->father_lastname 
                : null;
        }
        
        $pecosas = Pecosa::with(['association', 'state', 'managingPartner.people', 'detailPecosas'])
            ->orderBy('id', 'desc')
            ->paginate(10);
        
        $responsibles = \App\Models\Responsible::with('person')->where('active', true)->get();
        $detailProductsList = DetailProduct::with('product')
            ->where('end_date', '>=', now()->toDateString())
            ->orderBy('start_date', 'asc')
            ->get()
            ->map(function($dp) {
                $used = \App\Models\ProductStock::where('detail_product_id', $dp->id)->sum('quantity');
                $dp->available_stock = $dp->quantity - $used;
                return $dp;
            });

        return view('productos-pecosas.index', compact('products', 'states', 'uoms', 'pecosas', 'associationsForModal', 'responsibles', 'detailProductsList'));
    }

    // ==================== PRODUCTOS ====================

    public function indexProductos(Request $request)
    {
        $query = Product::with(['state', 'uom', 'detailProducts']);

        if ($request->has('search') && $request->search != '') {
            $search = $request->search;
            $query->where('title', 'like', "%{$search}%")
                ->orWhere('abbreviation', 'like', "%{$search}%")
                ->orWhere('code', 'like', "%{$search}%");
        }

        if ($request->has('state_id') && $request->state_id != '') {
            $query->where('state_id', $request->state_id);
        }


        $products = $query->orderBy('id', 'desc')->paginate(10);
        $states = State::all();
        $uoms = Uom::all();

        $detailQuery = DetailProduct::with(['product', 'stocks', 'stocks.pecosa']);

        if ($request->filled('product_id')) {
            $detailQuery->where('product_id', $request->product_id);
        }

        if ($request->filled('periodo')) {
            $periodo = $request->periodo;
            if ($periodo === 'vigente') {
                $detailQuery->where('start_date', '<=', now()->toDateString())
                    ->where('end_date', '>=', now()->toDateString());
            } elseif ($periodo === 'vencido') {
                $detailQuery->where('end_date', '<', now()->toDateString());
            }
        }

        if ($request->filled('search_detalle')) {
            $searchDetalle = $request->search_detalle;
            $detailQuery->whereHas('product', function ($q) use ($searchDetalle) {
                $q->where('title', 'like', "%{$searchDetalle}%")
                    ->orWhere('abbreviation', 'like', "%{$searchDetalle}%");
            });
        }

        $detailProducts = $detailQuery->orderBy('created_at', 'desc')->paginate(15);

        return view('productos-pecosas.productos.index', compact('products', 'states', 'uoms', 'detailProducts'));
    }

    public function storeProductoAjax(Request $request)
    {
        try {
            $validated = $request->validate([
                'code' => 'required|string|max:20|unique:products,code',
                'title' => 'required|string|max:255',
                'abbreviation' => 'nullable|string|max:50',
                'state_id' => 'required|exists:states,id',
                'uom_id' => 'required|exists:uoms,id',
            ]);

            $product = Product::create($validated);

            return response()->json([
                'success' => true,
                'product' => $product,
                'message' => 'Producto registrado exitosamente'
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error al registrar producto: ' . $e->getMessage()
            ], 422);
        }
    }

    public function createProducto()
    {
        $states = State::all();
        $uoms = Uom::all();
        return view('productos-pecosas.productos.create', compact('states', 'uoms'));
    }

    public function storeProducto(Request $request)
    {
        $validated = $request->validate([
            'code' => 'required|string|max:20|unique:products,code',
            'title' => 'required|string|max:255',
            'abbreviation' => 'nullable|string|max:50',
            'state_id' => 'required|exists:states,id',
            'uom_id' => 'required|exists:uoms,id',
        ]);
        Product::create($validated);
        return redirect()->route('productos-pecosas.productos.index')->with('success', 'Producto creado exitosamente');
    }

    public function showProducto(Product $product)
    {
        $product->load(['detailProducts' => function($q) {
            $q->orderBy('created_at', 'desc');
        }]);
        return view('productos-pecosas.productos.show', compact('product'));
    }

    public function editProducto(Product $product)
    {
        $states = State::all();
        $uoms = Uom::all();
        return view('productos-pecosas.productos.edit', compact('product', 'states', 'uoms'));
    }

    public function updateProducto(Request $request, Product $product)
    {
        $validated = $request->validate([
            'code' => 'required|string|max:20|unique:products,code,' . $product->id,
            'title' => 'required|string|max:255',
            'abbreviation' => 'nullable|string|max:50',
            'state_id' => 'required|exists:states,id',
            'uom_id' => 'required|exists:uoms,id',
        ]);
        $product->update($validated);
        return redirect()->route('productos-pecosas.productos.index')->with('success', 'Producto actualizado exitosamente');
    }

    public function destroyProducto(Product $product)
    {
        $product->delete();
        return redirect()->route('productos-pecosas.productos.index')->with('success', 'Producto eliminado exitosamente');
    }

    // ==================== PECOSAS ====================

    public function indexPecosas(Request $request)
    {
        $query = Pecosa::with(['association', 'state', 'managingPartner.people', 'detailPecosas.detailProduct.product']);

        if ($request->has('search') && $request->search != '') {
            $search = $request->search;
            $query->where('pecosa_number', 'like', "%{$search}%");
        }

        if ($request->has('association_id') && $request->association_id != '') {
            $query->where('association_id', $request->association_id);
        }

        if ($request->has('state_id') && $request->state_id != '') {
            $query->where('state_id', $request->state_id);
        }

        $pecosas = $query->orderBy('id', 'desc')->paginate(10);
        $associations = Association::all();
        $states = State::all();
        
        $estadoActivo = State::where('abbreviation', 'A')->first();
        $associationsForModal = $estadoActivo
            ? Association::where('state_id', $estadoActivo->id)->get()
            : Association::all();
        foreach ($associationsForModal as $association) {
            $president = $association->getPresidenta();
            $association->president_partner_id = $president ? $president->id : null;
            $association->president_name = $president && $president->people 
                ? $president->people->names . ' ' . $president->people->father_lastname 
                : null;
        }
        
        $partners = Partner::with('people')->get();
        $responsibles = \App\Models\Responsible::with('person')->where('active', true)->get();
        $detailProductsList = DetailProduct::with('product')
            ->where('end_date', '>=', now()->toDateString())
            ->orderBy('start_date', 'asc')
            ->get()
            ->map(function($dp) {
                $used = \App\Models\ProductStock::where('detail_product_id', $dp->id)->sum('quantity');
                $dp->available_stock = $dp->quantity - $used;
                return $dp;
            });

        // Para los modales de editar, incluir también detail_products ya asignados aunque tengan stock 0
        $assignedDetailProductIds = $pecosas->pluck('detailPecosas')->flatten()->pluck('detail_product_id')->filter()->unique();
        $extraDetailProducts = DetailProduct::with('product')
            ->whereIn('id', $assignedDetailProductIds)
            ->whereNotIn('id', $detailProductsList->pluck('id'))
            ->get()
            ->map(function($dp) {
                $used = \App\Models\ProductStock::where('detail_product_id', $dp->id)->sum('quantity');
                $dp->available_stock = $dp->quantity - $used;
                return $dp;
            });
        $detailProductsList = $detailProductsList->concat($extraDetailProducts);

        return view('productos-pecosas.pecosas.index', compact('pecosas', 'associations', 'states', 'associationsForModal', 'partners', 'responsibles', 'detailProductsList'));
    }

    public function createPecosa()
    {
        $estadoActivo = State::where('abbreviation', 'A')->first();
        $associations = $estadoActivo
            ? Association::where('state_id', $estadoActivo->id)->get()
            : Association::all();
        
        // Agregar president_partner_id a cada asociación
        foreach ($associations as $association) {
            $president = $association->getPresidenta();
            $association->president_partner_id = $president ? $president->id : null;
        }

        $states = State::all();
        $partners = Partner::with('people')->get();
        $products = Product::with(['detailProducts', 'uom'])->get();
        $uoms = Uom::all();
        $responsibles = \App\Models\Responsible::with('person')->where('active', true)->get();
        
        $detailProductsList = DetailProduct::with('product')
            ->where('end_date', '>=', now()->toDateString())
            ->orderBy('start_date', 'asc')
            ->get()
            ->map(function($dp) {
                $used = \App\Models\ProductStock::where('detail_product_id', $dp->id)->sum('quantity');
                $dp->available_stock = $dp->quantity - $used;
                return $dp;
            });

        return view('productos-pecosas.pecosas.create', 
            compact('associations', 'states', 'partners', 'products', 'uoms', 'responsibles', 'detailProductsList'));
    }

    public function storePecosa(Request $request)
    {
        $validated = $request->validate([
            'pecosa_number' => 'required|string|max:50',
            'observation' => 'nullable|string',
            'delivery_date' => 'required|date',
            'chief_id' => 'nullable|exists:responsibles,id',
            'storekeeper_id' => 'nullable|exists:responsibles,id',
            'managing_partner_id' => 'required|exists:partners,id',
            'state_id' => 'required|exists:states,id',
            'association_id' => 'required|exists:associations,id',
            'details' => 'required|array|min:1',
            'details.*.detail_product_id' => 'required|exists:detail_products,id',
            'details.*.product_id' => 'required|exists:products,id',
            'details.*.quantity' => 'required|numeric|min:0.01',
        ]);

        $association = Association::findOrFail($request->association_id);
        if (!$association->isHabilitado() && !$request->filled('managing_partner_id')) {
            return back()->withInput()->with('error', 'El comité no está habilitado. Debe asignar una presidenta primero.');
        }

        $detailProductIds = collect($request->details)->pluck('detail_product_id');
        if ($detailProductIds->count() !== $detailProductIds->unique()->count()) {
            return back()->withInput()->with('error', 'No se permiten productos duplicados en la misma PECOSA.');
        }

        foreach ($request->details as $detail) {
            $detailProduct = DetailProduct::find($detail['detail_product_id']);
            if (!$detailProduct) {
                return back()->withInput()->with('error', 'Detalle de producto no encontrado.');
            }
            $availableStock = $detailProduct->quantity - ProductStock::where('detail_product_id', $detailProduct->id)->sum('quantity');
            if ($availableStock < $detail['quantity']) {
                $product = $detailProduct->product;
                return back()->withInput()->with('error', 
                    "Stock insuficiente para {$product->title}. Disponible: {$availableStock}, Solicitado: {$detail['quantity']}");
            }
        }

        try {
            DB::beginTransaction();

            $pecosa = Pecosa::create($validated);
            $typeSalida = TypeTransaction::whereRaw('LOWER(title) = ?', ['salida'])->first();

            foreach ($request->details as $index => $detail) {
                $detailProduct = DetailProduct::find($detail['detail_product_id']);
                
                $unitPrice = $detailProduct ? $detailProduct->unit_price : (isset($detail['unit_price']) ? $detail['unit_price'] : 0);
                $subtotal = $detail['quantity'] * $unitPrice;

                DetailPecosa::create([
                    'pecosa_id' => $pecosa->id,
                    'detail_product_id' => $detail['detail_product_id'],
                    'quantity' => $detail['quantity'],
                    'unit_price' => $unitPrice,
                    'subtotal' => $subtotal,
                    'priority' => $index + 1,
                ]);

                $this->deductStockByDetailProduct($detail['detail_product_id'], $detail['quantity'], $pecosa->id);

                if ($typeSalida) {
                    $stockData = $this->getStockInfo($detail['product_id']);
                    
                    Transaction::create([
                        'product_id' => $detail['product_id'],
                        'type_transaction_id' => $typeSalida->id,
                        'quantity' => $detail['quantity'],
                        'unit_price' => $unitPrice,
                        'total_price' => $detail['quantity'] * $unitPrice,
                        'document_number' => $validated['pecosa_number'],
                        'stock_quantity' => $stockData['quantity'],
                        'stock_unit_price' => $stockData['unit_price'],
                        'stock_total_price' => $stockData['total'],
                        'transaction_date' => $validated['delivery_date'],
                    ]);
                }
            }

            DB::commit();
            return redirect()->route('productos-pecosas.pecosas.index')->with('success', 'Pecosa creada exitosamente');
        } catch (\Exception $e) {
            DB::rollBack();
            return back()->withInput()->with('error', 'Error al crear PECOSA: ' . $e->getMessage());
        }
    }

    private function getAvailableStock($productId)
    {
        $detailProducts = DetailProduct::where('product_id', $productId)
            ->where('start_date', '<=', now()->toDateString())
            ->where('end_date', '>=', now()->toDateString())
            ->get();

        $totalStock = 0;
        foreach ($detailProducts as $detail) {
            $in = $detail->quantity;
            $out = ProductStock::where('detail_product_id', $detail->id)->sum('quantity');
            $totalStock += ($in - $out);
        }

        return $totalStock;
    }

    private function deductStock($pecosaId, $productId, $quantity)
    {
        $detailProducts = DetailProduct::where('product_id', $productId)
            ->where('start_date', '<=', now()->toDateString())
            ->where('end_date', '>=', now()->toDateString())
            ->orderBy('start_date', 'asc')
            ->get();

        $remainingToDeduct = $quantity;

        foreach ($detailProducts as $detail) {
            if ($remainingToDeduct <= 0) {
                break;
            }

            $available = $detail->quantity - ProductStock::where('detail_product_id', $detail->id)->sum('quantity');

            if ($available > 0) {
                $deduct = min($remainingToDeduct, $available);
                
                ProductStock::create([
                    'detail_product_id' => $detail->id,
                    'pecosa_id' => $pecosaId,
                    'quantity' => $deduct,
                    'observation' => 'Salida por Pecosa',
                ]);

                $remainingToDeduct -= $deduct;
            }
        }

        if ($remainingToDeduct > 0) {
            throw new \Exception('Stock insuficiente para el producto. Faltan ' . $remainingToDeduct . ' unidades.');
        }

        return true;
    }

    private function deductStockByDetailProduct($detailProductId, $quantity, $pecosaId = null)
    {
        $detailProduct = DetailProduct::find($detailProductId);
        
        if (!$detailProduct) {
            throw new \Exception('Detalle de producto no encontrado.');
        }

        $available = $detailProduct->quantity - ProductStock::where('detail_product_id', $detailProductId)->sum('quantity');

        if ($quantity > $available) {
            throw new \Exception('Stock insuficiente. Disponible: ' . $available . ', Solicitado: ' . $quantity);
        }

        ProductStock::create([
            'detail_product_id' => $detailProductId,
            'pecosa_id' => $pecosaId,
            'quantity' => $quantity,
            'observation' => 'Salida por Pecosa #' . ($pecosaId ?? 'N/A'),
        ]);

        return true;
    }

    private function getStockInfo($productId)
    {
        $detailProducts = DetailProduct::where('product_id', $productId)
            ->where('start_date', '<=', now()->toDateString())
            ->where('end_date', '>=', now()->toDateString())
            ->get();

        $totalStock = 0;
        $totalValue = 0;

        foreach ($detailProducts as $detail) {
            $in = $detail->quantity;
            $out = ProductStock::where('detail_product_id', $detail->id)->sum('quantity');
            $available = $in - $out;
            
            $totalStock += $available;
            $totalValue += $available * $detail->unit_price;
        }

        return [
            'quantity' => $totalStock,
            'unit_price' => $totalStock > 0 ? $totalValue / $totalStock : 0,
            'total' => $totalValue,
        ];
    }

    public function showPecosa(Pecosa $pecosa)
    {
        $pecosa->load(['detailPecosas.detailProduct.product', 'association', 'managingPartner.people']);
        return view('productos-pecosas.pecosas.show', compact('pecosa'));
    }

    public function generarComprobante(Pecosa $pecosa)
    {
        $pecosa->load(['detailPecosas.detailProduct.product', 'association.placeSector.place', 'managingPartner.people']);

        $articulos = [];
        foreach ($pecosa->detailPecosas as $index => $detail) {
            $product = $detail->detailProduct->product ?? null;
            $articulos[] = [
                'numero' => str_pad($index + 1, 2, '0', STR_PAD_LEFT),
                'cantidad_solicitado' => number_format($detail->quantity, 2),
                'descripcion' => $product ? $product->title . ' (' . $product->abbreviation . ')' : '-',
                'cantidad_despachado' => number_format($detail->quantity, 2),
                'unidad' => $product && $product->uom ? $product->uom->title : 'UNIDAD',
                'unitary' => number_format($detail->unit_price, 2),
                'unitario' => number_format($detail->unit_price, 2),
                'total' => number_format($detail->quantity * $detail->unit_price, 2),
            ];
        }

        $total_general = number_format($pecosa->detailPecosas->sum(function ($d) {
            return $d->quantity * $d->unit_price;
        }), 2);

        $data = [
            'zona' => $pecosa->association && $pecosa->association->placeSector && $pecosa->association->placeSector->place ? $pecosa->association->placeSector->place->title : '01',
            'comite' => $pecosa->association ? $pecosa->association->name : 'N/A',
            'num_mes' => date('m', strtotime($pecosa->delivery_date)),
            'racion' => 'N/A',
            'numero_orden' => $pecosa->pecosa_number,
            'solicitante_nombre' => $pecosa->managingPartner && $pecosa->managingPartner->people ?
                $pecosa->managingPartner->people->names . ' ' . $pecosa->managingPartner->people->father_lastname : 'N/A',
            'domicilio' => $pecosa->association ? $pecosa->association->address : 'N/A',
            'fecha' => date('d/m/Y', strtotime($pecosa->delivery_date)),
            'articulos' => $articulos,
            'total_general' => 'S/. ' . $total_general,
            'encargado_almacen' => 'ENCARGADO DE PROVALE',
            'dni_encargado' => '18357683',
            'control' => 'JEFA DE ALMACÉN PROVALE',
            'dni_control' => '40353394',
        ];

        return view('comprobante_salida', $data);
    }

    public function editPecosa(Pecosa $pecosa)
    {
        $associations = Association::all();
        $states = State::all();
        $partners = Partner::with('people')->get();
        return view('productos-pecosas.pecosas.edit', compact('pecosa', 'associations', 'states', 'partners'));
    }

    public function updatePecosa(Request $request, Pecosa $pecosa)
    {
        $validated = $request->validate([
            'pecosa_number' => 'required|string|max:50',
            'observation' => 'nullable|string',
            'delivery_date' => 'required|date',
            'chief_id' => 'nullable|exists:responsibles,id',
            'storekeeper_id' => 'nullable|exists:responsibles,id',
            'managing_partner_id' => 'nullable|exists:partners,id',
            'state_id' => 'required|exists:states,id',
            'association_id' => 'required|exists:associations,id',
            'details' => 'required|array|min:1',
            'details.*.detail_product_id' => 'required|exists:detail_products,id',
            'details.*.product_id' => 'required|exists:products,id',
            'details.*.quantity' => 'required|numeric|min:0.01',
        ]);

        $detailProductIds = collect($request->details)->pluck('detail_product_id');
        if ($detailProductIds->count() !== $detailProductIds->unique()->count()) {
            return back()->withInput()->with('error', 'No se permiten productos duplicados en la misma PECOSA.');
        }

        try {
            DB::beginTransaction();

            // Revertir stock de los detalles anteriores
            ProductStock::where('pecosa_id', $pecosa->id)->delete();
            Transaction::where('document_number', $pecosa->pecosa_number)->delete();
            DetailPecosa::where('pecosa_id', $pecosa->id)->delete();

            $pecosa->update($validated);

            $typeSalida = TypeTransaction::whereRaw('LOWER(title) = ?', ['salida'])->first();

            foreach ($request->details as $index => $detail) {
                $detailProduct = DetailProduct::find($detail['detail_product_id']);
                $unitPrice = $detailProduct ? $detailProduct->unit_price : ($detail['unit_price'] ?? 0);
                $subtotal = $detail['quantity'] * $unitPrice;

                DetailPecosa::create([
                    'pecosa_id' => $pecosa->id,
                    'detail_product_id' => $detail['detail_product_id'],
                    'quantity' => $detail['quantity'],
                    'unit_price' => $unitPrice,
                    'subtotal' => $subtotal,
                    'priority' => $index + 1,
                ]);

                $this->deductStockByDetailProduct($detail['detail_product_id'], $detail['quantity'], $pecosa->id);

                if ($typeSalida) {
                    $stockData = $this->getStockInfo($detail['product_id']);
                    Transaction::create([
                        'product_id' => $detail['product_id'],
                        'type_transaction_id' => $typeSalida->id,
                        'quantity' => $detail['quantity'],
                        'unit_price' => $unitPrice,
                        'total_price' => $subtotal,
                        'document_number' => $validated['pecosa_number'],
                        'stock_quantity' => $stockData['quantity'],
                        'stock_unit_price' => $stockData['unit_price'],
                        'stock_total_price' => $stockData['total'],
                        'transaction_date' => $validated['delivery_date'],
                    ]);
                }
            }

            DB::commit();
            return redirect()->route('productos-pecosas.pecosas.index')->with('success', 'Pecosa actualizada exitosamente');
        } catch (\Exception $e) {
            DB::rollBack();
            return back()->withInput()->with('error', 'Error al actualizar PECOSA: ' . $e->getMessage());
        }
    }

    public function destroyPecosa(Pecosa $pecosa)
    {
        $pecosa->delete();
        return redirect()->route('productos-pecosas.pecosas.index')->with('success', 'Pecosa eliminada exitosamente');
    }

    // ==================== REPORTES ====================

    public function reportesProductos()
    {
        return view('productos-pecosas.productos.reportes');
    }

    public function generarReporteProductos($tipo, Request $request)
    {
        $query = Product::with(['state', 'uom', 'detailProducts']);

        switch ($tipo) {
            case 'general':
                $products = $query->get();
                $titulo = 'Inventario General de Productos';
                break;
            case 'stock-bajo':
                $stockMinimo = $request->get('stock_minimo', 10);
                $products = $query->get()->filter(function($p) use ($stockMinimo) {
                    return $p->stock <= $stockMinimo;
                });
                $titulo = 'Productos con Stock Bajo (≤ ' . $stockMinimo . ')';
                break;
            case 'valorizacion':
                $products = $query->get();
                $titulo = 'Valorización de Inventario';
                break;
            case 'movimientos':
                $products = $query->with('transactions')->get();
                $titulo = 'Productos con Movimientos';
                break;
            case 'top':
                $products = $query->withCount('transactions')->orderBy('transactions_count', 'desc')->limit(10)->get();
                $titulo = 'Top 10 Productos Más Utilizados';
                break;
            default:
                $products = $query->get();
                $titulo = 'Reporte de Productos';
        }

        return view('productos-pecosas.productos.reportes.pdf', compact('products', 'titulo', 'tipo'));
    }

    public function reportesPecosas()
    {
        return view('productos-pecosas.pecosas.reportes');
    }

    public function generarReportePecosas($tipo, Request $request)
    {
        $query = Pecosa::with(['association', 'state', 'managingPartner.people']);

        switch ($tipo) {
            case 'general':
                $pecosas = $query->get();
                $titulo = 'Todas las Pecosas';
                break;
            case 'club':
                $associationId = $request->get('association_id');
                $pecosas = $query->where('association_id', $associationId)->get();
                $association = Association::find($associationId);
                $titulo = 'Pecosas del Club: ' . ($association->name ?? 'N/A');
                break;
            case 'fecha':
                $fechaInicio = $request->get('fecha_inicio');
                $fechaFin = $request->get('fecha_fin');
                $pecosas = $query->whereBetween('delivery_date', [$fechaInicio, $fechaFin])->get();
                $titulo = 'Pecosas del ' . date('d/m/Y', strtotime($fechaInicio)) . ' al ' . date('d/m/Y', strtotime($fechaFin));
                break;
            case 'detalle':
                $pecosas = $query->with('detailPecosas.detailProduct.product')->get();
                $titulo = 'Pecosas con Detalle de Productos';
                break;
            case 'estadistico':
                $pecosas = $query->get();
                $titulo = 'Reporte Estadístico de Pecosas';
                break;
            case 'responsable':
                $partnerId = $request->get('partner_id');
                $pecosas = $query->where('managing_partner_id', $partnerId)->get();
                $partner = Partner::with('people')->find($partnerId);
                $titulo = 'Pecosas del Responsable: ' . ($partner->people ? $partner->people->names . ' ' . $partner->people->father_lastname : 'N/A');
                break;
            default:
                $pecosas = $query->get();
                $titulo = 'Reporte de Pecosas';
        }

        return view('productos-pecosas.pecosas.reportes.pdf', compact('pecosas', 'titulo', 'tipo'));
    }

    public function generarProgramacionEntrega(Request $request)
    {
        $mes = $request->get('month', date('n'));
        $anio = $request->get('year', date('Y'));
        $sector = $request->get('sector', '');

        $estadoActivo = State::where('abbreviation', 'ACTI')->first();
        $associations = Association::with(['placeSector.place', 'partners.beneficiaries'])
            ->when($estadoActivo, function ($q) use ($estadoActivo) {
                $q->where('state_id', $estadoActivo->id);
            })
            ->get();

        $clubes = [];
        foreach ($associations as $association) {
            $presidenta = '';
            $directiva = \App\Models\Directive::whereHas('resolution', function ($q) use ($association) {
                $q->where('association_id', $association->id);
            })->whereHas('position', function ($q) {
                $q->where('title', 'like', '%PRESIDENTA%');
            })->whereHas('state', function ($q) {
                $q->where('abbreviation', 'ACTI');
            })->with('partner.people')->first();

            if ($directiva && $directiva->partner && $directiva->partner->people) {
                $p = $directiva->partner->people;
                $presidenta = strtoupper($p->names . ' ' . $p->father_lastname);
            }

            $totalBenef = 0;
            $primeraPrioridad = 0;
            $segundaPrioridad = 0;

            foreach ($association->partners as $partner) {
                foreach ($partner->beneficiaries as $beneficiario) {
                    $totalBenef++;
                    $persona = $beneficiario->person;
                    if ($persona && $persona->birthdate) {
                        $edad = \Carbon\Carbon::parse($persona->birthdate)->age;
                        if ($edad <= 6) {
                            $primeraPrioridad++;
                        } else {
                            $segundaPrioridad++;
                        }
                    } else {
                        $segundaPrioridad++;
                    }
                }
            }

            $pecosa = Pecosa::with('detailPecosas.detailProduct.product')
                ->where('association_id', $association->id)
                ->whereMonth('delivery_date', $mes)
                ->whereYear('delivery_date', $anio)
                ->first();

            $bolsas = 0;
            $kilos = 0;
            if ($pecosa) {
                foreach ($pecosa->detailPecosas as $detail) {
                    $bolsas += $detail->quantity;
                }
            }

            $clubes[] = [
                'codigo' => $association->code ?? $association->id,
                'nombre' => strtoupper($association->name),
                'presidenta' => $presidenta,
                'direccion' => $association->address ?? '',
                'primera_prioridad' => $primeraPrioridad,
                'segunda_prioridad' => $segundaPrioridad,
                'bolsas' => $bolsas,
                'kilos' => $kilos,
                'racion' => '',
                'fecha_entrega' => $pecosa ? date('d/m/Y', strtotime($pecosa->delivery_date)) : '',
                'recibe' => $presidenta,
                'dni' => $directiva && $directiva->partner && $directiva->partner->people ? $directiva->partner->people->dni : '',
            ];
        }

        $data = [
            'clubes' => $clubes,
            'sector' => $sector,
        ];

        return view('programacion_entrega', $data);
    }

    // ==================== KARDEX ====================

    public function kardex(Request $request)
    {
        $query = DetailProduct::with(['product', 'stocks']);

        if ($request->filled('product_id')) {
            $query->where('product_id', $request->product_id);
        }

        if ($request->filled('periodo')) {
            $periodo = $request->periodo;
            if ($periodo === 'vigente') {
                $query->where('start_date', '<=', now()->toDateString())
                    ->where('end_date', '>=', now()->toDateString());
            } elseif ($periodo === 'vencido') {
                $query->where('end_date', '<', now()->toDateString());
            } elseif ($periodo === 'futuro') {
                $query->where('start_date', '>', now()->toDateString());
            }
        }

        if ($request->filled('stock_status')) {
            $stockStatus = $request->stock_status;
            $query->whereHas('product', function ($q) use ($stockStatus) {
                $q->whereRaw('1=1');
            });
            
            if ($stockStatus === 'disponible') {
                $query->whereRaw('(quantity - (SELECT COALESCE(SUM(quantity), 0) FROM product_stocks WHERE product_stocks.detail_product_id = detail_products.id)) > 0');
            } elseif ($stockStatus === 'agotado') {
                $query->whereRaw('(quantity - (SELECT COALESCE(SUM(quantity), 0) FROM product_stocks WHERE product_stocks.detail_product_id = detail_products.id)) <= 0');
            }
        }

        if ($request->filled('search')) {
            $search = $request->search;
            $query->whereHas('product', function ($q) use ($search) {
                $q->where('title', 'like', "%{$search}%")
                    ->orWhere('abbreviation', 'like', "%{$search}%");
            });
        }

        $detailProducts = $query->orderBy('created_at', 'desc')->paginate(15);
        $products = Product::all();

        return view('productos-pecosas.kardex', compact('detailProducts', 'products'));
    }

    // ==================== PRODUCTOS DETALLE (KARDEX) ====================

    public function productosDetalle(Request $request)
    {
        $query = DetailProduct::with(['product', 'stocks', 'stocks.pecosa']);

        if ($request->filled('product_id')) {
            $query->where('product_id', $request->product_id);
        }

        if ($request->filled('periodo')) {
            $periodo = $request->periodo;
            if ($periodo === 'vigente') {
                $query->where('start_date', '<=', now()->toDateString())
                    ->where('end_date', '>=', now()->toDateString());
            } elseif ($periodo === 'vencido') {
                $query->where('end_date', '<', now()->toDateString());
            } elseif ($periodo === 'futuro') {
                $query->where('start_date', '>', now()->toDateString());
            }
        }

        if ($request->filled('stock_status')) {
            $stockStatus = $request->stock_status;
            $query->whereHas('product', function ($q) use ($stockStatus) {
                $q->whereRaw('1=1');
            });
            
            if ($stockStatus === 'disponible') {
                $query->whereRaw('(quantity - (SELECT COALESCE(SUM(quantity), 0) FROM product_stocks WHERE product_stocks.detail_product_id = detail_products.id)) > 0');
            } elseif ($stockStatus === 'agotado') {
                $query->whereRaw('(quantity - (SELECT COALESCE(SUM(quantity), 0) FROM product_stocks WHERE product_stocks.detail_product_id = detail_products.id)) <= 0');
            }
        }

        if ($request->filled('search')) {
            $search = $request->search;
            $query->whereHas('product', function ($q) use ($search) {
                $q->where('title', 'like', "%{$search}%")
                    ->orWhere('abbreviation', 'like', "%{$search}%");
            });
        }

        $detailProducts = $query->orderBy('created_at', 'desc')->paginate(15);
        $products = Product::with(['state', 'uom'])->get();
        $uoms = Uom::all();
        $states = State::all();

        return view('productos-pecosas.productos-detalle', compact('detailProducts', 'products', 'uoms', 'states'));
    }

    public function storeProductoDetalle(Request $request)
    {
        $validated = $request->validate([
            'product_id' => 'required|exists:products,id',
            'unit_price' => 'required|numeric|min:0',
            'quantity' => 'required|integer|min:0',
            'start_date' => 'required|date',
            'end_date' => 'required|date|after:start_date',
        ]);

        DetailProduct::create($validated);

        return redirect()->route('productos-pecosas.productos.index')->with('success', 'Producto registrado correctamente.');
    }
}
