<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class AssociationRosterPeriod extends Model
{
    use HasFactory;

    protected $fillable = [
        'association_id',
        'period',
        'partner_count',
        'beneficiary_count',
        'president_name',
        'president_partner_id',
    ];

    protected $casts = [
        'period' => 'date:Y-m-d',
    ];

    public function association()
    {
        return $this->belongsTo(Association::class);
    }

    public function presidentPartner()
    {
        return $this->belongsTo(Partner::class, 'president_partner_id');
    }
}
