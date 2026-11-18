<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class AssociationCodeHistory extends Model
{
    protected $fillable = [
        'association_id',
        'code',
        'valid_from',
        'valid_to',
        'source',
        'observation',
    ];

    protected $casts = [
        'valid_from' => 'date:Y-m-d',
        'valid_to' => 'date:Y-m-d',
    ];

    public function association()
    {
        return $this->belongsTo(Association::class);
    }
}
