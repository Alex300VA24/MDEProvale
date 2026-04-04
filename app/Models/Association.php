<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;

class Association extends Model
{
    use HasFactory;

    protected $fillable = [
        'code',
        'name',
        'address',
        'company_name',
        'observation',
        'state_id',
        'place_sector_id',
        'type_premises_id',
        'resolution_id',
    ];

    public function placeSector()
    {
        return $this->belongsTo(PlaceSector::class);
    }

    public function typePremises()
    {
        return $this->belongsTo(TypePremises::class);
    }

    public function state()
    {
        return $this->belongsTo(State::class);
    }

    public function resolution()
    {
        return $this->belongsTo(Resolution::class);
    }

    // Backward compatibility for existing code that still uses the plural name.
    public function resolutions()
    {
        return $this->resolution();
    }

    public function partners()
    {
        return $this->hasMany(Partner::class);
    }

    public function pecosas()
    {
        return $this->hasMany(Pecosa::class);
    }

    public function directives()
    {
        // Directivas del comité a través de los socios que pertenecen a él
        return Directive::whereIn('partner_id', $this->partners()->pluck('id'));
    }

    public function hasPresidenta(): bool
    {
        $partnerIds = $this->partners()->pluck('id');
        $resolutionIds = \DB::table('resolution_associations')
            ->where('association_id', $this->id)
            ->pluck('resolution_id');
        
        return Directive::whereIn('partner_id', $partnerIds)
            ->whereIn('resolution_id', $resolutionIds)
            ->whereHas('position', fn($q) => $q->where('title', 'like', '%PRESIDENTA%'))
            ->whereHas('state', fn($q) => $q->where('abbreviation', 'A'))
            ->exists();
    }

    public function isHabilitado(): bool
    {
        return $this->state && $this->state->abbreviation === 'A';
    }

    public function getPresidenta(): ?Partner
    {
        $partnerIds = $this->partners()
            ->where('state_id', 1)
            ->pluck('id');
        
        $resolutionIds = \DB::table('resolution_associations')
            ->where('association_id', $this->id)
            ->orderBy('resolution_id', 'desc')
            ->pluck('resolution_id');

        if ($resolutionIds->isEmpty()) {
            return null;
        }

        $directive = Directive::whereIn('partner_id', $partnerIds)
            ->whereIn('resolution_id', $resolutionIds)
            ->whereHas('position', fn($q) => $q->where('title', 'like', '%PRESIDENTA%'))
            ->whereHas('state', fn($q) => $q->where('abbreviation', 'A'))
            ->with('partner')
            ->first();

        if (!$directive) {
            $directive = Directive::whereIn('partner_id', $partnerIds)
                ->whereHas('position', fn($q) => $q->where('title', 'like', '%PRESIDENTA%'))
                ->whereHas('state', fn($q) => $q->where('abbreviation', 'A'))
                ->orderBy('resolution_id', 'desc')
                ->with('partner')
                ->first();
        }

        return $directive ? $directive->partner : null;
    }

    public function getPresidentName(): ?string
    {
        $presidenta = $this->getPresidenta();
        if ($presidenta && $presidenta->people) {
            return $presidenta->people->names . ' ' . $presidenta->people->father_lastname;
        }
        return null;
    }

    public function resolutionsHistory()
    {
        return $this->belongsToMany(Resolution::class, 'resolution_associations');
    }

}
