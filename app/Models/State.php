<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class State extends Model
{
    use HasFactory;

    protected $fillable = [
        'title',
        'abbreviation',
    ];

    public function users()
    {
        return $this->hasMany(User::class);
    }

    public function associations()
    {
        return $this->hasMany(Association::class);
    }

    public function awards()
    {
        return $this->hasMany(Award::class);
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
        return $this->hasMany(Beneficiarie::class);
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
