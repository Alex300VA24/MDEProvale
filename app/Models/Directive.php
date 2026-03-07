<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Directive extends Model
{
    use HasFactory;

    protected $fillable = [
        'resolution_id',
        'partner_id',
        'position_id',
        'state_id',
    ];

    public function resolution()
    {
        return $this->belongsTo(Resolution::class);
    }

    public function partner()
    {
        return $this->belongsTo(Partner::class);
    }

    public function position()
    {
        return $this->belongsTo(Position::class);
    }

    public function state()
    {
        return $this->belongsTo(State::class);
    }
}
