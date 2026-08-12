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
use App\Models\DetailPecosa;
use App\Models\DetailProduct;
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
use App\Services\StockService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

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
        StockService $stockService
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
            'states' => State::select(['id', 'title', 'abbreviation'])->get(),
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
            ->orderBy('start_date', 'asc')
            ->orderBy('id', 'asc');

        if ($search = trim((string) $request->input('search'))) {
            $query->whereHas('product', function ($q) use ($search) {
                $q->where('title', 'like', "%{$search}%")
                    ->orWhere('abbreviation', 'like', "%{$search}%");
            });
        }

        if ($request->filled('product_id')) {
            $query->where('product_id', $request->input('product_id'));
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
        $product = Product::create($request->validated());

        return (new ProductResource($product->load(['state:id,title', 'uom:id,title'])))
            ->response()
            ->setStatusCode(201);
    }

    public function updateProduct(UpdateProductRequest $request, Product $product)
    {
        $product->update($request->validated());

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

    // ==================== PECOSAS ====================

    public function pecosas(Request $request)
    {
        $filters = $request->only(['search', 'association_id', 'state_id', 'fecha_inicio', 'fecha_fin']);
        $pecosas = $this->pecosaRepo->searchWithFilters($filters, (int) $request->input('per_page', 10));

        return PecosaResource::collection($pecosas);
    }

    public function pecosasOptions()
    {
        $activeState = State::where('abbreviation', 'A')->select(['id'])->first();

        $associations = ($activeState
            ? Association::select(['id', 'name', 'code', 'state_id'])->where('state_id', $activeState->id)
            : Association::select(['id', 'name', 'code', 'state_id']))
            ->get();

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

        $today = now()->toDateString();
        $detailProducts = DetailProduct::select(['id', 'product_id', 'quantity', 'unit_price', 'start_date', 'end_date'])
            ->with(['product:id,title,abbreviation,uom_id', 'product.uom:id,title'])
            ->withSum('stocks as used_quantity', 'quantity')
            ->orderBy('start_date', 'asc')
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
            'states' => State::select(['id', 'title', 'abbreviation'])->get(),
            'associations' => $associations,
            'responsibles' => $responsibles,
            'detail_products' => $detailProducts,
        ]);
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
