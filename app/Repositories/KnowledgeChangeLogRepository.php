<?php

namespace App\Repositories;

use App\Models\KnowledgeChangeLog;
use App\Repositories\Contracts\KnowledgeChangeLogRepositoryInterface;
use Illuminate\Pagination\LengthAwarePaginator;

class KnowledgeChangeLogRepository implements KnowledgeChangeLogRepositoryInterface
{
    public function create(array $data): KnowledgeChangeLog
    {
        return KnowledgeChangeLog::create($data);
    }

    public function paginate(?string $source, ?string $search, int $perPage): LengthAwarePaginator
    {
        return KnowledgeChangeLog::query()
            ->when($source, fn ($q) => $q->where('source', $source))
            ->when($search, fn ($q) => $q->where(fn ($w) => $w
                ->where('title', 'like', "%{$search}%")
                ->orWhere('summary', 'like', "%{$search}%")))
            ->orderByDesc('occurred_at')
            ->orderByDesc('id')
            ->paginate($perPage);
    }
}
