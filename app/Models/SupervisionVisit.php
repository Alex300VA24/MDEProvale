<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class SupervisionVisit extends Model
{
    public const SI = 'SI';
    public const NO = 'NO';

    protected $fillable = [
        'association_id',
        'visit_date',
        'result',
        'user_id',
    ];

    protected $casts = [
        'visit_date' => 'date:Y-m-d',
    ];

    public function association(): BelongsTo
    {
        return $this->belongsTo(Association::class);
    }
}
