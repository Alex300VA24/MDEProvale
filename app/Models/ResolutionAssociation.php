<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ResolutionAssociation extends Model
{
    use HasFactory;

    protected $fillable = [
        'resolution_id',
        'association_id',
    ];

    
}
