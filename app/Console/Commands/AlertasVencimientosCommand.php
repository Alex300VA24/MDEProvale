<?php

namespace App\Console\Commands;

use App\Services\Alertas\VencimientoAlertService;
use Illuminate\Console\Command;

class AlertasVencimientosCommand extends Command
{
    protected $signature = 'alertas:vencimientos';

    protected $description = 'Genera avisos de edad, término de beneficios y resoluciones por vencer (IA opcional)';

    public function handle(VencimientoAlertService $alerts): int
    {
        $result = $alerts->scanAndNotify();

        $this->info(sprintf(
            'Avisos de edad: %d · de beneficios: %d · de resoluciones: %d · con complemento IA: %d',
            $result['edad'],
            $result['beneficios'],
            $result['resoluciones'],
            $result['con_ia'],
        ));

        return self::SUCCESS;
    }
}
