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
use App\Models\ModuleIcon;
use App\Models\Notification;
use App\Models\Rol;
use App\Models\State;
use App\Models\SystemAuditEvent;
use App\Models\User;
use App\Services\AssistantSettingsService;
use App\Services\MonthClosureService;
use App\Services\UploadSettingsService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\ValidationException;

class SistemaController extends Controller
{
    // ==================== USUARIOS ====================

    public function usuarios(Request $request)
    {
        $query = User::with(['rol', 'state'])->orderBy('names');

        if ($search = trim((string) $request->input('search'))) {
            $query->searchIdentity($search);
        }

        if ($request->filled('rol_id')) {
            $query->where('rol_id', $request->input('rol_id'));
        }

        if ($request->filled('state_id')) {
            $query->where('state_id', $request->input('state_id'));
        }

        $usuarios = $query->paginate((int) $request->input('per_page', 10));

        return UserResource::collection($usuarios)->additional([
            'roles' => Rol::where('is_active', true)->orderBy('title')->get(['id', 'title']),
            'estados' => State::administrative()->orderBy('title')->get(['id', 'title', 'abbreviation']),
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
        if ($request->user()->is($usuario)) {
            return response()->json(['message' => 'No puedes eliminar tu propio usuario.'], 403);
        }

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

    public function roles(Request $request)
    {
        $roles = Rol::withCount('users')->with('modules')->orderBy('title')->paginate((int) $request->input('per_page', 10));

        return RolResource::collection($roles);
    }

    public function storeRol(StoreRolRequest $request)
    {
        $validated = $request->validated();
        $modules = $validated['modules'] ?? [];
        unset($validated['modules']);
        $this->validateModuleIds($modules);

        $rol = Rol::create($validated);
        $this->syncRolModules($rol, $modules);

        return response()->json(['data' => new RolResource($rol->fresh(['modules'])->loadCount('users'))], 201);
    }

    public function updateRol(UpdateRolRequest $request, Rol $rol)
    {
        $validated = $request->validated();

        if ($rol->id === 1 && array_key_exists('is_active', $validated) && !$validated['is_active']) {
            return response()->json(['message' => 'No se puede inactivar el rol Administrador.'], 422);
        }

        $modules = $validated['modules'] ?? [];
        unset($validated['modules']);
        $this->validateModuleIds($modules);

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
            $canView = (bool) ($permissions['can_view'] ?? false);
            $modulesData[$moduleId] = [
                'can_view' => $canView,
                'can_create' => $canView && (bool) ($permissions['can_create'] ?? false),
                'can_edit' => $canView && (bool) ($permissions['can_edit'] ?? false),
                'can_delete' => $canView && (bool) ($permissions['can_delete'] ?? false),
            ];
        }
        $rol->modules()->attach($modulesData);
    }

    private function validateModuleIds(array $modules): void
    {
        $requestedIds = array_map('intval', array_keys($modules));
        $validIds = Module::whereKey($requestedIds)->pluck('id')->map(fn ($id) => (int) $id)->all();

        if (count(array_diff($requestedIds, $validIds)) > 0) {
            throw ValidationException::withMessages(['modules' => 'Uno o más módulos seleccionados no existen.']);
        }
    }

    // ==================== MÓDULOS ====================

    public function modulos(Request $request)
    {
        $modulos = Module::orderBy('order')->paginate((int) $request->input('per_page', 10));

        return ModuleResource::collection($modulos);
    }

    public function modulosOptions()
    {
        return response()->json(['data' => ModuleResource::collection(Module::orderBy('order')->get())]);
    }

    public function moduleIcons()
    {
        $icons = ModuleIcon::query()
            ->where('is_active', true)
            ->orderBy('sort_order')
            ->get(['id', 'name', 'class_name', 'category']);

        return response()->json(['data' => $icons]);
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
            $query->where(fn ($q) => $q
                ->where('requested_by', $user->id)
                ->orWhereIn('type', Notification::SYSTEM_WIDE_TYPES));
        }

        if ($type = $request->query('type')) {
            $query->where('type', $type);
        }

        $limit = min(100, max(1, (int) $request->query('limit', 100)));

        return response()->json(['data' => NotificationResource::collection($query->limit($limit)->get())]);
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
            $query->where(fn ($q) => $q
                ->where('requested_by', $user->id)
                ->orWhereIn('type', Notification::SYSTEM_WIDE_TYPES));
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

    // ==================== CIERRE DE MES ====================

    public function cierreMesIndex(MonthClosureService $service)
    {
        $now = now();

        $closed = $service->currentClosedPeriod($now);

        return response()->json([
            'ultimo_cerrado' => $closed
                ? ['anio' => $closed[0], 'mes' => $closed[1], 'label' => $service->label($closed[0], $closed[1])]
                : null,
            'cerrados' => $service->closedMonths($now),
            'disponibles' => $service->availableMonths($now),
            'mes_actual' => [
                'anio' => $now->year,
                'mes' => $now->month,
                'label' => $service->label($now->year, $now->month),
            ],
        ]);
    }

    public function storeCierreMes(Request $request, MonthClosureService $service)
    {
        $validated = $request->validate([
            'anio' => ['required', 'integer', 'min:2000', 'max:2100'],
            'mes' => ['required', 'integer', 'min:1', 'max:12'],
        ]);

        try {
            $service->close((int) $validated['anio'], (int) $validated['mes']);
        } catch (\InvalidArgumentException $e) {
            return response()->json(['message' => $e->getMessage()], 422);
        }

        $now = now();
        $closed = $service->currentClosedPeriod($now);

        return response()->json([
            'data' => [
                'ultimo_cerrado' => $closed
                    ? ['anio' => $closed[0], 'mes' => $closed[1], 'label' => $service->label($closed[0], $closed[1])]
                    : null,
                'mes_actual' => [
                    'anio' => $now->year,
                    'mes' => $now->month,
                    'label' => $service->label($now->year, $now->month),
                ],
            ],
            'message' => 'Mes cerrado correctamente.',
        ]);
    }

    // ==================== ASISTENTE IA ====================

    public function asistenteConfig(AssistantSettingsService $config)
    {
        return response()->json([
            'data' => $config->all() + [
                'limites' => [
                    'max_consultas' => [AssistantSettingsService::MAX_MINIMO, AssistantSettingsService::MAX_MAXIMO],
                    'ventana_horas' => [AssistantSettingsService::VENTANA_MINIMA, AssistantSettingsService::VENTANA_MAXIMA],
                ],
            ],
        ]);
    }

    public function updateAsistenteConfig(Request $request, AssistantSettingsService $config)
    {
        $datos = $request->validate([
            'max_consultas' => ['required', 'integer', 'min:'.AssistantSettingsService::MAX_MINIMO, 'max:'.AssistantSettingsService::MAX_MAXIMO],
            'ventana_horas' => ['required', 'integer', 'min:'.AssistantSettingsService::VENTANA_MINIMA, 'max:'.AssistantSettingsService::VENTANA_MAXIMA],
        ]);

        return response()->json([
            'data' => $config->update((int) $datos['max_consultas'], (int) $datos['ventana_horas']),
            'message' => 'Configuración del asistente actualizada.',
        ]);
    }

    // ==================== PARÁMETROS GLOBALES ====================

    public function uploadConfig(UploadSettingsService $settings)
    {
        return response()->json(['data' => [
            'max_attachment_mb' => $settings->maxMb(),
            'min' => UploadSettingsService::MIN_MB,
            'max' => UploadSettingsService::MAX_MB,
            'php_upload_max' => ini_get('upload_max_filesize'),
            'php_post_max' => ini_get('post_max_size'),
        ]]);
    }

    public function updateUploadConfig(Request $request, UploadSettingsService $settings)
    {
        $data = $request->validate([
            'max_attachment_mb' => ['required', 'integer', 'min:'.UploadSettingsService::MIN_MB, 'max:'.UploadSettingsService::MAX_MB],
        ]);

        return response()->json([
            'data' => ['max_attachment_mb' => $settings->update((int) $data['max_attachment_mb'])],
            'message' => 'Límite de archivos actualizado.',
        ]);
    }

    // ==================== AUDITORÍA DEL SISTEMA ====================

    public function auditEvents(Request $request)
    {
        $data = $request->validate([
            'search' => ['nullable', 'string', 'max:100'],
            'source' => ['nullable', 'in:SYSTEM,USER'],
            'per_page' => ['nullable', 'integer', 'min:5', 'max:100'],
        ]);

        $query = SystemAuditEvent::with(['fromState:id,title,abbreviation', 'toState:id,title,abbreviation', 'executedBy:id,names,father_surname,mother_surname'])
            ->latest('occurred_at');

        if (! empty($data['source'])) {
            $query->where('source', $data['source']);
        }
        if ($search = trim((string) ($data['search'] ?? ''))) {
            $query->where(function ($builder) use ($search) {
                $builder->where('event_type', 'like', "%{$search}%")
                    ->orWhere('reason_code', 'like', "%{$search}%")
                    ->orWhere('subject_type', 'like', "%{$search}%")
                    ->orWhere('subject_id', 'like', "%{$search}%");
            });
        }

        $events = $query->paginate((int) ($data['per_page'] ?? 20));
        $events->through(fn (SystemAuditEvent $event) => [
            'id' => $event->id,
            'event_type' => $event->event_type,
            'subject' => class_basename($event->subject_type).' #'.$event->subject_id,
            'source' => $event->source,
            'from_state' => $event->fromState?->abbreviation,
            'to_state' => $event->toState?->abbreviation,
            'reason_code' => $event->reason_code,
            'executed_by' => $event->executedBy
                ? trim("{$event->executedBy->names} {$event->executedBy->father_surname} {$event->executedBy->mother_surname}")
                : 'Sistema',
            'occurred_at' => $event->occurred_at?->format('Y-m-d H:i:s'),
            'metadata' => $event->metadata,
        ]);

        return response()->json($events);
    }
}
