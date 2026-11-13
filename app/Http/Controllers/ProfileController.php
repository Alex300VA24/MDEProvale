<?php

namespace App\Http\Controllers;

use App\Http\Requests\UpdateProfileRequest;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Hash;

class ProfileController extends Controller
{
    public function show(): JsonResponse
    {
        $user = auth()->user()->loadMissing('rol');

        return response()->json(['data' => $this->payload($user)]);
    }

    public function update(UpdateProfileRequest $request): JsonResponse
    {
        $user = $request->user();
        $data = $request->safe()->only(['names', 'father_surname', 'mother_surname', 'email', 'dni']);

        if ($request->filled('password')) {
            $data['password'] = Hash::make($request->input('password'));
        }

        $user->update($data);

        return response()->json([
            'message' => 'Perfil actualizado correctamente.',
            'data' => $this->payload($user->fresh('rol')),
        ]);
    }

    private function payload($user): array
    {
        return [
            'username' => $user->username,
            'rol' => $user->rol->title ?? null,
            'names' => $user->names,
            'father_surname' => $user->father_surname,
            'mother_surname' => $user->mother_surname,
            'email' => $user->email,
            'dni' => $user->dni,
        ];
    }
}
