<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Cache;

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

    protected $cachedAttributes = [];

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
        return Directive::whereIn('partner_id', $this->partners()->pluck('id'));
    }

    public function hasPresidenta(): bool
    {
        return $this->getPresidentaCached() !== null;
    }

    public function isHabilitado(): bool
    {
        return $this->state && $this->state->abbreviation === 'A';
    }

    public function getPresidentaCached(): ?Partner
    {
        $cacheKey = 'association_presidenta_' . $this->id;
        
        return Cache::remember($cacheKey, 300, function () {
            return $this->getPresidenta();
        });
    }

    public function getPresidenta(): ?Partner
    {
        $resolutionId = $this->resolution_id;
        if (!$resolutionId || !is_numeric($resolutionId)) {
            return null;
        }
        
        $activeState = State::where('abbreviation', 'A')->first();
        $presidentPosition = Position::where('title', 'like', '%PRESIDENTA%')->first();
        
        if (!$activeState || !$presidentPosition) {
            return null;
        }
        
        $directive = Directive::where('resolution_id', (int)$resolutionId)
            ->where('position_id', $presidentPosition->id)
            ->where('state_id', $activeState->id)
            ->first();
            
        if (!$directive) {
            return null;
        }
        
        return Partner::with('people')->find($directive->partner_id);
    }

    public function getPresidentName(): ?string
    {
        $presidenta = $this->getPresidentaCached();
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

    public static function clearPresidentaCache($associationId)
    {
        Cache::forget('association_presidenta_' . $associationId);
    }
}
