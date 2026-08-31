<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Inertia\Inertia;

class CheckSessionExpired
{
    /**
     * Handle an incoming request.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \Closure  $next
     * @return mixed
     */
    public function handle(Request $request, Closure $next)
    {
        if ($this->isPublicSessionRoute($request)) {
            return $next($request);
        }

        $isAuthenticated = auth()->check();

        if ($isAuthenticated) {
            $user = auth()->user();

            if ($user->remember_token) {
                $request->session()->put('had_remember_token', true);
            }

            $request->session()->put('user_was_authenticated', true);
            return $next($request);
        }

        if ($request->session()->has('user_was_authenticated')) {
            $loginRoute = $request->is('portal-presidentas*') ? 'president.login' : 'login';
            $request->session()->forget('user_was_authenticated');
            $request->session()->put('session_just_expired', true);

            if ($request->inertia()) {
                return Inertia::location(route($loginRoute, ['expired' => 1]));
            }

            if ($request->expectsJson() || $request->ajax()) {
                return response()->json([
                    'message' => 'Tu sesión ha expirado. Por favor, inicia sesión de nuevo.',
                    'session_expired' => true,
                    'redirect' => route($loginRoute, ['expired' => 1]),
                ], 401);
            }

            $request->session()->flash('session_expired', true);
            $request->session()->invalidate();
            $request->session()->regenerateToken();

            return redirect()->route($loginRoute, ['expired' => 1]);
        }

        return $next($request);
    }

    private function isPublicSessionRoute(Request $request): bool
    {
        return $request->routeIs(
            'login',
            'president.login',
            'president.login.store',
            'documents.*',
            'password.request',
            'password.email',
            'password.reset',
            'password.update',
            'password-reset-request',
            'register'
        ) || $request->is('/', 'refresh-csrf');
    }
}
