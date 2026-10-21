<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphMany;

class AssociationStatusChange extends Model
{
    protected $fillable = [
        'association_id', 'from_state_id', 'to_state_id', 'reason',
        'notes', 'effective_date', 'user_id',
    ];

    protected $casts = ['effective_date' => 'date:Y-m-d'];

    public function association(): BelongsTo
    {
        return $this->belongsTo(Association::class);
    }

    public function documents(): MorphMany
    {
        return $this->morphMany(DocumentAttachment::class, 'attachable');
    }
}
