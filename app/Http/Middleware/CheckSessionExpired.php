<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;

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
        if ($request->routeIs('login') || $request->is('login')) {
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
            $request->session()->forget('user_was_authenticated');
            $request->session()->put('session_just_expired', true);

            if ($request->expectsJson() || $request->ajax()) {
                return response()->json([
                    'message' => 'Tu sesión ha expirado. Por favor, inicia sesión de nuevo.',
                    'session_expired' => true,
                    'redirect' => route('login'),
                ], 401);
            }

            $request->session()->flash('session_expired', true);
            $request->session()->invalidate();
            $request->session()->regenerateToken();

            return redirect()->route('login', ['expired' => 1]);
        }

        return $next($request);
    }
}
