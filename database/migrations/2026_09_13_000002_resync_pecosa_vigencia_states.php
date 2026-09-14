<?php

use App\Models\Pecosa;
use Illuminate\Database\Migrations\Migration;

return new class extends Migration
{
    /**
     * Corrige PECOSAs existentes que conservaron ACT/INA desde SQL Server.
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
