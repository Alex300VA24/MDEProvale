<?php

namespace App\Repositories\Contracts;

use Illuminate\Support\Collection;

/**
 * Lecturas puntuales (conteos y búsquedas) con las que el asistente responde consultas de datos.
 */
interface AssistantDataRepositoryInterface
{
    public function committeeCount(): int;

    public function committeeCountWithState(int $stateId): int;

    /** Comités con los campos mínimos para buscarlos por nombre o código. */
    public function committees(): Collection;

    /** Pecosas con fecha de entrega entre ambas fechas (Y-m-d), inclusive. */
    public function pecosaCountBetween(string $from, string $to): int;

    public function beneficiaryCount(): int;

    public function partnerCount(): int;

    public function partnerCountOfCommittee(int $committeeId): int;

    public function beneficiaryCountOfCommittee(int $committeeId): int;

    /** Socias titulares cuya identidad (nombre o DNI) coincide, con sus beneficiarios cargados. */
    public function partnersMatchingIdentity(string $identity): Collection;
}
