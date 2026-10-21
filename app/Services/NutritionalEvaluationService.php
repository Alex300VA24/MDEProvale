<?php

namespace App\Services;

use App\Models\Association;
use App\Models\Beneficiarie;
use App\Repositories\Contracts\AssociationRepositoryInterface;
use Illuminate\Database\Eloquent\Collection;

/**
 * Evaluación nutricional: padrón de beneficiarios de un comité para el acta de control.
 */
class NutritionalEvaluationService
{
    public function __construct(private AssociationRepositoryInterface $associations)
    {
    }

    public function committees(): Collection
    {
        return $this->associations->withResolutionForSelect();
    }

    /**
     * Datos del acta: comité, sector, presidenta y filas de beneficiarios.
     */
    public function actaData(Association $association): array
    {
        $association = $this->associations->findWithPlaceSector($association->id) ?? $association;
        $presidenta = $association->getPresidenta()?->people;

        return [
            'association' => $association,
            'sector' => $association->placeSector
                ? trim(($association->placeSector->place?->title ?? '').' '.($association->placeSector->sector?->title ?? ''))
                : '',
            'presidenta' => $presidenta
                ? trim("{$presidenta->names} {$presidenta->father_lastname} {$presidenta->mother_lastname}")
                : '',
            'filas' => $this->rows($association),
            'generado' => now()->format('d/m/Y H:i'),
        ];
    }

    /** Beneficiarios ordenados por apellidos, con los datos de la ficha. */
    public function rows(Association $association): array
    {
        $beneficiaries = $this->associations->beneficiariesWithHistory($association)
            ->sortBy(fn ($b) => mb_strtoupper(trim(($b->person?->father_lastname ?? '').' '.($b->person?->mother_lastname ?? '').' '.($b->person?->names ?? ''))))
            ->values();

        $n = 0;

        return $beneficiaries->map(function (Beneficiarie $b) use (&$n) {
            $person = $b->person;
            $history = $b->histories->first();
            $age = $person ? $person->getAgeArray() : null;

            return [
                'numero' => ++$n,
                'name' => $person
                    ? mb_strtoupper(trim("{$person->father_lastname} {$person->mother_lastname}, {$person->names}"), 'UTF-8')
                    : 'SIN NOMBRE',
                'dni' => $person->dni ?? '',
                'gender' => $person->gender ?? '',
                'birthdate' => $person?->birthdate ? date('d/m/Y', strtotime($person->birthdate)) : '',
                'age' => $age ? ($age['years'] > 0 ? "{$age['years']} a {$age['months']} m" : "{$age['months']} m") : '',
                'population_group' => $age && $age['years'] > 65 ? 'ADULTO_MAYOR' : null,
                'relationship' => $b->relationship?->title ?? '',
                'weight' => $history && $history->weight > 0 ? (float) $history->weight : null,
                'height' => $history && $history->height > 0 ? (float) $history->height : null,
                'is_malnourished' => (bool) ($history?->is_malnourished),
                'is_disabled' => (bool) ($history?->is_disabled),
            ];
        })->all();
    }
}
