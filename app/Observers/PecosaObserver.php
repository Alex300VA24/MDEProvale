<?php

namespace App\Observers;

use App\Models\Pecosa;
use App\Models\State;

class PecosaObserver
{
    /**
     * Al registrarse una PECOSA nueva, la PECOSA del período de repartición
     * inmediatamente anterior del mismo comité deja de estar vigente y pasa a
     * figurar como VENCIDA automáticamente.
     */
    public function created(Pecosa $pecosa): void
    {
        $pecosa->expirePreviousPeriodSiblings();
    }

    /**
     * Si se corrige la fecha de entrega o el comité de una PECOSA existente,
     * se vuelve a evaluar el desplazamiento de vigencia del período anterior.
     */
    public function updated(Pecosa $pecosa): void
    {
        if ($pecosa->wasChanged(['delivery_date', 'association_id'])) {
            $pecosa->expirePreviousPeriodSiblings();
        }
    }

    /**
     * Toda PECOSA nace vigente salvo que se indique explícitamente lo contrario.
     */
    public function creating(Pecosa $pecosa): void
    {
        if ($pecosa->state_id === null) {
            $pecosa->state_id = State::idFor(State::CURRENT);
        }
    }
}
