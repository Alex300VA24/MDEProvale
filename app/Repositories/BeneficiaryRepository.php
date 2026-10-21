<?php

namespace App\Repositories;

use App\Models\Beneficiarie;
use App\Models\BeneficiaryHistory;
use App\Models\Partner;
use App\Models\People;
use App\Models\Relationship;
use App\Models\TypeBenefit;
use App\Repositories\Contracts\BeneficiaryRepositoryInterface;
use Illuminate\Pagination\LengthAwarePaginator;

class BeneficiaryRepository extends BaseRepository implements BeneficiaryRepositoryInterface
{
    public function model(): string
    {
        return Beneficiarie::class;
    }

    public function paginateFiltered(array $filters, int $perPage = 10): LengthAwarePaginator
    {
        return $this->model->with(['person', 'partner', 'relationship'])
            ->when($filters['search'] ?? null, fn ($q, $search) => $q->whereHas('person', fn ($p) => $p->searchIdentity($search)))
            ->when($filters['partner_id'] ?? null, fn ($q, $v) => $q->where('partner_id', $v))
            ->when($filters['relationship_id'] ?? null, fn ($q, $v) => $q->where('relationship_id', $v))
            ->orderBy('id', 'desc')
            ->paginate($perPage);
    }

    public function createPerson(array $data): People
    {
        return People::create($data);
    }

    public function createBeneficiary(array $data): Beneficiarie
    {
        return $this->model->create($data);
    }

    public function updateBeneficiary(Beneficiarie $beneficiary, array $data): bool
    {
        return $beneficiary->update($data);
    }

    public function deleteBeneficiary(Beneficiarie $beneficiary): void
    {
        $beneficiary->delete();
    }

    public function createHistory(array $data): BeneficiaryHistory
    {
        return BeneficiaryHistory::create($data);
    }

    public function firstOrCreateRelationship(string $title): Relationship
    {
        return Relationship::firstOrCreate(['title' => $title]);
    }

    public function findTypeBenefitByAbbreviation(string $abbreviation): ?TypeBenefit
    {
        return TypeBenefit::where('abbreviation', $abbreviation)->first();
    }

    public function personBirthdate(int $personId): mixed
    {
        return People::whereKey($personId)->value('birthdate');
    }

    public function typeBenefitAbbreviation(int $typeBenefitId): ?string
    {
        return TypeBenefit::whereKey($typeBenefitId)->value('abbreviation');
    }

    public function firstOrCreatePartnerBeneficiary(Partner $partner, array $values): Beneficiarie
    {
        $beneficiary = $this->model->firstOrCreate([
            'person_id' => $partner->person_id,
            'partner_id' => $partner->id,
        ], $values);

        $beneficiary->update($values);

        return $beneficiary;
    }

    public function latestHistory(Beneficiarie $beneficiary, int $typeBenefitId): ?BeneficiaryHistory
    {
        return $beneficiary->histories()
            ->where('type_benefit_id', $typeBenefitId)
            ->latest('date_begin')
            ->with(['obstetricData', 'state'])
            ->first();
    }

    public function findChild(Partner $partner, Beneficiarie $self, ?int $personId, ?int $childBeneficiaryId): ?Beneficiarie
    {
        if (! $personId && ! $childBeneficiaryId) {
            return null;
        }

        $query = $partner->beneficiaries()->where('id', '!=', $self->id)->with('person');
        if ($personId) {
            $query->where('person_id', $personId);
        } else {
            $query->whereKey($childBeneficiaryId);
        }

        return $query->first();
    }

    public function upsertHistory(Beneficiarie $beneficiary, string $dateBegin, array $values): BeneficiaryHistory
    {
        return $beneficiary->histories()->updateOrCreate(['date_begin' => $dateBegin], $values);
    }

    public function upsertObstetricData(BeneficiaryHistory $history, array $values): void
    {
        $history->obstetricData()->updateOrCreate([], $values);
    }
}
