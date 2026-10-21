<?php

namespace App\Repositories\Contracts;

use App\Models\People;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Pagination\LengthAwarePaginator;

interface PersonRepositoryInterface
{
    /** Filtros: search, gender, place_sector_id. */
    public function paginateFiltered(array $filters, int $perPage = 15, array $with = []): LengthAwarePaginator;

    /** Todas las personas por nombre. */
    public function allByName(): Collection;

    public function loadPlaceSector(People $person): People;

    public function createPerson(array $data): People;

    public function updatePerson(People $person, array $data): bool;

    public function isLinkedToPartnerOrBeneficiary(People $person): bool;

    public function deletePerson(People $person): void;

    /** Personas que coinciden con todas las palabras en nombre, apellidos o DNI. */
    public function searchByTerms(array $terms, int $limit): Collection;
}
