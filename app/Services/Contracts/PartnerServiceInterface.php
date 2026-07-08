<?php

namespace App\Services\Contracts;

use App\Models\Partner;

interface PartnerServiceInterface
{
    public function storeWithBeneficiaries(array $partnerData, ?array $beneficiaries = null): Partner;
    public function updateWithBeneficiaries(Partner $partner, array $partnerData, ?array $beneficiaries = null): Partner;
    public function deleteWithRelations(Partner $partner): void;
}