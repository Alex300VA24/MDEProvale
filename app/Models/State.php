<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class State extends Model
{
    use HasFactory;

    public const ACTIVE = 'ACT';
    public const INACTIVE = 'INA';
    public const CURRENT = 'VIG';
    public const EXPIRED = 'VEN';
    public const PENDING = 'PEN';

    protected $fillable = [
        'title',
        'abbreviation',
    ];

    public function scopeAdministrative($query)
    {
        return $query->whereIn('abbreviation', [self::ACTIVE, self::INACTIVE]);
    }

    public function scopeTemporal($query)
    {
        return $query->whereIn('abbreviation', [self::CURRENT, self::EXPIRED]);
    }

    public function scopeForAssociations($query)
    {
        return $query->whereIn('abbreviation', [self::CURRENT, self::PENDING, self::EXPIRED]);
    }

    public static function idFor(string $abbreviation): ?int
    {
        return static::where('abbreviation', $abbreviation)->value('id');
    }

    public function users()
    {
        return $this->hasMany(User::class);
    }

    public function associations()
    {
        return $this->hasMany(Association::class);
    }

    public function resolutions()
    {
        return $this->hasMany(Resolution::class);
    }

    public function partners()
    {
        return $this->hasMany(Partner::class);
    }

    public function directives()
    {
        return $this->hasMany(Directive::class);
    }
    
    public function beneficiaries()
    {
        return $this->hasMany(BeneficiaryHistory::class);
    }

    public function pecosas()
    {
        return $this->hasMany(Pecosa::class);
    }

    public function products()
    {
        return $this->hasMany(Product::class);
    }

}
