<?php

namespace App\Http\Controllers;

use App\Http\Requests\StorePecosaRequest;
use App\Http\Requests\UpdatePecosaRequest;
use App\Models\Association;
use App\Models\DetailProduct;
use App\Models\Directive;
use App\Models\Partner;
use App\Models\Pecosa;
use App\Models\Position;
use App\Models\ProductStock;
use App\Models\Responsible;
use App\Models\State;
use App\Models\DetailPecosa;
use App\Models\Transaction;
use App\Services\PecosaService;
use App\Services\PDFService;
use App\Services\StockService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class PecosaController extends Controller
{
    private PecosaService $pecosaService;
    private PDFService $pdfService;
    private StockService $stockService;

    public function __construct(PecosaService $pecosaService, PDFService $pdfService, StockService $stockService)
    {
        $this->pecosaService = $pecosaService;
        $this->pdfService = $pdfService;
        $this->stockService = $stockService;
    }

    public function index(Request $request)
    {
        $filters = $request->only(['search', 'association_id', 'state_id', 'fecha_inicio', 'fecha_fin']);
        $pecosas = $this->pecosaService->searchWithFilters($filters);

        $associations = Association::select(['id', 'name', 'code'])->get();
        $states = State::temporal()->get(['id', 'title', 'abbreviation']);

        $activeState = State::where('abbreviation', State::CURRENT)->select(['id'])->first();
        $associationsForModal = $activeState
            ? Association::select(['id', 'name', 'code', 'state_id'])
                ->where('state_id', $activeState->id)->get()
            : Association::select(['id', 'name', 'code', 'state_id'])->get();

        $presidentPosition = Position::where('title', 'PRESIDENTA')->first();

        $directives = Directive::select(['id', 'partner_id', 'resolution_id', 'position_id', 'state_id']);

        if ($presidentPosition) {
            $directives = $directives->where('position_id', $presidentPosition->id);
        }

        if ($activeState) {
            $directives = $directives->where('state_id', $activeState->id);
        }

        $associationIds = $associationsForModal->pluck('id');
        $directives = $directives
            ->whereHas('partner', function ($q) use ($associationIds) {
                $q->whereIn('association_id', $associationIds);
            })
            ->with(['partner:id,person_id,association_id', 'partner.people:id,names,father_lastname'])
            ->get();

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

        $responsibles = Responsible::select(['id', 'person_id', 'type', 'active'])
            ->with(['person:id,names,father_lastname,mother_lastname,dni'])
            ->where('active', true)
            ->get();

        $detailProductsList = DetailProduct::select(['id', 'product_id', 'quantity', 'unit_price', 'start_date', 'end_date'])
            ->with(['product:id,title,abbreviation'])
            ->withSum('stocks as used_quantity', 'quantity')
            ->orderBy('start_date', 'asc')
            ->get()
            ->map(function ($dp) {
                $dp->available_stock = $dp->quantity - ($dp->used_quantity ?? 0);
                return $dp;
            });

        return view('productos-pecosas.pecosas.index', compact(
            'pecosas', 'associations', 'states', 'associationsForModal', 'responsibles', 'detailProductsList'
        ));
    }

    public function create()
    {
        $estadoActivo = State::where('abbreviation', State::CURRENT)->first();
        $associations = $estadoActivo
            ? Association::where('state_id', $estadoActivo->id)->get()
            : Association::all();

        $states = State::temporal()->get(['id', 'title', 'abbreviation']);
        $partners = Partner::select(['id', 'person_id'])->with('people:id,names,father_lastname')->get();
        $responsibles = Responsible::with('person')->where('active', true)->get();

        $detailProductsList = DetailProduct::with('product')
            ->where('end_date', '>=', now()->toDateString())
            ->orderBy('start_date', 'asc')
            ->get();

        $usedQuantities = \App\Models\ProductStock::selectRaw('detail_product_id, SUM(quantity) as total_used')
            ->whereIn('detail_product_id', $detailProductsList->pluck('id'))
            ->groupBy('detail_product_id')
            ->pluck('total_used', 'detail_product_id');

        $detailProductsList->each(function($dp) use ($usedQuantities) {
            $dp->available_stock = $dp->quantity - ($usedQuantities[$dp->id] ?? 0);
        });

        return view('productos-pecosas.pecosas.create', 
            compact('associations', 'states', 'partners', 'responsibles', 'detailProductsList'));
    }

    public function store(StorePecosaRequest $request)
    {
        try {
            $pecosa = $this->pecosaService->createPecosa($request->validated());
            return redirect()->route('pecosas.index')
                ->with('success', 'Pecosa creada exitosamente');
        } catch (\DomainException $e) {
            return back()->withInput()->with('error', $e->getMessage());
        } catch (\Exception $e) {
            return back()->withInput()->with('error', 'Error al crear PECOSA: ' . $e->getMessage());
        }
    }

    public function show(Pecosa $pecosa)
    {
        $pecosa->load(['detailPecosas.detailProduct.product', 'association', 'managingPartner.people']);
        return view('productos-pecosas.pecosas.show', compact('pecosa'));
    }

    public function edit(Pecosa $pecosa)
    {
        $associations = Association::select(['id', 'name', 'code'])->get();
        $states = State::temporal()->get(['id', 'title', 'abbreviation']);
        $partners = Partner::select(['id', 'person_id'])->with('people:id,names,father_lastname')->get();
        return view('productos-pecosas.pecosas.edit', compact('pecosa', 'associations', 'states', 'partners'));
    }

    public function update(UpdatePecosaRequest $request, Pecosa $pecosa)
    {
        try {
            $this->pecosaService->updatePecosa($pecosa->id, $request->validated());
            return redirect()->route('pecosas.index')
                ->with('success', 'Pecosa actualizada exitosamente');
        } catch (\Exception $e) {
            return back()->withInput()->with('error', 'Error al actualizar PECOSA: ' . $e->getMessage());
        }
    }

    public function destroy(Pecosa $pecosa)
    {
        try {
            DB::transaction(function () use ($pecosa) {
                $this->stockService->revertStockByPecosa($pecosa->id);
                DetailPecosa::where('pecosa_id', $pecosa->id)->delete();
                Transaction::where('document_number', $pecosa->pecosa_number)->delete();
                $pecosa->delete();
            });

            return redirect()->route('pecosas.index')->with('success', 'Pecosa eliminada exitosamente');
        } catch (\Exception $e) {
            return back()->with('error', 'Error al eliminar PECOSA: ' . $e->getMessage());
        }
    }

    public function generarComprobante(Pecosa $pecosa)
    {
        return $this->pecosaService->generateComprobante($pecosa)
            ->stream('comprobante-salida-' . $pecosa->pecosa_number . '.pdf');
    }

    public function reportes()
    {
        return view('productos-pecosas.pecosas.reportes');
    }
}
