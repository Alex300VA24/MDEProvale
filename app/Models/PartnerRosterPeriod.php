<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class PartnerRosterPeriod extends Model
{
    use HasFactory;

    protected $fillable = [
        'partner_id',
        'period',
    ];

    protected $casts = [
        'period' => 'date:Y-m-d',
    ];

    public function partner()
    {
        return $this->belongsTo(Partner::class);
    }
}
