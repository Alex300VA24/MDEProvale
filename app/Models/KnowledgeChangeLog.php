<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

/** Registro de trazabilidad de cada cambio detectado o extraído del portal. */
class KnowledgeChangeLog extends Model
{
    protected $fillable = [
        'source', 'event_type', 'subject_type', 'subject_id', 'title', 'summary',
        'priority', 'details', 'notification_id', 'triggered_by', 'occurred_at',
    ];

    protected $casts = [
        'details' => 'array',
        'occurred_at' => 'datetime',
    ];
}
