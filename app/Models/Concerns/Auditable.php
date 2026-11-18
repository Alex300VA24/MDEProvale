<?php

namespace App\Models\Concerns;

use App\Models\AuditLog;
use App\Services\AuditTrailService;
use Illuminate\Database\Eloquent\Relations\MorphMany;

/**
 * Registra en el Historial de Cambios cada alta, edición y baja del modelo.
 *
 * Propiedades opcionales del modelo:
 *   protected string $auditModule = 'socios';          módulo mostrado en el historial
 *   protected string $auditName = 'Socio';             nombre legible del registro
 *   protected array  $auditExclude = ['password'];     campos que nunca se registran
 *   protected array  $auditLabels = ['dni' => 'DNI'];  etiquetas legibles por campo
 *   public function auditLabel(): string               descripción del registro concreto
 */
trait Auditable
{
    protected static function bootAuditable(): void
    {
        static::created(fn ($model) => app(AuditTrailService::class)->recordModel($model, 'created'));
        static::updated(fn ($model) => app(AuditTrailService::class)->recordModel($model, 'updated'));
        static::deleted(fn ($model) => app(AuditTrailService::class)->recordModel($model, 'deleted'));
    }

    public function auditLogs(): MorphMany
    {
        return $this->morphMany(AuditLog::class, 'auditable');
    }

    public function auditModule(): string
    {
        return $this->auditModule ?? $this->getTable();
    }

    public function auditName(): string
    {
        return $this->auditName ?? class_basename($this);
    }

    /** @return string[] */
    public function auditExcluded(): array
    {
        return array_merge(
            ['created_at', 'updated_at', 'deleted_at', 'password', 'remember_token', 'reniec_photo'],
            $this->auditExclude ?? []
        );
    }

    public function auditFieldLabel(string $field): string
    {
        return ($this->auditLabels ?? [])[$field] ?? ucfirst(str_replace('_', ' ', $field));
    }

    public function auditLabel(): string
    {
        return $this->auditName().' #'.$this->getKey();
    }
}
