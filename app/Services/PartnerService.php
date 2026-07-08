<?php

namespace App\Services;

use App\Models\Beneficiarie;
use App\Models\BeneficiaryHistory;
use App\Models\Partner;
use App\Models\People;
use App\Repositories\PartnerRepository;
use Barryvdh\DomPDF\Facade\PDF;
use Illuminate\Support\Facades\DB;

class PartnerService
{
    private PartnerRepository $partnerRepo;
    private PDFService $pdfService;

    public function __construct(PartnerRepository $partnerRepo, PDFService $pdfService)
    {
        $this->partnerRepo = $partnerRepo;
        $this->pdfService = $pdfService;
    }

    public function storeWithBeneficiaries(array $partnerData, ?array $beneficiaries = null): Partner
    {
        if (isset($partnerData['create_person']) && $partnerData['create_person']) {
            $person = People::create($partnerData);
            $partnerData['person_id'] = $person->id;
        }

        $partner = Partner::create($partnerData);

        if ($beneficiaries) {
            $this->syncBeneficiaries($partner, $beneficiaries);
        }

        return $partner;
    }

    public function updateWithBeneficiaries(Partner $partner, array $partnerData, ?array $beneficiaries = null): Partner
    {
        $partner->update($partnerData);

        if ($beneficiaries !== null) {
            $partner->beneficiaries()->delete();
            $this->syncBeneficiaries($partner, $beneficiaries);
        }

        return $partner;
    }

    public function deleteWithRelations(Partner $partner): void
    {
        $partner->beneficiaries()->delete();
        $partner->delete();
    }

    private function syncBeneficiaries(Partner $partner, array $beneficiaries): void
    {
        foreach ($beneficiaries as $b) {
            if (empty($b['person_id']) || empty($b['relationship_id'])) continue;

            $ben = Beneficiarie::create([
                'person_id' => $b['person_id'],
                'partner_id' => $partner->id,
                'relationship_id' => $b['relationship_id'],
            ]);

            if (!empty($b['type_benefit_id']) && !empty($b['history_state_id'])
                && !empty($b['date_begin']) && !empty($b['date_end'])) {
                BeneficiaryHistory::create([
                    'weight' => $b['weight'] ?? 0,
                    'height' => $b['height'] ?? 0,
                    'hmg' => $b['hmg'] ?? 0,
                    'date_begin' => $b['date_begin'],
                    'date_end' => $b['date_end'],
                    'type_benefit_id' => $b['type_benefit_id'],
                    'beneficiary_id' => $ben->id,
                    'state_id' => $b['history_state_id'],
                    'reason_disqualification_id' => $b['reason_disqualification_id'] ?? null,
                ]);
            }
        }
    }
}