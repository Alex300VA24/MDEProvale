<?php

namespace App\Services;

use App\Models\Beneficiarie;
use App\Models\BeneficiaryHistory;
use App\Models\Partner;
use App\Models\People;
use App\Models\Relationship;
use App\Models\State;
use App\Models\TypeBenefit;
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
        return DB::transaction(function () use ($partnerData, $beneficiaries) {
            if (isset($partnerData['create_person']) && $partnerData['create_person']) {
                $person = People::create($partnerData);
                $partnerData['person_id'] = $person->id;
            }

            $partner = Partner::create($partnerData);
            $this->syncBeneficiaries($partner, $beneficiaries ?? []);
            $this->syncPartnerAsBeneficiary($partner);

            return $partner;
        });
    }

    public function updateWithBeneficiaries(Partner $partner, array $partnerData, ?array $beneficiaries = null): Partner
    {
        $partner->update($partnerData);

        if ($beneficiaries !== null) {
            $partner->beneficiaries()->delete();
            $this->syncBeneficiaries($partner, $beneficiaries);
        }

        $this->syncPartnerAsBeneficiary($partner);

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
                'priority' => $b['priority'] ?? 1,
            ]);

            if (!empty($b['type_benefit_id']) && !empty($b['history_state_id'])
                && !empty($b['date_begin'])) {
                BeneficiaryHistory::create([
                    'weight' => 0,
                    'height' => 0,
                    'hmg' => 0,
                    'date_begin' => $b['date_begin'] ?? now()->toDateString(),
                    'date_end' => $this->benefitEndDate($b['person_id'], $b['type_benefit_id']),
                    'type_benefit_id' => $b['type_benefit_id'],
                    'beneficiary_id' => $ben->id,
                    'state_id' => $b['history_state_id'],
                    'reason_disqualification_id' => $b['reason_disqualification_id'] ?? null,
                    'is_malnourished' => false,
                    'is_disabled' => false,
                ]);
            }
        }
    }

    private function syncPartnerAsBeneficiary(Partner $partner): void
    {
        $condition = $partner->socioeconomic_condition;
        $abbreviation = match ($condition) {
            'GESTANTE' => 'GES',
            'LACTANTE' => 'LAC',
            'ADULTO_MAYOR' => 'ADU',
            'DISCAPACITADO' => 'DIS',
            'OTROS' => preg_match('/\b(tb|tbc|tuberc|tebec)/i', (string) $partner->observations) ? 'TBC' : null,
            default => null,
        };

        if (! $abbreviation) {
            return;
        }

        $relationship = Relationship::firstOrCreate(['title' => 'Socio']);
        $typeBenefit = TypeBenefit::where('abbreviation', $abbreviation)->first();
        if (! $typeBenefit) {
            return;
        }

        $beneficiary = Beneficiarie::firstOrCreate([
            'person_id' => $partner->person_id,
            'partner_id' => $partner->id,
        ], [
            'relationship_id' => $relationship->id,
            'priority' => in_array($abbreviation, ['GES', 'LAC'], true) ? 1 : 2,
        ]);

        $beneficiary->update([
            'relationship_id' => $relationship->id,
            'priority' => in_array($abbreviation, ['GES', 'LAC'], true) ? 1 : 2,
        ]);

        $beneficiary->histories()->updateOrCreate([
            'date_begin' => $partner->date_begin ?? now()->toDateString(),
        ], [
            'weight' => 0,
            'height' => 0,
            'hmg' => 0,
            'date_end' => $this->benefitEndDate($partner->person_id, $typeBenefit->id),
            'type_benefit_id' => $typeBenefit->id,
            'state_id' => $partner->state_id ?: State::idFor(State::CURRENT),
            'reason_disqualification_id' => null,
            'is_malnourished' => false,
            'is_disabled' => false,
        ]);
    }

    private function benefitEndDate(int $personId, int $typeBenefitId): ?string
    {
        $birthdate = People::whereKey($personId)->value('birthdate');
        $abbreviation = TypeBenefit::whereKey($typeBenefitId)->value('abbreviation');

        if (! $birthdate || ! in_array($abbreviation, ['NI0', 'NI7'], true)) {
            return null;
        }

        $years = $abbreviation === 'NI0' ? 7 : 14;

        return \Illuminate\Support\Carbon::parse($birthdate)->addYears($years)->subDay()->toDateString();
    }
}
