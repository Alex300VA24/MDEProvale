<?php

namespace App\Http\Middleware;

use Illuminate\Http\Request;
use Inertia\Middleware;
use App\Services\ModuleService;
use App\Services\UploadSettingsService;

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

        $modules = $user ? app(ModuleService::class)->permissionsFor($user) : [];

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
                    'is_admin' => $user->isAdmin(),
                ] : null,
            ],
            'modules' => $modules,
            'csrf_token' => csrf_token(),
            'upload_max_mb' => app(UploadSettingsService::class)->maxMb(),
            'flash' => [
                'success' => session('success'),
                'error'   => session('error'),
            ],
        ]);
    }
}
