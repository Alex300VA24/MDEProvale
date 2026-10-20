<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\StorePecosaRequest;
use App\Http\Requests\StoreProductRequest;
use App\Http\Requests\UpdatePecosaRequest;
use App\Http\Requests\UpdateProductRequest;
use App\Http\Resources\DetailProductResource;
use App\Http\Resources\PecosaResource;
use App\Http\Resources\ProductResource;
use App\Models\Association;
use App\Models\AssociationRosterPeriod;
use App\Models\DetailPecosa;
use App\Models\DetailProduct;
use App\Models\DistributionAssignment;
use App\Models\DistributionPeriod;
use App\Models\Pecosa;
use App\Models\Position;
use App\Models\Product;
use App\Models\Responsible;
use App\Models\State;
use App\Models\Transaction;
use App\Models\Uom;
use App\Repositories\PecosaRepository;
use App\Repositories\ProductRepository;
use App\Services\PecosaService;
use App\Services\ReparticionService;
use App\Services\SchedulingService;
use App\Services\StockService;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class ProductosPecosasController extends Controller
{
    private const PECOSA_WITH = [
        'association:id,name,code',
        'state:id,title,abbreviation',
        'managingPartner.people:id,names,father_lastname,mother_lastname,dni',
        'chief.person:id,names,father_lastname,mother_lastname,dni',
        'storekeeper.person:id,names,father_lastname,mother_lastname,dni',
        'detailPecosas.detailProduct.product:id,title,abbreviation',
    ];

    private ProductRepository $productRepo;
    private PecosaRepository $pecosaRepo;
    private PecosaService $pecosaService;
    private StockService $stockService;

    public function __construct(
        ProductRepository $productRepo,
        PecosaRepository $pecosaRepo,
        PecosaService $pecosaService,
        StockService $stockService,
        private SchedulingService $schedulingService,
        private ReparticionService $reparticionService
    ) {
        $this->productRepo = $productRepo;
        $this->pecosaRepo = $pecosaRepo;
        $this->pecosaService = $pecosaService;
        $this->stockService = $stockService;
    }

    // ==================== PRODUCTOS ====================

    public function products(Request $request)
    {
        $filters = $request->only(['search', 'state_id', 'uom_id']);
        $products = $this->productRepo->searchWithFilters($filters, (int) $request->input('per_page', 10));

        return ProductResource::collection($products);
    }

    public function productsOptions()
    {
        return response()->json([
            'states' => State::temporal()->get(['id', 'title', 'abbreviation']),
            'uoms' => Uom::select(['id', 'title'])->get(),
        ]);
    }

    public function detailProducts(Request $request)
    {
        $perPage = (int) $request->input('per_page', 15);
        $today = now()->toDateString();

        $query = DetailProduct::query()
            ->select(['id', 'product_id', 'quantity', 'unit_price', 'start_date', 'end_date'])
            ->with(['product:id,title,abbreviation,uom_id', 'product.uom:id,title'])
            ->withSum('stocks as used_quantity', 'quantity')
            ->orderByDesc('start_date')
            ->orderByDesc('id');

        if ($search = trim((string) $request->input('search'))) {
            $query->whereHas('product', function ($q) use ($search) {
                $q->where('title', 'like', "%{$search}%")
                    ->orWhere('abbreviation', 'like', "%{$search}%");
            });
        }

        if ($request->filled('product_id')) {
            $query->where('product_id', $request->input('product_id'));
        }

        if ($request->filled('year') || $request->filled('month')) {
            $year = (int) $request->input('year', now()->year);

            if ($request->filled('month')) {
                $start = Carbon::create($year, (int) $request->input('month'), 1)->startOfMonth();
                $end = $start->copy()->endOfMonth();
            } else {
                $start = Carbon::create($year, 1, 1)->startOfYear();
                $end = $start->copy()->endOfYear();
            }

            $query->where(function ($q) use ($start, $end) {
                $q->whereNull('start_date')->orWhere('start_date', '<=', $end->toDateString());
            })->where(function ($q) use ($start, $end) {
                $q->whereNull('end_date')->orWhere('end_date', '>=', $start->toDateString());
            });
        }

        $periodo = $request->input('periodo');
        if ($periodo === 'vigente') {
            $query->where(fn ($q) => $q->whereNull('start_date')->orWhere('start_date', '<=', $today))
                ->where(fn ($q) => $q->whereNull('end_date')->orWhere('end_date', '>=', $today));
        } elseif ($periodo === 'vencido') {
            $query->whereNotNull('end_date')->where('end_date', '<', $today);
        }

        return DetailProductResource::collection($query->paginate($perPage));
    }

    public function storeProduct(StoreProductRequest $request)
    {
        $validated = $request->validated();
        $validated['state_id'] = State::idFor(State::CURRENT);
        if (!$validated['state_id']) {
            return response()->json(['message' => 'No se encontró el estado Vigente para crear el producto.'], 422);
        }
        $validated['abbreviation'] = ($validated['abbreviation'] ?? null)
            ?: Str::upper(Str::substr(Str::ascii($validated['title']), 0, 5));
        $validated['abbreviation'] = $validated['abbreviation'] ?: 'PROD';

        $product = Product::create($validated);

        return (new ProductResource($product->load(['state:id,title', 'uom:id,title'])))
            ->response()
            ->setStatusCode(201);
    }

    public function updateProduct(UpdateProductRequest $request, Product $product)
    {
        $validated = $request->validated();
        if (array_key_exists('abbreviation', $validated) && !$validated['abbreviation']) {
            $title = $validated['title'] ?? $product->title;
            $validated['abbreviation'] = Str::upper(Str::substr(Str::ascii($title), 0, 5)) ?: 'PROD';
        }
        $product->update($validated);

        return new ProductResource($product->load(['state:id,title', 'uom:id,title']));
    }

    public function destroyProduct(Product $product)
    {
        $referencedByPecosa = DetailPecosa::whereHas('detailProduct', function ($q) use ($product) {
            $q->where('product_id', $product->id);
        })->exists();

        $hasStock = DetailProduct::where('product_id', $product->id)
            ->whereHas('stocks')
            ->exists();

        if ($referencedByPecosa || $hasStock) {
            return response()->json([
                'message' => 'No se puede eliminar: el producto tiene detalles/stock asociado',
            ], 422);
        }

        DetailProduct::where('product_id', $product->id)->delete();
        $product->delete();

        return response()->json(null, 204);
    }

    public function programacionEntrega(Request $request)
    {
        $validated = $request->validate([
            'year' => ['required', 'integer', 'between:2019,2100'],
            'month' => ['required', 'integer', 'between:1,12'],
            'sector' => ['nullable', 'string', 'max:150'],
        ]);

        $year = (int) $validated['year'];
        $month = (int) $validated['month'];
        $sector = trim((string) ($validated['sector'] ?? ''));

        if ($this->reparticionService->isBeforeFirstRecordedPeriod($year, $month)) {
            return response()->json([
                'message' => $this->reparticionService->periodRecordNotFoundMessage($year, $month),
                'code' => 'PERIOD_NOT_FOUND',
            ], 404);
        }

        if (! $this->reparticionService->hasIngresoForPeriod($year, $month)) {
            return response()->json([
                'message' => $this->reparticionService->ingresoRequiredMessage($year, $month),
                'code' => 'INGRESO_REQUIRED',
            ], 422);
        }

        $allRows = collect($this->schedulingService->generateProgramacionEntrega($month, $year));
        $racion = $this->reparticionService->getActiveRacion($year, $month);
        $calculation = null;
        if ($racion) {
            $configuration = $this->reparticionService->getConfiguration($racion, $year, $month);
            unset($configuration['assignments']);
            $calculation = $configuration;
        }
        $sectors = $allRows->pluck('sector')->filter()->unique()->sort()->values();
        $rows = $sector === ''
            ? $allRows
            : $allRows->where('sector', $sector)->values();

        return response()->json([
            'data' => $rows->values(),
            'sectors' => $sectors,
            'summary' => [
                'committees' => $rows->count(),
                'beneficiaries' => $rows->sum('total_beneficiarios'),
                'milk' => $rows->sum('leche'),
                'milk_rounded' => $rows->sum('leche_redondeo'),
                'oat' => $rows->sum('hojuela'),
                'oat_rounded' => $rows->sum('hojuela_redondeo'),
            ],
            'pdf_url' => route('productos-pecosas.pecosas.programacion-entrega', array_filter([
                'year' => $year,
                'month' => $month,
                'sector' => $sector,
            ], fn ($value) => $value !== '')),
            'period' => ['year' => $year, 'month' => $month],
            'calculation' => $calculation,
        ]);
    }

    public function updateProgramacionEntrega(Request $request)
    {
        $validated = $request->validate([
            'year' => ['required', 'integer', 'between:2019,2100'],
            'month' => ['required', 'integer', 'between:1,12'],
            'sector' => ['nullable', 'string', 'max:150'],
            'rows' => ['required', 'array', 'min:1'],
            'rows.*.association_id' => ['required', 'integer', 'distinct', 'exists:associations,id'],
            'rows.*.total_beneficiarios' => ['required', 'integer', 'min:0', 'max:1000000'],
            'rows.*.leche' => ['required', 'numeric', 'min:0', 'max:999999999999.99'],
            'rows.*.leche_redondeo' => ['required', 'integer', 'min:0', 'max:999999999999'],
            'rows.*.hojuela' => ['required', 'numeric', 'min:0', 'max:999999999999.99'],
            'rows.*.hojuela_redondeo' => ['required', 'integer', 'min:0', 'max:999999999999'],
        ]);

        $year = (int) $validated['year'];
        $month = (int) $validated['month'];

        if ($this->reparticionService->isBeforeFirstRecordedPeriod($year, $month)) {
            return response()->json([
                'message' => $this->reparticionService->periodRecordNotFoundMessage($year, $month),
                'code' => 'PERIOD_NOT_FOUND',
            ], 404);
        }

        $racion = $this->reparticionService->getActiveRacion($year, $month);
        if (! $racion) {
            return response()->json(['message' => 'No hay ración configurada para el período seleccionado.'], 422);
        }

        if (! $this->reparticionService->hasIngresoForPeriod($year, $month)) {
            return response()->json([
                'message' => $this->reparticionService->ingresoRequiredMessage($year, $month),
                'code' => 'INGRESO_REQUIRED',
            ], 422);
        }

        $baseRows = collect($this->schedulingService->generateProgramacionEntrega($month, $year))
            ->keyBy('association_id');
        $unknownAssociation = collect($validated['rows'])
            ->contains(fn ($row) => ! $baseRows->has($row['association_id']));
        if ($unknownAssociation) {
            return response()->json(['message' => 'Uno de los comités no pertenece a la programación seleccionada.'], 422);
        }

        $configuration = $this->reparticionService->getConfiguration($racion, $year, $month);

        DB::transaction(function () use ($validated, $year, $month, $configuration, $baseRows) {
            $period = DistributionPeriod::firstOrCreate(
                ['year' => $year, 'month' => $month],
                [
                    'service_days' => $configuration['service_days'],
                    'milk_grams_per_beneficiary' => $configuration['milk_grams_per_beneficiary'],
                    'oat_grams_per_beneficiary' => $configuration['oat_grams_per_beneficiary'],
                    'milk_can_grams' => $configuration['milk_can_grams'],
                    'oat_bag_grams' => $configuration['oat_bag_grams'],
                    'milk_cans_per_box' => $configuration['milk_cans_per_box'],
                    'oat_kg_per_sack' => $configuration['oat_kg_per_sack'],
                ]
            );

            foreach ($validated['rows'] as $row) {
                $baseBeneficiaries = (int) $baseRows->get($row['association_id'])['beneficiarios_base'];
                DistributionAssignment::updateOrCreate(
                    [
                        'distribution_period_id' => $period->id,
                        'association_id' => $row['association_id'],
                    ],
                    [
                        'beneficiary_adjustment' => (int) $row['total_beneficiarios'] - $baseBeneficiaries,
                        'scheduled_milk' => round((float) $row['leche'], 2),
                        'scheduled_milk_rounded' => (int) $row['leche_redondeo'],
                        'scheduled_oat' => round((float) $row['hojuela'], 2),
                        'scheduled_oat_rounded' => (int) $row['hojuela_redondeo'],
                    ]
                );
            }
        });

        return $this->programacionEntrega($request);
    }

    // ==================== PECOSAS ====================

    public function pecosas(Request $request)
    {
        $filters = $request->only(['search', 'association_id', 'state_id', 'fecha_inicio', 'fecha_fin', 'year', 'month']);

        if ($request->filled('year')) {
            $filters['year'] = max(2019, min(now()->year + 1, (int) $filters['year']));
        } else {
            unset($filters['year']);
        }

        if ($request->filled('month')) {
            $filters['month'] = max(1, min(12, (int) $filters['month']));
        } else {
            unset($filters['month']);
        }

        $pecosas = $this->pecosaRepo->searchWithFilters($filters, (int) $request->input('per_page', 10));

        return PecosaResource::collection($pecosas)->additional([
            'period' => [
                'year' => $filters['year'] ?? null,
                'month' => $filters['month'] ?? null,
            ],
        ]);
    }

    public function pecosasOptions()
    {
        $activeState = State::where('abbreviation', State::CURRENT)->select(['id'])->first();

        // El formulario solo admite comites vigentes, pero el filtro debe poder
        // consultar todo el historial, incluso el de comites vencidos o pendientes.
        $filterAssociations = Association::select(['id', 'name', 'code', 'address', 'state_id'])
            ->orderBy('name')
            ->get();

        $filterAssociations->each(function ($association) use ($activeState) {
            $association->is_current = ! $activeState || $association->state_id === $activeState->id;
        });

        $associations = $activeState
            ? $filterAssociations->where('state_id', $activeState->id)->values()
            : $filterAssociations;

        $presidentPosition = Position::where('title', 'PRESIDENTA')->first();

        if ($presidentPosition && $activeState && $associations->isNotEmpty()) {
            $directives = $this->pecosaRepo->getPresidentDirectivesByAssociation(
                $associations->pluck('id'),
                $presidentPosition->id,
                $activeState->id
            );

            foreach ($associations as $association) {
                $directive = $directives->get($association->id);
                $association->president_partner_id = $directive ? $directive->partner_id : null;
                $association->president_name = $directive && $directive->partner && $directive->partner->people
                    ? $directive->partner->people->names . ' ' . $directive->partner->people->father_lastname
                    : null;
            }
        }

        $responsibles = Responsible::select(['id', 'person_id', 'type', 'active'])
            ->with(['person:id,names,father_lastname,mother_lastname,dni'])
            ->where('active', true)
            ->get()
            ->map(fn ($r) => [
                'id' => $r->id,
                'type' => $r->type,
                'name' => $r->person
                    ? trim(collect([$r->person->names, $r->person->father_lastname, $r->person->mother_lastname])->filter()->implode(' '))
                    : null,
                'dni' => $r->person->dni ?? null,
            ]);

        $presidentPeriods = AssociationRosterPeriod::query()
            ->whereIn('association_id', $associations->pluck('id'))
            ->with('presidentPartner.people:id,dni')
            ->orderBy('period')
            ->get(['association_id', 'period', 'president_name', 'president_partner_id'])
            ->map(fn ($period) => [
                'association_id' => $period->association_id,
                'period' => $period->period?->format('Y-m-d'),
                'partner_id' => $period->president_partner_id,
                'name' => $period->president_name,
                'dni' => $period->presidentPartner?->people?->dni,
            ]);

        $today = now()->toDateString();
        $detailProducts = DetailProduct::select(['id', 'product_id', 'quantity', 'unit_price', 'start_date', 'end_date'])
            ->with(['product:id,title,abbreviation,uom_id', 'product.uom:id,title'])
            ->withSum('stocks as used_quantity', 'quantity')
            ->orderByDesc('start_date')
            ->orderByDesc('id')
            ->get()
            ->map(fn ($dp) => [
                'id' => $dp->id,
                'product_id' => $dp->product_id,
                'product_title' => $dp->product->title ?? null,
                'product_abbreviation' => $dp->product->abbreviation ?? null,
                'uom_title' => $dp->product->uom->title ?? null,
                'unit_price' => (float) $dp->unit_price,
                'quantity' => (float) $dp->quantity,
                'used_quantity' => (float) ($dp->used_quantity ?? 0),
                'available_stock' => (float) ($dp->quantity - ($dp->used_quantity ?? 0)),
                'start_date' => $dp->start_date?->toDateString(),
                'end_date' => $dp->end_date?->toDateString(),
                'active' => $dp->end_date !== null && $dp->end_date->toDateString() >= $today,
            ]);

        return response()->json([
            'states' => State::temporal()->get(['id', 'title', 'abbreviation']),
            'next_pecosa_number' => $this->pecosaService->nextPecosaNumber(),
            'associations' => $associations,
            'filter_associations' => $filterAssociations,
            'president_periods' => $presidentPeriods,
            'responsibles' => $responsibles,
            'detail_products' => $detailProducts,
        ]);
    }

    public function nextPecosaNumber()
    {
        return response()->json(['pecosa_number' => $this->pecosaService->nextPecosaNumber()]);
    }

    public function storePecosa(StorePecosaRequest $request)
    {
        try {
            $pecosa = $this->pecosaService->createPecosa($request->validated());

            return (new PecosaResource($pecosa->load(self::PECOSA_WITH)))
                ->response()
                ->setStatusCode(201);
        } catch (\DomainException $e) {
            return response()->json(['message' => $e->getMessage()], 422);
        } catch (\Exception $e) {
            return response()->json(['message' => 'Error al crear PECOSA: ' . $e->getMessage()], 422);
        }
    }

    public function updatePecosa(UpdatePecosaRequest $request, Pecosa $pecosa)
    {
        try {
            $pecosa = $this->pecosaService->updatePecosa($pecosa->id, $request->validated());

            return new PecosaResource($pecosa->load(self::PECOSA_WITH));
        } catch (\DomainException $e) {
            return response()->json(['message' => $e->getMessage()], 422);
        } catch (\Exception $e) {
            return response()->json(['message' => 'Error al actualizar PECOSA: ' . $e->getMessage()], 422);
        }
    }

    public function destroyPecosa(Pecosa $pecosa)
    {
        try {
            DB::transaction(function () use ($pecosa) {
                $this->stockService->revertStockByPecosa($pecosa->id);
                DetailPecosa::where('pecosa_id', $pecosa->id)->delete();
                Transaction::where('document_number', $pecosa->pecosa_number)->delete();
                $pecosa->delete();
            });

            return response()->json(null, 204);
        } catch (\Exception $e) {
            return response()->json(['message' => 'Error al eliminar PECOSA: ' . $e->getMessage()], 422);
        }
    }
}
