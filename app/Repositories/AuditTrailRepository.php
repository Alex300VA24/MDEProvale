<?php

namespace App\Repositories;

use App\Models\AuditLog;
use App\Repositories\Contracts\AuditTrailRepositoryInterface;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

class AuditTrailRepository implements AuditTrailRepositoryInterface
{
    public function store(array $log, array $changes): AuditLog
    {
        return DB::transaction(function () use ($log, $changes) {
            $entry = AuditLog::create($log);

            if ($changes !== []) {
                $now = now();
                $entry->changes()->insert(array_map(
                    fn (array $change) => $change + ['audit_log_id' => $entry->id, 'created_at' => $now, 'updated_at' => $now],
                    $changes
                ));
            }

            return $entry;
        });
    }

    public function paginate(array $filters, int $perPage): LengthAwarePaginator
    {
        $search = isset($filters['search']) ? trim((string) $filters['search']) : '';

        return AuditLog::query()
            ->withCount('changes')
            ->when($filters['module'] ?? null, fn ($q, $v) => $q->where('module', $v))
            ->when($filters['action'] ?? null, fn ($q, $v) => $q->where('action', $v))
            ->when($filters['user_id'] ?? null, fn ($q, $v) => $q->where('user_id', $v))
            ->when($filters['from'] ?? null, fn ($q, $v) => $q->where('occurred_at', '>=', $v.' 00:00:00'))
            ->when($filters['to'] ?? null, fn ($q, $v) => $q->where('occurred_at', '<=', $v.' 23:59:59'))
            ->when($search !== '', function ($q) use ($search) {
                $like = '%'.addcslashes($search, '\\%_').'%';
                $q->where(fn ($w) => $w->where('user_name', 'like', $like)
                    ->orWhere('auditable_label', 'like', $like)
                    ->orWhere('description', 'like', $like));
            })
            ->orderByDesc('occurred_at')->orderByDesc('id')
            ->paginate($perPage);
    }

    public function changesOf(AuditLog $log): Collection
    {
        return $log->changes()->orderBy('id')->get(['field', 'field_label', 'old_value', 'new_value']);
    }

    public function filterValues(): array
    {
        return [
            'modules' => AuditLog::query()->distinct()->orderBy('module')->pluck('module'),
            'users' => AuditLog::query()->whereNotNull('user_id')->select('user_id', 'user_name')->distinct()->orderBy('user_name')->get(),
        ];
    }
}
