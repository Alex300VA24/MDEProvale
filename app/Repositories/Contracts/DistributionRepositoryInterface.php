<?php

namespace App\Repositories\Contracts;

use App\Models\Association;
use App\Models\DistributionPeriod;
use App\Models\Racion;
use Illuminate\Database\Eloquent\Collection;

interface DistributionRepositoryInterface
{
    public function racionForMonth(int $year, int $month): ?Racion;

    public function periodWithAssignments(int $year, int $month): ?DistributionPeriod;

    public function firstOrCreatePeriod(int $year, int $month, array $defaults): DistributionPeriod;

    public function upsertAssignment(int $periodId, int $associationId, array $values): void;

    /** true si hay un ingreso de producto (transacción o lote) entre las fechas. */
    public function hasIngresoBetween(string $startDate, string $endDate): bool;

    /** Comités con sector y beneficiarios vigentes a la fecha, ordenados por código. */
    public function associationsForReport(string $endDate): Collection;

    /** Socios del comité vigentes a la fecha, con beneficiarios y su persona. */
    public function partnersWithBeneficiariesAt(Association $association, string $endDate): Collection;
}
