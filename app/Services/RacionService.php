<?php

namespace App\Services;

use App\Models\Racion;
use App\Repositories\Contracts\RacionRepositoryInterface;
use Illuminate\Database\Eloquent\Collection;

/**
 * Raciones de hojuelas y leche por año.
 */
class RacionService
{
    public function __construct(private RacionRepositoryInterface $raciones)
    {
    }

    public function all(): Collection
    {
        return $this->raciones->allOrdered();
    }

    public function create(array $data): Racion
    {
        return $this->raciones->create([...$data, 'active' => true]);
    }

    public function update(Racion $racion, array $data): Racion
    {
        $this->raciones->update($racion, $data);

        return $racion;
    }

    public function delete(Racion $racion): void
    {
        $this->raciones->delete($racion);
    }
}
