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
        if ($request->user()) {
            return redirect()->route(
                $request->user()->isPresidentPortalUser() ? 'president-portal.index' : 'dashboard'
            );
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
        $request->session()->forget(['session_just_expired', 'user_was_authenticated']);

        $intendedUrl = $request->session()->get('url.intended');
        if ($intendedUrl && ! $this->isPresidentPortalUrl($intendedUrl)) {
            $request->session()->forget('url.intended');
        }

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

    private function isPresidentPortalUrl(string $url): bool
    {
        $path = parse_url($url, PHP_URL_PATH) ?? '';

        return str_contains($path, '/portal-presidentas');
    }
}
