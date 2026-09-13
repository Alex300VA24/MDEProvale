<?php

namespace App\Services;

use App\Models\Setting;
use Illuminate\Support\Carbon;

/**
 * Estado del "cierre de mes" administrativo.
 *
 * La clave `cierre_mes` guarda el último período cerrado en formato YYYY-MM.
 * Mientras un mes no esté cerrado, los avisos y detalles históricos (auditoría
 * de padrones, stock consumido/faltante) no deben mostrarse en el panel de
 * inicio: son datos definitivos que solo tienen sentido una vez terminado el
 * mes y cerrado por el administrador.
 *
 * Si la clave no existe se usa como fallback el mes calendario anterior, de
 * modo que el mes en curso nunca se considera cerrado por accidente.
 */
class MonthClosureService
{
    public const SETTING_CLOSED = 'cierre_mes';

    /**
     * Mes (YYYY-MM) desde el cual se considera el ciclo mensual del programa.
     */
    public const MIN_PERIOD = '2026-01';

    /**
     * Último período cerrado como [año, mes], o null si no hay ninguno.
     */
    public function currentClosedPeriod(?Carbon $date = null): ?array
    {
        $value = Setting::get(self::SETTING_CLOSED);

        if (is_string($value) && preg_match('/^(\d{4})-(\d{2})$/', $value, $matches)) {
            return [(int) $matches[1], (int) $matches[2]];
        }

        if ($value !== null && $value !== '') {
            return null;
        }

        $anchor = ($date ?? now())->copy()->subMonthNoOverflow();

        return [(int) $anchor->year, (int) $anchor->month];
    }

    /**
     * Indica si un período ya fue cerrado (o está atrás del último cierre).
     */
    public function isPeriodClosed(int $year, int $month, ?Carbon $date = null): bool
    {
        $closed = $this->currentClosedPeriod($date);

        if (! $closed) {
            return false;
        }

        return self::index([$year, $month]) <= self::index($closed);
    }

    /**
     * Meses ya cerrados, desde MIN_PERIOD hasta el último cierre inclusive.
     *
     * @return array<int, array{anio:int, mes:int, label:string}>
     */
    public function closedMonths(?Carbon $date = null): array
    {
        $closed = $this->currentClosedPeriod($date);

        if (! $closed) {
            return [];
        }

        [$floorYear, $floorMonth] = $this->minPeriod();

        $period = $this->minPeriod();
        $months = [];

        while (self::index($period) <= self::index($closed)) {
            $months[] = [
                'anio' => $period[0],
                'mes' => $period[1],
                'label' => $this->label($period[0], $period[1]),
            ];

            $period = $this->addMonth($period);
        }

        return $months;
    }

    /**
     * Meses que el administrador puede cerrar ahora: desde el período siguiente
     * al último cierre hasta el mes calendario anterior (nunca el vigente).
     *
     * @return array<int, array{anio:int, mes:int, label:string}>
     */
    public function availableMonths(?Carbon $date = null): array
    {
        $anchor = ($date ?? now())->copy()->startOfMonth();
        $lastCloseable = [
            (int) $anchor->copy()->subMonthNoOverflow()->year,
            (int) $anchor->copy()->subMonthNoOverflow()->month,
        ];

        [$minYear, $minMonth] = $this->minPeriod();
        $closed = $this->currentClosedPeriod($date);

        $next = $closed
            ? $this->addMonth($closed)
            : $this->minPeriod();

        if ($next[0] < $minYear || ($next[0] === $minYear && $next[1] < $minMonth)) {
            $next = $this->minPeriod();
        }

        $months = [];
        while (self::index($next) <= self::index($lastCloseable)) {
            $months[] = [
                'anio' => $next[0],
                'mes' => $next[1],
                'label' => $this->label($next[0], $next[1]),
            ];

            $next = $this->addMonth($next);
        }

        return $months;
    }

    /**
     * Sirve únicamente días posteriores al cierre del mes: el administrador
     * define el momento del cierre y este servicio lo registra. El mes objetivo
     * debe ser anterior al mes calendario vigente.
     */
    public function close(int $year, int $month, ?Carbon $date = null): void
    {
        $anchor = ($date ?? now())->copy()->startOfMonth();
        $current = [(int) $anchor->year, (int) $anchor->month];

        if (self::index([$year, $month]) >= self::index($current)) {
            throw new \InvalidArgumentException("El mes $year-$month aún no ha terminado y no puede cerrarse.");
        }

        $available = $this->availableMonths($date);
        $periods = array_map(
            fn (array $item) => [$item['anio'], $item['mes']],
            $available
        );

        if (! in_array([$year, $month], $periods, true)) {
            throw new \InvalidArgumentException('El mes indicado no está disponible para cerrar.');
        }

        Setting::put(self::SETTING_CLOSED, sprintf('%04d-%02d', $year, $month));
    }

    /**
     * Etiqueta legible de un período, p. ej. "Agosto 2026".
     */
    public function label(int $year, int $month): string
    {
        static $months = [1 => 'Enero', 2 => 'Febrero', 3 => 'Marzo', 4 => 'Abril', 5 => 'Mayo', 6 => 'Junio', 7 => 'Julio', 8 => 'Agosto', 9 => 'Septiembre', 10 => 'Octubre', 11 => 'Noviembre', 12 => 'Diciembre'];

        return ($months[$month] ?? '') . " $year";
    }

    /**
     * @return array{0:int, 1:int} [año, mes]
     */
    private function minPeriod(): array
    {
        return [(int) substr(self::MIN_PERIOD, 0, 4), (int) substr(self::MIN_PERIOD, 5, 2)];
    }

    /**
     * Índice comparable de un período (año * 12 + mes).
     */
    private static function index(array $period): int
    {
        return $period[0] * 12 + $period[1];
    }

    /**
     * @return array{0:int, 1:int} período del mes siguiente
     */
    private function addMonth(array $period): array
    {
        $carbon = Carbon::create($period[0], $period[1], 1)->addMonthNoOverflow();

        return [(int) $carbon->year, (int) $carbon->month];
    }
}