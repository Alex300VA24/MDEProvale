<?php

namespace App\Console\Commands;

use App\Services\Normativa\NormativaScraperService;
use Illuminate\Console\Command;

class NormativaScanPvlCommand extends Command
{
    protected $signature = 'normativa:scan-pvl';

    protected $description = 'Descubre normas nuevas en el portal municipal, clasifica su relevancia para el PVL con IA y notifica';

    public function handle(NormativaScraperService $scraper): int
    {
        $result = $scraper->scanAndNotify();

        $this->info(sprintf(
            'Normas descubiertas: %d · clasificadas: %d · relevantes notificadas: %d',
            $result['descubiertos'],
            $result['clasificados'],
            $result['relevantes'],
        ));

        return self::SUCCESS;
    }
}
