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
use Barryvdh\DomPDF\Facade\PDF;

class TransactionController extends Controller
{
    public function index(Request $request)
    {
        $query = Transaction::query()
            ->select(['id', 'detail_product_id', 'type_transaction_id', 'quantity', 'unit_price', 'total_price', 'document_number', 'transaction_date', 'product_name', 'uom_title', 'created_at'])
            ->with(['detailProduct:id,product_id,quantity,unit_price,start_date,end_date', 'detailProduct.product:id,title,abbreviation', 'typeTransaction:id,title']);

        if ($request->filled('search')) {
            $search = $request->search;
            $query->where('product_name', 'like', "{$search}%");
        }

        if ($request->filled('type_transaction_id')) {
            $query->where('type_transaction_id', $request->type_transaction_id);
        }

        if ($request->filled('fecha_inicio')) {
            $query->whereDate('transaction_date', '>=', $request->fecha_inicio);
        }

        if ($request->filled('fecha_fin')) {
            $query->whereDate('transaction_date', '<=', $request->fecha_fin);
        }

        $transactions = $query->orderBy('created_at', 'desc')->paginate(15);

        $types    = TypeTransaction::select(['id', 'title'])->get();
        $products = Product::select(['id', 'title', 'abbreviation'])->get();
        $pecosas  = Pecosa::select(['id', 'pecosa_number', 'association_id', 'delivery_date'])
            ->with(['association:id,name,code'])
            ->orderBy('created_at', 'desc')
            ->get();

        return view('movimientos.index', compact('transactions', 'types', 'products', 'pecosas'));
    }

    public function create()
    {
        $products = Product::select(['id', 'title', 'abbreviation'])
            ->with(['detailProducts:id,product_id,quantity,unit_price,start_date,end_date'])
            ->get();
        $types = TypeTransaction::select(['id', 'title'])->get();
        $states = State::select(['id', 'title', 'abbreviation'])->get();
        $pecosas = Pecosa::select(['id', 'pecosa_number', 'association_id', 'delivery_date'])
            ->with(['association:id,name,code'])
            ->orderBy('created_at', 'desc')
            ->get();
        return view('movimientos.create', compact('products', 'types', 'states', 'pecosas'));
    }

    public function store(Request $request)
    {
        $rules = [
            'product_id'        => 'required|exists:products,id',
            'quantity'          => 'required|numeric|min:0',
            'unit_price'        => 'required|numeric|min:0',
            'document_number'   => 'nullable|string|max:20',
            'transaction_date'  => 'nullable|date',
            'start_date'        => 'nullable|date',
            'end_date'          => 'nullable|date',
        ];

        $validated = $request->validate($rules);

        $typeIngreso = TypeTransaction::whereRaw('LOWER(title) = ?', ['ingreso'])->first();
        $typeSalida = TypeTransaction::whereRaw('LOWER(title) = ?', ['salida'])->first();

        $isIngreso = $typeIngreso && $request->type_transaction_id == $typeIngreso->id;
        $isSalida = $typeSalida && $request->type_transaction_id == $typeSalida->id;

        if ($isIngreso) {
            $detailProduct = DetailProduct::create([
                'product_id'   => $validated['product_id'],
                'quantity'     => $validated['quantity'],
                'unit_price'   => $validated['unit_price'],
                'start_date'   => $validated['start_date'] ?? now()->toDateString(),
                'end_date'     => $validated['end_date'] ?? now()->addYear()->toDateString(),
            ]);

            $detailProductWithRelations = DetailProduct::with('product.uom')->find($detailProduct->id);

            Transaction::create([
                'detail_product_id'   => $detailProduct->id,
                'type_transaction_id' => $typeIngreso->id,
                'quantity'            => $validated['quantity'],
                'unit_price'          => $validated['unit_price'],
                'total_price'         => $validated['quantity'] * $validated['unit_price'],
                'document_number'     => $validated['document_number'] ?? null,
                'transaction_date'    => $validated['transaction_date'] ?? now()->toDateString(),
                'product_name'        => $detailProductWithRelations->product ? $detailProductWithRelations->product->title : null,
                'uom_title'           => ($detailProductWithRelations->product && $detailProductWithRelations->product->uom) ? $detailProductWithRelations->product->uom->title : null,
            ]);

            return redirect()->route('movimientos.index')->with('success', 'Ingreso registrado correctamente.');
        }

        if ($isSalida) {
            $detailProduct = DetailProduct::with('product.uom')->findOrFail($request->detail_product_id);
            $pecosaId = $request->filled('pecosa_id') ? $request->pecosa_id : null;
            $this->deductStock($detailProduct->product_id, $validated['quantity'], $pecosaId);

            Transaction::create([
                'detail_product_id'   => $request->detail_product_id,
                'type_transaction_id' => $typeSalida->id,
                'quantity'            => $validated['quantity'],
                'unit_price'          => $validated['unit_price'],
                'total_price'         => $validated['quantity'] * $validated['unit_price'],
                'document_number'     => $validated['document_number'] ?? null,
                'transaction_date'    => $validated['transaction_date'] ?? now()->toDateString(),
                'product_name'        => $detailProduct->product ? $detailProduct->product->title : null,
                'uom_title'           => ($detailProduct->product && $detailProduct->product->uom) ? $detailProduct->product->uom->title : null,
            ]);

            return redirect()->route('movimientos.index')->with('success', 'Salida registrada correctamente.');
        }

        return back()->with('error', 'Tipo de transacción no válido.');
    }

    private function getStockForProduct($productId)
    {
        $detailProducts = DetailProduct::where('product_id', $productId)
            ->where('start_date', '<=', now()->toDateString())
            ->where('end_date', '>=', now()->toDateString())
            ->withSum('stocks as used_quantity', 'quantity')
            ->get();

        $totalStock = 0;
        foreach ($detailProducts as $detail) {
            $in = $detail->quantity;
            $out = $detail->used_quantity ?? 0;
            $totalStock += ($in - $out);
        }

        return $totalStock;
    }

    private function deductStock($productId, $quantity, $pecosaId = null)
    {
        $detailProducts = DetailProduct::where('product_id', $productId)
            ->where('start_date', '<=', now()->toDateString())
            ->where('end_date', '>=', now()->toDateString())
            ->withSum('stocks as used_quantity', 'quantity')
            ->orderBy('start_date', 'asc')
            ->get();

        $remainingToDeduct = $quantity;

        foreach ($detailProducts as $detail) {
            if ($remainingToDeduct <= 0) {
                break;
            }

            $available = $detail->quantity - ($detail->used_quantity ?? 0);

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
            $transaction = Transaction::with(['detailProduct.product.uom', 'typeTransaction'])->findOrFail($transaction_id);
            $product = $transaction->detailProduct ? $transaction->detailProduct->product : null;

            $articulos = [
                [
                    'numero'               => '01',
                    'cantidad_solicitado'  => number_format($transaction->quantity, 2),
                    'descripcion'          => strtoupper($transaction->product_name ?? ($product ? $product->title : '') ?? ''),
                    'cantidad_despachado'  => number_format($transaction->quantity, 2),
                    'unidad'               => strtoupper($transaction->uom_title ?? ($product && $product->uom ? $product->uom->title : 'KILOS')),
                    'unitario'             => number_format($transaction->unit_price, 2),
                    'total'                => number_format($transaction->total_price, 2),
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

        $pdf = PDF::loadView('comprobante_salida', $data);
        return $pdf->setPaper('A4', 'landscape')->stream('comprobante-salida-' . date('Ymd') . '.pdf');
    }

    public function reparticion(Request $request)
    {
        $currentYear = $request->get('year', date('Y'));
        $currentMonth = $request->get('month', date('n'));
        
        $daysInMonth = date('t', strtotime("$currentYear-$currentMonth-01"));

        $racion = \App\Models\Racion::where('year', $currentYear)->where('active', true)->first();

        if (!$racion) {
            return redirect()->route('movimientos.index')->with('error', 'No hay ración configurada para el año ' . $currentYear . '. Configure las raciones en Mantenimiento.');
        }

        $racionLecheMl = $racion->racion_leche_militros;
        $racionHojuelasGr = $racion->racion_hojuelas_gramos;

        $activeState = State::whereRaw('LOWER(title) = ?', ['activo'])->first();
        
        if (!$activeState) {
            return redirect()->route('movimientos.index')->with('error', 'No se encontró estado ACTIVO. Configure los estados en Mantenimiento.');
        }
        
        $startDate = "$currentYear-$currentMonth-01";
        $endDate = "$currentYear-$currentMonth-" . $daysInMonth;

        $associations = \App\Models\Association::with(['placeSector.sector', 'partners' => function($query) use ($activeState, $startDate, $endDate) {
            $query->select(['id', 'association_id', 'state_id', 'date_begin', 'date_end'])
                  ->where('state_id', $activeState->id)
                  ->where(function($q) use ($startDate, $endDate) {
                      $q->whereNull('date_begin')
                        ->orWhere('date_begin', '')
                        ->orWhere('date_begin', '<=', $endDate);
                  })
                  ->where(function($q) use ($startDate) {
                      $q->whereNull('date_end')
                        ->orWhere('date_end', '')
                        ->orWhere('date_end', '>=', $startDate);
                  });
        }, 'partners.beneficiaries' => function($q) use ($activeState, $startDate, $endDate) {
            $q->select(['id', 'partner_id'])
              ->whereHas('histories', function($hq) use ($activeState, $startDate, $endDate) {
                  $hq->where('state_id', $activeState->id)
                     ->where(function($q) use ($endDate) {
                         $q->whereNull('date_begin')
                           ->orWhere('date_begin', '')
                           ->orWhere('date_begin', '<=', $endDate);
                     })
                     ->where(function($q) use ($startDate) {
                         $q->whereNull('date_end')
                           ->orWhere('date_end', '')
                           ->orWhere('date_end', '>=', $startDate);
                     });
              });
        }])->get()->map(function($association) use ($racionLecheMl, $racionHojuelasGr, $daysInMonth, $activeState, $startDate, $endDate) {
            $totalBeneficiaries = 0;

            foreach ($association->partners as $partner) {
                $totalBeneficiaries += $partner->beneficiaries->count();
            }

            $presidenta = $association->getPresidentName() ?? '';

            $lecheTarros = round(($totalBeneficiaries * $daysInMonth * $racionLecheMl) / 410);
            $lecheCajas = intdiv((int) $lecheTarros, 48);
            $lecheTarrosSueltos = (int) $lecheTarros % 48;
            $hojuelasKg = round(($totalBeneficiaries * $daysInMonth * $racionHojuelasGr) / 1000);
            $hojuelasSacos = intdiv((int) $hojuelasKg, 30);
            $hojuelasKilosSueltos = (int) $hojuelasKg % 30;

            return [
                'id' => $association->id,
                'codigo' => $association->code ?? $association->id,
                'nombre' => $association->name,
                'presidenta' => $presidenta,
                'direccion' => $association->address ?? '',
                'sector' => optional(optional($association->placeSector)->sector)->title ?? '',
                'beneficiarios' => $totalBeneficiaries,
                'dias' => $daysInMonth,
                'leche_ml' => $racionLecheMl,
                'hojuelas_gramos' => $racionHojuelasGr,
                'leche_litros' => $lecheTarros,
                'leche_cajas' => $lecheCajas,
                'leche_tarros' => $lecheTarrosSueltos,
                'hojuelas_kg' => $hojuelasKg,
                'hojuelas_sacos' => $hojuelasSacos,
                'hojuelas_kilos' => $hojuelasKilosSueltos,
            ];
        })->filter(function($club) {
            return $club['beneficiarios'] > 0;
        })->values();

        $pdf = PDF::loadView('movimientos.reparticion', [
            'clubs' => $associations,
            'currentYear' => $currentYear,
            'currentMonth' => $currentMonth,
            'monthName' => date('F', strtotime($startDate)),
            'daysInMonth' => $daysInMonth,
            'racionLecheMl' => $racionLecheMl,
            'racionHojuelasGr' => $racionHojuelasGr,
            'totalBeneficiarios' => $associations->sum('beneficiarios'),
            'totalLecheLitros' => $associations->sum('leche_litros'),
            'totalHojuelasKg' => $associations->sum('hojuelas_kg'),
        ]);

        return $pdf->setPaper('landscape')->stream('reparticion-' . $currentYear . '-' . date('m') . '.pdf');
    }

    public function reparticionTabla(Request $request)
    {
        $currentYear = $request->get('year', date('Y'));
        $currentMonth = $request->get('month', date('n'));
        $daysInMonth = date('t', strtotime("$currentYear-$currentMonth-01"));

        $racion = \App\Models\Racion::where('year', $currentYear)->where('active', true)->first();

        if (!$racion) {
            return redirect()->route('movimientos.index')->with('error', 'No hay ración configurada para el año ' . $currentYear . '. Configure las raciones en Mantenimiento.');
        }

        $racionLecheMl = $racion->racion_leche_militros;
        $racionHojuelasGr = $racion->racion_hojuelas_gramos;

        $activeState = State::whereRaw('LOWER(title) = ?', ['activo'])->first();
        
        $startDate = "$currentYear-$currentMonth-01";
        $endDate = "$currentYear-$currentMonth-" . $daysInMonth;

        $associations = Association::with(['placeSector.sector', 'partners' => function($query) use ($activeState, $startDate, $endDate) {
            $query->where('state_id', $activeState->id ?? 0)
                  ->where(function($q) use ($startDate, $endDate) {
                      $q->whereNull('date_begin')
                        ->orWhere('date_begin', '')
                        ->orWhere('date_begin', '<=', $endDate);
                  })
                  ->where(function($q) use ($startDate) {
                      $q->whereNull('date_end')
                        ->orWhere('date_end', '')
                        ->orWhere('date_end', '>=', $startDate);
                  });
        }, 'partners.beneficiaries', 'partners.beneficiaries.histories' => function($q) use ($startDate, $endDate) {
            $q->where(function($q) use ($endDate) {
                  $q->whereNull('date_begin')
                    ->orWhere('date_begin', '')
                    ->orWhere('date_begin', '<=', $endDate);
              })
              ->where(function($q) use ($startDate) {
                  $q->whereNull('date_end')
                    ->orWhere('date_end', '')
                    ->orWhere('date_end', '>=', $startDate);
              });
        }])->get()->map(function($association) use ($racionLecheMl, $racionHojuelasGr, $daysInMonth, $activeState, $startDate, $endDate) {
            $totalBeneficiaries = 0;

            foreach ($association->partners as $partner) {
                foreach ($partner->beneficiaries as $beneficiary) {
                    $isActive = $beneficiary->histories->where('state_id', $activeState->id)->count() > 0;
                    if ($isActive) {
                        $totalBeneficiaries++;
                    }
                }
            }

            $lecheTarros = round(($totalBeneficiaries * $daysInMonth * $racionLecheMl) / 410);
            $lecheCajas = intdiv((int) $lecheTarros, 48);
            $lecheTarrosSueltos = (int) $lecheTarros % 48;
            $hojuelasKg = round(($totalBeneficiaries * $daysInMonth * $racionHojuelasGr) / 1000);
            $hojuelasSacos = intdiv((int) $hojuelasKg, 30);
            $hojuelasKilosSueltos = (int) $hojuelasKg % 30;

            $presidenta = $association->getPresidentName() ?? '';

            return [
                'id' => $association->id,
                'codigo' => $association->code ?? $association->id,
                'nombre' => $association->name,
                'presidenta' => $presidenta,
                'direccion' => $association->address ?? '',
                'sector' => optional(optional($association->placeSector)->sector)->title ?? '',
                'beneficiarios' => $totalBeneficiaries,
                'dias' => $daysInMonth,
                'leche_ml' => $racionLecheMl,
                'hojuelas_gramos' => $racionHojuelasGr,
                'leche_litros' => $lecheTarros,
                'leche_kilos' => $lecheCajas,
                'leche_cajas' => $lecheCajas,
                'leche_tarros' => $lecheTarrosSueltos,
                'hojuelas_kg' => $hojuelasKg,
                'hojuelas_bolsas' => $hojuelasSacos,
                'hojuelas_sacos' => $hojuelasSacos,
                'hojuelas_kilos' => $hojuelasKilosSueltos,
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
