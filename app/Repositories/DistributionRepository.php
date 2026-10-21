<?php

namespace App\Repositories;

use App\Models\Association;
use App\Models\DetailProduct;
use App\Models\DistributionAssignment;
use App\Models\DistributionPeriod;
use App\Models\Racion;
use App\Models\Transaction;
use App\Repositories\Contracts\DistributionRepositoryInterface;
use Illuminate\Database\Eloquent\Collection;

class DistributionRepository extends BaseRepository implements DistributionRepositoryInterface
{
    public function model(): string
    {
        return DistributionPeriod::class;
    }

    public function racionForMonth(int $year, int $month): ?Racion
    {
        return Racion::forMonth($year, $month)->first();
    }

    public function periodWithAssignments(int $year, int $month): ?DistributionPeriod
    {
        return $this->model->with('assignments')
            ->where('year', $year)
            ->where('month', $month)
            ->first();
    }

    public function firstOrCreatePeriod(int $year, int $month, array $defaults): DistributionPeriod
    {
        return $this->model->firstOrCreate(['year' => $year, 'month' => $month], $defaults);
    }

    public function upsertAssignment(int $periodId, int $associationId, array $values): void
    {
        DistributionAssignment::updateOrCreate(
            ['distribution_period_id' => $periodId, 'association_id' => $associationId],
            $values
        );
    }

    public function hasIngresoBetween(string $startDate, string $endDate): bool
    {
        $hasTransaction = Transaction::query()
            ->whereBetween('transaction_date', [$startDate, $endDate])
            ->where('quantity', '>', 0)
            ->whereHas('typeTransaction', fn ($query) => $query
                ->whereRaw('LOWER(TRIM(title)) = ?', ['ingreso']))
            ->exists();

        if ($hasTransaction) {
            return true;
        }

        // Los ingresos migrados anteriores al módulo de movimientos existen
        // como lotes de producto, aunque no siempre tengan transacción asociada.
        return DetailProduct::query()
            ->whereBetween('start_date', [$startDate, $endDate])
            ->where('quantity', '>', 0)
            ->exists();
    }

    public function associationsForReport(string $endDate): Collection
    {
        return Association::with([
            'placeSector.sector',
            'partners' => function ($query) use ($endDate) {
                $query->select(['id', 'association_id', 'date_begin', 'date_end'])
                    ->where(fn ($q) => $q->whereNull('date_begin')->orWhere('date_begin', '<=', $endDate))
                    ->where(fn ($q) => $q->whereNull('date_end')->orWhere('date_end', '>=', $endDate));
            },
            'partners.beneficiaries' => function ($query) use ($endDate) {
                $query->select(['id', 'partner_id'])
                    ->whereHas('histories', function ($history) use ($endDate) {
                        $history->where(fn ($q) => $q->whereNull('date_begin')->orWhere('date_begin', '<=', $endDate))
                            ->where(fn ($q) => $q->whereNull('date_end')->orWhere('date_end', '>=', $endDate));
                    });
            },
        ])->orderBy('code')->get();
    }

    public function partnersWithBeneficiariesAt(Association $association, string $endDate): Collection
    {
        return $association->partners()
            ->with(['beneficiaries.person'])
            ->where(function ($q) use ($endDate) {
                $q->whereNull('date_begin')->orWhere('date_begin', '<=', $endDate);
            })
            ->where(function ($q) use ($endDate) {
                $q->whereNull('date_end')->orWhere('date_end', '>=', $endDate);
            })
            ->get();
    }
}
