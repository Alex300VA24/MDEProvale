<?php

namespace App\Repositories\Contracts;

use App\Models\People;
use Illuminate\Database\Eloquent\Collection;

/**
 * Consultas de la ficha y la tarjeta de control interno (kardex de leche y hojuela).
 */
interface ControlInternoRepositoryInterface
{
    /** Ids de los lotes cuyo producto coincide con alguna de las palabras clave. */
    public function detailProductIdsByKeywords(array $keywords): array;

    /** Transacciones de ingreso del mes sobre esos lotes, ordenadas por fecha. */
    public function ingresosBetween(array $detailProductIds, string $from, string $to): Collection;

    /** Lotes con cantidad que empiezan en el rango (ingresos sin transacción). */
    public function lotsStartingBetween(array $detailProductIds, string $from, string $to): Collection;

    /** Entregas de PECOSA del período sobre esos lotes, con la fecha de la PECOSA. */
    public function pecosaDetailsForPeriod(array $detailProductIds, int $year, int $month): Collection;

    /** Responsables registrados con su persona. */
    public function responsibles(): Collection;

    /** Persona del almacenero activo más reciente. */
    public function activeStorekeeper(): ?People;
}
