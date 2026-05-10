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
use App\Models\Directive;
use App\Models\Position;
use App\Models\Responsible;
use App\Models\People;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class ProductosPecosasController extends Controller
{
    // ==================== ÍNDICE PRINCIPAL ====================

    public function index(Request $request)
    {
        $query = Product::query()
            ->select(['id', 'title', 'abbreviation', 'state_id', 'uom_id', 'created_at', 'updated_at'])
            ->with(['state:id,title,abbreviation', 'uom:id,title', 'detailProducts:id,product_id,quantity,unit_price,start_date,end_date']);

        if ($request->has('search') && $request->search != '') {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->where('title', 'like', "{$search}%")
                  ->orWhere('abbreviation', 'like', "{$search}%");
            });
        }

        if ($request->has('state_id') && $request->state_id != '') {
            $query->where('state_id', $request->state_id);
        }

        $products = $query->orderBy('id', 'desc')->paginate(10);
        $states = State::select(['id', 'title', 'abbreviation'])->get();
        $uoms = Uom::select(['id', 'title'])->get();
        
        $estadoActivo = State::where('abbreviation', 'A')->first();
        $associationsForModal = $estadoActivo ? Association::where('state_id', $estadoActivo->id)->get() : Association::all();
        
        foreach ($associationsForModal as $association) {
            $presidentPartner = $association->getPresidenta();
            $association->president_partner_id = $presidentPartner ? $presidentPartner->id : null;
            $association->president_name = $presidentPartner && $presidentPartner->people 
                ? $presidentPartner->people->names . ' ' . $presidentPartner->people->father_lastname 
                : 'SIN ASIGNAR';
        }
        
        $pecosas = Pecosa::select(['id', 'pecosa_number', 'delivery_date', 'managing_partner_id', 'state_id', 'association_id', 'chief_name', 'storekeeper_name', 'created_at'])
            ->with(['association:id,name,code', 'state:id,title,abbreviation', 'managingPartner.people:id,names,father_lastname,mother_lastname', 'detailPecosas:id,pecosa_id,detail_product_id,quantity'])
            ->orderBy('id', 'desc')
            ->paginate(10);
        
        $detailProductIds = DetailProduct::orderBy('id')->pluck('id');
        $usedQuantities = DetailPecosa::whereIn('detail_product_id', $detailProductIds)
            ->groupBy('detail_product_id')
            ->select('detail_product_id', DB::raw('SUM(quantity) as total_used'))
            ->pluck('total_used', 'detail_product_id')
            ->toArray();
            
        $detailProductsList = DetailProduct::select(['id', 'product_id', 'quantity', 'unit_price', 'start_date', 'end_date'])
            ->with(['product:id,title,abbreviation'])
            ->orderBy('start_date', 'asc')
            ->get()
            ->map(function($dp) use ($usedQuantities) {
                $dp->available_stock = $dp->quantity - ($usedQuantities[$dp->id] ?? 0);
                return $dp;
            });

        $detailProductsListForPecosa = DetailProduct::select(['id', 'product_id', 'quantity', 'unit_price', 'start_date', 'end_date'])
            ->with(['product:id,title,abbreviation'])
            ->orderBy('start_date', 'asc')
            ->get()
            ->map(function($dp) use ($usedQuantities) {
                $dp->available_stock = $dp->quantity - ($usedQuantities[$dp->id] ?? 0);
                return $dp;
            });

        $responsibles = Responsible::select(['id', 'person_id', 'type', 'active'])
            ->with(['person:id,names,father_lastname,mother_lastname,dni'])
            ->where('active', true)
            ->get();

        return view('productos-pecosas.index', compact('products', 'states', 'uoms', 'pecosas', 'associationsForModal', 'responsibles', 'detailProductsList', 'detailProductsListForPecosa'));
    }

    // ==================== PRODUCTOS ====================

    public function indexProductos(Request $request)
    {
        $query = Product::query()
            ->select(['id', 'title', 'abbreviation', 'state_id', 'uom_id', 'created_at', 'updated_at'])
            ->with(['state:id,title,abbreviation', 'uom:id,title', 'detailProducts:id,product_id,quantity,unit_price,start_date,end_date']);

        if ($request->has('search') && $request->search != '') {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->where('title', 'like', "{$search}%")
                  ->orWhere('abbreviation', 'like', "{$search}%");
            });
        }

        if ($request->has('state_id') && $request->state_id != '') {
            $query->where('state_id', $request->state_id);
        }

        if ($request->filled('uom_id')) {
            $query->where('uom_id', $request->uom_id);
        }

        $products = $query->orderBy('id')->paginate(10);
        $states = State::select(['id', 'title', 'abbreviation'])->get();
        $uoms = Uom::select(['id', 'title'])->get();

        $detailQuery = DetailProduct::query()
            ->select(['id', 'product_id', 'quantity', 'unit_price', 'start_date', 'end_date', 'created_at'])
            ->with(['product:id,title,abbreviation,state_id,uom_id', 'stocks:id,detail_product_id,pecosa_id,quantity']);

        if ($request->filled('product_id')) {
            $detailQuery->where('product_id', $request->product_id);
        }

        if ($request->filled('periodo')) {
            $periodo = $request->periodo;
            $today = now()->toDateString();
            if ($periodo === 'vigente') {
                $detailQuery->where('start_date', '<=', $today)
                    ->where('end_date', '>=', $today);
            } elseif ($periodo === 'vencido') {
                $detailQuery->where('end_date', '<', $today);
            }
        }

        if ($request->filled('search_detalle')) {
            $searchDetalle = $request->search_detalle;
            $detailQuery->whereHas('product', function ($q) use ($searchDetalle) {
                $q->where('title', 'like', "{$searchDetalle}%")
                  ->orWhere('abbreviation', 'like', "{$searchDetalle}%");
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
        $query = Pecosa::query()
            ->select(['id', 'pecosa_number', 'delivery_date', 'observation', 'managing_partner_id', 'president_name', 'state_id', 'association_id', 'chief_name', 'storekeeper_name', 'created_at', 'updated_at'])
            ->with([
                'association:id,name,code,address,state_id',
                'state:id,title,abbreviation',
                'managingPartner.people:id,names,father_lastname,mother_lastname,dni',
                'detailPecosas:id,pecosa_id,detail_product_id,quantity,unit_price,subtotal'
            ]);

        if ($request->has('search') && $request->search != '') {
            $search = $request->search;
            $query->where('pecosa_number', 'like', "{$search}%");
        }

        if ($request->has('association_id') && $request->association_id != '') {
            $query->where('association_id', $request->association_id);
        }

        if ($request->has('state_id') && $request->state_id != '') {
            $query->where('state_id', $request->state_id);
        }

        if ($request->filled('fecha_inicio')) {
            $query->whereDate('delivery_date', '>=', $request->fecha_inicio);
        }

        if ($request->filled('fecha_fin')) {
            $query->whereDate('delivery_date', '<=', $request->fecha_fin);
        }

        $pecosas = $query->orderBy('id', 'desc')->paginate(10);
        $associations = Association::select(['id', 'name', 'code'])->get();
        $states = State::select(['id', 'title', 'abbreviation'])->get();
        
        $estadoActivo = State::where('abbreviation', 'A')->select(['id'])->first();
        $associationsForModal = $estadoActivo
            ? Association::select(['id', 'name', 'code', 'state_id'])
                ->where('state_id', $estadoActivo->id)->get()
            : Association::select(['id', 'name', 'code', 'state_id'])->get();
        
        $associationIds = $associationsForModal->pluck('id');
        $activeState = State::where('abbreviation', 'A')->first();
        $presidentPosition = Position::where('title', 'like', '%PRESIDENTA%')->first();
        
        $directives = Directive::select(['id', 'partner_id', 'resolution_id', 'position_id', 'state_id']);
        
        if ($presidentPosition) {
            $directives = $directives->where('position_id', $presidentPosition->id);
        }
        
        if ($activeState) {
            $directives = $directives->where('state_id', $activeState->id);
        }
        
        $directives = $directives
            ->whereHas('partner', function ($q) use ($associationIds) {
                $q->whereIn('association_id', $associationIds);
            })
            ->with(['partner:id,person_id,association_id', 'partner.people:id,names,father_lastname'])
            ->get();
        
        // Indexar directivas por association_id
        $directivesByAssociation = $directives->mapToGroups(function ($directive) {
            return [$directive->partner->association_id => $directive];
        })->map(function ($collection) {
            return $collection->first();
        });
        
        foreach ($associationsForModal as $association) {
            $directive = $directivesByAssociation->get($association->id);
            $association->president_partner_id = $directive ? $directive->partner_id : null;
            $association->president_name = $directive && $directive->partner && $directive->partner->people 
                ? $directive->partner->people->names . ' ' . $directive->partner->people->father_lastname 
                : null;
        }
        
        $partners = Partner::select(['id', 'person_id', 'association_id', 'state_id'])
            ->with(['people:id,names,father_lastname,mother_lastname,dni'])
            ->get();
        $responsibles = Responsible::select(['id', 'person_id', 'type', 'active'])
            ->with(['person:id,names,father_lastname,mother_lastname,dni'])
            ->where('active', true)
            ->get();
        
        $detailProductIds = DetailProduct::orderBy('id')->pluck('id');
        $usedQuantities = DetailPecosa::whereIn('detail_product_id', $detailProductIds)
            ->groupBy('detail_product_id')
            ->select('detail_product_id', DB::raw('SUM(quantity) as total_used'))
            ->pluck('total_used', 'detail_product_id')
            ->toArray();
            
        $detailProductsList = DetailProduct::select(['id', 'product_id', 'quantity', 'unit_price', 'start_date', 'end_date'])
            ->with(['product:id,title,abbreviation'])
            ->orderBy('start_date', 'asc')
            ->get()
            ->map(function($dp) use ($usedQuantities) {
                $dp->available_stock = $dp->quantity - ($usedQuantities[$dp->id] ?? 0);
                return $dp;
            });

        return view('productos-pecosas.pecosas.index', compact('pecosas', 'associations', 'states', 'associationsForModal', 'partners', 'responsibles', 'detailProductsList'));
    }

    public function createPecosa()
    {
        $estadoActivo = State::where('abbreviation', 'A')->first();
        $associations = $estadoActivo
            ? Association::where('state_id', $estadoActivo->id)->get()
            : Association::all();
        
        // Agregar president_partner_id y president_name a cada asociación
        foreach ($associations as $association) {
            $president = $association->getPresidenta();
            $association->president_partner_id = $president ? $president->id : null;
            $association->president_name = $president && $president->people 
                ? $president->people->names . ' ' . $president->people->father_lastname 
                : null;
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

            $chiefName = null;
            if ($request->filled('chief_id')) {
                $chief = \App\Models\Responsible::with('person')->find($request->chief_id);
                if ($chief && $chief->person) {
                    $chiefName = $chief->person->names . ' ' . $chief->person->father_lastname;
                }
            }

            $storekeeperName = null;
            if ($request->filled('storekeeper_id')) {
                $storekeeper = \App\Models\Responsible::with('person')->find($request->storekeeper_id);
                if ($storekeeper && $storekeeper->person) {
                    $storekeeperName = $storekeeper->person->names . ' ' . $storekeeper->person->father_lastname;
                }
            }

            $managingPartnerName = null;
            if ($request->filled('managing_partner_id')) {
                $managingPartner = Partner::with('people')->find($request->managing_partner_id);
                if ($managingPartner && $managingPartner->people) {
                    $managingPartnerName = $managingPartner->people->names . ' ' . $managingPartner->people->father_lastname;
                }
            }

            $presidentName = null;
            $association = Association::find($request->association_id);
            if ($association) {
                $presidentName = $association->getPresidentName();
            }

            $pecosaData = array_merge($validated, [
                'chief_name'            => $chiefName,
                'storekeeper_name'      => $storekeeperName,
                'managing_partner_name' => $managingPartnerName,
                'president_name'        => $presidentName,
                'association_name'      => $association ? $association->name : null,
                'association_code'      => $association ? $association->code : null,
            ]);

            $pecosa = Pecosa::create($pecosaData);
            $typeSalida = TypeTransaction::whereRaw('LOWER(title) = ?', ['salida'])->first();

            foreach ($request->details as $index => $detail) {
                $detailProduct = DetailProduct::with('product.uom')->find($detail['detail_product_id']);
                
                $unitPrice = $detailProduct ? $detailProduct->unit_price : ($detail['unit_price'] ?? 0);
                $subtotal  = $detail['quantity'] * $unitPrice;

                DetailPecosa::create([
                    'pecosa_id'            => $pecosa->id,
                    'detail_product_id'    => $detail['detail_product_id'],
                    'quantity'             => $detail['quantity'],
                    'unit_price'           => $unitPrice,
                    'subtotal'             => $subtotal,
                    'priority'             => $index + 1,
                    // Snapshots históricos
                    'product_name'         => $detailProduct ? ($detailProduct->product ? $detailProduct->product->title : null) : null,
                    'product_abbreviation' => $detailProduct ? ($detailProduct->product ? $detailProduct->product->abbreviation : null) : null,
                    'uom_title'            => $detailProduct ? ($detailProduct->product ? ($detailProduct->product->uom ? $detailProduct->product->uom->title : null) : null) : null,
                ]);

                $this->deductStockByDetailProduct($detail['detail_product_id'], $detail['quantity'], $pecosa->id);

                if ($typeSalida && $detailProduct) {
                    Transaction::create([
                        'detail_product_id'  => $detail['detail_product_id'],
                        'type_transaction_id' => $typeSalida->id,
                        'quantity'           => $detail['quantity'],
                        'unit_price'         => $unitPrice,
                        'total_price'        => $subtotal,
                        'document_number'    => $validated['pecosa_number'],
                        'transaction_date'   => $validated['delivery_date'],
                        // Snapshots históricos
                        'product_name'       => $detailProduct->product ? $detailProduct->product->title : null,
                        'uom_title'          => ($detailProduct->product && $detailProduct->product->uom) ? $detailProduct->product->uom->title : null,
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
        $pecosa->load([
            'detailPecosas.detailProduct.product.uom',
            'association.placeSector.place',
            'association.partners.beneficiaries',
            'chief.person',
            'storekeeper.person'
        ]);

        $formatCantidad = function ($value) {
            return floor($value) == $value
                ? number_format($value, 0, '.', '')
                : rtrim(rtrim(number_format($value, 2, '.', ''), '0'), '.');
        };

        $articulos = [];
        foreach ($pecosa->detailPecosas as $index => $detail) {
            $product = $detail->detailProduct->product ?? null;
            $articulos[] = [
                'numero' => str_pad($index + 1, 2, '0', STR_PAD_LEFT),
                'cantidad_solicitado' => $formatCantidad($detail->quantity),
                'descripcion' => $product ? $product->title . ' (' . $product->abbreviation . ')' : '-',
                'cantidad_despachado' => $formatCantidad($detail->quantity),
                'unidad' => $product && $product->uom ? $product->uom->title : 'UNIDAD',
                'unitary' => number_format($detail->unit_price, 2),
                'unitario' => number_format($detail->unit_price, 2),
                'total' => number_format($detail->quantity * $detail->unit_price, 2),
            ];
        }

        $total_general = number_format($pecosa->detailPecosas->sum(function ($d) {
            return $d->quantity * $d->unit_price;
        }), 2);

        $jefePerson = $pecosa->chief && $pecosa->chief->person ? $pecosa->chief->person : null;
        $storekeeperPerson = $pecosa->storekeeper && $pecosa->storekeeper->person ? $pecosa->storekeeper->person : null;
        $jefeName = $pecosa->chief_name ?? ($jefePerson ? trim($jefePerson->names . ' ' . $jefePerson->father_lastname . ' ' . $jefePerson->mother_lastname) : 'ENCARGADO DE PROVALE');
        $storekeeperName = $pecosa->storekeeper_name ?? ($storekeeperPerson ? trim($storekeeperPerson->names . ' ' . $storekeeperPerson->father_lastname . ' ' . $storekeeperPerson->mother_lastname) : 'JEFA DE ALMACÉN PROVALE');
        $association = $pecosa->association;
        $zonaCode = $association && $association->placeSector && $association->placeSector->place
            ? ($association->placeSector->place->code ?? '01')
            : '01';
        $totalBeneficiarios = $association
            ? $association->partners->sum(function ($partner) {
                return $partner->beneficiaries->count();
            })
            : 0;
        $fechaLarga = Carbon::parse($pecosa->delivery_date)
            ->locale('es')
            ->translatedFormat('l, j \d\e F \d\e Y');

        $data = [
            'zona' => $zonaCode,
            'comite' => $association ? $association->code : 'N/A',
            'num_mes' => $totalBeneficiarios,
            'racion' => 'N/A',
            'numero_orden' => $pecosa->pecosa_number,
            'solicitante_nombre' => $pecosa->managing_partner_name ?? 'N/A',
            'domicilio' => $association ? $association->name : 'N/A',
            'fecha' => $fechaLarga,
            'articulos' => $articulos,
            'total_general' => 'S/. ' . $total_general,
            'encargado_almacen' => $jefeName,
            'dni_encargado' => $jefePerson->dni ?? '',
            'control' => $storekeeperName,
            'dni_control' => $storekeeperPerson->dni ?? '',
        ];

        $pdf = \PDF::loadView('comprobante_salida', $data);
        return $pdf->setPaper('A4', 'landscape')->stream('comprobante-salida-' . $pecosa->pecosa_number . '.pdf');
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
                $detailProduct = DetailProduct::with('product.uom')->find($detail['detail_product_id']);
                $unitPrice = $detailProduct ? $detailProduct->unit_price : ($detail['unit_price'] ?? 0);
                $subtotal  = $detail['quantity'] * $unitPrice;

                DetailPecosa::create([
                    'pecosa_id'            => $pecosa->id,
                    'detail_product_id'    => $detail['detail_product_id'],
                    'quantity'             => $detail['quantity'],
                    'unit_price'           => $unitPrice,
                    'subtotal'             => $subtotal,
                    'priority'             => $index + 1,
                    'product_name'         => $detailProduct ? ($detailProduct->product ? $detailProduct->product->title : null) : null,
                    'product_abbreviation' => $detailProduct ? ($detailProduct->product ? $detailProduct->product->abbreviation : null) : null,
                    'uom_title'            => $detailProduct ? ($detailProduct->product ? ($detailProduct->product->uom ? $detailProduct->product->uom->title : null) : null) : null,
                ]);

                $this->deductStockByDetailProduct($detail['detail_product_id'], $detail['quantity'], $pecosa->id);

                if ($typeSalida && $detailProduct) {
                    Transaction::create([
                        'detail_product_id'   => $detail['detail_product_id'],
                        'type_transaction_id' => $typeSalida->id,
                        'quantity'            => $detail['quantity'],
                        'unit_price'          => $unitPrice,
                        'total_price'         => $subtotal,
                        'document_number'     => $validated['pecosa_number'],
                        'transaction_date'    => $validated['delivery_date'],
                        'product_name'        => $detailProduct->product ? $detailProduct->product->title : null,
                        'uom_title'           => ($detailProduct->product && $detailProduct->product->uom) ? $detailProduct->product->uom->title : null,
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
            $resolutionIds = DB::table('resolution_associations')
                ->where('association_id', $association->id)
                ->pluck('resolution_id');
            
            $directiva = \App\Models\Directive::whereIn('resolution_id', $resolutionIds)
                ->whereHas('position', function ($q) {
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
            'mes' => $mes,
            'anio' => $anio,
        ];

        $pdf = \PDF::loadView('programacion_entrega', $data);
        return $pdf->setPaper('A4', 'landscape')->stream('programacion-entrega-' . $anio . '-' . sprintf('%02d', $mes) . '.pdf');
    }

    // ==================== KARDEX ====================

    public function kardex(Request $request)
    {
        $query = DetailProduct::query()
            ->select(['id', 'product_id', 'quantity', 'unit_price', 'start_date', 'end_date', 'created_at'])
            ->with(['product:id,title,abbreviation,state_id,uom_id', 'stocks:id,detail_product_id,pecosa_id,quantity']);

        if ($request->filled('product_id')) {
            $query->where('product_id', $request->product_id);
        }

        if ($request->filled('periodo')) {
            $periodo = $request->periodo;
            $today = now()->toDateString();
            if ($periodo === 'vigente') {
                $query->where('start_date', '<=', $today)
                    ->where('end_date', '>=', $today);
            } elseif ($periodo === 'vencido') {
                $query->where('end_date', '<', $today);
            } elseif ($periodo === 'futuro') {
                $query->where('start_date', '>', $today);
            }
        }

        if ($request->filled('stock_status')) {
            $stockStatus = $request->stock_status;
            $query->whereHas('product', function ($q) {
                $q->select('id');
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
                $q->where('title', 'like', "{$search}%")
                  ->orWhere('abbreviation', 'like', "{$search}%");
            });
        }

        $detailProducts = $query->orderBy('created_at', 'desc')->paginate(15);
        $products = Product::select(['id', 'title', 'abbreviation'])->get();

        return view('productos-pecosas.kardex', compact('detailProducts', 'products'));
    }

    // ==================== PRODUCTOS DETALLE (KARDEX) ====================

    public function productosDetalle(Request $request)
    {
        $query = DetailProduct::query()
            ->select(['id', 'product_id', 'quantity', 'unit_price', 'start_date', 'end_date', 'created_at'])
            ->with(['product:id,title,abbreviation,state_id,uom_id', 'stocks:id,detail_product_id,pecosa_id,quantity']);

        if ($request->filled('product_id')) {
            $query->where('product_id', $request->product_id);
        }

        if ($request->filled('periodo')) {
            $periodo = $request->periodo;
            $today = now()->toDateString();
            if ($periodo === 'vigente') {
                $query->where('start_date', '<=', $today)
                    ->where('end_date', '>=', $today);
            } elseif ($periodo === 'vencido') {
                $query->where('end_date', '<', $today);
            } elseif ($periodo === 'futuro') {
                $query->where('start_date', '>', $today);
            }
        }

        if ($request->filled('stock_status')) {
            $stockStatus = $request->stock_status;
            $query->whereHas('product', function ($q) {
                $q->select('id');
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
                $q->where('title', 'like', "{$search}%")
                  ->orWhere('abbreviation', 'like', "{$search}%");
            });
        }

        $detailProducts = $query->orderBy('created_at', 'desc')->paginate(15);
        $products = Product::select(['id', 'title', 'abbreviation', 'state_id', 'uom_id'])
            ->with(['state:id,title', 'uom:id,title'])
            ->get();
        $uoms = Uom::select(['id', 'title'])->get();
        $states = State::select(['id', 'title', 'abbreviation'])->get();

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

        try {
            DB::beginTransaction();

            $detailProduct = DetailProduct::create($validated);

            $typeIngreso = TypeTransaction::whereRaw('LOWER(title) = ?', ['ingreso'])->first();
            
            if ($typeIngreso) {
                $product = Product::with('uom')->find($validated['product_id']);
                
                Transaction::create([
                    'detail_product_id'   => $detailProduct->id,
                    'type_transaction_id' => $typeIngreso->id,
                    'quantity'            => $validated['quantity'],
                    'unit_price'          => $validated['unit_price'],
                    'total_price'         => $validated['quantity'] * $validated['unit_price'],
                    'transaction_date'     => $validated['start_date'],
                    'product_name'        => $product ? $product->title : null,
                    'uom_title'            => $product && $product->uom ? $product->uom->title : null,
                ]);
            }

            DB::commit();
            return redirect()->route('productos-pecosas.productos.index')->with('success', 'Producto registrado correctamente.');
        } catch (\Exception $e) {
            DB::rollBack();
            return back()->withInput()->with('error', 'Error al registrar el producto: ' . $e->getMessage());
        }
    }
}
