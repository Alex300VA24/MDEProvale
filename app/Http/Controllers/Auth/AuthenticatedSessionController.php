<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Http\Requests\Auth\LoginRequest;
use App\Providers\RouteServiceProvider;
use Illuminate\Http\Response;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class AuthenticatedSessionController extends Controller
{
    /**
     * Display the login view.
     *
     * @return \Illuminate\View\View
     */
public function create(Request $request)
    {
        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return response()
            ->view('auth.login')
            ->header('Cache-Control', 'no-store, no-cache, must-revalidate, max-age=0')
            ->header('Pragma', 'no-cache')
            ->header('Expires', 'Sat, 01 Jan 2000 00:00:00 GMT');
    }

    /**
     * Handle an incoming authentication request.
     *
     * @param  \App\Http\Requests\Auth\LoginRequest  $request
     * @return \Illuminate\Http\RedirectResponse
     */
public function store(LoginRequest $request)
    {
        $request->authenticate();

        $request->session()->regenerate();

        $intendedUrl = $request->session()->get('url.intended');
        if ($intendedUrl && $this->shouldIgnoreIntendedUrl($intendedUrl)) {
            $request->session()->forget('url.intended');
        }

        if ($request->session()->has('session_just_expired') || $request->get('expired') == 1) {
            $request->session()->forget('session_just_expired');
            return redirect()->route('dashboard');
        }

        return redirect()->intended(RouteServiceProvider::HOME);
    }

    /**
     * Destroy an authenticated session.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\RedirectResponse
     */
    public function destroy(Request $request)
    {
        Auth::guard('web')->logout();

        $request->session()->invalidate();

        $request->session()->regenerateToken();

        return redirect()->route('login');
    }

    private function shouldIgnoreIntendedUrl(string $url): bool
    {
        $path = parse_url($url, PHP_URL_PATH) ?? '';

        return str_contains($path, '/sistema/notifications/count/unread');
    }
}
