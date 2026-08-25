<?php

namespace App\Console\Commands;

use App\Services\AssociationStateService;
use App\Services\ResolutionStateService;
use Illuminate\Console\Command;

class SyncAssociationStates extends Command
{
    protected $signature = 'states:sync-associations';

    protected $description = 'Actualiza resoluciones y asociaciones según sus fechas de vigencia';

    public function handle(AssociationStateService $associationStates, ResolutionStateService $resolutionStates): int
    {
        $resolutionsUpdated = $resolutionStates->syncAll();
        $associationsUpdated = $associationStates->syncAll();
        $this->info("Resoluciones actualizadas: {$resolutionsUpdated}");
        $this->info("Asociaciones actualizadas: {$associationsUpdated}");

        return self::SUCCESS;
    }
}
