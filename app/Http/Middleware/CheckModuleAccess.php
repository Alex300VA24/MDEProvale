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
            return redirect('/login');
        }

        // Delegates to User::canAccessModule() which uses a per-request
        // cached permissions query (single JOIN, loaded once per request).
        if (!$user->canAccessModule($moduleSlug)) {
            return redirect()->route('dashboard')->with('error', 'No tienes acceso a este módulo');
        }

        return $next($request);
    }
}