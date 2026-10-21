<?php

namespace App\Console\Commands;

use App\Services\BenefitExpiryService;
use Illuminate\Console\Command;

class ProcessBenefitExpiries extends Command
{
    protected $signature = 'beneficios:procesar-vencimientos';

    protected $description = 'Inactiva beneficios GES y LAC vencidos y registra auditoría';

    public function handle(BenefitExpiryService $service): int
    {
        $this->info('Beneficios inactivados: '.$service->process());

        return self::SUCCESS;
    }
}
