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
        // Si el usuario estaba autenticado antes y ahora no lo está, la sesión expiró.
        if ($request->session()->has('user_was_authenticated') && !auth()->check()) {
            $request->session()->forget('user_was_authenticated');

            if ($request->expectsJson() || $request->ajax()) {
                return response()->json([
                    'message' => 'Tu sesión ha expirado. Por favor, inicia sesión de nuevo.',
                    'session_expired' => true,
                    'redirect' => route('login'),
                ], 401);
            }

            return redirect()->route('login')->with('session_expired', true);
        }

        // Guardar que el usuario estaba autenticado
        if (auth()->check()) {
            $request->session()->put('user_was_authenticated', true);
        }

        return $next($request);
    }
}
