<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/** Detalle campo a campo (valor anterior / nuevo) de un AuditLog. */
class AuditLogChange extends Model
{
    protected $fillable = ['audit_log_id', 'field', 'field_label', 'old_value', 'new_value'];

    public function auditLog(): BelongsTo
    {
        return $this->belongsTo(AuditLog::class);
    }
}
