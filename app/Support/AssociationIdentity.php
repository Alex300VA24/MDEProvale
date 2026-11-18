<?php

namespace App\Support;

use App\Models\Pecosa;
use Carbon\Carbon;

final class AssociationIdentity
{
    public const SOL_Y_ARENA_INTERNAL_CODE = '019-H';
    public const ISABEL_CRUZ_CODE = '019';
    public const ISABEL_LEGACY_PLACEHOLDER = '390';
    public const ISABEL_START = '2026-09-01';

    /**
     * El padrón reutilizó el código 019 para otra organización en setiembre.
     * Se conserva el valor fuente, pero se resuelve contra una identidad
     * interna distinta según el periodo.
     */
    public static function rosterCode(string $sourceCode, string $period): string
    {
        $sourceCode = trim($sourceCode);
        $period = Carbon::parse($period)->startOfMonth()->toDateString();

        if ($sourceCode !== self::ISABEL_CRUZ_CODE) {
            return $sourceCode;
        }

        return $period < self::ISABEL_START
            ? self::SOL_Y_ARENA_INTERNAL_CODE
            : self::ISABEL_CRUZ_CODE;
    }

    /**
     * Traduce las asociaciones provisionales usadas por la exportación de
     * SQL Server a las identidades definitivas del sistema.
     */
    public static function pecosaCode(
        string $sourceCode,
        ?string $associationName,
        string $deliveryDate
    ): string {
        $sourceCode = trim($sourceCode);
        $name = mb_strtoupper(trim((string) $associationName), 'UTF-8');
        $period = Pecosa::effectiveDeliveryDate($deliveryDate)
            ->startOfMonth()
            ->toDateString();

        if ($sourceCode === self::ISABEL_LEGACY_PLACEHOLDER
            || str_contains($name, 'ISABEL CRUZ DE MARCELO')) {
            return self::ISABEL_CRUZ_CODE;
        }

        if ($sourceCode === self::ISABEL_CRUZ_CODE
            && $period < self::ISABEL_START
            && str_contains($name, 'SOL Y ARENA')) {
            return self::SOL_Y_ARENA_INTERNAL_CODE;
        }

        return $sourceCode;
    }
}
