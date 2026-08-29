<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Http\Requests\Auth\LoginRequest;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\ValidationException;

class PresidentAuthenticatedSessionController extends Controller
{
    public function create(Request $request)
    {
        if ($request->user()?->isPresidentPortalUser()) {
            return redirect()->route('president-portal.index');
        }

        return response()->view('portal-presidentas.login')->withHeaders([
            'Cache-Control' => 'no-store, no-cache, must-revalidate, max-age=0',
            'Pragma' => 'no-cache',
        ]);
    }

    public function store(LoginRequest $request)
    {
        $candidate = User::with('rol')->where('username', $request->string('username'))->first();
        if ($candidate && !$candidate->isPresidentPortalUser()) {
            throw ValidationException::withMessages([
                'username' => 'Este acceso es exclusivo para Socias Presidentas.',
            ]);
        }

        $request->authenticate();
        $request->session()->regenerate();

        if ($request->user()->mustChangePassword()) {
            return redirect()->route('president.password.change');
        }

        return redirect()->intended(route('president-portal.index'));
    }

    public function destroy(Request $request)
    {
        Auth::logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect()->route('president.login');
    }
}
