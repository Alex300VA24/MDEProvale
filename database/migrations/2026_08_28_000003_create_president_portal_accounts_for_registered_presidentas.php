<?php

use App\Models\Directive;
use App\Models\State;
use App\Services\PresidentAccountService;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Crea las cuentas de portal de las presidentas ya registradas antes de
     * esta implementación: usuario y contraseña inicial = DNI, con obligación
     * de cambio en el primer ingreso.
     */
    public function up(): void
    {
        if (!Schema::hasTable('directives') || !Schema::hasTable('users')) {
            return;
        }

        $directives = Directive::query()
            ->whereHas('position', fn ($query) => $query->where('title', 'like', '%PRESIDENTA%'))
            ->whereHas('state', fn ($query) => $query->where('abbreviation', State::CURRENT))
            ->with('partner.people')
            ->latest('date_start')
            ->get()
            ->unique('partner_id');

        $service = app(PresidentAccountService::class);

        foreach ($directives as $directive) {
            if ($directive->partner) {
                $service->sync($directive->partner);
            }
        }
    }

    public function down(): void
    {
        // No se revierten cuentas ya creadas por ser una operación de datos.
    }
};