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
        if (!$this->resolution_id) {
            return false;
        }
        
        $partnerIds = $this->partners()->pluck('id');
        
        return Directive::whereIn('partner_id', $partnerIds)
            ->where('resolution_id', $this->resolution_id)
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
        $resolutionId = $this->resolution_id;
        if (!$resolutionId || !is_numeric($resolutionId)) {
            return null;
        }
        
        $activeState = State::where('abbreviation', 'A')->first();
        $presidentPosition = \App\Models\Position::where('title', 'like', '%PRESIDENTA%')->first();
        
        $query = Directive::where('resolution_id', (int)$resolutionId);
        
        if ($presidentPosition) {
            $query = $query->where('position_id', $presidentPosition->id);
        }
        
        if ($activeState) {
            $query = $query->where('state_id', $activeState->id);
        }
        
        $directive = $query->first();
            
        if (!$directive) {
            return null;
        }
        
        return Partner::with('people')->find($directive->partner_id);
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

    public function getAllResolutions(): \Illuminate\Support\Collection
    {
        $resolutions = collect();
        
        if ($this->resolution) {
            $resolutions->push($this->resolution);
        }
        
        foreach ($this->resolutionsHistory as $res) {
            if ($res->id !== $this->resolution_id) {
                $resolutions->push($res);
            }
        }
        
        return $resolutions->sortBy('date_start')->values();
    }

}
