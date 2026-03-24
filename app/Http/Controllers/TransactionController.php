<?php

namespace App\Http\Controllers;

use App\Models\Transaction;
use App\Models\Product;
use App\Models\DetailProduct;
use App\Models\ProductStock;
use App\Models\Pecosa;
use App\Models\TypeTransaction;
use App\Models\State;
use App\Models\Association;
use App\Models\Directive;
use Illuminate\Http\Request;

class TransactionController extends Controller
{
    public function index(Request $request)
    {
        $query = Transaction::with(['product', 'typeTransaction']);

        if ($request->filled('search')) {
            $query->whereHas('product', function ($q) use ($request) {
                $q->where('title', 'like', '%' . $request->search . '%');
            });
        }

        if ($request->filled('type_transaction_id')) {
            $query->where('type_transaction_id', $request->type_transaction_id);
        }

        $transactions = Transaction::with(['product', 'typeTransaction', 'detailProducts'])
            ->orderBy('created_at', 'desc')
            ->paginate(15);
        
        foreach ($transactions as $transaction) {
            $detailProduct = DetailProduct::where('product_id', $transaction->product_id)
                ->where('quantity', $transaction->quantity)
                ->where('unit_price', $transaction->unit_price)
                ->orderBy('created_at', 'desc')
                ->first();
            $transaction->detail_start_date = $detailProduct ? $detailProduct->start_date : null;
            $transaction->detail_end_date = $detailProduct ? $detailProduct->end_date : null;
        }
        $types = TypeTransaction::all();
        $products = Product::all();
        $pecosas = Pecosa::with('association')->orderBy('created_at', 'desc')->get();

        return view('movimientos.index', compact('transactions', 'types', 'products', 'pecosas'));
    }

    public function create()
    {
        $products = Product::with('detailProducts')->get();
        $types = TypeTransaction::all();
        $states = State::all();
        $pecosas = Pecosa::with('association')->orderBy('created_at', 'desc')->get();
        return view('movimientos.create', compact('products', 'types', 'states', 'pecosas'));
    }

    public function store(Request $request)
    {
        $typeTransaction = TypeTransaction::find(1);
        $isSalida = $typeTransaction && strtolower($typeTransaction->title) === 'salida';

        $rules = [
            'product_id' => 'required|exists:products,id',
            'quantity' => 'required|numeric|min:0',
            'unit_price' => 'required|numeric|min:0',
            'document_number' => 'nullable|string|max:20',
            'transaction_date' => 'nullable|date',
        ];

        if (!$isSalida) {
            $rules['start_date'] = 'required|date';
            $rules['end_date'] = 'required|date|after:start_date';
        }

        $validated = $request->validate($rules);

        $totalPrice = $validated['quantity'] * $validated['unit_price'];

        $stockQuantity = $validated['quantity'];
        $stockUnitPrice = $validated['unit_price'];
        $stockTotalPrice = $totalPrice;

        if (strtolower($typeTransaction->title) === 'ingreso') {
            $detailProduct = DetailProduct::create([
                'product_id' => $validated['product_id'],
                'unit_price' => $validated['unit_price'],
                'quantity' => $validated['quantity'],
                'start_date' => $validated['start_date'],
                'end_date' => $validated['end_date'],
            ]);

            $previousStock = $this->getStockForProduct($validated['product_id']);
            $stockQuantity = $previousStock + $validated['quantity'];
            $stockTotalPrice = $stockQuantity * $stockUnitPrice;
        } elseif (strtolower($typeTransaction->title) === 'salida') {
            $pecosaId = $request->filled('pecosa_id') ? $request->pecosa_id : null;
            $this->deductStock($validated['product_id'], $validated['quantity'], $pecosaId);
            
            $previousStock = $this->getStockForProduct($validated['product_id']);
            $stockQuantity = $previousStock;
            $stockTotalPrice = $stockQuantity * $stockUnitPrice;
        }

        Transaction::create([
            'product_id' => $validated['product_id'],
            'type_transaction_id' => 1,
            'quantity' => $validated['quantity'],
            'unit_price' => $validated['unit_price'],
            'total_price' => $totalPrice,
            'document_number' => $validated['document_number'] ?? ($request->filled('pecosa_id') ? 'PECOSA-' . $request->pecosa_id : null),
            'stock_quantity' => $stockQuantity,
            'stock_unit_price' => $stockUnitPrice,
            'stock_total_price' => $stockTotalPrice,
            'transaction_date' => $validated['transaction_date'] ?? now()->toDateString(),
        ]);

        return redirect()->route('movimientos.index')->with('success', 'Movimiento registrado correctamente.');
    }

    private function getStockForProduct($productId)
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

    private function deductStock($productId, $quantity, $pecosaId = null)
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
                    'observation' => 'Salida por transacción' . ($pecosaId ? ' - Pecosa #' . $pecosaId : ''),
                ]);

                $remainingToDeduct -= $deduct;
            }
        }

        if ($remainingToDeduct > 0) {
            throw new \Exception('Stock insuficiente. Faltan ' . $remainingToDeduct . ' unidades.');
        }

        return true;
    }

    public function show(Transaction $transaction)
    {
        $transaction->load(['product', 'typeTransaction']);
        return view('movimientos.show', compact('transaction'));
    }

    public function edit(Transaction $transaction)
    {
        $products = Product::all();
        $types = TypeTransaction::all();
        $states = State::all();
        return view('movimientos.edit', compact('transaction', 'products', 'types', 'states'));
    }

    public function update(Request $request, Transaction $transaction)
    {
        $transaction->update($request->all());
        return redirect()->route('movimientos.index')->with('success', 'Movimiento actualizado correctamente.');
    }

    public function destroy(Transaction $transaction)
    {
        $transaction->delete();
        return redirect()->route('movimientos.index')->with('success', 'Movimiento eliminado correctamente.');
    }

    public function reportes()
    {
        return view('movimientos.reportes');
    }

    public function generarReporte(Request $request, $tipo)
    {
        $query = Transaction::with(['product', 'typeTransaction']);
        $titulo = 'Reporte de Movimientos';

        switch ($tipo) {
            case 'ingresos':
                $query->whereHas('typeTransaction', function ($q) {
                    $q->where('title', 'Ingreso');
                });
                $titulo = 'Reporte de Ingresos';
                break;
            case 'salidas':
                $query->whereHas('typeTransaction', function ($q) {
                    $q->where('title', 'like', '%Salida%');
                });
                $titulo = 'Reporte de Salidas';
                break;
        }

        $transactions = $query->orderBy('created_at', 'desc')->get();
        return view('movimientos.reportes.pdf', compact('transactions', 'titulo', 'tipo'));
    }

    public function generarComprobanteSalida(Request $request)
    {
        $transaction_id = $request->get('transaction_id');

        if ($transaction_id) {
            $transaction = Transaction::with(['product.uom', 'typeTransaction'])->findOrFail($transaction_id);

            $articulos = [
                [
                    'numero' => '01',
                    'cantidad_solicitado' => number_format($transaction->quantity, 2),
                    'descripcion' => strtoupper($transaction->product->title ?? ''),
                    'cantidad_despachado' => number_format($transaction->quantity, 2),
                    'unidad' => strtoupper($transaction->product->uom->title ?? 'KILOS'),
                    'unitario' => number_format($transaction->unit_price, 2),
                    'total' => number_format($transaction->total_price, 2),
                ],
            ];

            $data = [
                'zona' => $request->get('zona', ''),
                'comite' => $request->get('comite', ''),
                'num_mes' => date('m'),
                'racion' => '',
                'numero_orden' => str_pad($transaction->id, 6, '0', STR_PAD_LEFT),
                'solicitante_nombre' => $request->get('solicitante', ''),
                'domicilio' => $request->get('domicilio', ''),
                'fecha' => date('d \d\e F \d\e\l Y'),
                'articulos' => $articulos,
                'total_general' => '****' . number_format($transaction->total_price, 2),
            ];
        } else {
            $data = [
                'zona' => '',
                'comite' => '',
                'num_mes' => '',
                'racion' => '',
                'numero_orden' => '',
                'solicitante_nombre' => '',
                'domicilio' => '',
                'fecha' => date('d \d\e F \d\e\l Y'),
                'articulos' => [],
                'total_general' => '*****0.00',
            ];
        }

        return view('comprobante_salida', $data);
    }

    public function reparticion()
    {
        $currentYear = date('Y');
        $currentMonth = date('n');
        $daysInMonth = date('t');

        $racion = \App\Models\Racion::where('year', $currentYear)->where('active', true)->first();

        if (!$racion) {
            return redirect()->route('movimientos.index')->with('error', 'No hay ración configurada para el año ' . $currentYear . '. Configure las raciones en Mantenimiento.');
        }

        $racionLecheMl = $racion->racion_leche_militros;
        $racionHojuelasGr = $racion->racion_hojuelas_gramos;

        $activeState = State::whereRaw('LOWER(title) = ?', ['activo'])->first();

        $associations = \App\Models\Association::with(['partners' => function($query) use ($activeState) {
            $query->where('state_id', $activeState->id ?? 0);
        }, 'partners.beneficiaries'])->get()->map(function($association) use ($racionLecheMl, $racionHojuelasGr, $daysInMonth, $activeState) {
            $totalBeneficiaries = 0;

            foreach ($association->partners as $partner) {
                $beneficiaryCount = $partner->beneficiaries()->where('relationship_id', '!=', 1)->count();
                $totalBeneficiaries += $beneficiaryCount;
            }

            $presidenta = '';
            $directive = \App\Models\Directive::where('resolution_id', $association->resolution_id)
                ->where('position_id', 1)
                ->where('state_id', 1)
                ->first();
            if ($directive && $directive->partner && $directive->partner->person) {
                $presidenta = $directive->partner->person->names . ' ' . $directive->partner->person->father_lastname;
            }

            $lecheLitros = round(($totalBeneficiaries * $daysInMonth * $racionLecheMl) / 410, 2);
            $hojuelasKg = round(($totalBeneficiaries * $daysInMonth * $racionHojuelasGr) / 1000, 2);

            return [
                'id' => $association->id,
                'codigo' => $association->id,
                'nombre' => $association->name,
                'presidenta' => $presidenta,
                'direccion' => $association->address ?? '',
                'beneficiarios' => $totalBeneficiaries,
                'dias' => $daysInMonth,
                'leche_ml' => $racionLecheMl,
                'hojuelas_gramos' => $racionHojuelasGr,
                'leche_litros' => $lecheLitros,
                'hojuelas_kg' => $hojuelasKg,
            ];
        })->filter(function($club) {
            return $club['beneficiarios'] > 0;
        })->values();

        $pdf = \PDF::loadView('movimientos.reparticion', [
            'clubs' => $associations,
            'currentYear' => $currentYear,
            'currentMonth' => $currentMonth,
            'monthName' => date('F'),
            'daysInMonth' => $daysInMonth,
            'racionLecheMl' => $racionLecheMl,
            'racionHojuelasGr' => $racionHojuelasGr,
            'totalBeneficiarios' => $associations->sum('beneficiarios'),
            'totalLecheLitros' => $associations->sum('leche_litros'),
            'totalHojuelasKg' => $associations->sum('hojuelas_kg'),
        ]);

        return $pdf->setPaper('landscape')->stream('reparticion-' . $currentYear . '-' . date('m') . '.pdf');
    }

    public function reparticionTabla()
    {
        $currentYear = date('Y');
        $currentMonth = date('n');
        $daysInMonth = date('t');

        $racion = \App\Models\Racion::where('year', $currentYear)->where('active', true)->first();

        if (!$racion) {
            return redirect()->route('movimientos.index')->with('error', 'No hay ración configurada para el año ' . $currentYear . '. Configure las raciones en Mantenimiento.');
        }

        $racionLecheMl = $racion->racion_leche_militros;
        $racionHojuelasGr = $racion->racion_hojuelas_gramos;

        $activeState = State::whereRaw('LOWER(title) = ?', ['activo'])->first();

        $associations = Association::with(['partners' => function($query) use ($activeState) {
            $query->where('state_id', $activeState->id ?? 0);
        }, 'partners.beneficiaries'])->get()->map(function($association) use ($racionLecheMl, $racionHojuelasGr, $daysInMonth, $activeState) {
            $totalBeneficiaries = 0;

            foreach ($association->partners as $partner) {
                $beneficiaryCount = $partner->beneficiaries()->where('relationship_id', '!=', 1)->count();
                $totalBeneficiaries += $beneficiaryCount;
            }

            $lecheLitros = round(($totalBeneficiaries * $daysInMonth * $racionLecheMl) / 410, 2);
            $lecheKilos = round($lecheLitros, 2);
            $hojuelasKg = round(($totalBeneficiaries * $daysInMonth * $racionHojuelasGr) / 1000, 2);
            $hojuelasBolsas = (int) ceil($hojuelasKg / 0.5);

            $presidenta = $association->president ?? '';

            return [
                'id' => $association->id,
                'codigo' => $association->id,
                'nombre' => $association->name,
                'presidenta' => $presidenta,
                'direccion' => $association->address ?? '',
                'beneficiarios' => $totalBeneficiaries,
                'dias' => $daysInMonth,
                'leche_ml' => $racionLecheMl,
                'hojuelas_gramos' => $racionHojuelasGr,
                'leche_litros' => $lecheLitros,
                'leche_kilos' => $lecheKilos,
                'hojuelas_kg' => $hojuelasKg,
                'hojuelas_bolsas' => $hojuelasBolsas,
            ];
        })->filter(function($club) {
            return $club['beneficiarios'] > 0;
        })->values();

        $totalBeneficiarios = $associations->sum('beneficiarios');
        $totalLecheLitros = $associations->sum('leche_litros');
        $totalLecheKilos = $associations->sum('leche_kilos');
        $totalHojuelasKg = $associations->sum('hojuelas_kg');
        $totalHojuelasBolsas = $associations->sum('hojuelas_bolsas');

        return view('movimientos.reparticion_tabla', compact(
            'associations',
            'currentYear',
            'currentMonth',
            'daysInMonth',
            'racionLecheMl',
            'racionHojuelasGr',
            'totalBeneficiarios',
            'totalLecheLitros',
            'totalLecheKilos',
            'totalHojuelasKg',
            'totalHojuelasBolsas'
        ));
    }
}
