<?php

namespace App\Repositories;

use App\Models\BeneficiaryHistory;
use App\Models\DetailPecosa;
use App\Models\DistributionPeriod;
use App\Models\Product;
use App\Models\PvlDocument;
use App\Models\PvlReportRun;
use App\Models\Racion;
use App\Models\Transaction;
use App\Repositories\Contracts\PvlReportRepositoryInterface;
use App\Models\Pecosa;
use Carbon\Carbon;
use Illuminate\Support\Collection;

class PvlReportRepository implements PvlReportRepositoryInterface
{
    public function recentRuns(int $limit): Collection
    {
        return PvlReportRun::query()->latest('id')->limit($limit)->get();
    }

    public function createRun(array $data): PvlReportRun
    {
        return PvlReportRun::create($data);
    }

    public function updateRun(PvlReportRun $run, array $attributes): PvlReportRun
    {
        $run->forceFill($attributes)->save();

        return $run->fresh();
    }

    public function deleteRun(PvlReportRun $run): void
    {
        $run->delete();
    }

    public function freshRun(PvlReportRun $run): PvlReportRun
    {
        return $run->fresh();
    }

    public function cachedRun(string $reportType, int $year, int $month, ?int $userId, string $fingerprint): ?PvlReportRun
    {
        return PvlReportRun::query()
            ->where('report_type', $reportType)
            ->where('year', $year)
            ->where('month', $month)
            ->where('created_by', $userId)
            ->where('source_fingerprint', $fingerprint)
            ->whereNotIn('status', [PvlReportRun::ERROR, PvlReportRun::ANALIZANDO])
            ->latest('id')
            ->first();
    }

    public function previousRun(int $year, int $month): ?PvlReportRun
    {
        $previous = Carbon::create($year, $month, 1)->subMonthNoOverflow();

        return PvlReportRun::query()
            ->whereIn('report_type', ['PVL', 'AMBOS'])
            ->where('year', $previous->year)
            ->where('month', $previous->month)
            ->whereIn('status', [PvlReportRun::LISTO_PARA_GENERAR, PvlReportRun::GENERADO])
            ->whereNotNull('validated_data_json')
            ->latest('id')
            ->first();
    }

    public function recentDocuments(int $limit): Collection
    {
        return PvlDocument::query()->with('product:id,title')->latest('id')->limit($limit)->get();
    }

    public function findDuplicateDocument(string $period, string $documentType, string $hash): ?PvlDocument
    {
        return PvlDocument::query()
            ->where('period', $period)
            ->where('document_type', $documentType)
            ->where('file_hash', $hash)
            ->first();
    }

    public function createDocument(array $data): PvlDocument
    {
        return PvlDocument::create($data);
    }

    public function deleteDocument(PvlDocument $document): void
    {
        $document->delete();
    }

    public function freshDocument(PvlDocument $document): PvlDocument
    {
        return $document->fresh('product');
    }

    public function documentsForPeriod(string $period): Collection
    {
        return PvlDocument::query()
            ->where('period', $period)
            ->orderBy('id')
            ->get(['id', 'document_type', 'file_name', 'index_status']);
    }

    public function productOptions(): Collection
    {
        return Product::query()->orderBy('title')->get(['id', 'title']);
    }

    public function productTitleLike(string $pattern): ?string
    {
        return Product::where('title', 'like', $pattern)->value('title');
    }

    public function incomeTransactions(Carbon $start, Carbon $end): Collection
    {
        return Transaction::query()
            ->with(['detailProduct.product.uom', 'typeTransaction'])
            ->whereBetween('transaction_date', [$start->toDateString(), $end->toDateString()])
            ->whereHas('typeTransaction', fn ($query) => $query->whereRaw('LOWER(TRIM(title)) = ?', ['ingreso']))
            ->orderBy('transaction_date')
            ->orderBy('id')
            ->get();
    }

    public function racionForMonth(int $year, int $month): ?Racion
    {
        return Racion::forMonth($year, $month)->first();
    }

    public function distributionPeriod(int $year, int $month): ?DistributionPeriod
    {
        return DistributionPeriod::query()
            ->where('year', $year)
            ->where('month', $month)
            ->first();
    }

    public function pecosaDetailsBetween(Carbon $start, Carbon $end): Collection
    {
        return DetailPecosa::query()
            ->with(['pecosa', 'detailProduct.product.uom'])
            ->whereHas('pecosa', fn ($query) => $query->whereBetween('delivery_date', [
                $start->toDateString(),
                $end->toDateString(),
            ]))
            ->get();
    }

    public function beneficiaryHistoriesBetween(Carbon $start, Carbon $end): Collection
    {
        return BeneficiaryHistory::query()
            ->with([
                'typeBenefit',
                'beneficiary.person.placeSector.place',
                'beneficiary.partner.association.placeSector.place',
            ])
            ->whereDate('date_begin', '<=', $end->toDateString())
            ->where(fn ($dates) => $dates->whereNull('date_end')->orWhereDate('date_end', '>=', $start->toDateString()))
            ->orderByDesc('date_begin')
            ->get()
            ->unique('beneficiary_id');
    }
}
