<?php

namespace App\Models;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Pecosa extends Model
{
    use HasFactory;

    protected $fillable = [
        'pecosa_number',
        'observation',
        'delivery_date',
        'president_id',
        'chief_id',
        'storekeeper_id',
        'managing_partner_id',
        'state_id',
        'association_id',
        'chief_name',
        'storekeeper_name',
        'managing_partner_name',
        'president_name',
        'association_name',
        'association_code',
        'chief_dni',
        'storekeeper_dni',
        'managing_partner_dni',
        'president_dni',
        'association_address',
        'association_zone_code',
        'association_zone_name',
        'association_sector_name',
        'beneficiaries_count',
    ];

    protected $casts = [
        'delivery_date' => 'date:Y-m-d',
        'beneficiaries_count' => 'integer',
    ];

    /**
     * Fecha de entrega "efectiva" para efectos del período de repartición.
     *
     * Una PECOSA emitida en los últimos 7 días de un mes corresponde en
     * realidad a la repartición del mes siguiente (se programa a fin de mes
     * para entregar a inicios del siguiente). Este desplazamiento debe usarse
     * en todo el sistema al agrupar o filtrar PECOSAs por mes.
     */
    public static function effectiveDeliveryDate($date): ?Carbon
    {
        if (! $date) {
            return null;
        }

        $date = $date instanceof Carbon ? $date->copy() : Carbon::parse($date);

        return $date->day > $date->daysInMonth - 7
            ? $date->copy()->addMonthNoOverflow()->startOfDay()
            : $date->startOfDay();
    }

    /**
     * Rango de fechas de entrega (inclusive) cuyas PECOSAs pertenecen al
     * período de repartición indicado, considerando el desplazamiento de
     * fin de mes descrito en effectiveDeliveryDate().
     *
     * @return array{0:\Carbon\Carbon,1:\Carbon\Carbon}
     */
    public static function deliveryPeriodRange(int $year, int $month): array
    {
        $anchor = Carbon::create($year, $month, 1)->startOfDay();
        $prev = $anchor->copy()->subMonthNoOverflow();

        // Primer día del mes anterior que ya se contabiliza en este período.
        $start = $prev->copy()
            ->day(max(1, $prev->daysInMonth - 7 + 1))
            ->startOfDay();

        // Último día de este mes que todavía pertenece a este período
        // (los posteriores se desplazan al mes siguiente).
        $end = $anchor->copy()
            ->day(max(1, $anchor->daysInMonth - 7))
            ->endOfDay();

        return [$start, $end];
    }

    /**
     * Período de repartición vigente según el mes calendario actual.
     *
     * El desplazamiento de los últimos 7 días solo clasifica la fecha de
     * entrega de una PECOSA; no debe adelantar el mes vigente antes de que
     * termine el mes calendario.
     *
     * @return array{0:int,1:int} [año, mes]
     */
    public static function currentDeliveryPeriod($date = null): array
    {
        $current = $date instanceof Carbon
            ? $date->copy()
            : Carbon::parse($date ?? now());

        return [$current->year, $current->month];
    }

    public function scopeForDeliveryPeriod($query, int $year, int $month)
    {
        [$start, $end] = self::deliveryPeriodRange($year, $month);

        return $query->whereBetween('delivery_date', [$start->toDateString(), $end->toDateString()]);
    }

    /**
     * Índice comparable de un período de repartición (year * 12 + month - 1).
     */
    public static function deliveryPeriodIndex($date): ?int
    {
        $effective = self::effectiveDeliveryDate($date);

        return $effective ? $effective->year * 12 + ($effective->month - 1) : null;
    }

    /**
     * Clasifica cada PECOSA con fecha de entrega en el estado VIG (su período de
     * repartición efectivo es el actual o uno futuro) o VEN (ya pasó). Se usa
     * para el respaldo inicial y para la sincronización diaria programada.
     *
     * @return int Cantidad de PECOSAs cuyo estado cambió.
     */
    public static function syncVigenciaStates(): int
    {
        $vigId = State::idFor(State::CURRENT);
        $venId = State::idFor(State::EXPIRED);

        if (! $vigId || ! $venId) {
            return 0;
        }

        [$currentYear, $currentMonth] = self::currentDeliveryPeriod();
        $currentIndex = $currentYear * 12 + ($currentMonth - 1);
        $changed = 0;

        self::query()
            ->whereNotNull('delivery_date')
            ->select(['id', 'state_id', 'delivery_date'])
            ->chunkById(500, function ($pecosas) use ($currentIndex, $vigId, $venId, &$changed) {
                foreach ($pecosas as $pecosa) {
                    $index = self::deliveryPeriodIndex($pecosa->delivery_date);
                    $target = $index !== null && $index < $currentIndex ? $venId : $vigId;

                    if ((int) $pecosa->state_id !== $target) {
                        $pecosa->forceFill(['state_id' => $target])->saveQuietly();
                        $changed++;
                    }
                }
            });

        return $changed;
    }

    /**
     * Marca como VENCIDAS las PECOSAs aún vigentes del mismo comité cuyo período
     * de repartición efectivo es el inmediatamente anterior al de esta PECOSA.
     * Se dispara desde PecosaObserver al crear una PECOSA nueva.
     *
     * @return int Cantidad de PECOSAs anteriores marcadas como vencidas.
     */
    public function expirePreviousPeriodSiblings(): int
    {
        if ($this->delivery_date === null || $this->association_id === null) {
            return 0;
        }

        $vigId = State::idFor(State::CURRENT);
        $venId = State::idFor(State::EXPIRED);

        if (! $vigId || ! $venId) {
            return 0;
        }

        $previous = self::effectiveDeliveryDate($this->delivery_date)->subMonthNoOverflow();
        [$start, $end] = self::deliveryPeriodRange($previous->year, $previous->month);

        $changed = 0;

        self::query()
            ->where('association_id', $this->association_id)
            ->where('id', '!=', $this->id)
            ->where('state_id', $vigId)
            ->whereBetween('delivery_date', [$start->toDateString(), $end->toDateString()])
            ->get(['id', 'state_id'])
            ->each(function ($pecosa) use ($venId, &$changed) {
                $pecosa->forceFill(['state_id' => $venId])->saveQuietly();
                $changed++;
            });

        return $changed;
    }

    public function isVigente(): bool
    {
        // El estado persistido (VIG/VEN) es la fuente de verdad cuando la PECOSA
        // ya fue clasificada; se mantiene al día por PecosaObserver, el respaldo
        // inicial y el comando programado pecosas:sync-vigencia.
        $abbreviation = $this->state?->abbreviation;
        if (in_array($abbreviation, [State::CURRENT, State::EXPIRED], true)) {
            return $abbreviation === State::CURRENT;
        }

        // Respaldo para datos aún sin clasificar: se resuelve por fecha efectiva.
        if ($this->delivery_date === null) {
            return false;
        }

        $effective = self::effectiveDeliveryDate($this->delivery_date);
        [$currentYear, $currentMonth] = self::currentDeliveryPeriod();

        return (int) $effective->year === $currentYear
            && (int) $effective->month === $currentMonth;
    }

    public function getVigenciaAttribute(): string
    {
        return $this->isVigente() ? 'Vigente' : 'Vencido';
    }

    public function scopeVigentes($query)
    {
        [$year, $month] = self::currentDeliveryPeriod();

        return $query->forDeliveryPeriod($year, $month);
    }

    public function state()
    {
        return $this->belongsTo(State::class);
    }

    public function association()
    {
        return $this->belongsTo(Association::class);
    }

    public function president()
    {
        return $this->belongsTo(Partner::class, 'president_id');
    }

    public function chief()
    {
        return $this->belongsTo(Responsible::class, 'chief_id');
    }

    public function storekeeper()
    {
        return $this->belongsTo(Responsible::class, 'storekeeper_id');
    }

    public function managingPartner()
    {
        return $this->belongsTo(Partner::class, 'managing_partner_id');
    }

    public function detailPecosas()
    {
        return $this->hasMany(DetailPecosa::class);
    }

    public function getMonthAttribute()
    {
        return $this->delivery_date ? date('n', strtotime($this->delivery_date)) : null;
    }

    public function getYearAttribute()
    {
        return $this->delivery_date ? date('Y', strtotime($this->delivery_date)) : null;
    }
}
