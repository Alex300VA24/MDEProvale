<?php

namespace App\Repositories\Contracts;

use App\Models\Racion;
use Illuminate\Database\Eloquent\Collection;

/**
 * Raciones por año y periodo.
 */
interface RacionRepositoryInterface
{
    /** Año más reciente primero y, dentro del año, por mes de inicio. */
    public function allOrdered(): Collection;

    public function create(array $data): Racion;

    public function update(Racion $racion, array $data): bool;

    public function delete(Racion $racion): bool;
}
