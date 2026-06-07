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

    /**
     * Static cache for frequently-looked-up singleton records.
     * These values don't change during a request, so we load them once.
     */
    protected static $cachedActiveState = null;
    protected static $cachedPresidentPosition = null;
    protected static $staticCacheLoaded = false;

    /**
     * Load State(A) and Position(PRESIDENTA) once per request.
     */
    protected static function loadStaticLookups(): void
    {
        if (static::$staticCacheLoaded) return;

        static::$cachedActiveState = State::where('abbreviation', 'A')->first();
        static::$cachedPresidentPosition = Position::where('title', 'PRESIDENTA')->first();
        static::$staticCacheLoaded = true;
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
        
        static::loadStaticLookups();

        $activeState = static::$cachedActiveState;
        $presidentPosition = static::$cachedPresidentPosition;
        
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

    /**
     * Obtiene el nombre de la presidenta vigente en una fecha específica.
     * Usa directives.date_start / date_end para consulta histórica.
     */
    public function getPresidentNameAt(string $date): ?string
    {
        $presidentPosition = Position::where('title', 'PRESIDENTA')->first();
        if (!$presidentPosition) return null;

        $partnerIds = $this->partners()->pluck('id');

        $directive = Directive::whereIn('partner_id', $partnerIds)
            ->where('position_id', $presidentPosition->id)
            ->where(function ($q) use ($date) {
                $q->whereNull('date_start')->orWhere('date_start', '<=', $date);
            })
            ->where(function ($q) use ($date) {
                $q->whereNull('date_end')->orWhere('date_end', '>=', $date);
            })
            ->latest('date_start')
            ->first();

        if (!$directive) return null;

        $partner = Partner::with('people')->find($directive->partner_id);
        if ($partner && $partner->people) {
            return $partner->people->names . ' ' . $partner->people->father_lastname;
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
