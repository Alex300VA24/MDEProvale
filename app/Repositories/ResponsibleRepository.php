<?php

namespace App\Repositories;

use App\Models\Responsible;
use App\Repositories\Contracts\ResponsibleRepositoryInterface;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Pagination\LengthAwarePaginator;

class ResponsibleRepository implements ResponsibleRepositoryInterface
{
    public function allWithPerson(): Collection
    {
        return Responsible::with('person')->get();
    }

    public function activeWithPerson(): Collection
    {
        return Responsible::with('person')->where('active', true)->get();
    }

    public function activeOfType(string $type): Collection
    {
        return Responsible::with('person')->where('type', $type)->where('active', true)->get();
    }

    public function paginateHistory(array $filters, int $perPage = 10): LengthAwarePaginator
    {
        $query = Responsible::with('person:id,names,father_lastname,mother_lastname,dni')
            ->searchPerson($filters['search'] ?? null)
            ->orderByDesc('active')
            ->orderByRaw('COALESCE(start_date, created_at) DESC');

        if (! empty($filters['type'])) {
            $query->where('type', $filters['type']);
        }

        if (! empty($filters['vigencia'])) {
            $query->where('active', $filters['vigencia'] === 'vigente');
        }

        return $query->paginate($perPage);
    }

    public function loadPersonWithLocation(Responsible $responsible): Responsible
    {
        return $responsible->load('person.placeSector.place:id,title', 'person.placeSector.sector:id,title');
    }

    public function loadPerson(Responsible $responsible): Responsible
    {
        return $responsible->load('person');
    }

    public function create(array $data): Responsible
    {
        return Responsible::create($data);
    }

    public function update(Responsible $responsible, array $data): bool
    {
        return $responsible->update($data);
    }
}
