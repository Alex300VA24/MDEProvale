<?php

namespace App\Services;

use App\Models\People;
use App\Repositories\Contracts\CatalogRepositoryInterface;
use App\Repositories\Contracts\PersonRepositoryInterface;

class PersonService
{
    public function __construct(
        private PersonRepositoryInterface $personRepo,
        private CatalogRepositoryInterface $catalogs
    ) {
    }

    /** Listado de personas con los sectores para el filtro. */
    public function indexData(array $filters): array
    {
        return [
            'people' => $this->personRepo->paginateFiltered($filters),
            'placeSectors' => $this->catalogs->placeSectors(['id', 'place_id', 'sector_id']),
        ];
    }

    public function placeSectors()
    {
        return $this->catalogs->placeSectors();
    }

    public function create(array $data): People
    {
        return $this->personRepo->createPerson($data);
    }

    public function update(People $person, array $data): bool
    {
        return $this->personRepo->updatePerson($person, $data);
    }

    /**
     * @throws \DomainException si la persona está vinculada a un socio o beneficiario
     */
    public function delete(People $person): void
    {
        if ($this->personRepo->isLinkedToPartnerOrBeneficiary($person)) {
            throw new \DomainException('No se puede eliminar la persona porque está asociada a un socio o beneficiario');
        }

        $this->personRepo->deletePerson($person);
    }
}
