<?php

namespace App\Repositories\Contracts;

use Illuminate\Database\Eloquent\Collection;

/**
 * Consultas del generador de reportes de padrones. Cada método devuelve los
 * modelos con sus relaciones ya cargadas; el armado de filas es del servicio.
 */
interface ReportRepositoryInterface
{
    public function placeSectors(): Collection;

    public function associationStates(): Collection;

    public function productStates(): Collection;

    public function associations(): Collection;

    public function relationships(): Collection;

    public function typeTransactions(): Collection;

    /** Comités con presidenta hidratada, por código. Filtros: place_sector_id, state_id. */
    public function committees(array $filters): Collection;

    /** Filtros: relationship_id, association_id. */
    public function beneficiaries(array $filters): Collection;

    /** Filtro: state_id. */
    public function products(array $filters): Collection;

    /** Filtros: association_id, date_from, date_to. */
    public function pecosas(array $filters): Collection;

    /** Filtros: type_transaction_id, date_from, date_to. */
    public function transactions(array $filters): Collection;
}
