<?php

namespace App\Http\Controllers\Api;

use App\Exceptions\ProgramacionEntregaException;
use App\Http\Controllers\Controller;
use App\Http\Requests\StorePecosaRequest;
use App\Http\Requests\StoreProductRequest;
use App\Http\Requests\UpdatePecosaRequest;
use App\Http\Requests\UpdateProductRequest;
use App\Http\Resources\DetailProductResource;
use App\Http\Resources\PecosaResource;
use App\Http\Resources\ProductResource;
use App\Models\Pecosa;
use App\Models\Product;
use App\Services\PecosaService;
use App\Services\ProductService;
use App\Services\ProgramacionEntregaService;
use Illuminate\Http\Request;

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

    public function __construct(
        private ProductService $productService,
        private PecosaService $pecosaService,
        private ProgramacionEntregaService $programacionService
    ) {
    }

    // ==================== PRODUCTOS ====================

    public function products(Request $request)
    {
        $filters = $request->only(['search', 'state_id', 'uom_id']);

        return ProductResource::collection(
            $this->productService->search($filters, (int) $request->input('per_page', 10))
        );
    }

    public function productsOptions()
    {
        return response()->json($this->productService->optionsData());
    }

    public function detailProducts(Request $request)
    {
        $filters = $request->only(['search', 'product_id', 'year', 'month', 'periodo']);

        return DetailProductResource::collection(
            $this->productService->lotsByPeriod($filters, (int) $request->input('per_page', 15))
        );
    }

    public function storeProduct(StoreProductRequest $request)
    {
        try {
            $product = $this->productService->register($request->validated());
        } catch (\DomainException $e) {
            return response()->json(['message' => $e->getMessage()], 422);
        }

        return (new ProductResource($product))->response()->setStatusCode(201);
    }

    public function updateProduct(UpdateProductRequest $request, Product $product)
    {
        return new ProductResource($this->productService->updateProduct($product, $request->validated()));
    }

    public function destroyProduct(Product $product)
    {
        try {
            $this->productService->delete($product);
        } catch (\DomainException $e) {
            return response()->json(['message' => $e->getMessage()], 422);
        }

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

        try {
            $programacion = $this->programacionService->build($year, $month, $sector);
        } catch (ProgramacionEntregaException $e) {
            return $this->programacionError($e);
        }

        return response()->json($programacion + [
            'pdf_url' => route('productos-pecosas.pecosas.programacion-entrega', array_filter([
                'year' => $year,
                'month' => $month,
                'sector' => $sector,
            ], fn ($value) => $value !== '')),
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

        try {
            $this->programacionService->save((int) $validated['year'], (int) $validated['month'], $validated['rows']);
        } catch (ProgramacionEntregaException $e) {
            return $this->programacionError($e);
        }

        return $this->programacionEntrega($request);
    }

    private function programacionError(ProgramacionEntregaException $e)
    {
        $body = ['message' => $e->getMessage()];
        if ($e->errorCode()) {
            $body['code'] = $e->errorCode();
        }

        return response()->json($body, $e->status());
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

        $pecosas = $this->pecosaService->searchWithFilters($filters, (int) $request->input('per_page', 10));

        return PecosaResource::collection($pecosas)->additional([
            'period' => [
                'year' => $filters['year'] ?? null,
                'month' => $filters['month'] ?? null,
            ],
        ]);
    }

    public function pecosasOptions()
    {
        return response()->json($this->pecosaService->optionsData());
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
            $this->pecosaService->deletePecosa($pecosa);

            return response()->json(null, 204);
        } catch (\Exception $e) {
            return response()->json(['message' => 'Error al eliminar PECOSA: ' . $e->getMessage()], 422);
        }
    }
}
