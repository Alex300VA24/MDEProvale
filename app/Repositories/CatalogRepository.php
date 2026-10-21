<?php

namespace App\Repositories;

use App\Models\Association;
use App\Models\AssociationRosterPeriod;
use App\Models\Partner;
use App\Models\People;
use App\Models\PlaceSector;
use App\Models\Position;
use App\Models\Product;
use App\Models\ReasonDisqualification;
use App\Models\Relationship;
use App\Models\Responsible;
use App\Models\State;
use App\Models\TypeBenefit;
use App\Models\TypePremises;
use App\Models\TypeTransaction;
use App\Models\Uom;
use App\Repositories\Contracts\CatalogRepositoryInterface;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Collection as SupportCollection;

class CatalogRepository implements CatalogRepositoryInterface
{
    public function associations(array $columns = ['id', 'name']): Collection
    {
        return Association::select($columns)->get();
    }

    public function currentAssociations(array $columns = ['*']): Collection
    {
        $currentId = State::idFor(State::CURRENT);

        return Association::select($columns)
            ->when($currentId, fn ($q) => $q->where('state_id', $currentId))
            ->get();
    }

    public function temporalStates(array $columns = ['*']): Collection
    {
        return State::temporal()->get($columns);
    }

    public function uoms(array $columns = ['*']): Collection
    {
        return Uom::select($columns)->get();
    }

    public function products(array $columns = ['id', 'title', 'abbreviation']): Collection
    {
        return Product::select($columns)->get();
    }

    public function typeTransactions(): Collection
    {
        return TypeTransaction::all();
    }

    public function relationships(array $columns = ['*']): Collection
    {
        return Relationship::select($columns)->get();
    }

    public function placeSectors(array $columns = ['*'], array $with = ['place:id,title', 'sector:id,title']): Collection
    {
        return PlaceSector::select($columns)->with($with)->get();
    }

    public function typeBenefits(array $columns = ['id', 'title', 'abbreviation']): Collection
    {
        return TypeBenefit::select($columns)->get();
    }

    public function reasonDisqualifications(array $columns = ['id', 'title']): Collection
    {
        return ReasonDisqualification::select($columns)->get();
    }

    public function recentPeople(int $limit = 100): Collection
    {
        return People::select(['id', 'names', 'father_lastname', 'mother_lastname', 'dni'])
            ->orderBy('id', 'desc')
            ->limit($limit)
            ->get();
    }

    public function peopleByName(int $limit = 1000): Collection
    {
        return People::select(['id', 'names', 'father_lastname', 'mother_lastname', 'dni'])
            ->orderBy('names')
            ->limit($limit)
            ->get();
    }

    public function partnersWithPeople(): Collection
    {
        return Partner::select(['id', 'person_id'])->with('people:id,names,father_lastname')->get();
    }

    public function activeResponsibles(bool $selectColumns = false): Collection
    {
        $query = Responsible::query();

        if ($selectColumns) {
            $query->select(['id', 'person_id', 'type', 'active'])
                ->with(['person:id,names,father_lastname,mother_lastname,dni']);
        } else {
            $query->with('person');
        }

        return $query->where('active', true)->get();
    }

    public function findResponsibleWithPerson(int $id): ?Responsible
    {
        return Responsible::with('person')->find($id);
    }

    public function associationsForFilter(): Collection
    {
        return Association::select(['id', 'name', 'code', 'address', 'state_id'])
            ->orderBy('name')
            ->get();
    }

    public function padronReportCatalogs(): array
    {
        return [
            'parentescos' => Relationship::orderBy('id')->get(['id', 'title'])->toArray(),
            'tipo_beneficios' => TypeBenefit::orderBy('id')->get(['id', 'title', 'abbreviation'])->toArray(),
            'bajas' => ReasonDisqualification::orderBy('id')->get(['id', 'title'])->toArray(),
        ];
    }

    public function associationsWithAddress(): Collection
    {
        return Association::select(['id', 'name', 'code', 'address'])->orderBy('name')->get();
    }

    public function relationshipsByTitles(array $titles): Collection
    {
        return Relationship::select(['id', 'title'])->whereIn('title', $titles)->orderBy('title')->get();
    }

    public function typeBenefitsByAbbreviations(array $abbreviations): Collection
    {
        return TypeBenefit::select(['id', 'title', 'abbreviation'])
            ->whereIn('abbreviation', $abbreviations)
            ->orderBy('priority')
            ->get();
    }

    public function partnersForSelect(): Collection
    {
        return Partner::select(['id', 'person_id'])
            ->with('people:id,names,father_lastname')
            ->orderBy('id')
            ->get();
    }

    public function positionIdByTitle(string $title, bool $like = false): ?int
    {
        return Position::where('title', $like ? 'like' : '=', $like ? "%{$title}%" : $title)->value('id');
    }

    public function associationStates(): Collection
    {
        return State::forAssociations()->get(['id', 'title', 'abbreviation']);
    }

    public function typePremises(): Collection
    {
        return TypePremises::select(['id', 'title'])->get();
    }

    public function findStateOrFail(int $id): State
    {
        return State::findOrFail($id);
    }

    public function stateByAbbreviationOrFail(string $abbreviation): State
    {
        return State::where('abbreviation', $abbreviation)->firstOrFail();
    }

    public function partnerWithPeople(int $id): ?Partner
    {
        return Partner::with('people')->find($id);
    }

    public function rosterPeriodsFor(SupportCollection $associationIds): SupportCollection
    {
        return AssociationRosterPeriod::query()
            ->whereIn('association_id', $associationIds)
            ->with('presidentPartner.people:id,dni')
            ->orderBy('period')
            ->get(['association_id', 'period', 'president_name', 'president_partner_id']);
    }
}
