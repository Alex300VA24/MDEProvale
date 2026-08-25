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
        'phone',
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
        return $this->state && $this->state->abbreviation === State::CURRENT;
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

        static::$cachedActiveState = State::where('abbreviation', State::CURRENT)->first();
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
        static::loadStaticLookups();

        $presidentPosition = static::$cachedPresidentPosition;
        if (!$presidentPosition) {
            return null;
        }

        $partnerIds = $this->partners()->pluck('id');
        if ($partnerIds->isEmpty()) {
            return null;
        }

        $today = now()->toDateString();

        $directive = Directive::whereIn('partner_id', $partnerIds)
            ->where('position_id', $presidentPosition->id)
            ->whereDate('date_start', '<=', $today)
            ->whereDate('date_end', '>=', $today)
            ->orderByDesc('date_start')
            ->first();

        if (!$directive) {
            $directive = Directive::whereIn('partner_id', $partnerIds)
                ->where('position_id', $presidentPosition->id)
                ->orderByDesc('date_start')
                ->first();
        }

        if (!$directive) {
            return null;
        }

        return Partner::with('people')->find($directive->partner_id);
    }

    public static function hydratePresidents($associations): void
    {
        if (!$associations || $associations->isEmpty()) {
            return;
        }

        static::loadStaticLookups();

        $presidentPosition = static::$cachedPresidentPosition;
        if (!$presidentPosition) {
            return;
        }

        $today = now()->toDateString();

        $partnersByAssociation = Partner::whereIn('association_id', $associations->pluck('id'))
            ->with('people:id,names,father_lastname')
            ->get()
            ->groupBy('association_id');

        $directivesByPartner = Directive::whereIn(
                'partner_id',
                $partnersByAssociation->flatMap(fn ($partners) => $partners->pluck('id'))->unique()->values()
            )
            ->where('position_id', $presidentPosition->id)
            ->orderByDesc('date_start')
            ->get()
            ->groupBy('partner_id');

        foreach ($associations as $association) {
            $association->president_partner_id = null;
            $association->president_name = null;

            $partners = $partnersByAssociation->get($association->id);
            if (!$partners || $partners->isEmpty()) {
                continue;
            }

            $directive = null;

            foreach ($partners as $partner) {
                $directives = $directivesByPartner->get($partner->id) ?? collect();

                $covering = $directives->first(function ($d) use ($today) {
                    return $d->date_start && $d->date_end
                        && $d->date_start <= $today && $d->date_end >= $today;
                });
                if ($covering) {
                    $directive = $covering;
                    break;
                }

                $latest = $directives->first();
                if ($latest && (!$directive || $latest->date_start > $directive->date_start)) {
                    $directive = $latest;
                }
            }

            if (!$directive) {
                continue;
            }

            $partner = $partners->firstWhere('id', $directive->partner_id);
            if ($partner && $partner->people) {
                $association->president_partner_id = $partner->id;
                $association->president_name = trim($partner->people->names . ' ' . $partner->people->father_lastname);
            }
        }
    }

    public function getPresidentName(): ?string
    {
        if (array_key_exists('president_name', $this->attributes)) {
            return $this->attributes['president_name'];
        }

        $presidenta = $this->getPresidentaCached();
        if ($presidenta && $presidenta->people) {
            $name = $presidenta->people->names . ' ' . $presidenta->people->father_lastname;
            $this->attributes['president_name'] = $name;
            return $name;
        }
        $this->attributes['president_name'] = null;
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
