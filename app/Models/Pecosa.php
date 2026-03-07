<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Pecosa extends Model
{
    use HasFactory;

    protected $fillable = [
        'pecosa_number',
        'observation',
        'delivery_date',
        'managing_partner_id',
        'state_id',
        'association_id',
    ];

    public function state()
    {
        return $this->belongsTo(State::class);
    }

    public function association()
    {
        return $this->belongsTo(Association::class);
    }

    public function managingPartner()
    {
        return $this->belongsTo(Partner::class, 'managing_partner_id');
    }

    public function detailPecosas()
    {
        return $this->hasMany(DetailPecosa::class);
    }

    /**
     * Accessor: mes derivado de delivery_date
     */
    public function getMonthAttribute()
    {
        return $this->delivery_date ? date('n', strtotime($this->delivery_date)) : null;
    }

    /**
     * Accessor: año derivado de delivery_date
     */
    public function getYearAttribute()
    {
        return $this->delivery_date ? date('Y', strtotime($this->delivery_date)) : null;
    }
}
