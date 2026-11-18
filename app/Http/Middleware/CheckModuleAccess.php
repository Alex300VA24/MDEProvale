<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

/**
 * Autoriza el acceso a un módulo o submódulo según los permisos del rol.
 *
 * Uso en rutas:
 *   module:club-madres                       acción deducida del método HTTP
 *   module:club-madres,edit                  acción explícita (view|create|edit|delete)
 *   module:productos|registro-ingreso        basta con tener acceso a uno de los submódulos
 *   module:comites-resoluciones              un grupo vale si el rol ve alguno de sus submódulos
 */
class CheckModuleAccess
{
    public function handle(Request $request, Closure $next, $moduleSlug, $ability = null)
    {
        $user = Auth::user();

        if (!$user) {
            if ($request->expectsJson()) {
                return response()->json(['message' => 'No autenticado'], 401);
            }

            return redirect()->guest(route('login'));
        }

        $ability = $ability ?: $this->abilityFor($request);

        $allowed = collect(explode('|', (string) $moduleSlug))
            ->map(fn (string $slug) => trim($slug))
            ->filter()
            ->contains(fn (string $slug) => $user->canModule($slug, $ability));

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
        // Los PDF de control interno usan POST solo por el tamaño del payload; no modifican datos.
        if (in_array($request->route()?->getName(), ['movimientos.ficha-control', 'movimientos.tarjeta-control'], true)) {
            return 'view';
        }

        return match ($request->method()) {
            'POST' => str_ends_with((string) $request->route()?->getName(), '.store') ? 'create' : 'edit',
            'PUT', 'PATCH' => 'edit',
            'DELETE' => 'delete',
            default => 'view',
        };
    }
}
