<?php

namespace App\Repositories\Contracts;

use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Pagination\LengthAwarePaginator;
use App\Models\DetailPecosa;
use App\Models\Pecosa;

interface PecosaRepositoryInterface
{
    public function searchWithFilters(array $filters, int $perPage = 10): LengthAwarePaginator;
    public function findByAssociationAndPeriod(int $associationId, string $startDate, string $endDate): ?Pecosa;
    /** PECOSAs entregadas en el rango, con detalle, indexadas por comité. */
    public function deliveriesInRange(\Illuminate\Support\Collection $associationIds, string $from, string $to): \Illuminate\Support\Collection;

    public function getPresidentDirectivesByAssociation(\Illuminate\Support\Collection $associationIds, int $presidentPositionId, int $activeStateId): \Illuminate\Support\Collection;

    /** @return \Illuminate\Support\Collection<int,string> números que empiezan con el prefijo */
    public function numbersWithPrefix(string $prefix, bool $lock = false): \Illuminate\Support\Collection;

    public function findOrFailPecosa(int $id): Pecosa;

    public function delete(Model $model): bool;

    public function createPecosa(array $data): Pecosa;

    public function updatePecosa(Pecosa $pecosa, array $data): bool;

    public function createDetail(array $data): DetailPecosa;

    public function deleteDetails(int $pecosaId): void;

    public function numberExists(string $number): bool;

    public function lotIsInAnyPecosa(int $detailProductId): bool;

    public function loadForReceipt(Pecosa $pecosa): Pecosa;

    public function loadForShow(Pecosa $pecosa): Pecosa;

    /** PECOSAs del período de repartición con relaciones para comprobante. */
    public function forDeliveryPeriodWithReceiptData(int $year, int $month): Collection;

    /** Última PECOSA vigente del comité dentro del rango. */
    public function findLatestCurrentInRange(int $associationId, ?int $currentStateId, string $start, string $end): ?Pecosa;

    /** Primera PECOSA del comité en el rango de entrega, con estado y detalle. */
    public function firstScheduledInRange(int $associationId, string $from, string $to): ?Pecosa;

    /** Historial de PECOSAs del comité, más recientes primero. */
    public function paginateForAssociation(int $associationId, int $perPage): LengthAwarePaginator;

    /** Directiva PRESIDENTA vigente por comité (association_id => Directive). */
    public function presidentDirectivesFor(\Illuminate\Support\Collection $associationIds): \Illuminate\Support\Collection;
}
