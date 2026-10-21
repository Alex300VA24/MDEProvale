<?php

namespace App\Repositories;

use App\Models\Association;
use App\Models\Beneficiarie;
use App\Models\Pecosa;
use App\Repositories\Contracts\DashboardRepositoryInterface;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

class DashboardRepository implements DashboardRepositoryInterface
{
    public function hasPartnerRosterSnapshot(string $periodStart): bool
    {
        return DB::table('partner_roster_periods')->whereDate('period', $periodStart)->exists();
    }

    public function partnerCounts(string $start, string $end, bool $useSnapshot): array
    {
        $partners = $useSnapshot
            ? DB::table('partner_roster_periods as roster')
                ->join('partners', 'partners.id', '=', 'roster.partner_id')
                ->whereDate('roster.period', $start)
            : DB::table('partners')
                ->whereDate('date_begin', '<=', $end)
                ->where(fn ($query) => $query->whereNull('date_end')->orWhereDate('date_end', '>=', $start));

        return [
            'relations' => (clone $partners)->count(),
            'people' => (clone $partners)->distinct()->count($useSnapshot ? 'partners.person_id' : 'person_id'),
        ];
    }

    public function beneficiaryCounts(string $start, string $end): array
    {
        $active = Beneficiarie::activeDuring($start, $end);

        return [
            'relations' => (clone $active)->count(),
            'people' => (clone $active)->distinct()->count('person_id'),
        ];
    }

    public function committeeCount(string $periodStart): int
    {
        $snapshot = DB::table('association_roster_periods')->whereDate('period', $periodStart);

        return $snapshot->exists()
            ? $snapshot->distinct()->count('association_id')
            : Association::count();
    }

    public function dualRole(string $start, string $end, bool $useSnapshot): array
    {
        $query = DB::table('partners')
            ->join('people as partner_people', 'partner_people.id', '=', 'partners.person_id')
            ->join('beneficiaries', 'beneficiaries.person_id', '=', 'partner_people.id')
            ->join('beneficiary_histories', 'beneficiary_histories.beneficiary_id', '=', 'beneficiaries.id')
            ->join('type_benefits', 'type_benefits.id', '=', 'beneficiary_histories.type_benefit_id')
            ->whereIn('type_benefits.abbreviation', ['LAC', 'GES', 'DIS'])
            ->whereDate('beneficiary_histories.date_begin', '<=', $end)
            ->where(function ($history) use ($start) {
                $history->whereNull('beneficiary_histories.date_end')
                    ->orWhereDate('beneficiary_histories.date_end', '>=', $start);
            });

        if ($useSnapshot) {
            $query->join('partner_roster_periods', 'partner_roster_periods.partner_id', '=', 'partners.id')
                ->whereDate('partner_roster_periods.period', $start);
        } else {
            $query->whereDate('partners.date_begin', '<=', $end)
                ->where(function ($partners) use ($start) {
                    $partners->whereNull('partners.date_end')
                        ->orWhereDate('partners.date_end', '>=', $start);
                });
        }

        return [
            'byType' => (clone $query)
                ->selectRaw('type_benefits.abbreviation, COUNT(DISTINCT partner_people.id) as total')
                ->groupBy('type_benefits.abbreviation')
                ->pluck('total', 'type_benefits.abbreviation')
                ->map(fn ($total) => (int) $total)
                ->all(),
            'total' => (int) (clone $query)->distinct()->count('partner_people.id'),
        ];
    }

    public function pecosaCountBetween(string $start, string $end): int
    {
        return Pecosa::whereBetween('delivery_date', [$start, $end])->count();
    }

    public function stockTotal(): int
    {
        return (int) DB::table('detail_products')
            ->selectRaw('COALESCE(SUM(detail_products.quantity - COALESCE(used.total_used, 0)), 0) as stock')
            ->leftJoin(
                DB::raw('(SELECT detail_product_id, SUM(quantity) as total_used FROM product_stocks GROUP BY detail_product_id) as used'),
                'detail_products.id', '=', 'used.detail_product_id'
            )
            ->value('stock');
    }

    public function oatAndMilkLots(string $start, string $end): Collection
    {
        return DB::table('detail_products')
            ->join('products', 'detail_products.product_id', '=', 'products.id')
            ->leftJoin('uoms', 'products.uom_id', '=', 'uoms.id')
            ->leftJoinSub(
                DB::table('product_stocks')
                    ->selectRaw('detail_product_id, SUM(quantity) as total_used')
                    ->groupBy('detail_product_id'),
                'used',
                fn ($join) => $join->on('detail_products.id', '=', 'used.detail_product_id')
            )
            ->whereBetween('detail_products.start_date', [$start, $end])
            ->where(function ($query) {
                $query->whereRaw('LOWER(products.title) LIKE ?', ['%hojuela%'])
                    ->orWhereRaw('LOWER(products.title) LIKE ?', ['%leche%']);
            })
            ->get([
                'products.title as product',
                'uoms.title as unit',
                'detail_products.quantity',
                DB::raw('COALESCE(used.total_used, 0) as total_used'),
            ]);
    }

    public function pecosaDeliveryDates(string $from, string $to): Collection
    {
        // Query builder (no Eloquent): Pecosa::getMonthAttribute() pisaría alias de mes.
        return DB::table('pecosas')
            ->whereNotNull('delivery_date')
            ->whereBetween('delivery_date', [$from, $to])
            ->pluck('delivery_date');
    }

    public function distributedByMonth(int $year): Collection
    {
        return DB::table('product_stocks')
            ->join('detail_products', 'product_stocks.detail_product_id', '=', 'detail_products.id')
            ->join('products', 'detail_products.product_id', '=', 'products.id')
            ->whereYear('detail_products.start_date', $year)
            ->groupBy('mes', 'products.title')
            ->get([
                DB::raw('MONTH(detail_products.start_date) as mes'),
                'products.title as product',
                DB::raw('SUM(product_stocks.quantity) as total'),
            ]);
    }

    public function beneficiaryPeopleInPeriod(string $start, string $end): int
    {
        return DB::table('beneficiary_histories')
            ->join('beneficiaries', 'beneficiaries.id', '=', 'beneficiary_histories.beneficiary_id')
            ->whereDate('beneficiary_histories.date_begin', '<=', $end)
            ->where(function ($query) use ($start) {
                $query->whereNull('beneficiary_histories.date_end')
                    ->orWhereDate('beneficiary_histories.date_end', '>=', $start);
            })
            ->distinct()
            ->count('beneficiaries.person_id');
    }

    public function topCommittees(string $start, string $end, int $limit = 10): Collection
    {
        return Association::selectRaw('associations.code, associations.name as club, associations.address, COUNT(DISTINCT beneficiaries.id) as total')
            ->join('partners', 'partners.association_id', '=', 'associations.id')
            ->join('beneficiaries', 'beneficiaries.partner_id', '=', 'partners.id')
            ->leftJoin('beneficiary_histories', 'beneficiary_histories.beneficiary_id', '=', 'beneficiaries.id')
            ->where(function ($query) use ($start, $end) {
                $query->whereNull('beneficiary_histories.id')
                    ->orWhere(function ($history) use ($start, $end) {
                        $history->whereDate('beneficiary_histories.date_begin', '<=', $end)
                            ->where(fn ($dates) => $dates->whereNull('beneficiary_histories.date_end')->orWhereDate('beneficiary_histories.date_end', '>=', $start));
                    });
            })
            ->groupBy('associations.id', 'associations.code', 'associations.name', 'associations.address')
            ->orderByDesc('total')
            ->limit($limit)
            ->get();
    }

    public function latestBeneficiaryHistoryDate(): ?string
    {
        $date = DB::table('beneficiary_histories')->max('date_begin');

        return $date ? (string) $date : null;
    }

    public function latestPecosaDeliveryDate(): ?string
    {
        $date = Pecosa::whereNotNull('delivery_date')->max('delivery_date');

        return $date ? (string) $date : null;
    }
}
