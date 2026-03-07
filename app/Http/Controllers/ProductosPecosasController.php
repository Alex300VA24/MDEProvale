<?php

namespace App\Http\Controllers;

use App\Models\Product;
use App\Models\Pecosa;
use App\Models\DetailPecosa;
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
        return $this->indexPecosas($request);
    }

    // ==================== PRODUCTOS ====================

    public function indexProductos(Request $request)
    {
        $query = Product::with(['state', 'uom']);

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
        return view('productos-pecosas.productos.index', compact('products', 'states'));
    }

    public function storeProductoAjax(Request $request)
    {
        try {
            $validated = $request->validate([
                'code' => 'required|string|max:20|unique:products,code',
                'title' => 'required|string|max:255',
                'abbreviation' => 'nullable|string|max:50',
                'stock' => 'required|integer|min:0',
                'unit_price' => 'required|numeric|min:0',
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
            'stock' => 'required|integer|min:0',
            'unit_price' => 'required|numeric|min:0',
            'state_id' => 'required|exists:states,id',
            'uom_id' => 'required|exists:uoms,id',
        ]);
        Product::create($validated);
        return redirect()->route('productos-pecosas.productos.index')->with('success', 'Producto creado exitosamente');
    }

    public function showProducto(Product $product)
    {
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
            'stock' => 'required|integer|min:0',
            'unit_price' => 'required|numeric|min:0',
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
        $query = Pecosa::with(['association', 'state', 'managingPartner.people']);

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
        return view('productos-pecosas.pecosas.index', compact('pecosas', 'associations', 'states'));
    }

    public function createPecosa()
    {
        // Solo comités habilitados pueden generar PECOSAS
        $estadoActivo = State::where('abbreviation', 'ACTI')->first();
        $associations = $estadoActivo
            ? Association::where('state_id', $estadoActivo->id)->get()
            : Association::all();
        $states = State::all();
        $partners = Partner::with('people')->get();
        $products = Product::all();
        $uoms = Uom::all();
        return view('productos-pecosas.pecosas.create', compact('associations', 'states', 'partners', 'products', 'uoms'));
    }

    public function storePecosa(Request $request)
    {
        $validated = $request->validate([
            'pecosa_number' => 'required|string|max:50',
            'observation' => 'nullable|string',
            'delivery_date' => 'required|date',
            'managing_partner_id' => 'nullable|exists:partners,id',
            'state_id' => 'required|exists:states,id',
            'association_id' => 'required|exists:associations,id',
            'details' => 'required|array|min:1',
            'details.*.product_id' => 'required|exists:products,id',
            'details.*.quantity' => 'required|numeric|min:0.01',
            'details.*.unit_price' => 'required|numeric|min:0',
        ]);

        // Verificar que el comité esté habilitado
        $association = Association::findOrFail($request->association_id);
        if (!$association->isHabilitado()) {
            return back()->withInput()->with('error', 'El comité no está habilitado. Debe asignar una presidenta primero.');
        }

        // Verificar que no hay productos duplicados en el request
        $productIds = collect($request->details)->pluck('product_id');
        if ($productIds->count() !== $productIds->unique()->count()) {
            return back()->withInput()->with('error', 'No se permiten productos duplicados en la misma PECOSA.');
        }

        try {
            DB::beginTransaction();

            $pecosa = Pecosa::create($validated);

            foreach ($request->details as $index => $detail) {
                // updateOrCreate evita duplicados a nivel de BD
                $pecosa->detailPecosas()->updateOrCreate(
                    [
                        'product_id' => $detail['product_id'],
                    ],
                    [
                        'quantity' => $detail['quantity'],
                        'unit_price' => $detail['unit_price'],
                        'priority' => $index + 1,
                        'start_date' => now(),
                        'end_time' => now(),
                    ]
                );
            }

            DB::commit();
            return redirect()->route('productos-pecosas.pecosas.index')->with('success', 'Pecosa creada exitosamente');
        } catch (\Exception $e) {
            DB::rollBack();
            return back()->withInput()->with('error', 'Error al crear PECOSA: ' . $e->getMessage());
        }
    }

    public function showPecosa(Pecosa $pecosa)
    {
        $pecosa->load(['detailPecosas.product', 'association', 'managingPartner.people']);
        return view('productos-pecosas.pecosas.show', compact('pecosa'));
    }

    public function generarComprobante(Pecosa $pecosa)
    {
        $pecosa->load(['detailPecosas.product', 'association.placeSector.place', 'managingPartner.people']);

        $articulos = [];
        foreach ($pecosa->detailPecosas as $index => $detail) {
            $articulos[] = [
                'numero' => str_pad($index + 1, 2, '0', STR_PAD_LEFT),
                'cantidad_solicitado' => number_format($detail->quantity, 2),
                'descripcion' => $detail->product->title . ' (' . $detail->product->abbreviation . ')',
                'cantidad_despachado' => number_format($detail->quantity, 2),
                'unidad' => $detail->product->uom ? $detail->product->uom->title : 'UNIDAD',
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

        $pdf = \PDF::loadView('comprobante_salida', $data);
        $pdf->setPaper('a4', 'landscape');
        return $pdf->stream('PECOSA-' . $pecosa->pecosa_number . '.pdf');
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
            'managing_partner_id' => 'nullable|exists:partners,id',
            'state_id' => 'required|exists:states,id',
            'association_id' => 'required|exists:associations,id',
        ]);
        $pecosa->update($validated);
        return redirect()->route('productos-pecosas.pecosas.index')->with('success', 'Pecosa actualizada exitosamente');
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
        $query = Product::with(['state', 'uom']);

        switch ($tipo) {
            case 'general':
                $products = $query->get();
                $titulo = 'Inventario General de Productos';
                break;
            case 'stock-bajo':
                $stockMinimo = $request->get('stock_minimo', 10);
                $products = $query->where('stock', '<=', $stockMinimo)->get();
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

        $orientacion = $request->get('orientacion', 'portrait');
        $pdf = \PDF::loadView('productos-pecosas.productos.reportes.pdf', compact('products', 'titulo', 'tipo'));
        $pdf->setPaper('a4', $orientacion);
        return $pdf->download('reporte-productos-' . $tipo . '-' . date('Y-m-d') . '.pdf');
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
                $pecosas = $query->with('detailPecosas.product')->get();
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

        $orientacion = $request->get('orientacion', 'portrait');
        $pdf = \PDF::loadView('productos-pecosas.pecosas.reportes.pdf', compact('pecosas', 'titulo', 'tipo'));
        $pdf->setPaper('a4', $orientacion);
        return $pdf->download('reporte-pecosas-' . $tipo . '-' . date('Y-m-d') . '.pdf');
    }

    /**
     * Generar reporte de Programación de Entrega de Productos PVL
     */
    public function generarProgramacionEntrega(Request $request)
    {
        $mes = $request->get('month', date('n'));
        $anio = $request->get('year', date('Y'));
        $sector = $request->get('sector', '');

        // Obtener todas las asociaciones habilitadas con datos
        $estadoActivo = State::where('abbreviation', 'ACTI')->first();
        $associations = Association::with(['placeSector.place', 'partners.beneficiaries'])
            ->when($estadoActivo, function ($q) use ($estadoActivo) {
                $q->where('state_id', $estadoActivo->id);
            })
            ->get();

        $clubes = [];
        foreach ($associations as $association) {
            // Obtener presidenta
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

            // Contar beneficiarios por prioridad
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

            // Obtener PECOSA del mes/año
            $pecosa = Pecosa::with('detailPecosas.product')
                ->where('association_id', $association->id)
                ->whereMonth('delivery_date', $mes)
                ->whereYear('delivery_date', $anio)
                ->first();

            $bolsas = 0;
            $kilos = 0;
            if ($pecosa) {
                foreach ($pecosa->detailPecosas as $detail) {
                    $bolsas += $detail->quantity;
                    $kilos += $detail->quantity * ($detail->unit_price > 0 ? 1 : 0); // Approximation
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

        $pdf = \PDF::loadView('programacion_entrega', $data);
        $pdf->setPaper('a4', 'landscape');
        return $pdf->stream('programacion-entrega-' . $mes . '-' . $anio . '.pdf');
    }
}
