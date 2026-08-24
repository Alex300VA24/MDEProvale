<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class CheckModuleAccess
{
    public function handle(Request $request, Closure $next, $moduleSlug, $ability = null)
    {
        $user = Auth::user();

        if (!$user) {
            if ($request->expectsJson()) {
                return response()->json(['message' => 'No autenticado'], 401);
            }

            return redirect('/login');
        }

        $ability = $ability ?: $this->abilityFor($request);
        $allowed = match ($ability) {
            'create' => $user->canCreateModule($moduleSlug),
            'edit' => $user->canEditModule($moduleSlug),
            'delete' => $user->canDeleteModule($moduleSlug),
            default => $user->canAccessModule($moduleSlug),
        };

        if (!$allowed) {
            if ($request->expectsJson()) {
                return response()->json(['message' => 'No tienes permiso para realizar esta acción'], 403);
            }

            return redirect()->route('dashboard')->with('error', 'No tienes permiso para realizar esta acción');
        }

        return $next($request);
    }

    private function abilityFor(Request $request): string
    {
        return match ($request->method()) {
            'POST' => str_ends_with((string) $request->route()?->getName(), '.store') ? 'create' : 'edit',
            'PUT', 'PATCH' => 'edit',
            'DELETE' => 'delete',
            default => 'view',
        };
    }
}
