<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Racion extends Model
{
    use HasFactory;

    protected $table = 'raciones';

    protected $fillable = [
        'year',
        'month_start',
        'month_end',
        'racion_hojuelas_gramos',
        'racion_leche_militros',
        'active',
    ];

    protected $casts = [
        'year' => 'integer',
        'month_start' => 'integer',
        'month_end' => 'integer',
        'racion_hojuelas_gramos' => 'decimal:2',
        'racion_leche_militros' => 'decimal:2',
        'active' => 'boolean',
    ];

    /**
     * Busca la ración activa que cubre un mes específico dentro de un año.
     */
    public function scopeForMonth(Builder $query, int $year, int $month): Builder
    {
        return $query->where('year', $year)
            ->where('month_start', '<=', $month)
            ->where('month_end', '>=', $month)
            ->where('active', true);
    }

    /**
     * Verifica si el período de esta ración se superpone con otra del mismo año.
     */
    public function hasOverlap(?int $excludeId = null): bool
    {
        $query = static::where('year', $this->year)
            ->where('month_start', '<=', $this->month_end)
            ->where('month_end', '>=', $this->month_start);

        if ($excludeId) {
            $query->where('id', '!=', $excludeId);
        }

        return $query->exists();
    }

    /**
     * Retorna el nombre del mes de inicio.
     */
    public function getMonthStartNameAttribute(): string
    {
        $months = [
            1 => 'Enero', 2 => 'Febrero', 3 => 'Marzo', 4 => 'Abril',
            5 => 'Mayo', 6 => 'Junio', 7 => 'Julio', 8 => 'Agosto',
            9 => 'Septiembre', 10 => 'Octubre', 11 => 'Noviembre', 12 => 'Diciembre',
        ];

        return $months[$this->month_start] ?? '';
    }

    /**
     * Retorna el nombre del mes de fin.
     */
    public function getMonthEndNameAttribute(): string
    {
        $months = [
            1 => 'Enero', 2 => 'Febrero', 3 => 'Marzo', 4 => 'Abril',
            5 => 'Mayo', 6 => 'Junio', 7 => 'Julio', 8 => 'Agosto',
            9 => 'Septiembre', 10 => 'Octubre', 11 => 'Noviembre', 12 => 'Diciembre',
        ];

        return $months[$this->month_end] ?? '';
    }
}
