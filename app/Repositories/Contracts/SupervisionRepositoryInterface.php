<?php

namespace App\Repositories\Contracts;

use App\Models\SupervisionOffDay;
use App\Models\SupervisionVisit;
use Illuminate\Database\Eloquent\Collection;

/**
 * Visitas de supervisión a comités y días sin supervisión.
 */
interface SupervisionRepositoryInterface
{
    /** Visitas del rango con sus documentos, agrupadas por comité. */
    public function visitsBetween(string $start, string $end): \Illuminate\Support\Collection;

    public function offDaysBetween(string $start, string $end): Collection;

    public function findVisit(int $associationId, string $date): ?SupervisionVisit;

    public function visitHasDocuments(SupervisionVisit $visit): bool;

    public function deleteVisit(SupervisionVisit $visit): void;

    public function upsertVisit(int $associationId, string $date, string $result, ?int $userId): SupervisionVisit;

    public function upsertOffDay(string $date, ?string $reason, ?int $userId): SupervisionOffDay;

    public function deleteOffDay(string $date): void;

    /** Visita con bloqueo de escritura; solo dentro de una transacción. */
    public function lockVisit(int $id): SupervisionVisit;

    public function countDocuments(SupervisionVisit $visit, string $documentType): int;
}
