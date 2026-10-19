<?php

namespace App\Console\Commands;

use App\Services\Alertas\VencimientoAlertService;
use Illuminate\Console\Command;

class AlertasVencimientosCommand extends Command
{
    protected $signature = 'alertas:vencimientos';

    protected $description = 'Genera avisos de beneficiarios por superar el límite de edad y resoluciones de comités por vencer (IA opcional)';

    public function handle(VencimientoAlertService $alerts): int
    {
        $result = $alerts->scanAndNotify();

        $this->info(sprintf(
            'Avisos de edad: %d · de resoluciones: %d · con complemento IA: %d',
            $result['edad'],
            $result['resoluciones'],
            $result['con_ia'],
        ));

        return self::SUCCESS;
    }
}
