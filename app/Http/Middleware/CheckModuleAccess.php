<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class CheckModuleAccess
{
    public function handle(Request $request, Closure $next, $moduleSlug)
    {
        $user = Auth::user();

        if (!$user) {
            if ($request->expectsJson()) {
                return response()->json(['message' => 'No autenticado'], 401);
            }

            return redirect('/login');
        }

        if ($user->isAdmin()) {
            return $next($request);
        }

        // Delegates to User::canAccessModule() which uses a per-request
        // cached permissions query (single JOIN, loaded once per request).
        if (!$user->canAccessModule($moduleSlug)) {
            if ($request->expectsJson()) {
                return response()->json(['message' => 'No tienes acceso a este módulo'], 403);
            }

            return redirect()->route('dashboard')->with('error', 'No tienes acceso a este módulo');
        }

        return $next($request);
    }
}