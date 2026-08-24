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
        'president_id',
        'chief_id',
        'storekeeper_id',
        'managing_partner_id',
        'state_id',
        'association_id',
        'chief_name',
        'storekeeper_name',
        'managing_partner_name',
        'president_name',
        'association_name',
        'association_code',
        'chief_dni',
        'storekeeper_dni',
        'managing_partner_dni',
        'president_dni',
        'association_address',
        'association_zone_code',
        'association_zone_name',
        'association_sector_name',
        'beneficiaries_count',
    ];

    protected $casts = [
        'delivery_date' => 'date:Y-m-d',
        'beneficiaries_count' => 'integer',
    ];

    public function isVigente(): bool
    {
        return $this->delivery_date !== null
            && \Carbon\Carbon::parse($this->delivery_date)->isSameMonth(now());
    }

    public function getVigenciaAttribute(): string
    {
        return $this->isVigente() ? 'Vigente' : 'Vencido';
    }

    public function scopeVigentes($query)
    {
        return $query->whereBetween('delivery_date', [now()->startOfMonth(), now()->endOfMonth()]);
    }

    public function state()
    {
        return $this->belongsTo(State::class);
    }

    public function association()
    {
        return $this->belongsTo(Association::class);
    }

    public function president()
    {
        return $this->belongsTo(Partner::class, 'president_id');
    }

    public function chief()
    {
        return $this->belongsTo(Responsible::class, 'chief_id');
    }

    public function storekeeper()
    {
        return $this->belongsTo(Responsible::class, 'storekeeper_id');
    }

    public function managingPartner()
    {
        return $this->belongsTo(Partner::class, 'managing_partner_id');
    }

    public function detailPecosas()
    {
        return $this->hasMany(DetailPecosa::class);
    }

    public function getMonthAttribute()
    {
        return $this->delivery_date ? date('n', strtotime($this->delivery_date)) : null;
    }

    public function getYearAttribute()
    {
        return $this->delivery_date ? date('Y', strtotime($this->delivery_date)) : null;
    }
}
