<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

/** Evento de trazabilidad: qué usuario cambió qué registro, cuándo y desde dónde. */
class AuditLog extends Model
{
    protected $fillable = [
        'user_id', 'user_name', 'user_username', 'user_role',
        'module', 'action', 'auditable_type', 'auditable_id', 'auditable_label', 'description',
        'ip_address', 'user_agent', 'http_method', 'url', 'batch_uuid', 'occurred_at',
    ];

    protected $casts = [
        'occurred_at' => 'datetime',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function changes(): HasMany
    {
        return $this->hasMany(AuditLogChange::class);
    }
}
