<?php

namespace App\Services;

use App\Events\ResponsiblePeriodEnded;
use App\Models\Responsible;
use App\Repositories\Contracts\PersonRepositoryInterface;
use App\Repositories\Contracts\ResponsibleRepositoryInterface;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

/**
 * Responsables de almacén: vigentes, historial y cambio de periodo.
 */
class ResponsibleService
{
    public function __construct(
        private ResponsibleRepositoryInterface $responsibles,
        private PersonRepositoryInterface $people
    ) {
    }

    public function all(): Collection
    {
        return $this->responsibles->allWithPerson();
    }

    /** Jefe y almacenero vigentes y las personas elegibles. */
    public function current(): array
    {
        $active = $this->responsibles->activeWithPerson();

        return [
            'chief' => $active->firstWhere('type', 'chief'),
            'storekeeper' => $active->firstWhere('type', 'storekeeper'),
            'people' => $this->people->allByName(),
        ];
    }


    public function history(array $filters, int $perPage): LengthAwarePaginator
    {
        return $this->responsibles->paginateHistory($filters, $perPage);
    }

    public function detail(Responsible $responsible): Responsible
    {
        return $this->responsibles->loadPersonWithLocation($responsible);
    }

    /**
     * Cierra el periodo del responsable vigente del tipo y abre uno nuevo.
     */
    public function replace(string $type, int $personId): Responsible
    {
        $responsible = DB::transaction(function () use ($type, $personId) {
            foreach ($this->responsibles->activeOfType($type) as $previous) {
                $this->responsibles->update($previous, ['active' => false, 'end_date' => now()]);
                event(new ResponsiblePeriodEnded($previous, 'replaced'));
            }

            return $this->responsibles->create([
                'person_id' => $personId,
                'type' => $type,
                'active' => true,
                'start_date' => now(),
            ]);
        });

        return $this->responsibles->loadPerson($responsible);
    }

    /**
     * Finaliza manualmente el periodo de un responsable vigente sin asignar reemplazo.
     *
     * @throws ValidationException si el responsable ya no está vigente
     */
    public function endPeriod(Responsible $responsible): Responsible
    {
        if (! $responsible->active) {
            throw ValidationException::withMessages([
                'responsible' => 'Este responsable ya no está vigente.',
            ]);
        }

        $this->responsibles->update($responsible, ['active' => false, 'end_date' => now()]);
        event(new ResponsiblePeriodEnded($this->responsibles->loadPerson($responsible), 'manual'));

        return $responsible;
    }
}
