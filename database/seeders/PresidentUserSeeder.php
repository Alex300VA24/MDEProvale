<?php

namespace Database\Seeders;

use App\Models\Directive;
use App\Models\State;
use App\Services\PresidentAccountService;
use Illuminate\Database\Seeder;

class PresidentUserSeeder extends Seeder
{
    /**
     * Crea/actualiza las cuentas de portal de las presidentas ya registradas:
     * usuario y contraseña inicial = DNI, con obligación de cambio en el primer ingreso.
     *
     * @return void
     */
    public function run()
    {
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
}