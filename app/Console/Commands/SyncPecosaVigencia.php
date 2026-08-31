<?php

namespace App\Console\Commands;

use App\Models\Pecosa;
use Illuminate\Console\Command;

class SyncPecosaVigencia extends Command
{
    protected $signature = 'pecosas:sync-vigencia';

    protected $description = 'Marca como VIGENTE o VENCIDA cada PECOSA según su período de repartición efectivo';

    public function handle(): int
    {
        $changed = Pecosa::syncVigenciaStates();
        $this->info("PECOSAs actualizadas: {$changed}");

        return self::SUCCESS;
    }
}
