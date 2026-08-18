<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreModuleRequest;
use App\Http\Requests\StoreRolRequest;
use App\Http\Requests\StoreUserRequest;
use App\Http\Requests\UpdateModuleRequest;
use App\Http\Requests\UpdateRolRequest;
use App\Http\Requests\UpdateUserRequest;
use App\Http\Resources\ModuleResource;
use App\Http\Resources\NotificationResource;
use App\Http\Resources\RolResource;
use App\Http\Resources\UserResource;
use App\Models\Module;
use App\Models\Notification;
use App\Models\Rol;
use App\Models\State;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;

class SistemaController extends Controller
{
    // ==================== USUARIOS ====================

    public function usuarios(Request $request)
    {
        $query = User::with(['rol', 'state'])->orderBy('names');

        if ($search = trim((string) $request->input('search'))) {
            $query->where(function ($q) use ($search) {
                $q->where('names', 'like', "%{$search}%")
                    ->orWhere('father_surname', 'like', "%{$search}%")
                    ->orWhere('mother_surname', 'like', "%{$search}%")
                    ->orWhere('username', 'like', "%{$search}%")
                    ->orWhere('email', 'like', "%{$search}%")
                    ->orWhere('dni', 'like', "%{$search}%");
            });
        }

        if ($request->filled('rol_id')) {
            $query->where('rol_id', $request->input('rol_id'));
        }

        if ($request->filled('state_id')) {
            $query->where('state_id', $request->input('state_id'));
        }

        $usuarios = $query->get();

        return response()->json([
            'data' => UserResource::collection($usuarios),
            'roles' => Rol::orderBy('title')->get(['id', 'title']),
            'estados' => State::orderBy('title')->get(['id', 'title']),
        ]);
    }

    public function storeUsuario(StoreUserRequest $request)
    {
        $validated = $request->validated();
        $validated['password'] = Hash::make($validated['password']);

        $usuario = User::create($validated);

        return response()->json(['data' => new UserResource($usuario->load(['rol', 'state']))], 201);
    }

    public function updateUsuario(UpdateUserRequest $request, User $usuario)
    {
        $validated = $request->validated();

        if (!empty($validated['password'])) {
            $validated['password'] = Hash::make($validated['password']);
        } else {
            unset($validated['password']);
        }

        $usuario->update($validated);

        return response()->json(['data' => new UserResource($usuario->fresh(['rol', 'state']))]);
    }

    public function destroyUsuario(Request $request, User $usuario)
    {
        $this->authorize('delete', $usuario);

        $usuario->delete();

        return response()->json(null, 204);
    }

    public function resetUserPassword(User $usuario)
    {
        $this->authorize('update', $usuario);

        $usuario->update(['password' => Hash::make($usuario->dni)]);

        return response()->json(['message' => "Contraseña restaurada al DNI para {$usuario->names}."]);
    }

    // ==================== ROLES ====================

    public function roles()
    {
        $roles = Rol::withCount('users')->with('modules')->orderBy('title')->get();

        return response()->json(['data' => RolResource::collection($roles)]);
    }

    public function storeRol(StoreRolRequest $request)
    {
        $validated = $request->validated();
        $modules = $validated['modules'] ?? [];
        unset($validated['modules']);

        $rol = Rol::create($validated);
        $this->syncRolModules($rol, $modules);

        return response()->json(['data' => new RolResource($rol->fresh(['modules'])->loadCount('users'))], 201);
    }

    public function updateRol(UpdateRolRequest $request, Rol $rol)
    {
        $validated = $request->validated();
        $modules = $validated['modules'] ?? [];
        unset($validated['modules']);

        $rol->update($validated);
        $this->syncRolModules($rol, $modules);

        return response()->json(['data' => new RolResource($rol->fresh(['modules'])->loadCount('users'))]);
    }

    public function destroyRol(Rol $rol)
    {
        $this->authorize('delete', $rol);

        if ($rol->id === 1) {
            return response()->json(['message' => 'No se puede eliminar el rol Administrador: es el rol base del sistema.'], 422);
        }

        if ($rol->users()->count() > 0) {
            return response()->json(['message' => 'No se puede eliminar el rol porque tiene usuarios asociados.'], 422);
        }

        $rol->modules()->detach();
        $rol->delete();

        return response()->json(null, 204);
    }

    private function syncRolModules(Rol $rol, array $modules): void
    {
        $rol->modules()->detach();

        if (empty($modules)) {
            return;
        }

        $modulesData = [];
        foreach ($modules as $moduleId => $permissions) {
            $modulesData[$moduleId] = [
                'can_view' => (bool) ($permissions['can_view'] ?? false),
                'can_create' => (bool) ($permissions['can_create'] ?? false),
                'can_edit' => (bool) ($permissions['can_edit'] ?? false),
                'can_delete' => (bool) ($permissions['can_delete'] ?? false),
            ];
        }
        $rol->modules()->attach($modulesData);
    }

    // ==================== MÓDULOS ====================

    public function modulos()
    {
        $modulos = Module::orderBy('order')->get();

        return response()->json(['data' => ModuleResource::collection($modulos)]);
    }

    public function storeModulo(StoreModuleRequest $request)
    {
        $modulo = Module::create($request->validated());

        return response()->json(['data' => new ModuleResource($modulo)], 201);
    }

    public function updateModulo(UpdateModuleRequest $request, Module $modulo)
    {
        $modulo->update($request->validated());

        return response()->json(['data' => new ModuleResource($modulo)]);
    }

    public function destroyModulo(Module $modulo)
    {
        $this->authorize('delete', $modulo);

        if ($modulo->slug === 'sistema') {
            return response()->json(['message' => 'No se puede eliminar el módulo Sistema: es requerido para administrar usuarios, roles y módulos.'], 422);
        }

        if ($modulo->rols()->count() > 0) {
            return response()->json(['message' => 'No se puede eliminar el módulo porque está asignado a uno o más roles.'], 422);
        }

        $modulo->delete();

        return response()->json(null, 204);
    }

    // ==================== NOTIFICACIONES ====================
    // Disponibles para cualquier usuario autenticado (no solo con acceso al
    // módulo 'sistema'): la campanita de notificaciones y el flujo de
    // solicitud/estado de recuperación de contraseña son de uso general.

    public function notifications(Request $request)
    {
        $user = $request->user();

        $query = Notification::with(['requestedByUser', 'processedByUser'])
            ->orderBy('requested_at', 'desc');

        if (!$user->isAdmin()) {
            $query->where('requested_by', $user->id);
        }

        return response()->json(['data' => NotificationResource::collection($query->limit(100)->get())]);
    }

    public function unreadNotificationsCount(Request $request)
    {
        $count = Notification::unreadCountForUser($request->user());

        return response()->json(['count' => $count, 'label' => $count > 9 ? '9+' : (string) $count]);
    }

    public function markNotificationsSeen(Request $request)
    {
        $user = $request->user();

        $query = Notification::where('is_seen', false);
        if (!$user->isAdmin()) {
            $query->where('requested_by', $user->id);
        }
        $query->update(['is_seen' => true, 'seen_at' => now()]);

        return response()->json(['success' => true]);
    }

    public function approveNotification(Request $request, Notification $notification)
    {
        if ($notification->status !== 'pending') {
            return response()->json(['message' => 'La solicitud ya fue procesada.'], 422);
        }

        $requestedUser = User::find($notification->requested_by);
        if (!$requestedUser) {
            return response()->json(['message' => 'No se encontró el usuario de la solicitud.'], 404);
        }

        $requestedUser->update(['password' => Hash::make($requestedUser->dni)]);

        $notification->update([
            'status' => 'approved',
            'processed_at' => now(),
            'processed_by' => $request->user()->id,
            'is_seen' => true,
            'seen_at' => now(),
        ]);

        return response()->json(['data' => new NotificationResource($notification->fresh(['requestedByUser', 'processedByUser']))]);
    }

    public function rejectNotification(Request $request, Notification $notification)
    {
        if ($notification->status !== 'pending') {
            return response()->json(['message' => 'La solicitud ya fue procesada.'], 422);
        }

        $notification->update([
            'status' => 'rejected',
            'processed_at' => now(),
            'processed_by' => $request->user()->id,
            'is_seen' => true,
            'seen_at' => now(),
        ]);

        return response()->json(['data' => new NotificationResource($notification->fresh(['requestedByUser', 'processedByUser']))]);
    }
}
