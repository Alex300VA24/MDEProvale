<?php

namespace App\Services;

use App\Models\AuditLog;
use App\Repositories\Contracts\AuditTrailRepositoryInterface;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

/**
 * Escribe y consulta el Historial de Cambios (audit_logs / audit_log_changes).
 */
class AuditTrailService
{
    public const ACTIONS = [
        'created' => 'Creación',
        'updated' => 'Modificación',
        'deleted' => 'Eliminación',
        'restored' => 'Restauración',
        'status_changed' => 'Cambio de estado',
        'login' => 'Inicio de sesión',
        'logout' => 'Cierre de sesión',
        'export' => 'Exportación',
        'other' => 'Otra acción',
    ];

    private static ?string $batch = null;

    public function __construct(private AuditTrailRepositoryInterface $logs)
    {
    }

    /** Agrupa todos los registros de una misma petición bajo un identificador. */
    private function batch(): string
    {
        return self::$batch ??= (string) Str::uuid();
    }

    /** Registra un evento de un modelo Auditable. Un fallo aquí nunca debe romper la operación original. */
    public function recordModel(Model $model, string $action): void
    {
        try {
            $changes = $this->diff($model, $action);

            // Edición sin cambios relevantes (solo timestamps u otros campos excluidos).
            if ($action === 'updated' && $changes === []) {
                return;
            }

            $this->write([
                'module' => $model->auditModule(),
                'action' => $action,
                'auditable_type' => $model->getMorphClass(),
                'auditable_id' => $model->getKey(),
                'auditable_label' => mb_substr($model->auditLabel(), 0, 255),
                'description' => $this->describe($model, $action, $changes),
            ], $changes);
        } catch (\Throwable $e) {
            Log::warning('No se pudo registrar el historial de cambios: '.$e->getMessage());
        }
    }

    /** Registro manual para acciones que no son un cambio de modelo (login, exportaciones, etc.). */
    public function record(string $module, string $action, string $description, ?Model $subject = null, array $changes = []): void
    {
        try {
            $this->write([
                'module' => $module,
                'action' => $action,
                'auditable_type' => $subject?->getMorphClass(),
                'auditable_id' => $subject?->getKey(),
                'auditable_label' => $subject && method_exists($subject, 'auditLabel') ? mb_substr($subject->auditLabel(), 0, 255) : null,
                'description' => $description,
            ], $changes);
        } catch (\Throwable $e) {
            Log::warning('No se pudo registrar el historial de cambios: '.$e->getMessage());
        }
    }

    /** @param array<int, array{field:string, field_label:?string, old_value:?string, new_value:?string}> $changes */
    private function write(array $data, array $changes): void
    {
        $user = auth()->user();
        $request = app()->runningInConsole() ? null : request();

        $this->logs->store($data + [
            'user_id' => $user?->id,
            'user_name' => $user ? trim("{$user->names} {$user->father_surname} {$user->mother_surname}") : 'Sistema',
            'user_username' => $user?->username,
            'user_role' => $user?->rol?->title,
            'ip_address' => $request?->ip(),
            'user_agent' => $request ? mb_substr((string) $request->userAgent(), 0, 500) : null,
            'http_method' => $request?->method(),
            'url' => $request ? mb_substr($request->path(), 0, 500) : null,
            'batch_uuid' => $this->batch(),
            'occurred_at' => now(),
        ], $changes);
    }

    /** @return array<int, array{field:string, field_label:string, old_value:?string, new_value:?string}> */
    private function diff(Model $model, string $action): array
    {
        $excluded = $model->auditExcluded();
        $rows = [];

        $fields = $action === 'updated' ? $model->getChanges() : $model->getAttributes();

        foreach ($fields as $field => $value) {
            if (in_array($field, $excluded, true) || $field === $model->getKeyName()) {
                continue;
            }

            $old = $action === 'updated' ? $model->getOriginal($field) : ($action === 'deleted' ? $value : null);
            $new = $action === 'deleted' ? null : $value;

            if ($action === 'updated' && $this->stringify($old) === $this->stringify($new)) {
                continue;
            }

            $rows[] = [
                'field' => $field,
                'field_label' => $model->auditFieldLabel($field),
                'old_value' => $this->stringify($old),
                'new_value' => $this->stringify($new),
            ];
        }

        return $rows;
    }

    private function stringify(mixed $value): ?string
    {
        return match (true) {
            $value === null => null,
            is_bool($value) => $value ? 'Sí' : 'No',
            $value instanceof \DateTimeInterface => $value->format('Y-m-d H:i:s'),
            is_array($value), is_object($value) => json_encode($value, JSON_UNESCAPED_UNICODE),
            default => (string) $value,
        };
    }

    private function describe(Model $model, string $action, array $changes): string
    {
        $name = $model->auditName();

        return match ($action) {
            'created' => "Se registró {$name}",
            'deleted' => "Se eliminó {$name}",
            'updated' => "Se modificó {$name}: ".implode(', ', array_column($changes, 'field_label')),
            default => "{$name}: {$action}",
        };
    }

    // ==================== CONSULTA ====================

    public function list(array $filters, int $perPage = 20): LengthAwarePaginator
    {
        return $this->logs->paginate($filters, $perPage)
            ->through(fn (AuditLog $log) => $this->present($log));
    }

    public function detail(AuditLog $log): array
    {
        return $this->present($log) + [
            'ip_address' => $log->ip_address,
            'user_agent' => $log->user_agent,
            'http_method' => $log->http_method,
            'url' => $log->url,
            'batch_uuid' => $log->batch_uuid,
            'changes' => $this->logs->changesOf($log),
        ];
    }

    /** @return array<string, mixed> */
    private function present(AuditLog $log): array
    {
        return [
            'id' => $log->id,
            'occurred_at' => $log->occurred_at?->format('d/m/Y H:i:s'),
            'user_id' => $log->user_id,
            'user_name' => $log->user_name,
            'user_role' => $log->user_role,
            'module' => $log->module,
            'action' => $log->action,
            'action_label' => self::ACTIONS[$log->action] ?? $log->action,
            'auditable_label' => $log->auditable_label,
            'description' => $log->description,
            'changes_count' => $log->changes_count ?? null,
        ];
    }

    /** Valores disponibles para los filtros de la pantalla. */
    public function filterOptions(): array
    {
        return $this->logs->filterValues() + [
            'actions' => collect(self::ACTIONS)->map(fn ($label, $value) => ['value' => $value, 'label' => $label])->values(),
        ];
    }
}
