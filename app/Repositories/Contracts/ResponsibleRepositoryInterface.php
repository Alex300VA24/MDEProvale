<?php

namespace App\Repositories\Contracts;

use App\Models\Responsible;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Pagination\LengthAwarePaginator;

/**
 * Responsables de almacén (jefe y almacenero) y sus periodos.
 */
interface ResponsibleRepositoryInterface
{
    public function allWithPerson(): Collection;

    public function activeWithPerson(): Collection;

    /** Vigentes del tipo indicado, con su persona. */
    public function activeOfType(string $type): Collection;

    /** Filtros: search (nombre o DNI), type, vigencia (`vigente` u otro valor para históricos). */
    public function paginateHistory(array $filters, int $perPage = 10): LengthAwarePaginator;

    public function loadPersonWithLocation(Responsible $responsible): Responsible;

    public function loadPerson(Responsible $responsible): Responsible;

    public function create(array $data): Responsible;

    public function update(Responsible $responsible, array $data): bool;
}
