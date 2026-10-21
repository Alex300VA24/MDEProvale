<?php

namespace App\Repositories;

use App\Models\Directive;
use App\Models\DetailPecosa;
use App\Models\Pecosa;
use App\Models\Position;
use App\Models\State;
use App\Repositories\Contracts\PecosaRepositoryInterface;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Pagination\LengthAwarePaginator;

class PecosaRepository extends BaseRepository implements PecosaRepositoryInterface
{
    public function model(): string
    {
        return Pecosa::class;
    }

    public function searchWithFilters(array $filters, int $perPage = 10): LengthAwarePaginator
    {
        return $this->model
            ->select([
                'id', 'pecosa_number', 'delivery_date', 'observation',
                'association_id', 'state_id', 'managing_partner_id', 'chief_id', 'storekeeper_id',
                'president_name', 'president_dni', 'chief_name', 'chief_dni',
                'storekeeper_name', 'storekeeper_dni', 'managing_partner_name', 'managing_partner_dni',
                'association_name', 'association_code', 'association_address',
                'association_zone_code', 'association_zone_name', 'association_sector_name',
                'beneficiaries_count', 'created_at',
            ])
            ->with([
                'association:id,name,code,address,state_id',
                'state:id,title,abbreviation',
                'managingPartner.people:id,names,father_lastname,mother_lastname,dni',
                'chief.person:id,names,father_lastname,mother_lastname,dni',
                'storekeeper.person:id,names,father_lastname,mother_lastname,dni',
                'detailPecosas:id,pecosa_id,detail_product_id,quantity,unit_price,subtotal,product_name,product_abbreviation,uom_title',
                'detailPecosas.detailProduct:id,product_id,unit_price,start_date,end_date',
                'detailPecosas.detailProduct.product:id,title,abbreviation',
            ])
            ->when($filters['search'] ?? null, fn($q, $v) => $q->where('pecosa_number', 'like', "{$v}%"))
            ->when($filters['association_id'] ?? null, fn($q, $v) => $q->where('association_id', $v))
            ->when($filters['state_id'] ?? null, fn($q, $v) => $q->where('state_id', $v))
            ->when($filters['fecha_inicio'] ?? null, fn($q, $v) => $q->whereDate('delivery_date', '>=', $v))
            ->when($filters['fecha_fin'] ?? null, fn($q, $v) => $q->whereDate('delivery_date', '<=', $v))
            ->when(
                isset($filters['year'], $filters['month']),
                fn ($q) => $q->forDeliveryPeriod((int) $filters['year'], (int) $filters['month'])
            )
            ->when(
                isset($filters['year']) && ! isset($filters['month']),
                fn ($q) => $q->whereYear('delivery_date', (int) $filters['year'])
            )
            ->when(
                isset($filters['month']) && ! isset($filters['year']),
                fn ($q) => $q->whereMonth('delivery_date', (int) $filters['month'])
            )
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

    public function deliveriesInRange(\Illuminate\Support\Collection $associationIds, string $from, string $to): \Illuminate\Support\Collection
    {
        return Pecosa::with('detailPecosas:id,pecosa_id,quantity,product_abbreviation')
            ->whereIn('association_id', $associationIds)
            ->whereBetween('delivery_date', [$from, $to])
            ->get()
            ->keyBy('association_id');
    }

    public function getPresidentDirectivesByAssociation(\Illuminate\Support\Collection $associationIds, int $presidentPositionId, int $activeStateId): \Illuminate\Support\Collection
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

    public function numbersWithPrefix(string $prefix, bool $lock = false): \Illuminate\Support\Collection
    {
        $query = $this->model->newQuery()
            ->where('pecosa_number', 'like', $prefix . '%')
            ->select('pecosa_number');

        if ($lock) {
            $query->lockForUpdate();
        }

        return $query->get()->pluck('pecosa_number');
    }

    public function findOrFailPecosa(int $id): Pecosa
    {
        return $this->model->findOrFail($id);
    }

    public function createPecosa(array $data): Pecosa
    {
        return $this->model->create($data);
    }

    public function updatePecosa(Pecosa $pecosa, array $data): bool
    {
        return $pecosa->update($data);
    }

    public function createDetail(array $data): DetailPecosa
    {
        return DetailPecosa::create($data);
    }

    public function deleteDetails(int $pecosaId): void
    {
        DetailPecosa::where('pecosa_id', $pecosaId)->delete();
    }

    public function numberExists(string $number): bool
    {
        return $this->model->where('pecosa_number', $number)->exists();
    }

    public function lotIsInAnyPecosa(int $detailProductId): bool
    {
        return DetailPecosa::where('detail_product_id', $detailProductId)->exists();
    }

    public function loadForReceipt(Pecosa $pecosa): Pecosa
    {
        return $pecosa->load([
            'detailPecosas.detailProduct.product.uom',
            'association.placeSector.place',
        ]);
    }

    public function loadForShow(Pecosa $pecosa): Pecosa
    {
        return $pecosa->load(['detailPecosas.detailProduct.product', 'association', 'managingPartner.people']);
    }

    public function forDeliveryPeriodWithReceiptData(int $year, int $month): Collection
    {
        return $this->model->forDeliveryPeriod($year, $month)
            ->with([
                'detailPecosas.detailProduct.product.uom',
                'association.placeSector.place',
            ])
            ->orderBy('pecosa_number')
            ->get();
    }

    public function findLatestCurrentInRange(int $associationId, ?int $currentStateId, string $start, string $end): ?Pecosa
    {
        return $this->model->newQuery()
            ->where('association_id', $associationId)
            ->when($currentStateId, fn ($q) => $q->where('state_id', $currentStateId))
            ->whereBetween('delivery_date', [$start, $end])
            ->orderByDesc('delivery_date')
            ->first(['id', 'pecosa_number', 'delivery_date']);
    }

    public function presidentDirectivesFor(\Illuminate\Support\Collection $associationIds): \Illuminate\Support\Collection
    {
        $currentId = State::idFor(State::CURRENT);
        $positionId = Position::where('title', 'PRESIDENTA')->value('id');

        return Directive::select(['id', 'partner_id', 'resolution_id', 'position_id', 'state_id'])
            ->when($positionId, fn ($q) => $q->where('position_id', $positionId))
            ->when($currentId, fn ($q) => $q->where('state_id', $currentId))
            ->whereHas('partner', fn ($q) => $q->whereIn('association_id', $associationIds))
            ->with(['partner:id,person_id,association_id', 'partner.people:id,names,father_lastname'])
            ->get()
            ->mapToGroups(fn ($d) => [$d->partner->association_id => $d])
            ->map(fn ($c) => $c->first());
    }
}
