<?php

namespace App\Services;

use App\Models\Association;
use App\Models\DocumentAttachment;
use App\Repositories\Contracts\AssociationRepositoryInterface;
use App\Repositories\Contracts\SupervisionRepositoryInterface;
use Carbon\Carbon;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

/**
 * Supervisión mensual de comités: visitas, días sin supervisión y sus adjuntos.
 */
class SupervisionService
{
    public function __construct(
        private SupervisionRepositoryInterface $supervision,
        private AssociationRepositoryInterface $associations,
        private DocumentAttachmentService $attachments
    ) {
    }

    /**
     * Matriz mensual: un comité por fila, un día por columna.
     *
     * @param  callable(DocumentAttachment): array  $documentMapper  formato de cada adjunto
     * @return array{rows: \Illuminate\Support\Collection, off_days: array}
     */
    public function monthlyMatrix(int $year, int $month, callable $documentMapper): array
    {
        $start = Carbon::create($year, $month, 1)->startOfDay();
        $end = $start->copy()->endOfMonth();

        $visits = $this->supervision->visitsBetween($start->toDateString(), $end->toDateString());

        $rows = $this->associations->withResolutionAndLocation()->map(function (Association $association) use ($visits, $documentMapper) {
            $presidenta = $association->getPresidenta();
            $person = $presidenta?->people;
            $associationVisits = $visits[$association->id] ?? collect();

            return [
                'id' => $association->id,
                'code' => $association->code,
                'name' => $association->name,
                'president' => $person
                    ? trim("{$person->names} {$person->father_lastname} {$person->mother_lastname}")
                    : '',
                'address' => $association->address,
                'sector' => $association->placeSector
                    ? trim(($association->placeSector->place?->title ?? '').' '.($association->placeSector->sector?->title ?? ''))
                    : '',
                'beneficiaries' => $presidenta ? $this->associations->countBeneficiaries($association) : 0,
                'visits' => $associationVisits
                    ->mapWithKeys(fn ($v) => [$v->visit_date->format('Y-m-d') => $v->result])
                    ->all(),
                'visit_details' => $associationVisits
                    ->mapWithKeys(fn ($visit) => [$visit->visit_date->format('Y-m-d') => [
                        'id' => $visit->id,
                        'documents' => $visit->documents->map($documentMapper)->values(),
                    ]])->all(),
            ];
        })->values();

        $offDays = $this->supervision->offDaysBetween($start->toDateString(), $end->toDateString())
            ->mapWithKeys(fn ($d) => [$d->off_date->format('Y-m-d') => $d->reason ?? ''])
            ->all();

        return ['rows' => $rows, 'off_days' => $offDays];
    }

    /**
     * Marca un día SI / NO; sin resultado borra la marca. Devuelve el id de la visita o null.
     *
     * @throws ValidationException si la visita a limpiar aún tiene adjuntos
     */
    public function setVisit(Association $association, string $date, ?string $result, ?int $userId): ?int
    {
        if (empty($result)) {
            $visit = $this->supervision->findVisit($association->id, $date);
            if ($visit && $this->supervision->visitHasDocuments($visit)) {
                throw ValidationException::withMessages(['result' => 'Retire los adjuntos antes de limpiar la visita.']);
            }
            if ($visit) {
                $this->supervision->deleteVisit($visit);
            }

            return null;
        }

        return $this->supervision->upsertVisit($association->id, $date, $result, $userId)->id;
    }

    public function setOffDay(string $date, bool $off, ?string $reason, ?int $userId): void
    {
        if ($off) {
            $this->supervision->upsertOffDay($date, $reason, $userId);
        } else {
            $this->supervision->deleteOffDay($date);
        }
    }

    /**
     * @throws ValidationException si se supera el límite de adjuntos de ese tipo
     */
    public function attach(int $visitId, string $documentType, UploadedFile $file): DocumentAttachment
    {
        return DB::transaction(function () use ($visitId, $documentType, $file) {
            $visit = $this->supervision->lockVisit($visitId);
            $limit = $documentType === DocumentAttachment::TYPE_SUPERVISION_DOCUMENT ? 1 : 3;

            if ($this->supervision->countDocuments($visit, $documentType) >= $limit) {
                throw ValidationException::withMessages(['file' => $limit === 1 ? 'Solo se permite un documento escaneado.' : 'Solo se permiten tres fotografías.']);
            }

            return $this->attachments->store($visit, $documentType, $file);
        });
    }
}
