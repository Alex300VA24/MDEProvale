<?php

namespace App\Http\Controllers\Api;

use App\Exceptions\SistemaException;
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
use App\Models\User;
use App\Services\AssistantSettingsService;
use App\Services\ModuleService;
use App\Services\MonthClosureService;
use App\Services\NotificationService;
use App\Services\RolService;
use App\Services\SystemAuditService;
use App\Services\UploadSettingsService;
use App\Services\UserService;
use Illuminate\Http\Request;

class SistemaController extends Controller
{
    public function __construct(
        private UserService $users,
        private RolService $roles,
        private ModuleService $modules,
        private NotificationService $notifications,
        private SystemAuditService $audit
    ) {
    }

    private function fail(SistemaException $e)
    {
        return response()->json(['message' => $e->getMessage()], $e->status());
    }

    // ==================== USUARIOS ====================

    public function usuarios(Request $request)
    {
        $usuarios = $this->users->list(
            (string) $request->input('search'),
            $request->filled('rol_id') ? $request->input('rol_id') : null,
            $request->filled('state_id') ? $request->input('state_id') : null,
            (int) $request->input('per_page', 10)
        );

        return UserResource::collection($usuarios)->additional($this->users->formOptions());
    }

    public function storeUsuario(StoreUserRequest $request)
    {
        return response()->json(['data' => new UserResource($this->users->create($request->validated()))], 201);
    }

    public function updateUsuario(UpdateUserRequest $request, User $usuario)
    {
        return response()->json(['data' => new UserResource($this->users->update($usuario, $request->validated()))]);
    }

    public function destroyUsuario(Request $request, User $usuario)
    {
        $this->authorize('delete', $usuario);

        try {
            $this->users->delete($request->user(), $usuario);
        } catch (SistemaException $e) {
            return $this->fail($e);
        }

        return response()->json(null, 204);
    }

    public function resetUserPassword(User $usuario)
    {
        $this->authorize('update', $usuario);
        $this->users->resetPassword($usuario);

        return response()->json(['message' => "Contraseña restaurada al DNI para {$usuario->names}."]);
    }

    // ==================== ROLES ====================

    public function roles(Request $request)
    {
        return RolResource::collection($this->roles->list((int) $request->input('per_page', 10)));
    }

    public function storeRol(StoreRolRequest $request)
    {
        return response()->json(['data' => new RolResource($this->roles->create($request->validated()))], 201);
    }

    public function updateRol(UpdateRolRequest $request, Rol $rol)
    {
        try {
            $updated = $this->roles->update($rol, $request->validated());
        } catch (SistemaException $e) {
            return $this->fail($e);
        }

        return response()->json(['data' => new RolResource($updated)]);
    }

    public function destroyRol(Rol $rol)
    {
        $this->authorize('delete', $rol);

        try {
            $this->roles->delete($rol);
        } catch (SistemaException $e) {
            return $this->fail($e);
        }

        return response()->json(null, 204);
    }

    // ==================== MÓDULOS ====================

    public function currentModules(Request $request)
    {
        return response()->json([
            'data' => $this->modules->permissionsFor($request->user())->values(),
        ]);
    }

    public function modulos(Request $request)
    {
        return ModuleResource::collection($this->modules->list((int) $request->input('per_page', 10)));
    }

    public function modulosOptions()
    {
        return response()->json(['data' => ModuleResource::collection($this->modules->options())]);
    }

    public function moduleIcons()
    {
        return response()->json(['data' => $this->modules->icons()]);
    }

    public function storeModulo(StoreModuleRequest $request)
    {
        return response()->json(['data' => new ModuleResource($this->modules->create($request->validated()))], 201);
    }

    public function updateModulo(UpdateModuleRequest $request, Module $modulo)
    {
        return response()->json(['data' => new ModuleResource($this->modules->update($modulo, $request->validated()))]);
    }

    public function destroyModulo(Module $modulo)
    {
        $this->authorize('delete', $modulo);

        try {
            $this->modules->delete($modulo);
        } catch (SistemaException $e) {
            return $this->fail($e);
        }

        return response()->json(null, 204);
    }

    // ==================== NOTIFICACIONES ====================
    // Disponibles para cualquier usuario autenticado (no solo con acceso al
    // módulo 'sistema'): la campanita de notificaciones y el flujo de
    // solicitud/estado de recuperación de contraseña son de uso general.

    public function notifications(Request $request)
    {
        $items = $this->notifications->list(
            $request->user(),
            $request->query('type') ?: null,
            (int) $request->query('limit', 100)
        );

        return response()->json(['data' => NotificationResource::collection($items)]);
    }

    public function unreadNotificationsCount(Request $request)
    {
        return response()->json($this->notifications->unreadCount($request->user()));
    }

    public function markNotificationsSeen(Request $request)
    {
        $this->notifications->markSeen($request->user());

        return response()->json(['success' => true]);
    }

    public function approveNotification(Request $request, Notification $notification)
    {
        try {
            $processed = $this->notifications->approve($notification, $request->user());
        } catch (SistemaException $e) {
            return $this->fail($e);
        }

        return response()->json(['data' => new NotificationResource($processed)]);
    }

    public function rejectNotification(Request $request, Notification $notification)
    {
        try {
            $processed = $this->notifications->reject($notification, $request->user());
        } catch (SistemaException $e) {
            return $this->fail($e);
        }

        return response()->json(['data' => new NotificationResource($processed)]);
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

        return response()->json($this->audit->events(
            $data['source'] ?? null,
            $data['search'] ?? null,
            (int) ($data['per_page'] ?? 20)
        ));
    }
}
