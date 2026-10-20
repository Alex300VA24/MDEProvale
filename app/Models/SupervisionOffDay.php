<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class SupervisionOffDay extends Model
{
    protected $fillable = ['off_date', 'reason', 'user_id'];

    protected $casts = [
        'off_date' => 'date:Y-m-d',
    ];
}
