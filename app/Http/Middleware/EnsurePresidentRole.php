<?php

namespace App\Http\Middleware;

use App\Models\Rol;
use Closure;
use Illuminate\Http\Request;
use Inertia\Inertia;

class EnsurePresidentRole
{
    public function handle(Request $request, Closure $next)
    {
        $user = $request->user();

        if (!$user) {
            return redirect()->guest(route('president.login'));
        }

        $isPresident = $user->rol
            && $user->rol->is_active
            && mb_strtolower(trim($user->rol->title)) === mb_strtolower(Rol::PRESIDENT);

        if (! $isPresident) {
            $url = route('dashboard');

            if ($request->expectsJson()) {
                return $this->noStore(response()->json([
                    'message' => 'Esta sesión pertenece a la plataforma administrativa.',
                    'redirect' => $url,
                    'wrong_portal' => true,
                ], 409));
            }

            if ($request->inertia()) {
                return $this->noStore(Inertia::location($url));
            }

            return $this->noStore(redirect()->to($url));
        }

        return $this->noStore($next($request));
    }

    private function noStore($response)
    {
        $response->headers->set('Cache-Control', 'no-store, no-cache, must-revalidate, private');
        $response->headers->set('Pragma', 'no-cache');

        return $response;
    }
}
