<?php

namespace App\Repositories;

use App\Models\Beneficiarie;
use App\Models\BeneficiaryHistory;
use App\Models\Partner;
use App\Models\People;
use App\Models\Relationship;
use App\Models\TypeBenefit;
use App\Repositories\Contracts\BeneficiaryRepositoryInterface;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Model;
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

    public function paginateForPeriod(array $filters, string $startDate, string $endDate, int $perPage = 10): LengthAwarePaginator
    {
        return $this->model
            ->with([
                'person',
                'partner.people:id,names,father_lastname',
                'relationship',
                'histories' => fn ($history) => $history
                    ->whereDate('date_begin', '<=', $endDate)
                    ->where(fn ($dates) => $dates->whereNull('date_end')->orWhereDate('date_end', '>=', $startDate))
                    ->orderByDesc('date_begin'),
                'histories.typeBenefit:id,title,abbreviation',
                'histories.state:id,title',
                'histories.reasonDisqualification:id,title',
                'documents',
            ])
            ->activeDuring($startDate, $endDate)
            ->when($filters['search'] ?? null, fn ($q, $search) => $q->whereHas('person', fn ($p) => $p->searchIdentity($search)))
            ->when($filters['partner_id'] ?? null, fn ($q, $v) => $q->where('partner_id', $v))
            ->when($filters['relationship_id'] ?? null, fn ($q, $v) => $q->where('relationship_id', $v))
            ->orderBy('id', 'desc')
            ->paginate($perPage);
    }

    public function chunkExpiredBenefitHistories(int $inactiveStateId, string $today, int $size, callable $callback): void
    {
        BeneficiaryHistory::query()
            ->whereNotNull('date_end')
            ->whereDate('date_end', '<', $today)
            ->where('state_id', '!=', $inactiveStateId)
            ->whereHas('typeBenefit', fn ($query) => $query->whereIn('abbreviation', ['GES', 'LAC']))
            ->with(['typeBenefit', 'beneficiary.person', 'beneficiary.partner.association'])
            ->chunkById($size, $callback);
    }

    public function lockHistory(int $id): ?BeneficiaryHistory
    {
        return BeneficiaryHistory::query()->lockForUpdate()->find($id);
    }

    public function latestHistoryBegin(): ?string
    {
        return BeneficiaryHistory::max('date_begin');
    }

    public function loadDetail(Beneficiarie $beneficiary): Beneficiarie
    {
        return $beneficiary->load([
            'person.placeSector.place:id,title',
            'person.placeSector.sector:id,title',
            'partner.people:id,names,father_lastname,dni',
            'relationship',
            'histories.typeBenefit:id,title,abbreviation',
            'histories.state:id,title',
            'histories.reasonDisqualification:id,title',
            'documents',
        ]);
    }

    public function findByPartnerAndPerson(Partner $partner, mixed $personId): ?Beneficiarie
    {
        return $partner->beneficiaries()->where('person_id', $personId)->first();
    }

    public function findAttachable(string $type, int $id): Model
    {
        return $type === 'partner'
            ? Partner::findOrFail($id)
            : $this->model->findOrFail($id);
    }

    public function activeHistories(Beneficiarie $beneficiary, string $date): Collection
    {
        return $beneficiary->histories()
            ->whereDate('date_begin', '<=', $date)
            ->where(fn ($query) => $query->whereNull('date_end')->orWhereDate('date_end', '>=', $date))
            ->get();
    }

    public function hasHistories(Beneficiarie $beneficiary): bool
    {
        return $beneficiary->histories()->exists();
    }

    public function updateHistory(BeneficiaryHistory $history, array $data): bool
    {
        return $history->update($data);
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
