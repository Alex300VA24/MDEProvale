<?php

namespace App\Http\Middleware;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Inertia\Middleware;

class HandleInertiaRequests extends Middleware
{
    /**
     * The root template that's loaded on the first page visit.
     *
     * @var string
     */
    protected $rootView = 'app';

    /**
     * Determines the current asset version.
     */
    public function version(Request $request): ?string
    {
        return parent::version($request);
    }

    /**
     * Defines the props that are shared by default.
     */
    public function share(Request $request): array
    {
        $user = $request->user();

        // Módulos permitidos para el rol del usuario (misma lógica que
        // User::getModulePermissions(): single JOIN sobre module_rol + modules).
        $modules = [];
        if ($user) {
            $modules = DB::table('module_rol')
                ->join('modules', 'modules.id', '=', 'module_rol.module_id')
                ->where('module_rol.rol_id', $user->rol_id)
                ->where('modules.is_active', true)
                ->orderBy('modules.order')
                ->select(
                    'modules.slug',
                    'modules.name',
                    'modules.description',
                    'modules.icon',
                    'modules.route',
                    'modules.order',
                    'module_rol.can_view',
                    'module_rol.can_create',
                    'module_rol.can_edit',
                    'module_rol.can_delete'
                )
                ->get();
        }

        return array_merge(parent::share($request), [
            'auth' => [
                'user' => $user ? [
                    'id'       => $user->id,
                    'name'     => $user->username,
                    'fullname' => trim(implode(' ', array_filter([
                        $user->names,
                        $user->father_surname,
                        $user->mother_surname,
                    ]))) ?: $user->username,
                    'email'  => $user->email,
                    'dni'    => $user->dni,
                    'cui'    => $user->cui,
                    'rol_id' => $user->rol_id,
                    'rol'    => $user->rol->title ?? null,
                ] : null,
            ],
            'modules' => $modules,
            'csrf_token' => csrf_token(),
            'flash' => [
                'success' => session('success'),
                'error'   => session('error'),
            ],
        ]);
    }
}
