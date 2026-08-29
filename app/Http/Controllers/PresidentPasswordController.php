<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\ValidationException;

class PresidentPasswordController extends Controller
{
    public function edit(Request $request)
    {
        return view('portal-presidentas.cambiar-contrasena');
    }

    public function update(Request $request)
    {
        $user = $request->user();

        $validated = $request->validate([
            'current_password' => ['required', 'current_password'],
            'password'         => ['required', 'string', 'min:8', 'confirmed'],
        ]);

        if (trim((string) $user->dni) === trim((string) $request->input('password'))) {
            throw ValidationException::withMessages([
                'password' => 'La nueva contraseña no puede ser igual a tu DNI.',
            ]);
        }

        $user->forceFill([
            'password'             => Hash::make($validated['password']),
            'must_change_password' => false,
        ])->save();

        return redirect()->route('president-portal.index')
            ->with('success', 'Contraseña actualizada correctamente.');
    }
}