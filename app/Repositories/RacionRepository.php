<?php

namespace App\Repositories;

use App\Models\Racion;
use App\Repositories\Contracts\RacionRepositoryInterface;
use Illuminate\Database\Eloquent\Collection;

class RacionRepository implements RacionRepositoryInterface
{
    public function allOrdered(): Collection
    {
        return Racion::orderBy('year', 'desc')
            ->orderBy('month_start', 'asc')
            ->get();
    }

    public function create(array $data): Racion
    {
        return Racion::create($data);
    }

    public function update(Racion $racion, array $data): bool
    {
        return $racion->update($data);
    }

    public function delete(Racion $racion): bool
    {
        return (bool) $racion->delete();
    }
}
