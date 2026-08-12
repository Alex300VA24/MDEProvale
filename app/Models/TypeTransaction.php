<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class TypeTransaction extends Model
{
    use HasFactory;

    protected $fillable = [
        'title',
    ];

    public function transactions()
    {
        return $this->hasMany(Transaction::class);
    }

    public function isIngreso(): bool
    {
        return mb_strtolower(trim((string) $this->title)) === 'ingreso';
    }

    public function isSalida(): bool
    {
        return mb_strtolower(trim((string) $this->title)) === 'salida';
    }
}
