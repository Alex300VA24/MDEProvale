<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;

class EnsureAdminRole
{
    public function handle(Request $request, Closure $next)
    {
        if (!$request->user()?->isAdmin()) {
            if ($request->expectsJson()) {
                return response()->json(['message' => 'Esta opción está disponible solo para el administrador.'], 403);
            }

            return redirect()->route('dashboard')->with('error', 'Esta opción está disponible solo para el administrador.');
        }

        return $next($request);
    }
}
