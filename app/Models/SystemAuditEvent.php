<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class SystemAuditEvent extends Model
{
    protected $fillable = [
        'event_key', 'event_type', 'subject_type', 'subject_id', 'source',
        'from_state_id', 'to_state_id', 'reason_code', 'executed_by',
        'occurred_at', 'before_data', 'after_data', 'metadata',
    ];

    protected $casts = [
        'occurred_at' => 'datetime',
        'before_data' => 'array',
        'after_data' => 'array',
        'metadata' => 'array',
    ];

    public function fromState(): BelongsTo
    {
        return $this->belongsTo(State::class, 'from_state_id');
    }

    public function toState(): BelongsTo
    {
        return $this->belongsTo(State::class, 'to_state_id');
    }

    public function executedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'executed_by');
    }
}
