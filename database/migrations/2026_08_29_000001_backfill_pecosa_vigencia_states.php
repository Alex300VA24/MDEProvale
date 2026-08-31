<?php

use App\Models\Pecosa;
use Illuminate\Database\Migrations\Migration;

return new class extends Migration
{
    /**
     * Respaldo inicial: reclasifica todas las PECOSAs existentes en VIGENTE o
     * VENCIDA según su período de repartición efectivo. A partir de aquí el
     * estado se mantiene con PecosaObserver y el comando pecosas:sync-vigencia.
     */
    public function up(): void
    {
        Pecosa::syncVigenciaStates();
    }

    public function down(): void
    {
        // Reclasificación de datos: no se revierte.
    }
};
