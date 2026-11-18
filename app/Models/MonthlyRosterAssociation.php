<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class MonthlyRosterAssociation extends Model
{
    protected $fillable = [
        'monthly_roster_id',
        'association_id',
        'partner_count',
        'beneficiary_count',
        'president_name',
        'president_dni',
        'association_snapshot',
    ];

    protected $casts = [
        'partner_count' => 'integer',
        'beneficiary_count' => 'integer',
        'association_snapshot' => 'array',
    ];

    public function roster()
    {
        return $this->belongsTo(MonthlyRoster::class, 'monthly_roster_id');
    }

    public function association()
    {
        return $this->belongsTo(Association::class);
    }

    public function entries()
    {
        return $this->hasMany(MonthlyRosterEntry::class);
    }
}
