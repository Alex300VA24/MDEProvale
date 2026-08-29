<?php

namespace App\Http\Middleware;

use App\Models\Rol;
use Closure;
use Illuminate\Http\Request;

class EnsurePresidentRole
{
    public function handle(Request $request, Closure $next)
    {
        $user = $request->user();

        if (!$user) {
            return redirect()->guest(route('president.login'));
        }

        abort_unless($user->rol && $user->rol->is_active, 403);
        abort_unless(mb_strtolower(trim($user->rol->title)) === mb_strtolower(Rol::PRESIDENT), 403);

        return $next($request);
    }
}
