<?php

namespace Database\Seeders;

use App\Models\Directive;
use App\Models\Rol;
use App\Models\State;
use App\Models\User;
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
        $today = now()->toDateString();
        $directives = Directive::query()
            ->whereHas('position', fn ($query) => $query->where('title', 'like', '%PRESIDENTA%'))
            ->whereHas('state', fn ($query) => $query->where('abbreviation', State::CURRENT))
            ->where(fn ($query) => $query->whereNull('date_start')->orWhereDate('date_start', '<=', $today))
            ->where(fn ($query) => $query->whereNull('date_end')->orWhereDate('date_end', '>=', $today))
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

        $currentPresidentDnis = $directives
            ->pluck('partner.people.dni')
            ->filter()
            ->unique()
            ->values();
        $presidentRoleId = Rol::where('title', Rol::PRESIDENT)->value('id');
        $inactiveStateId = State::idFor(State::INACTIVE);

        if ($presidentRoleId && $inactiveStateId) {
            User::where('rol_id', $presidentRoleId)
                ->when(
                    $currentPresidentDnis->isNotEmpty(),
                    fn ($query) => $query->whereNotIn('dni', $currentPresidentDnis),
                    fn ($query) => $query
                )
                ->update(['state_id' => $inactiveStateId, 'updated_at' => now()]);
        }
    }
}
