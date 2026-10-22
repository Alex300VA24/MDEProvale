<?php

namespace App\Repositories\Contracts;

use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection as SupportCollection;

/**
 * Consultas de detección de vencimientos: fin de beneficio, límite de edad y resoluciones.
 */
interface VencimientoRepositoryInterface
{
    /** Historiales GES/LAC activos cuyo término cae entre hoy y hoy + $days. */
    public function benefitsEndingWithin(Carbon $today, int $days): Collection;

    /** Edad máxima por tipo de beneficio (id => max_age), solo los que tienen límite. */
    public function maxAgeByBenefitType(): SupportCollection;

    /** Historiales vigentes de un tipo de beneficio cuyo beneficiario nació en el rango. */
    public function benefitsBornBetween(int $typeBenefitId, Carbon $today, string $bornFrom, string $bornTo): Collection;

    /** Resoluciones con fecha de término en el rango, con sus comités principales. */
    public function resolutionsEndingBetween(Carbon $from, Carbon $to): Collection;
}
