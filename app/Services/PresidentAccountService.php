<?php

namespace App\Services;

use App\Models\Partner;
use App\Models\Rol;
use App\Models\State;
use App\Models\User;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Log;

class PresidentAccountService
{
    /**
     * Garantiza que la socia presidenta tenga cuenta de acceso al Portal de
     * Presidentas.
     *
     * - Usuario = DNI
     * - Contraseña inicial = DNI (solo al crearse la cuenta por primera vez)
     * - Señaliza que debe cambiar su contraseña en el primer ingreso.
     */
    public function sync(Partner $partner): ?User
    {
        $people = $partner->people;

        if (!$people || !$people->dni) {
            Log::warning("PresidentAccountService: la socia {$partner->id} no tiene DNI registrado.");
            return null;
        }

        $dni = trim($people->dni);

        $presidentRol = Rol::firstOrCreate(
            ['title' => Rol::PRESIDENT],
            ['description' => 'Acceso exclusivo al portal de consultas del comité asignado', 'is_active' => true]
        );

        $activeState = State::whereIn('abbreviation', [State::ACTIVE, 'A'])
            ->orderBy('id')
            ->first();

        if (!$activeState) {
            Log::warning('PresidentAccountService: no existe un estado activo (ACT/A) para el usuario.');
            return null;
        }

        $data = [
            'names'          => (string) $people->names,
            'father_surname' => (string) $people->father_lastname,
            'mother_surname' => (string) $people->mother_lastname,
            'username'       => $dni,
            'email'          => $dni . '@presidentas.provale.local',
            'dni'            => $dni,
            'cui'            => '0',
            'state_id'       => $activeState->id,
            'rol_id'         => $presidentRol->id,
        ];

        $user = User::firstWhere('dni', $dni);

        if (!$user) {
            // No crear cuenta si el username (DNI) ya está ocupado por otra cuenta.
            if (User::where('username', $dni)->exists()) {
                Log::warning("PresidentAccountService: existe un usuario con username {$dni} sin coincidencia de DNI.");
                return null;
            }

            return User::create(array_merge($data, [
                'password'             => Hash::make($dni),
                'must_change_password' => true,
            ]));
        }

        if ($user->rol_id !== $presidentRol->id && !$user->isPresidentPortalUser()) {
            Log::warning("PresidentAccountService: el DNI {$dni} pertenece a una cuenta de otro rol, no se reasigna.");
            return null;
        }

        $user->update($data);

        return $user->fresh(['rol']);
    }
}