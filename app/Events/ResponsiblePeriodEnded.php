<?php

namespace App\Events;

use App\Models\Responsible;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

/**
 * Se dispara cuando el periodo de un responsable de almacén (chief /
 * storekeeper) llega a su fin: ya sea porque se asignó a otra persona en su
 * lugar o porque un usuario finalizó el periodo manualmente.
 */
class ResponsiblePeriodEnded
{
    use Dispatchable;
    use SerializesModels;

    public Responsible $responsible;

    /** Cómo terminó el periodo: 'replaced' (reemplazo) o 'manual'. */
    public string $reason;

    public function __construct(Responsible $responsible, string $reason = 'replaced')
    {
        $this->responsible = $responsible;
        $this->reason = $reason;
    }
}
