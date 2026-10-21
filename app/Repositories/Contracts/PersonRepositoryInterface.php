<?php

namespace App\Repositories\Contracts;

use App\Models\People;
use Illuminate\Pagination\LengthAwarePaginator;

interface PersonRepositoryInterface
{
    /** Filtros: search, gender, place_sector_id. */
    public function paginateFiltered(array $filters, int $perPage = 15, array $with = []): LengthAwarePaginator;

    public function loadPlaceSector(People $person): People;

    public function createPerson(array $data): People;

    public function updatePerson(People $person, array $data): bool;

    public function isLinkedToPartnerOrBeneficiary(People $person): bool;

    public function deletePerson(People $person): void;
}
