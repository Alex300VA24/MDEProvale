<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Beneficiarie extends Model
{
    use HasFactory;

    protected $fillable = [
        'person_id',
        'partner_id',
        'relationship_id',
    ];

    public function person()
    {
        return $this->belongsTo(People::class);
    }

    public function partner()
    {
        return $this->belongsTo(Partner::class);
    }

    public function relationship()
    {
        return $this->belongsTo(Relationship::class);
    }

    public function histories()
    {
        return $this->hasMany(BeneficiaryHistory::class, 'beneficiary_id');
    }

    public function getNameAttribute()
    {
        return $this->person ? $this->person->names . ' ' . $this->person->father_lastname . ' ' . $this->person->mother_lastname : 'Sin nombre';
    }
}
