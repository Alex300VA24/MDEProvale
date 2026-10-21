<?php

namespace App\Repositories\Contracts;

use Illuminate\Support\Collection;

/**
 * Consultas agregadas del panel de inicio.
 */
interface DashboardRepositoryInterface
{
    /** true si existe el padrón mensual de socios del periodo (`$periodStart`). */
    public function hasPartnerRosterSnapshot(string $periodStart): bool;

    /** @return array{relations:int, people:int} socios vigentes (relaciones y personas distintas). */
    public function partnerCounts(string $start, string $end, bool $useSnapshot): array;

    /** @return array{relations:int, people:int} beneficiarios vigentes (relaciones y personas distintas). */
    public function beneficiaryCounts(string $start, string $end): array;

    /** Comités del padrón mensual del periodo, o todos si no hay padrón. */
    public function committeeCount(string $periodStart): int;

    /** @return array{byType: array<string,int>, total:int} personas socias y beneficiarias a la vez. */
    public function dualRole(string $start, string $end, bool $useSnapshot): array;

    public function pecosaCountBetween(string $start, string $end): int;

    /** Existencias totales: ingresado menos repartido en todos los lotes. */
    public function stockTotal(): int;

    /** Lotes de Hojuelas y Leche ingresados en el rango, con `quantity`, `total_used`, `product`, `unit`. */
    public function oatAndMilkLots(string $start, string $end): Collection;

    /** Fechas de entrega de PECOSAs entre ambas fechas. */
    public function pecosaDeliveryDates(string $from, string $to): Collection;

    /** Salidas por mes y producto de los lotes ingresados en el año (`mes`, `product`, `total`). */
    public function distributedByMonth(int $year): Collection;

    /** Personas con beneficio vigente en el rango, según el historial de beneficiarios. */
    public function beneficiaryPeopleInPeriod(string $start, string $end): int;

    /** Comités con más beneficiarios vigentes en el rango (`code`, `club`, `address`, `total`). */
    public function topCommittees(string $start, string $end, int $limit = 10): Collection;

    public function latestBeneficiaryHistoryDate(): ?string;

    public function latestPecosaDeliveryDate(): ?string;
}
