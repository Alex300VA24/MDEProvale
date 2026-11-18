<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class MonthlyRosterEntry extends Model
{
    protected $fillable = [
        'monthly_roster_association_id',
        'partner_id',
        'beneficiary_id',
        'beneficiary_history_id',
        'partner_snapshot',
        'beneficiary_snapshot',
    ];

    protected $casts = [
        'partner_snapshot' => 'array',
        'beneficiary_snapshot' => 'array',
    ];

    public function rosterAssociation()
    {
        return $this->belongsTo(MonthlyRosterAssociation::class, 'monthly_roster_association_id');
    }
}
