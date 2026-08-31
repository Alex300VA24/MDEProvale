<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Inertia\Inertia;

class EnsurePlatformAccess
{
    public function handle(Request $request, Closure $next)
    {
        $user = $request->user();

        if ($user && $user->isPresidentPortalUser()) {
            $url = route('president-portal.index');

            if ($request->expectsJson()) {
                return $this->noStore(response()->json([
                    'message' => 'Esta sesión pertenece al Portal de Presidentas.',
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
