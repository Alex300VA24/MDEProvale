<?php

namespace App\Observers;

use App\Models\Pecosa;

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
     * Estado siempre calculado por período: VIGENTE para mes actual/futuro y
     * VENCIDO para meses anteriores. Nunca ACTIVO/INACTIVO.
     */
    public function saving(Pecosa $pecosa): void
    {
        $stateId = Pecosa::stateIdForDeliveryDate($pecosa->delivery_date);

        if ($stateId !== null) {
            $pecosa->state_id = $stateId;
        }
    }
}
