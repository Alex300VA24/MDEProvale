<?php

namespace App\Http\Controllers;

use App\Models\Pecosa;
use App\Models\DetailPecosa;
use App\Models\Transaction;
use App\Models\DetailProduct;
use App\Models\ProductStock;
use App\Models\TypeTransaction;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class PecosaController extends Controller
{
    public function index()
    {
        $pecosas = Pecosa::with(['state', 'association'])->orderBy('created_at', 'desc')->get();
        return view('pecosas.index', compact('pecosas'));
    }

    public function create()
    {
        $products = \App\Models\Product::with('detailProducts')->get();
        return view('pecosas.create', compact('products'));
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'pecosa_number' => 'required|string|max:8',
            'observation' => 'nullable|string',
            'delivery_date' => 'required|date',
            'managing_partner_id' => 'required|integer',
            'state_id' => 'required|exists:states,id',
            'association_id' => 'required|exists:associations,id',
            'details' => 'required|array|min:1',
            'details.*.product_id' => 'required|exists:products,id',
            'details.*.quantity' => 'required|numeric|min:1',
            'details.*.unit_price' => 'required|numeric|min:0',
        ]);

        DB::beginTransaction();
        try {
            $pecosa = Pecosa::create([
                'pecosa_number' => $validated['pecosa_number'],
                'observation' => $validated['observation'] ?? null,
                'delivery_date' => $validated['delivery_date'],
                'managing_partner_id' => $validated['managing_partner_id'],
                'state_id' => $validated['state_id'],
                'association_id' => $validated['association_id'],
            ]);

            $typeSalida = TypeTransaction::whereRaw('LOWER(title) = ?', ['salida'])->first();

            foreach ($validated['details'] as $detail) {
                DetailPecosa::create([
                    'pecosa_id' => $pecosa->id,
                    'product_id' => $detail['product_id'],
                    'quantity' => $detail['quantity'],
                    'unit_price' => $detail['unit_price'],
                    'priority' => 1,
                ]);

                $this->deductStock($pecosa->id, $detail['product_id'], $detail['quantity'], $detail['unit_price']);

                if ($typeSalida) {
                    $stockData = $this->getStockInfo($detail['product_id']);
                    
                    Transaction::create([
                        'product_id' => $detail['product_id'],
                        'type_transaction_id' => $typeSalida->id,
                        'quantity' => $detail['quantity'],
                        'unit_price' => $detail['unit_price'],
                        'total_price' => $detail['quantity'] * $detail['unit_price'],
                        'document_number' => $validated['pecosa_number'],
                        'stock_quantity' => $stockData['quantity'],
                        'stock_unit_price' => $stockData['unit_price'],
                        'stock_total_price' => $stockData['total'],
                        'transaction_date' => $validated['delivery_date'],
                    ]);
                }
            }

            DB::commit();
            return redirect()->route('pecosas.index')->with('success', 'Pecosa registrada correctamente.');
        } catch (\Exception $e) {
            DB::rollBack();
            return redirect()->back()->with('error', 'Error al registrar pecosa: ' . $e->getMessage())->withInput();
        }
    }

    private function deductStock($pecosaId, $productId, $quantity, $unitPrice)
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

    public function show(Pecosa $pecosa)
    {
        $pecosa->load(['details.product', 'state', 'association']);
        return view('pecosas.show', compact('pecosa'));
    }

    public function edit(Pecosa $pecosa)
    {
        $pecosa->load('details');
        $products = \App\Models\Product::all();
        return view('pecosas.edit', compact('pecosa', 'products'));
    }

    public function update(Request $request, Pecosa $pecosa)
    {
        $pecosa->update($request->all());
        return redirect()->route('pecosas.index');
    }

    public function destroy(Pecosa $pecosa)
    {
        $pecosa->delete();
        return redirect()->route('pecosas.index');
    }
}