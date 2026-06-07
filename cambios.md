# Plan de Refactorización Arquitectónica

## Diagnóstico actual

| Problema | Ejemplos |
|---|---|
| **Controllers de 500-1225 líneas** | `ProductosPecosasController` (1225), `SociosBeneficiariosController` (723), `TransactionController` (515) |
| **Lógica duplicada** | `deductStock()` en 2 controllers, `formatPersonName()` en varios, `getStockForProduct()` repetido |
| **Validación inline** | Solo 1 Form Request (`LoginRequest`), todo lo demás es `$request->validate()` |
| **Sin autorización por Policy** | Todo viaja en middleware `module:` + checks inline (`$user->isAdmin()`) |
| **PDF generation acoplado** | Llamadas directas a `PDF::loadView()` dentro de controllers |
| **Sin DTOs/Resources** | Los arrays asociativos se construyen manualmente en cada método |
| **Transacciones DB en controller** | `DB::beginTransaction()` / `commit()` / `rollBack()` directamente |

---

## Estructura de directorios objetivo

```
app/
├── DTOs/                        # Data Transfer Objects
│   ├── BaseDTO.php
│   ├── PecosaSnapshotDTO.php
│   ├── PartnerReportDTO.php
│   ├── ReparticionDTO.php
│   ├── StockDTO.php
│   └── BeneficiaryReportItemDTO.php
├── Services/                    # Lógica de negocio
│   ├── BaseService.php
│   ├── Contracts/
│   │   ├── PartnerServiceInterface.php
│   │   ├── PecosaServiceInterface.php
│   │   ├── ProductServiceInterface.php
│   │   └── TransactionServiceInterface.php
│   ├── PartnerService.php
│   ├── PecosaService.php
│   ├── ProductService.php
│   ├── TransactionService.php
│   ├── BeneficiaryReportService.php
│   ├── SchedulingService.php
│   ├── StockService.php
│   └── PDFService.php
├── Repositories/                # Acceso a datos
│   ├── BaseRepository.php
│   ├── Contracts/
│   │   ├── PartnerRepositoryInterface.php
│   │   ├── PecosaRepositoryInterface.php
│   │   ├── ProductRepositoryInterface.php
│   │   ├── TransactionRepositoryInterface.php
│   │   └── AssociationRepositoryInterface.php
│   ├── PartnerRepository.php
│   ├── PecosaRepository.php
│   ├── ProductRepository.php
│   ├── TransactionRepository.php
│   └── AssociationRepository.php
├── Http/
│   ├── Controllers/             # Solo HTTP handling (thin)
│   │   ├── PersonaController.php
│   │   ├── PartnerController.php        # Refactorizado
│   │   ├── BeneficiarieController.php   # Refactorizado
│   │   ├── ProductController.php
│   │   ├── PecosaController.php
│   │   ├── KardexController.php
│   │   ├── TransactionController.php    # Refactorizado
│   │   ├── ReparticionController.php
│   │   ├── ReportController.php
│   │   └── ...
│   ├── Requests/                # Validación por endpoint
│   │   ├── StorePersonaRequest.php
│   │   ├── UpdatePersonaRequest.php
│   │   ├── StorePartnerRequest.php
│   │   ├── UpdatePartnerRequest.php
│   │   ├── StorePecosaRequest.php
│   │   ├── UpdatePecosaRequest.php
│   │   ├── StoreProductRequest.php
│   │   ├── UpdateProductRequest.php
│   │   ├── StoreTransactionRequest.php
│   │   └── ...
│   └── Resources/               # Transformación de respuestas JSON
│       ├── PartnerResource.php
│       ├── PeopleResource.php
│       ├── ProductResource.php
│       └── PecosaResource.php
└── Policies/                    # Autorización por modelo
    ├── PartnerPolicy.php
    ├── PecosaPolicy.php
    ├── ProductPolicy.php
    ├── TransactionPolicy.php
    └── UserPolicy.php
```

---

## Fase 1: Fundación

### 1.1 Crear directorios

```
app/DTOs/
app/Services/
app/Services/Contracts/
app/Repositories/
app/Repositories/Contracts/
app/Http/Requests/
app/Http/Resources/
app/Policies/
```

### 1.2 Crear clases base

**`app/DTOs/BaseDTO.php`**
```php
<?php

namespace App\DTOs;

abstract class BaseDTO
{
    public function toArray(): array
    {
        return get_object_vars($this);
    }

    public static function fromArray(array $data): static
    {
        return new static(...$data);
    }
}
```

**`app/Repositories/BaseRepository.php`**
```php
<?php

namespace App\Repositories;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Collection;

abstract class BaseRepository
{
    protected Model $model;

    public function __construct()
    {
        $this->model = app($this->model());
    }

    abstract public function model(): string;

    public function find(int $id): ?Model
    {
        return $this->model->find($id);
    }

    public function findOrFail(int $id): Model
    {
        return $this->model->findOrFail($id);
    }

    public function all(): Collection
    {
        return $this->model->all();
    }

    public function paginate(int $perPage = 15): LengthAwarePaginator
    {
        return $this->model->paginate($perPage);
    }

    public function create(array $data): Model
    {
        return $this->model->create($data);
    }

    public function update(Model $model, array $data): bool
    {
        return $model->update($data);
    }

    public function delete(Model $model): bool
    {
        return $model->delete();
    }
}
```

**`app/Services/BaseService.php`**
```php
<?php

namespace App\Services;

abstract class BaseService
{
    // Métodos utilitarios comunes
}
```

---

## Fase 2: Repositorios (extraer queries de los controllers)

### 2.1 PartnerRepository

```php
class PartnerRepository extends BaseRepository
{
    public function model(): string { return Partner::class; }

    public function searchWithFilters(array $filters, int $perPage = 10): LengthAwarePaginator
    {
        return $this->model
            ->select(['id', 'person_id', 'association_id', 'state_id', 'date_begin', 'date_end', 'observations'])
            ->with(['people:id,names,father_lastname,mother_lastname,dni', 'association:id,name,code', 'state:id,title'])
            ->withCount('beneficiaries')
            ->when($filters['search'] ?? null, function ($q, $search) {
                $q->whereHas('people', function ($q) use ($search) {
                    $q->where('names', 'like', "%{$search}%")
                      ->orWhere('father_lastname', 'like', "%{$search}%")
                      ->orWhere('mother_lastname', 'like', "%{$search}%")
                      ->orWhere('dni', 'like', "%{$search}%");
                });
            })
            ->when($filters['association_id'] ?? null, fn($q, $v) => $q->where('association_id', $v))
            ->when($filters['state_id'] ?? null, fn($q, $v) => $q->where('state_id', $v))
            ->orderBy('id', 'desc')
            ->paginate($perPage);
    }

    public function findActiveByAssociation(int $associationId, string $date): Collection
    {
        return $this->model
            ->where('association_id', $associationId)
            ->where('date_begin', '<=', $date)
            ->where(function ($q) use ($date) {
                $q->whereNull('date_end')->orWhere('date_end', '>=', $date);
            })
            ->with(['people', 'beneficiaries.person', 'beneficiaries.relationship', 'beneficiaries.histories.typeBenefit', 'beneficiaries.histories.reasonDisqualification'])
            ->get();
    }

    public function countBeneficiariesForAssociationAtDate(int $associationId, string $date): int
    {
        $activeStateIds = State::whereIn('abbreviation', ['A', 'ACTI'])
            ->orWhereRaw('LOWER(title) = ?', ['activo'])
            ->pluck('id');

        return $this->model
            ->where('association_id', $associationId)
            ->when($activeStateIds->isNotEmpty(), fn($q) => $q->whereIn('state_id', $activeStateIds))
            ->where(fn($q) => $q->whereNull('date_begin')->orWhere('date_begin', '<=', $date))
            ->where(fn($q) => $q->whereNull('date_end')->orWhere('date_end', '>=', $date))
            ->withCount(['beneficiaries as historical_count' => function ($q) use ($activeStateIds, $date) {
                $q->where(fn($q) => $q->whereDoesntHave('histories')->orWhereHas('histories', function ($h) use ($activeStateIds, $date) {
                    $h->when($activeStateIds->isNotEmpty(), fn($q) => $q->whereIn('state_id', $activeStateIds))
                      ->where(fn($q) => $q->whereNull('date_begin')->orWhere('date_begin', '<=', $date))
                      ->where(fn($q) => $q->whereNull('date_end')->orWhere('date_end', '>=', $date));
                }));
            }])
            ->get()
            ->sum('historical_count');
    }
}
```

### 2.2 ProductRepository

```php
class ProductRepository extends BaseRepository
{
    public function model(): string { return Product::class; }

    public function searchWithFilters(array $filters, int $perPage = 10): LengthAwarePaginator
    {
        return $this->model
            ->select(['id', 'title', 'abbreviation', 'state_id', 'uom_id', 'created_at'])
            ->with(['state:id,title,abbreviation', 'uom:id,title', 'detailProducts' => function ($q) {
                $q->select(['id', 'product_id', 'quantity', 'unit_price', 'start_date', 'end_date'])
                  ->withSum('stocks as used_quantity', 'quantity');
            }])
            ->when($filters['search'] ?? null, function ($q, $search) {
                $q->where(fn($q) => $q->where('title', 'like', "{$search}%")->orWhere('abbreviation', 'like', "{$search}%"));
            })
            ->when($filters['state_id'] ?? null, fn($q, $v) => $q->where('state_id', $v))
            ->when($filters['uom_id'] ?? null, fn($q, $v) => $q->where('uom_id', $v))
            ->orderBy('id', 'desc')
            ->paginate($perPage);
    }

    public function getDetailProductsWithStock(?int $productId = null, ?string $periodo = null): Collection
    {
        $query = DetailProduct::query()
            ->select(['id', 'product_id', 'quantity', 'unit_price', 'start_date', 'end_date', 'created_at'])
            ->with(['product:id,title,abbreviation,state_id,uom_id'])
            ->withSum('stocks as used_quantity', 'quantity');

        if ($productId) $query->where('product_id', $productId);

        if ($periodo) {
            $today = now()->toDateString();
            match ($periodo) {
                'vigente' => $query->where('start_date', '<=', $today)->where('end_date', '>=', $today),
                'vencido' => $query->where('end_date', '<', $today),
                'futuro'  => $query->where('start_date', '>', $today),
                default   => null,
            };
        }

        return $query->orderBy('start_date', 'asc')->get()
            ->map(fn($dp) => $dp->setAttribute('available_stock', $dp->quantity - ($dp->used_quantity ?? 0)));
    }
}
```

### 2.3 PecosaRepository

```php
class PecosaRepository extends BaseRepository
{
    public function model(): string { return Pecosa::class; }

    public function searchWithFilters(array $filters, int $perPage = 10): LengthAwarePaginator
    {
        return $this->model
            ->select(['id', 'pecosa_number', 'delivery_date', 'observation', 'managing_partner_id', 'president_name', 'state_id', 'association_id', 'chief_name', 'storekeeper_name', 'created_at'])
            ->with(['association:id,name,code,address,state_id', 'state:id,title,abbreviation', 'managingPartner.people:id,names,father_lastname,mother_lastname,dni', 'detailPecosas:id,pecosa_id,detail_product_id,quantity,unit_price,subtotal'])
            ->when($filters['search'] ?? null, fn($q, $v) => $q->where('pecosa_number', 'like', "{$v}%"))
            ->when($filters['association_id'] ?? null, fn($q, $v) => $q->where('association_id', $v))
            ->when($filters['state_id'] ?? null, fn($q, $v) => $q->where('state_id', $v))
            ->when($filters['fecha_inicio'] ?? null, fn($q, $v) => $q->whereDate('delivery_date', '>=', $v))
            ->when($filters['fecha_fin'] ?? null, fn($q, $v) => $q->whereDate('delivery_date', '<=', $v))
            ->orderBy('id', 'desc')
            ->paginate($perPage);
    }

    public function findByAssociationAndPeriod(int $associationId, string $startDate, string $endDate): ?Pecosa
    {
        return $this->model
            ->with('detailPecosas.detailProduct.product')
            ->where('association_id', $associationId)
            ->whereBetween('delivery_date', [$startDate, $endDate])
            ->first();
    }

    public function getPresidentDirectivesByAssociation(Collection $associationIds, int $presidentPositionId, int $activeStateId): Collection
    {
        return Directive::select(['id', 'partner_id', 'resolution_id', 'position_id', 'state_id'])
            ->where('position_id', $presidentPositionId)
            ->where('state_id', $activeStateId)
            ->whereHas('partner', fn($q) => $q->whereIn('association_id', $associationIds))
            ->with(['partner:id,person_id,association_id', 'partner.people:id,names,father_lastname'])
            ->get()
            ->mapToGroups(fn($d) => [$d->partner->association_id => $d])
            ->map(fn($c) => $c->first());
    }
}
```

### 2.4 TransactionRepository

```php
class TransactionRepository extends BaseRepository
{
    public function model(): string { return Transaction::class; }

    public function searchTransactions(array $filters, int $perPage = 15): LengthAwarePaginator
    {
        return $this->model
            ->select(['id', 'detail_product_id', 'type_transaction_id', 'quantity', 'unit_price', 'total_price', 'document_number', 'transaction_date', 'product_name', 'uom_title', 'created_at'])
            ->with(['detailProduct:id,product_id,quantity,unit_price,start_date,end_date', 'detailProduct.product:id,title,abbreviation', 'typeTransaction:id,title'])
            ->when($filters['search'] ?? null, fn($q, $v) => $q->where('product_name', 'like', "{$v}%"))
            ->when($filters['type_transaction_id'] ?? null, fn($q, $v) => $q->where('type_transaction_id', $v))
            ->when($filters['fecha_inicio'] ?? null, fn($q, $v) => $q->whereDate('transaction_date', '>=', $v))
            ->when($filters['fecha_fin'] ?? null, fn($q, $v) => $q->whereDate('transaction_date', '<=', $v))
            ->orderBy('created_at', 'desc')
            ->paginate($perPage);
    }
}
```

### 2.5 AssociationRepository

```php
class AssociationRepository extends BaseRepository
{
    public function model(): string { return Association::class; }

    public function getActiveAssociations(?int $activeStateId = null): Collection
    {
        $query = $this->model->select(['id', 'name', 'code', 'state_id', 'resolution_id', 'address']);
        if ($activeStateId) $query->where('state_id', $activeStateId);
        return $query->get();
    }

    public function getAssociationsWithSectorAndBeneficiaries(?int $activeStateId = null, ?int $sectorId = null): Collection
    {
        return $this->model
            ->with(['placeSector.sector', 'partners.beneficiaries.person:id,birthdate'])
            ->when($activeStateId, fn($q) => $q->where('state_id', $activeStateId))
            ->when($sectorId, fn($q) => $q->whereHas('placeSector', fn($q) => $q->where('sector_id', $sectorId)))
            ->get();
    }
}
```

---

## Fase 3: Form Requests (extraer validación)

### Requests a crear (~25 clases)

| Request | Endpoint | Reglas clave |
|---|---|---|
| `StorePersonaRequest` | POST personas | `dni:unique:people`, campos requeridos |
| `UpdatePersonaRequest` | PUT personas | `dni:unique:people,dni,{id}` |
| `StorePartnerRequest` | POST socios | Validación de `beneficiaries.*` array |
| `UpdatePartnerRequest` | PUT socios | Validación de `beneficiaries.*` array |
| `StorePecosaRequest` | POST pecosas | `details.*.quantity:min:0.01`, stock validation |
| `UpdatePecosaRequest` | PUT pecosas | Stock validation |
| `StoreProductRequest` | POST productos | `code:unique:products` |
| `UpdateProductRequest` | PUT productos | `code:unique:products,code,{id}` |
| `StoreTransactionRequest` | POST movimientos | Validación condicional ingreso/salida |
| `StoreResolutionRequest` | POST resoluciones | -- |
| `StoreUserRequest` | POST usuarios | `email:unique:users` |
| `UpdateUserRequest` | PUT usuarios | `email:unique:users,email,{id}` |
| ... | | |

### Ejemplo de StorePecosaRequest

```php
<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\Gate;
use App\Models\Pecosa;

class StorePecosaRequest extends FormRequest
{
    public function authorize(): bool
    {
        return Gate::allows('create', Pecosa::class);
    }

    public function rules(): array
    {
        return [
            'pecosa_number' => 'required|string|max:50|unique:pecosas,pecosa_number',
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
        ];
    }

    public function messages(): array
    {
        return [
            'pecosa_number.unique' => 'El número de PECOSA ya está registrado.',
            'details.required' => 'Debe agregar al menos un producto.',
            'details.*.quantity.min' => 'La cantidad debe ser mayor a 0.',
        ];
    }
}
```

---

## Fase 4: Services (extraer lógica de negocio)

### 4.1 StockService

```php
<?php

namespace App\Services;

use App\Models\DetailProduct;
use App\Models\ProductStock;
use Illuminate\Support\Collection;

class StockService
{
    public function getAvailableStockByProduct(int $productId): int
    {
        return DetailProduct::where('product_id', $productId)
            ->where('start_date', '<=', now()->toDateString())
            ->where('end_date', '>=', now()->toDateString())
            ->withSum('stocks as used_quantity', 'quantity')
            ->get()
            ->sum(fn($dp) => $dp->quantity - ($dp->used_quantity ?? 0));
    }

    public function getAvailableStockByDetailProduct(int $detailProductId): int
    {
        $dp = DetailProduct::withSum('stocks as used_quantity', 'quantity')->findOrFail($detailProductId);
        return $dp->quantity - ($dp->used_quantity ?? 0);
    }

    public function deductByDetailProduct(int $detailProductId, float $quantity, ?int $pecosaId = null, string $observation = 'Salida por Pecosa'): ProductStock
    {
        $available = $this->getAvailableStockByDetailProduct($detailProductId);

        if ($quantity > $available) {
            throw new \RuntimeException("Stock insuficiente. Disponible: {$available}, Solicitado: {$quantity}");
        }

        return ProductStock::create([
            'detail_product_id' => $detailProductId,
            'pecosa_id' => $pecosaId,
            'quantity' => $quantity,
            'observation' => $observation . ($pecosaId ? " #{$pecosaId}" : ''),
        ]);
    }

    public function deductByProduct(int $productId, float $quantity, ?int $pecosaId = null): void
    {
        $detailProducts = DetailProduct::where('product_id', $productId)
            ->where('start_date', '<=', now()->toDateString())
            ->where('end_date', '>=', now()->toDateString())
            ->withSum('stocks as used_quantity', 'quantity')
            ->orderBy('start_date', 'asc')
            ->get();

        $remaining = $quantity;

        foreach ($detailProducts as $dp) {
            if ($remaining <= 0) break;
            $available = $dp->quantity - ($dp->used_quantity ?? 0);
            if ($available > 0) {
                $deduct = min($remaining, $available);
                ProductStock::create([
                    'detail_product_id' => $dp->id,
                    'pecosa_id' => $pecosaId,
                    'quantity' => $deduct,
                    'observation' => 'Salida por transacción' . ($pecosaId ? " - Pecosa #{$pecosaId}" : ''),
                ]);
                $remaining -= $deduct;
            }
        }

        if ($remaining > 0) {
            throw new \RuntimeException("Stock insuficiente. Faltan {$remaining} unidades.");
        }
    }

    public function revertStockByPecosa(int $pecosaId): void
    {
        ProductStock::where('pecosa_id', $pecosaId)->delete();
    }

    public function getStockInfoByProduct(int $productId): array
    {
        $detailProducts = DetailProduct::where('product_id', $productId)
            ->where('start_date', '<=', now()->toDateString())
            ->where('end_date', '>=', now()->toDateString())
            ->withSum('stocks as used_quantity', 'quantity')
            ->get();

        $totalStock = 0;
        $totalValue = 0;

        foreach ($detailProducts as $dp) {
            $available = $dp->quantity - ($dp->used_quantity ?? 0);
            $totalStock += $available;
            $totalValue += $available * $dp->unit_price;
        }

        return [
            'quantity' => $totalStock,
            'unit_price' => $totalStock > 0 ? $totalValue / $totalStock : 0,
            'total' => $totalValue,
        ];
    }
}
```

### 4.2 PecosaService

```php
<?php

namespace App\Services;

use App\DTOs\PecosaSnapshotDTO;
use App\Models\Association;
use App\Models\DetailPecosa;
use App\Models\Pecosa;
use App\Models\Responsible;
use App\Models\Partner;
use App\Models\Transaction;
use App\Models\TypeTransaction;
use App\Repositories\PecosaRepository;
use App\Repositories\ProductRepository;
use Barryvdh\DomPDF\Facade\PDF;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class PecosaService
{
    public function __construct(
        private PecosaRepository $pecosaRepo,
        private ProductRepository $productRepo,
        private StockService $stockService,
        private PDFService $pdfService,
    ) {}

    public function createPecosa(array $data): Pecosa
    {
        $association = Association::findOrFail($data['association_id']);
        if (!$association->isHabilitado() && empty($data['managing_partner_id'])) {
            throw new \DomainException('El comité no está habilitado. Debe asignar una presidenta primero.');
        }

        $detailProductIds = collect($data['details'])->pluck('detail_product_id');
        if ($detailProductIds->count() !== $detailProductIds->unique()->count()) {
            throw new \DomainException('No se permiten productos duplicados en la misma PECOSA.');
        }

        $detailProductsById = $this->productRepo->getDetailProductsByIds($detailProductIds);

        foreach ($data['details'] as $detail) {
            $dp = $detailProductsById->get($detail['detail_product_id']);
            if (!$dp) throw new \DomainException('Detalle de producto no encontrado.');
            $available = $dp->quantity - ($dp->used_quantity ?? 0);
            if ($available < $detail['quantity']) {
                throw new \DomainException("Stock insuficiente para {$dp->product->title}. Disponible: {$available}, Solicitado: {$detail['quantity']}");
            }
        }

        return DB::transaction(function () use ($data, $detailProductsById) {
            $snapshot = $this->buildPecosaSnapshotDTO($data);
            $pecosa = Pecosa::create(array_merge($data, $snapshot->toArray()));

            $typeSalida = TypeTransaction::whereRaw('LOWER(title) = ?', ['salida'])->first();

            foreach ($data['details'] as $index => $detail) {
                $dp = $detailProductsById->get($detail['detail_product_id']);
                $unitPrice = $dp->unit_price;
                $subtotal = $detail['quantity'] * $unitPrice;

                DetailPecosa::create([
                    'pecosa_id' => $pecosa->id,
                    'detail_product_id' => $detail['detail_product_id'],
                    'quantity' => $detail['quantity'],
                    'unit_price' => $unitPrice,
                    'subtotal' => $subtotal,
                    'priority' => $index + 1,
                    'product_name' => $dp->product->title,
                    'product_abbreviation' => $dp->product->abbreviation,
                    'uom_title' => $dp->product->uom->title ?? null,
                ]);

                $this->stockService->deductByDetailProduct($detail['detail_product_id'], $detail['quantity'], $pecosa->id);

                if ($typeSalida) {
                    Transaction::create([
                        'detail_product_id' => $detail['detail_product_id'],
                        'type_transaction_id' => $typeSalida->id,
                        'quantity' => $detail['quantity'],
                        'unit_price' => $unitPrice,
                        'total_price' => $subtotal,
                        'document_number' => $data['pecosa_number'],
                        'transaction_date' => $data['delivery_date'],
                        'product_name' => $dp->product->title,
                        'uom_title' => $dp->product->uom->title ?? null,
                    ]);
                }
            }

            return $pecosa;
        });
    }

    public function updatePecosa(int $id, array $data): Pecosa
    {
        $pecosa = Pecosa::findOrFail($id);

        return DB::transaction(function () use ($pecosa, $data) {
            $this->stockService->revertStockByPecosa($pecosa->id);
            DetailPecosa::where('pecosa_id', $pecosa->id)->delete();
            Transaction::where('document_number', $pecosa->pecosa_number)->delete();

            $snapshot = $this->buildPecosaSnapshotDTO($data);
            $pecosa->update(array_merge($data, $snapshot->toArray()));

            $detailProductsById = $this->productRepo->getDetailProductsByIds(collect($data['details'])->pluck('detail_product_id'));
            $typeSalida = TypeTransaction::whereRaw('LOWER(title) = ?', ['salida'])->first();

            foreach ($data['details'] as $index => $detail) {
                $dp = $detailProductsById->get($detail['detail_product_id']);
                $unitPrice = $dp->unit_price;
                $subtotal = $detail['quantity'] * $unitPrice;

                DetailPecosa::create([/* ... */]);
                $this->stockService->deductByDetailProduct($detail['detail_product_id'], $detail['quantity'], $pecosa->id);

                if ($typeSalida) {
                    Transaction::create([/* ... */]);
                }
            }

            return $pecosa->fresh();
        });
    }

    public function generateComprobante(Pecosa $pecosa): \Barryvdh\DomPDF\PDF
    {
        $pecosa->load([
            'detailPecosas.detailProduct.product.uom',
            'association.placeSector.place',
            'association.partners.beneficiaries',
            'chief.person',
            'storekeeper.person'
        ]);

        $data = $this->buildComprobanteData($pecosa);
        return $this->pdfService->generate('comprobante_salida', $data, 'A4', 'landscape');
    }

    private function buildPecosaSnapshotDTO(array $data): PecosaSnapshotDTO
    {
        $chief = isset($data['chief_id']) ? Responsible::with('person')->find($data['chief_id']) : null;
        $storekeeper = isset($data['storekeeper_id']) ? Responsible::with('person')->find($data['storekeeper_id']) : null;
        $managingPartner = isset($data['managing_partner_id']) ? Partner::with('people')->find($data['managing_partner_id']) : null;
        $association = Association::with(['placeSector.place', 'placeSector.sector'])->find($data['association_id']);
        $president = $association?->getPresidenta();

        return new PecosaSnapshotDTO(
            chief_name: $chief?->person ? self::formatName($chief->person) : null,
            chief_dni: $chief?->person?->dni,
            storekeeper_name: $storekeeper?->person ? self::formatName($storekeeper->person) : null,
            storekeeper_dni: $storekeeper?->person?->dni,
            managing_partner_name: $managingPartner?->people ? self::formatName($managingPartner->people) : null,
            managing_partner_dni: $managingPartner?->people?->dni,
            president_name: $president?->people ? self::formatName($president->people) : null,
            president_dni: $president?->people?->dni,
            association_name: $association?->name,
            association_code: $association?->code,
            association_address: $association?->address,
            association_zone_code: $association?->placeSector?->place?->code,
            association_zone_name: $association?->placeSector?->place?->title,
            association_sector_name: $association?->placeSector?->sector?->title,
            beneficiaries_count: $association ? app(PartnerRepository::class)->countBeneficiariesForAssociationAtDate($association->id, $data['delivery_date']) : 0,
        );
    }

    private static function formatName($person): string
    {
        return trim(collect([$person->names, $person->father_lastname, $person->mother_lastname])->filter()->implode(' '));
    }
}
```

### 4.3 PartnerService

```php
<?php

namespace App\Services;

use App\Models\Beneficiarie;
use App\Models\BeneficiaryHistory;
use App\Models\Partner;
use App\Models\People;
use App\Repositories\PartnerRepository;
use Barryvdh\DomPDF\Facade\PDF;
use Illuminate\Support\Facades\DB;

class PartnerService
{
    public function __construct(
        private PartnerRepository $partnerRepo,
        private PDFService $pdfService,
    ) {}

    public function storeWithBeneficiaries(array $partnerData, ?array $beneficiaries = null): Partner
    {
        if (isset($partnerData['create_person']) && $partnerData['create_person']) {
            $person = People::create($partnerData);
            $partnerData['person_id'] = $person->id;
        }

        $partner = Partner::create($partnerData);

        if ($beneficiaries) {
            $this->syncBeneficiaries($partner, $beneficiaries);
        }

        return $partner;
    }

    public function updateWithBeneficiaries(Partner $partner, array $partnerData, ?array $beneficiaries = null): Partner
    {
        $partner->update($partnerData);

        if ($beneficiaries !== null) {
            $partner->beneficiaries()->delete();
            $this->syncBeneficiaries($partner, $beneficiaries);
        }

        return $partner;
    }

    public function deleteWithRelations(Partner $partner): void
    {
        $partner->beneficiaries()->delete();
        $partner->delete();
    }

    private function syncBeneficiaries(Partner $partner, array $beneficiaries): void
    {
        foreach ($beneficiaries as $b) {
            if (empty($b['person_id']) || empty($b['relationship_id'])) continue;

            $ben = Beneficiarie::create([
                'person_id' => $b['person_id'],
                'partner_id' => $partner->id,
                'relationship_id' => $b['relationship_id'],
            ]);

            if (!empty($b['type_benefit_id']) && !empty($b['history_state_id'])
                && !empty($b['date_begin']) && !empty($b['date_end'])) {
                BeneficiaryHistory::create([
                    'weight' => $b['weight'] ?? 0,
                    'height' => $b['height'] ?? 0,
                    'hmg' => $b['hmg'] ?? 0,
                    'date_begin' => $b['date_begin'],
                    'date_end' => $b['date_end'],
                    'type_benefit_id' => $b['type_benefit_id'],
                    'beneficiary_id' => $ben->id,
                    'state_id' => $b['history_state_id'],
                    'reason_disqualification_id' => $b['reason_disqualification_id'] ?? null,
                ]);
            }
        }
    }
}
```

### 4.4 TransactionService

```php
<?php

namespace App\Services;

use App\Models\DetailProduct;
use App\Models\Transaction;
use App\Models\TypeTransaction;
use App\Repositories\TransactionRepository;
use Illuminate\Support\Facades\DB;

class TransactionService
{
    public function __construct(
        private TransactionRepository $transactionRepo,
        private StockService $stockService,
    ) {}

    public function registerIngreso(array $data): Transaction
    {
        $typeIngreso = TypeTransaction::whereRaw('LOWER(title) = ?', ['ingreso'])->firstOrFail();

        return DB::transaction(function () use ($data, $typeIngreso) {
            $detailProduct = DetailProduct::create([
                'product_id' => $data['product_id'],
                'quantity' => $data['quantity'],
                'unit_price' => $data['unit_price'],
                'start_date' => $data['start_date'] ?? now()->toDateString(),
                'end_date' => $data['end_date'] ?? now()->addYear()->toDateString(),
            ]);

            $detailProduct->load('product.uom');

            return Transaction::create([
                'detail_product_id' => $detailProduct->id,
                'type_transaction_id' => $typeIngreso->id,
                'quantity' => $data['quantity'],
                'unit_price' => $data['unit_price'],
                'total_price' => $data['quantity'] * $data['unit_price'],
                'document_number' => $data['document_number'] ?? null,
                'transaction_date' => $data['transaction_date'] ?? now()->toDateString(),
                'product_name' => $detailProduct->product?->title,
                'uom_title' => $detailProduct->product?->uom?->title,
            ]);
        });
    }

    public function registerSalida(array $data): Transaction
    {
        $typeSalida = TypeTransaction::whereRaw('LOWER(title) = ?', ['salida'])->firstOrFail();
        $detailProduct = DetailProduct::with('product.uom')->findOrFail($data['detail_product_id']);

        return DB::transaction(function () use ($data, $typeSalida, $detailProduct) {
            $this->stockService->deductByProduct($detailProduct->product_id, $data['quantity'], $data['pecosa_id'] ?? null);

            return Transaction::create([
                'detail_product_id' => $data['detail_product_id'],
                'type_transaction_id' => $typeSalida->id,
                'quantity' => $data['quantity'],
                'unit_price' => $data['unit_price'],
                'total_price' => $data['quantity'] * $data['unit_price'],
                'document_number' => $data['document_number'] ?? null,
                'transaction_date' => $data['transaction_date'] ?? now()->toDateString(),
                'product_name' => $detailProduct->product?->title,
                'uom_title' => $detailProduct->product?->uom?->title,
            ]);
        });
    }
}
```

### 4.5 BeneficiaryReportService

```php
<?php

namespace App\Services;

use App\DTOs\BeneficiaryReportItemDTO;
use App\Models\Association;
use App\Models\Pecosa;
use App\Models\Partner;
use App\Models\ReasonDisqualification;
use App\Models\Relationship;
use App\Models\State;
use App\Models\TypeBenefit;
use App\Repositories\PartnerRepository;
use Carbon\Carbon;
use Barryvdh\DomPDF\Facade\PDF;
use Illuminate\Support\Collection;

class BeneficiaryReportService
{
    public function __construct(
        private PartnerRepository $partnerRepo,
        private PDFService $pdfService,
    ) {}

    public function generatePadronReport(int $associationId, int $month, int $year): array
    {
        $association = Association::with(['placeSector.place', 'placeSector.sector'])->findOrFail($associationId);
        $startDate = Carbon::create($year, $month, 1)->startOfMonth();
        $endDate = Carbon::create($year, $month, 1)->endOfMonth();
        $cutoffDate = $endDate;

        $presidenta = $association->getPresidentNameAt($endDate->toDateString())
            ?? $association->getPresidentName();

        $partners = $this->partnerRepo->findActiveByAssociation($associationId, $endDate->toDateString());

        if ($partners->isEmpty()) {
            throw new \DomainException('No hay socios vigentes para el comité y periodo seleccionado.');
        }

        $pecosa = Pecosa::with('detailPecosas.detailProduct.product')
            ->where('association_id', $associationId)
            ->whereBetween('delivery_date', [$startDate->toDateString(), $endDate->toDateString()])
            ->first();

        [$beneficiarios, $resumen] = $this->buildReportData($partners, $cutoffDate, $startDate, $endDate);
        $observaciones = $this->buildObservaciones($beneficiarios, $cutoffDate);

        $meses = ['', 'ENERO', 'FEBRERO', 'MARZO', 'ABRIL', 'MAYO', 'JUNIO', 'JULIO', 'AGOSTO', 'SETIEMBRE', 'OCTUBRE', 'NOVIEMBRE', 'DICIEMBRE'];
        $periodo = $year . '-' . ($month <= 6 ? 'I' : 'II');

        $resumenFilas = [
            ['label' => 'MASCULINO', 'data' => $resumen['masculino']],
            ['label' => 'FEMENINO',  'data' => $resumen['femenino']],
            ['label' => 'TOTAL',     'data' => $resumen['total']],
        ];

        return [
            'beneficiarios' => $beneficiarios,
            'resumen' => $resumen,
            'resumen_filas' => $resumenFilas,
            'observaciones' => $observaciones,
            'club_nombre' => strtoupper($association->name),
            'direccion' => $association->address ?? '',
            'ccpp' => $association->placeSector?->sector?->title ?? '',
            'presidenta' => $presidenta ?? 'SIN ASIGNAR',
            'zona' => $association->placeSector?->place?->code ?? '01',
            'comite' => $association->code ?? $association->id,
            'num_mes' => $month,
            'periodo' => $periodo,
            'semestre' => $month <= 6 ? "{$year}-I" : "{$year}-II",
            'mes_nombre' => $meses[$month] ?? '',
            'anio' => $year,
            'total_beneficiarios' => collect($beneficiarios)->sum('rowspan'),
            'fecha' => date('d/m/Y'),
            'hora' => date('H:i:s'),
            'productos_pecosa' => $pecosa?->detailPecosas ?? collect([]),
            'parentescos' => Relationship::orderBy('id')->get(['id', 'title'])->toArray(),
            'tipo_beneficios' => TypeBenefit::orderBy('id')->get(['id', 'title', 'abbreviation'])->toArray(),
            'bajas' => ReasonDisqualification::orderBy('id')->get(['id', 'title'])->toArray(),
        ];
    }

    private function buildReportData(Collection $partners, Carbon $cutoffDate, Carbon $startDate, Carbon $endDate): array
    {
        $beneficiarios = [];
        $resumen = [
            'total' => array_fill_keys(['0_anos', '1_ano', '2_anos', '3_anos', '4_anos', '5_anos', '6_anos', 'total', 'madres_gestantes', 'madres_lactantes', 'ninos_7_13', 'ancianos', 'tuberculosos', 'discapacitados', 'gap', 'total_general'], 0),
            'masculino' => array_fill_keys(['0_anos', '1_ano', '2_anos', '3_anos', '4_anos', '5_anos', '6_anos', 'total', 'madres_gestantes', 'madres_lactantes', 'ninos_7_13', 'ancianos', 'tuberculosos', 'discapacitados', 'gap', 'total_general'], 0),
            'femenino' => array_fill_keys(['0_anos', '1_ano', '2_anos', '3_anos', '4_anos', '5_anos', '6_anos', 'total', 'madres_gestantes', 'madres_lactantes', 'ninos_7_13', 'ancianos', 'tuberculosos', 'discapacitados', 'gap', 'total_general'], 0),
        ];

        foreach ($partners as $partner) {
            $socia = $partner->people;
            if (!$socia) continue;
            $items = [];

            foreach ($partner->beneficiaries as $beneficiario) {
                $persona = $beneficiario->person;
                if (!$persona) continue;

                $edadAnos = $persona->birthdate ? Carbon::parse($persona->birthdate)->diffInYears($cutoffDate) : 0;
                $edadMeses = $persona->birthdate ? Carbon::parse($persona->birthdate)->diff($cutoffDate)->m : 0;
                $edadDias = $persona->birthdate ? Carbon::parse($persona->birthdate)->diff($cutoffDate)->d : 0;

                $historialActivo = $beneficiario->histories
                    ->whereNotNull('state_id')
                    ->filter(fn($h) => $h->date_begin && Carbon::parse($h->date_begin)->lte($endDate)
                        && (!$h->date_end || Carbon::parse($h->date_end)->gte($startDate)))
                    ->sortByDesc('date_begin')
                    ->first();

                $tipoBeneficio = $historialActivo?->typeBenefit?->abbreviation ?? '';
                $razonBaja = $historialActivo?->reasonDisqualification?->id ?? '';
                $fechaInicio = $historialActivo?->date_begin;

                [$bajaFlag, $observationFlag, $razonBaja] = $this->evaluateBeneficiaryRules($edadAnos, $tipoBeneficio, $fechaInicio, $cutoffDate, $historialActivo, $razonBaja);

                $items[] = new BeneficiaryReportItemDTO(
                    nombre: strtoupper("{$persona->father_lastname} {$persona->mother_lastname} {$persona->names}"),
                    dni: $persona->dni ?? '',
                    tipo: in_array($tipoBeneficio, ['LAC', 'GES']) ? $tipoBeneficio : '',
                    fechaNacimiento: $persona->birthdate ? date('d/m/Y', strtotime($persona->birthdate)) : '',
                    sexo: $persona->gender === 'M' ? 'M' : 'F',
                    parentesco: $beneficiario->relationship?->title ?? '',
                    edadAnos: $edadAnos,
                    edadMeses: $edadMeses,
                    edadDias: $edadDias,
                    esBaja: !empty($razonBaja) && $razonBaja != 1,
                    observation: $observationFlag,
                    fechaInicio: $fechaInicio ? date('d/m/Y', strtotime($fechaInicio)) : '',
                );

                // Resumen counters
                $this->updateResumen($resumen, $persona, $tipoBeneficio, $edadAnos, $razonBaja);
            }

            if (!empty($items)) {
                $beneficiarios[] = [
                    'socia_nombre' => strtoupper("{$socia->father_lastname} {$socia->mother_lastname} {$socia->names}"),
                    'socia_direccion' => $socia->address ?? '',
                    'socia_dni' => $socia->dni ?? '',
                    'rowspan' => count($items),
                    'items' => $items,
                ];
            }
        }

        return [$beneficiarios, $resumen];
    }

    private function evaluateBeneficiaryRules(int $edadAnos, string $tipoBeneficio, ?string $fechaInicio, Carbon $cutoffDate, $historialActivo, $razonBaja): array
    {
        $observationFlag = false;
        $bajaFlag = false;

        if ($edadAnos >= 14 && $historialActivo && !$historialActivo->reason_disqualification_id) {
            $razonBaja = 4; $bajaFlag = true;
        }

        if ($tipoBeneficio === 'GES') {
            $mesesTotales = $fechaInicio ? Carbon::parse($fechaInicio)->diffInMonths($cutoffDate) : 999;
            if (!$fechaInicio || $mesesTotales > 9) {
                if ($historialActivo && (!$historialActivo->reason_disqualification_id || $historialActivo->reason_disqualification_id == 1)) {
                    $razonBaja = 4; $bajaFlag = true;
                }
            }
        }

        if ($tipoBeneficio === 'LAC') {
            $mesesTotales = $fechaInicio ? Carbon::parse($fechaInicio)->diffInMonths($cutoffDate) : 999;
            if (!$fechaInicio || $mesesTotales > 12) {
                if ($historialActivo && (!$historialActivo->reason_disqualification_id || $historialActivo->reason_disqualification_id == 1)) {
                    $razonBaja = 4; $bajaFlag = true;
                }
            }
        }

        if (in_array($tipoBeneficio, ['GES', 'LAC']) && $edadAnos <= 12) {
            $observationFlag = true;
        }

        return [$bajaFlag, $observationFlag, $razonBaja];
    }

    private function updateResumen(array &$resumen, $persona, string $tipoBeneficio, int $edadAnos, $razonBaja): void
    {
        $gender = $persona->gender === 'M' ? 'masculino' : 'femenino';

        if ($edadAnos <= 6) {
            $key = $edadAnos == 1 ? '1_ano' : "{$edadAnos}_anos";
            foreach (['total', $gender] as $g) {
                $resumen[$g][$key]++;
                $resumen[$g]['total']++;
            }
        }

        if ($edadAnos >= 7 && $edadAnos <= 13) {
            foreach (['total', $gender] as $g) $resumen[$g]['ninos_7_13']++;
        }

        $typeMap = ['GES' => 'madres_gestantes', 'LAC' => 'madres_lactantes', 'ADU' => 'ancianos', 'TBC' => 'tuberculosos'];
        if (isset($typeMap[$tipoBeneficio])) {
            foreach (['total', $gender] as $g) $resumen[$g][$typeMap[$tipoBeneficio]]++;
        }

        if (!empty($razonBaja) && $razonBaja != 1) {
            foreach (['total', $gender] as $g) $resumen[$g]['gap']++;
        }

        foreach (['total', $gender] as $g) $resumen[$g]['total_general']++;
    }

    private function buildObservaciones(array $beneficiarios, Carbon $cutoffDate): array
    {
        $todos = collect($beneficiarios)->flatMap(fn($g) => $g['items']);

        return [
            [
                'codigo' => 1,
                'descripcion' => 'EDAD >= 14 años (BAJA)',
                'cantidad' => $todos->filter(fn($b) => !empty($b->beneficiario_baja))->count(),
            ],
            [
                'codigo' => 2,
                'descripcion' => 'GES / LAC <= DE 12 AÑOS',
                'cantidad' => $todos->filter(fn($b) => in_array($b?->tipo ?? '', ['GES', 'LAC']) && $b->edadAnos <= 12)->count(),
            ],
        ];
    }
}
```

### 4.6 SchedulingService

```php
<?php

namespace App\Services;

use App\Models\Association;
use App\Models\Directive;
use App\Models\Pecosa;
use App\Models\Position;
use App\Models\State;
use App\Repositories\AssociationRepository;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;

class SchedulingService
{
    public function __construct(
        private AssociationRepository $associationRepo,
        private PDFService $pdfService,
    ) {}

    public function generateProgramacionEntrega(int $month, int $year, ?string $sector = null): array
    {
        $startDate = Carbon::create($year, $month, 1)->startOfMonth()->toDateString();
        $endDate = Carbon::create($year, $month, 1)->endOfMonth()->toDateString();
        $estadoActivo = State::where('abbreviation', 'ACTI')->first();

        $associations = $this->associationRepo->getAssociationsWithSectorAndBeneficiaries(
            $estadoActivo?->id, $sector
        );

        $associationIds = $associations->pluck('id');

        $presidentPosition = Position::where('title', 'like', '%PRESIDENTA%')->first();
        $directivesByResolution = $this->getPresidentDirectivesByAssociation($associationIds, $presidentPosition?->id, $estadoActivo?->id);

        $pecosasByAssociation = Pecosa::with('detailPecosas:id,pecosa_id,quantity')
            ->whereIn('association_id', $associationIds)
            ->whereBetween('delivery_date', [$startDate, $endDate])
            ->get()
            ->keyBy('association_id');

        return $associations->map(function ($association) use ($directivesByResolution, $pecosasByAssociation, $startDate, $endDate) {
            $presidenta = $this->resolvePresidentName($association, $directivesByResolution);
            [$totalBenef, $primeraPrioridad, $segundaPrioridad] = $this->calculatePriorities($association, $startDate, $endDate);
            $pecosa = $pecosasByAssociation->get($association->id);
            $bolsas = $pecosa?->detailPecosas->sum('quantity') ?? 0;

            $directive = $this->findDirective($association, $directivesByResolution);

            return [
                'codigo' => $association->code ?? $association->id,
                'nombre' => strtoupper($association->name),
                'presidenta' => $presidenta,
                'direccion' => $association->address ?? '',
                'primera_prioridad' => $primeraPrioridad,
                'segunda_prioridad' => $segundaPrioridad,
                'bolsas' => $bolsas,
                'kilos' => 0,
                'racion' => '',
                'fecha_entrega' => $pecosa ? date('d/m/Y', strtotime($pecosa->delivery_date)) : '',
                'recibe' => $presidenta,
                'dni' => $directive?->partner?->people?->dni ?? '',
            ];
        })->values()->toArray();
    }
}
```

### 4.7 PDFService

```php
<?php

namespace App\Services;

use Barryvdh\DomPDF\Facade\PDF;

class PDFService
{
    public function generate(string $view, array $data, string $paper = 'a4', string $orientation = 'portrait'): \Barryvdh\DomPDF\PDF
    {
        $pdf = PDF::loadView($view, $data);
        $pdf->setPaper($paper, $orientation);
        return $pdf;
    }

    public function stream(string $view, array $data, string $filename, string $paper = 'a4', string $orientation = 'portrait')
    {
        return $this->generate($view, $data, $paper, $orientation)->stream($filename);
    }

    public function download(string $view, array $data, string $filename, string $paper = 'a4', string $orientation = 'portrait')
    {
        return $this->generate($view, $data, $paper, $orientation)->download($filename);
    }
}
```

### 4.8 ProductService

```php
<?php

namespace App\Services;

use App\Repositories\ProductRepository;

class ProductService
{
    public function __construct(
        private ProductRepository $productRepo,
    ) {}

    public function generateReport(string $tipo, array $filters = []): array
    {
        $query = Product::with(['state', 'uom', 'detailProducts' => function ($q) {
            $q->withSum('stocks as used_quantity', 'quantity');
        }]);

        return match ($tipo) {
            'general' => ['products' => $query->get(), 'titulo' => 'Inventario General de Productos'],
            'stock-bajo' => [
                'products' => $query->get()->filter(fn($p) => $p->stock <= ($filters['stock_minimo'] ?? 10)),
                'titulo' => 'Productos con Stock Bajo',
            ],
            'valorizacion' => ['products' => $query->get(), 'titulo' => 'Valorización de Inventario'],
            'movimientos' => [
                'products' => $query->with('transactions')->get(),
                'titulo' => 'Productos con Movimientos',
            ],
            'top' => [
                'products' => $query->withCount('transactions')->orderBy('transactions_count', 'desc')->limit(10)->get(),
                'titulo' => 'Top 10 Productos Más Utilizados',
            ],
            default => ['products' => $query->get(), 'titulo' => 'Reporte de Productos'],
        };
    }
}
```

---

## Fase 5: DTOs

```php
<?php

namespace App\DTOs;

class PecosaSnapshotDTO extends BaseDTO
{
    public function __construct(
        public readonly ?string $chief_name,
        public readonly ?string $chief_dni,
        public readonly ?string $storekeeper_name,
        public readonly ?string $storekeeper_dni,
        public readonly ?string $managing_partner_name,
        public readonly ?string $managing_partner_dni,
        public readonly ?string $president_name,
        public readonly ?string $president_dni,
        public readonly ?string $association_name,
        public readonly ?string $association_code,
        public readonly ?string $association_address,
        public readonly ?string $association_zone_code,
        public readonly ?string $association_zone_name,
        public readonly ?string $association_sector_name,
        public readonly int $beneficiaries_count,
    ) {}
}
```

```php
<?php

namespace App\DTOs;

class BeneficiaryReportItemDTO extends BaseDTO
{
    public function __construct(
        public readonly string $nombre,
        public readonly string $dni,
        public readonly string $tipo,
        public readonly string $fechaNacimiento,
        public readonly string $sexo,
        public readonly string $parentesco,
        public readonly int $edadAnos,
        public readonly int $edadMeses,
        public readonly int $edadDias,
        public readonly bool $esBaja,
        public readonly bool $observation,
        public readonly string $fechaInicio,
    ) {}
}
```

```php
<?php

namespace App\DTOs;

class ReparticionDTO extends BaseDTO
{
    public function __construct(
        public readonly int $associationId,
        public readonly string $codigo,
        public readonly string $nombre,
        public readonly string $presidenta,
        public readonly string $direccion,
        public readonly string $sector,
        public readonly int $beneficiarios,
        public readonly int $dias,
        public readonly float $lecheMl,
        public readonly float $hojuelasGramos,
        public readonly float $lecheLitros,
        public readonly int $lecheCajas,
        public readonly int $lecheTarros,
        public readonly float $hojuelasKg,
        public readonly int $hojuelasSacos,
        public readonly int $hojuelasKilos,
    ) {}
}
```

```php
<?php

namespace App\DTOs;

class StockDTO extends BaseDTO
{
    public function __construct(
        public readonly int $quantity,
        public readonly float $unit_price,
        public readonly float $total,
    ) {}
}
```

---

## Fase 6: Policies (autorización)

```php
<?php

namespace App\Policies;

use App\Models\Partner;
use App\Models\User;

class PartnerPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->hasModuleAccess('socios-beneficiarios');
    }

    public function view(User $user, Partner $partner): bool
    {
        return $user->hasModuleAccess('socios-beneficiarios');
    }

    public function create(User $user): bool
    {
        return $user->hasModuleAccess('socios-beneficiarios');
    }

    public function update(User $user, Partner $partner): bool
    {
        return $user->hasModuleAccess('socios-beneficiarios');
    }

    public function delete(User $user, Partner $partner): bool
    {
        return $user->hasModuleAccess('socios-beneficiarios') && $user->isAdmin();
    }
}
```

```php
<?php

namespace App\Policies;

use App\Models\Pecosa;
use App\Models\User;

class PecosaPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->hasModuleAccess('productos');
    }

    public function create(User $user): bool
    {
        return $user->hasModuleAccess('productos');
    }

    public function update(User $user, Pecosa $pecosa): bool
    {
        return $user->hasModuleAccess('productos');
    }

    public function delete(User $user, Pecosa $pecosa): bool
    {
        return $user->hasModuleAccess('productos') && $user->isAdmin();
    }
}
```

Registro en `AuthServiceProvider.php`:

```php
protected $policies = [
    Partner::class => PartnerPolicy::class,
    Pecosa::class => PecosaPolicy::class,
    Product::class => ProductPolicy::class,
    Transaction::class => TransactionPolicy::class,
    User::class => UserPolicy::class,
];
```

---

## Fase 7: Resources (respuestas JSON)

```php
<?php

namespace App\Http\Resources;

use Illuminate\Http\Resources\Json\JsonResource;

class PartnerResource extends JsonResource
{
    public function toArray($request): array
    {
        return [
            'id' => $this->id,
            'person' => new PeopleResource($this->whenLoaded('people')),
            'association' => $this->whenLoaded('association', fn() => [
                'id' => $this->association->id,
                'name' => $this->association->name,
                'code' => $this->association->code,
            ]),
            'state' => $this->whenLoaded('state', fn() => [
                'id' => $this->state->id,
                'title' => $this->state->title,
            ]),
            'date_begin' => $this->date_begin,
            'date_end' => $this->date_end,
            'beneficiaries_count' => $this->beneficiaries_count ?? $this->beneficiaries->count(),
            'created_at' => $this->created_at,
        ];
    }
}
```

```php
<?php

namespace App\Http\Resources;

use Illuminate\Http\Resources\Json\JsonResource;

class PeopleResource extends JsonResource
{
    public function toArray($request): array
    {
        return [
            'id' => $this->id,
            'full_name' => trim("{$this->names} {$this->father_lastname} {$this->mother_lastname}"),
            'names' => $this->names,
            'father_lastname' => $this->father_lastname,
            'mother_lastname' => $this->mother_lastname,
            'dni' => $this->dni,
        ];
    }
}
```

```php
<?php

namespace App\Http\Resources;

use Illuminate\Http\Resources\Json\JsonResource;

class ProductResource extends JsonResource
{
    public function toArray($request): array
    {
        return [
            'id' => $this->id,
            'title' => $this->title,
            'abbreviation' => $this->abbreviation,
            'code' => $this->code,
            'uom' => $this->whenLoaded('uom', fn() => [
                'id' => $this->uom->id,
                'title' => $this->uom->title,
            ]),
            'state' => $this->whenLoaded('state', fn() => [
                'id' => $this->state->id,
                'title' => $this->state->title,
            ]),
            'stock' => $this->stock,
        ];
    }
}
```

---

## Fase 8: Refactorizar Controllers (thin)

### División de controllers existentes

| Controller actual | Pasa a ser | Métodos (~30-50 líneas c/u) |
|---|---|---|
| `SociosBeneficiariosController` (723L) | `PersonaController` (CRUD) + `PartnerController` (refactorizado) + `BeneficiarieController` (refactorizado) | `index()`, `create()`, `store()`, `show()`, `edit()`, `update()`, `destroy()`, `reportes()` |
| `ProductosPecosasController` (1225L) | `ProductController` + `PecosaController` + `ProductReportController` + `KardexController` | CRUD + `generarComprobante()`, `kardex()`, `reportes()` |
| `TransactionController` (515L) | `TransactionController` (reducido) + `ReparticionController` | CRUD + `reparticion()`, `reparticionTabla()` |
| `PartnerController` (156L) | Refactorizado para usar `PartnerService` | `index()`, `store()`, `update()`, `destroy()`, `generarReporte()` |
| `BeneficiarieController` (140L) | Refactorizado para usar `BeneficiaryReportService` | CRUD + `generarReporte()` |
| `AssociationController` | Refactorizado | CRUD |
| `ResolutionController` | Refactorizado | CRUD |

### Ejemplo de controller thin (PecosaController)

```php
<?php

namespace App\Http\Controllers;

use App\Http\Requests\StorePecosaRequest;
use App\Http\Requests\UpdatePecosaRequest;
use App\Models\Pecosa;
use App\Services\PecosaService;
use App\Services\PDFService;

class PecosaController extends Controller
{
    public function __construct(
        private PecosaService $pecosaService,
        private PDFService $pdfService,
    ) {}

    public function index(Request $request)
    {
        $filters = $request->only(['search', 'association_id', 'state_id', 'fecha_inicio', 'fecha_fin']);
        $pecosas = $this->pecosaService->searchWithFilters($filters);
        return view('productos-pecosas.pecosas.index', compact('pecosas'));
    }

    public function store(StorePecosaRequest $request)
    {
        try {
            $pecosa = $this->pecosaService->createPecosa($request->validated());
            return redirect()->route('pecosas.index')
                ->with('success', 'Pecosa creada exitosamente');
        } catch (\DomainException $e) {
            return back()->withInput()->with('error', $e->getMessage());
        }
    }

    public function generarComprobante(Pecosa $pecosa)
    {
        return $this->pecosaService->generateComprobante($pecosa)
            ->stream('comprobante-salida-' . $pecosa->pecosa_number . '.pdf');
    }
}
```

### Ejemplo de controller thin (TransactionController)

```php
<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreTransactionRequest;
use App\Models\Transaction;
use App\Services\TransactionService;
use App\Services\PDFService;

class TransactionController extends Controller
{
    public function __construct(
        private TransactionService $transactionService,
        private PDFService $pdfService,
    ) {}

    public function store(StoreTransactionRequest $request)
    {
        try {
            $type = $request->input('type_transaction_id');
            $isIngreso = TypeTransaction::find($type)?->isIngreso();

            $transaction = $isIngreso
                ? $this->transactionService->registerIngreso($request->validated())
                : $this->transactionService->registerSalida($request->validated());

            $message = $isIngreso ? 'Ingreso registrado correctamente.' : 'Salida registrada correctamente.';
            return redirect()->route('movimientos.index')->with('success', $message);
        } catch (\RuntimeException $e) {
            return back()->withInput()->with('error', $e->getMessage());
        }
    }
}
```

---

## Fase 9: Limpieza de código duplicado

### Métodos a eliminar de controllers (pasan a Services)

| Método duplicado | Aparece en | Nueva ubicación |
|---|---|---|
| `deductStock()` | `ProductosPecosasController`, `TransactionController` | `StockService::deductByProduct()` |
| `deductStockByDetailProduct()` | `ProductosPecosasController` | `StockService::deductByDetailProduct()` |
| `getAvailableStock()` / `getStockForProduct()` | `ProductosPecosasController`, `TransactionController` | `StockService::getAvailableStockByProduct()` |
| `getStockInfo()` | `ProductosPecosasController` | `StockService::getStockInfoByProduct()` |
| `formatPersonName()` | `ProductosPecosasController`, `SociosBeneficiariosController` | `StringHelper` trait o método estático |
| `countBeneficiariesForAssociationAtDate()` | `ProductosPecosasController` | `PartnerRepository::countBeneficiariesForAssociationAtDate()` |
| `buildPecosaSnapshot()` | `ProductosPecosasController` | `PecosaService::buildPecosaSnapshotDTO()` |
| `buildComprobanteData()` | `ProductosPecosasController`, `TransactionController` | `PecosaService::buildComprobanteData()` |

---

## Orden de implementación recomendado

| Fase | Prioridad | Esfuerzo estimado | Dependencias |
|---|---|---|---|
| **1.** Fundación (directorios + clases base) | Alta | 🟢 1 día | Ninguna |
| **2.** Repositorios (extraer queries) | Alta | 🟡 3-4 días | Fase 1 |
| **3.** Form Requests (extraer validación) | Alta | 🟢 2 días | Ninguna |
| **4.** Services - Stock/Pecosas/Transactions | Alta | 🔴 4-5 días | Fases 1, 2 |
| **5.** Services - Partners/Beneficiaries/Reports | Alta | 🔴 3-4 días | Fases 1, 2 |
| **6.** Services - PDF Reports | Media | 🟢 1 día | Fase 1 |
| **7.** Policies (autorización) | Media | 🟢 1 día | Ninguna |
| **8.** DTOs | Baja | 🟢 1 día | Services existentes |
| **9.** Resources | Baja | 🟢 0.5 día | DTOs |
| **10.** Refactorizar Controllers (thin) | Alta | 🔴 2-3 días | Fases 2-7 |
| **11.** Limpiar código muerto + tests | Media | 🟡 2 días | Fase 10 |

---

## Beneficios clave

1. **Controllers de ~30-50 líneas** en vez de 500-1200
2. **Código duplicado eliminado** (stock deduction, formateo de nombres, etc.)
3. **Tests unitarios posibles** (Services y Repositories fácilmente testables)
4. **Validación centralizada** en Form Requests con mensajes consistentes
5. **Autorización declarativa** via Policies en vez de checks inline
6. **Transacciones DB** manejadas en Services, no en controllers
7. **DTOs tipados** eliminan errores de llaves en arrays asociativos
8. **Separación clara de responsabilidades** siguiendo SOLID
