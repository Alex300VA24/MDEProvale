<?php

namespace App\Repositories\Contracts;

use App\Models\Responsible;
use Illuminate\Database\Eloquent\Collection;

/**
 * Consultas de solo lectura de catálogos usados por formularios y filtros.
 */
interface CatalogRepositoryInterface
{
    public function associations(array $columns = ['id', 'name']): Collection;

    /** Asociaciones con estado VIGENTE; si no existe ese estado devuelve todas. */
    public function currentAssociations(array $columns = ['*']): Collection;

    public function temporalStates(array $columns = ['*']): Collection;

    public function uoms(array $columns = ['*']): Collection;

    public function products(array $columns = ['id', 'title', 'abbreviation']): Collection;

    public function typeTransactions(): Collection;

    public function relationships(array $columns = ['*']): Collection;

    public function placeSectors(array $columns = ['*'], array $with = ['place:id,title', 'sector:id,title']): Collection;

    public function typeBenefits(array $columns = ['id', 'title', 'abbreviation']): Collection;

    public function reasonDisqualifications(array $columns = ['id', 'title']): Collection;

    public function recentPeople(int $limit = 100): Collection;

    public function peopleByName(int $limit = 1000): Collection;

    public function partnersWithPeople(): Collection;

    public function activeResponsibles(bool $selectColumns = false): Collection;

    public function findResponsibleWithPerson(int $id): ?Responsible;

    /** Todos los comités (id, name, code, address, state_id) por nombre, para filtros. */
    public function associationsForFilter(): Collection;

    /** Id del cargo por título exacto, o por coincidencia parcial si `$like`. */
    public function positionIdByTitle(string $title, bool $like = false): ?int;

    /** Períodos de directiva mensual de los comités indicados, por fecha. */
    public function rosterPeriodsFor(\Illuminate\Support\Collection $associationIds): \Illuminate\Support\Collection;
}
