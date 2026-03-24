<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use App\Models\Module;
use Illuminate\Support\Facades\DB;

class CheckModuleAccess
{
    public function handle(Request $request, Closure $next, $moduleSlug)
    {
        $user = Auth::user();
        
        if (!$user) {
            return redirect('/login');
        }

        $module = Module::where('slug', $moduleSlug)->first();
        
        if (!$module) {
            abort(404);
        }

        $hasAccess = DB::table('module_rol')
            ->where('module_id', $module->id)
            ->where('rol_id', $user->rol_id)
            ->where('can_view', true)
            ->exists();

        if (!$hasAccess) {
            return redirect()->route('dashboard')->with('error', 'No tienes acceso a este módulo');
        }

        return $next($request);
    }
}