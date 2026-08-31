<?php

namespace App\Listeners;

use App\Events\ResponsiblePeriodEnded;
use Illuminate\Support\Facades\Log;

class LogResponsiblePeriodEnded
{
    public function handle(ResponsiblePeriodEnded $event): void
    {
        $responsible = $event->responsible->loadMissing('person');

        Log::channel(config('logging.default'))->info('Periodo de responsable finalizado', [
            'responsible_id' => $responsible->id,
            'type' => $responsible->type,
            'person_id' => $responsible->person_id,
            'person_dni' => $responsible->person->dni ?? null,
            'start_date' => optional($responsible->start_date)->toDateTimeString(),
            'end_date' => optional($responsible->end_date)->toDateTimeString(),
            'reason' => $event->reason,
        ]);
    }
}
