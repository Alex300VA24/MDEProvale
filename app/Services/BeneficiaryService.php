<?php

namespace App\Services;

use App\Models\Beneficiarie;
use App\Repositories\Contracts\BeneficiaryRepositoryInterface;
use App\Repositories\Contracts\CatalogRepositoryInterface;

class BeneficiaryService
{
    public function __construct(
        private BeneficiaryRepositoryInterface $beneficiaryRepo,
        private CatalogRepositoryInterface $catalogs
    ) {
    }

    public function indexData(array $filters): array
    {
        return [
            'beneficiaries' => $this->beneficiaryRepo->paginateFiltered($filters),
            'partners' => $this->catalogs->partnersWithPeople(),
            'relationships' => $this->catalogs->relationships(),
        ];
    }

    public function create(array $data): Beneficiarie
    {
        return $this->beneficiaryRepo->createBeneficiary($data);
    }

    public function update(Beneficiarie $beneficiary, array $data): bool
    {
        return $this->beneficiaryRepo->updateBeneficiary($beneficiary, $data);
    }

    public function delete(Beneficiarie $beneficiary): void
    {
        $this->beneficiaryRepo->deleteBeneficiary($beneficiary);
    }
}
