<?php

namespace App\Repositories;

use App\Models\SupervisionOffDay;
use App\Models\SupervisionVisit;
use App\Repositories\Contracts\SupervisionRepositoryInterface;
use Illuminate\Database\Eloquent\Collection;

class SupervisionRepository implements SupervisionRepositoryInterface
{
    public function visitsBetween(string $start, string $end): \Illuminate\Support\Collection
    {
        return SupervisionVisit::with('documents')
            ->whereBetween('visit_date', [$start, $end])
            ->get()
            ->groupBy('association_id');
    }

    public function offDaysBetween(string $start, string $end): Collection
    {
        return SupervisionOffDay::whereBetween('off_date', [$start, $end])->get();
    }

    public function findVisit(int $associationId, string $date): ?SupervisionVisit
    {
        return SupervisionVisit::where('association_id', $associationId)
            ->whereDate('visit_date', $date)
            ->first();
    }

    public function visitHasDocuments(SupervisionVisit $visit): bool
    {
        return $visit->documents()->exists();
    }

    public function deleteVisit(SupervisionVisit $visit): void
    {
        $visit->delete();
    }

    public function upsertVisit(int $associationId, string $date, string $result, ?int $userId): SupervisionVisit
    {
        return SupervisionVisit::updateOrCreate(
            ['association_id' => $associationId, 'visit_date' => $date],
            ['result' => $result, 'user_id' => $userId]
        );
    }

    public function upsertOffDay(string $date, ?string $reason, ?int $userId): SupervisionOffDay
    {
        return SupervisionOffDay::updateOrCreate(
            ['off_date' => $date],
            ['reason' => $reason, 'user_id' => $userId]
        );
    }

    public function deleteOffDay(string $date): void
    {
        SupervisionOffDay::whereDate('off_date', $date)->delete();
    }

    public function lockVisit(int $id): SupervisionVisit
    {
        return SupervisionVisit::query()->lockForUpdate()->findOrFail($id);
    }

    public function countDocuments(SupervisionVisit $visit, string $documentType): int
    {
        return $visit->documents()->where('document_type', $documentType)->count();
    }
}
