<?php

namespace App\Repositories;

use App\Models\People;
use App\Repositories\Contracts\PersonRepositoryInterface;
use Illuminate\Pagination\LengthAwarePaginator;

class PersonRepository extends BaseRepository implements PersonRepositoryInterface
{
    public function model(): string
    {
        return People::class;
    }

    public function paginateFiltered(array $filters, int $perPage = 15): LengthAwarePaginator
    {
        return $this->model
            ->select('id', 'names', 'father_lastname', 'mother_lastname', 'dni', 'gender', 'telephone_number', 'phone_number', 'birthdate', 'place_sector_id', 'address')
            ->when($filters['search'] ?? null, fn ($q, $v) => $q->searchIdentity($v))
            ->when($filters['gender'] ?? null, fn ($q, $v) => $q->where('gender', $v))
            ->when($filters['place_sector_id'] ?? null, fn ($q, $v) => $q->where('place_sector_id', $v))
            ->orderBy('id')
            ->paginate($perPage);
    }

    public function createPerson(array $data): People
    {
        return $this->model->create($data);
    }

    public function updatePerson(People $person, array $data): bool
    {
        return $person->update($data);
    }

    public function isLinkedToPartnerOrBeneficiary(People $person): bool
    {
        return $person->partners()->exists() || $person->beneficiaries()->exists();
    }

    public function deletePerson(People $person): void
    {
        $person->delete();
    }
}
