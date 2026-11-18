<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class MonthlyRoster extends Model
{
    public const DRAFT = 'DRAFT';
    public const CLOSED = 'CLOSED';
    public const SUPERSEDED = 'SUPERSEDED';

    protected $fillable = [
        'period',
        'version',
        'status',
        'source_roster_id',
        'created_by',
        'closed_by',
        'closed_at',
        'correction_reason',
    ];

    protected $casts = [
        'period' => 'date:Y-m-d',
        'version' => 'integer',
        'closed_at' => 'datetime',
    ];

    public function source()
    {
        return $this->belongsTo(self::class, 'source_roster_id');
    }

    public function associations()
    {
        return $this->hasMany(MonthlyRosterAssociation::class);
    }

    public function creator()
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function closer()
    {
        return $this->belongsTo(User::class, 'closed_by');
    }

    public function getLabelAttribute(): string
    {
        return ucfirst($this->period->locale('es')->translatedFormat('F Y')) . ' · v' . $this->version;
    }
}
