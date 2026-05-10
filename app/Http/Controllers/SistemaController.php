<?php

namespace App\Http\Controllers;

use App\Models\Module;
use App\Models\Rol;
use App\Models\User;
use App\Models\State;
use App\Models\Notification;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;

class SistemaController extends Controller
{
    public function index()
    {
        return view('sistema.index');
    }

    public function usuarios()
    {
        $usuarios = User::with(['rol', 'state'])->get();
        $roles = Rol::all();
        $estados = State::all();
        return view('sistema.usuarios.index', compact('usuarios', 'roles', 'estados'));
    }

    public function storeUsuario(Request $request)
    {
        $validated = $request->validate([
            'names' => 'required|string|max:150',
            'father_surname' => 'required|string|max:100',
            'mother_surname' => 'required|string|max:100',
            'username' => 'required|string|max:100|unique:users',
            'email' => 'required|email|unique:users',
            'dni' => 'required|string|size:8|unique:users',
            'cui' => 'nullable|string|max:1',
            'rol_id' => 'required|exists:rols,id',
            'state_id' => 'required|exists:states,id',
            'password' => 'required|string|min:8',
        ]);

        $validated['password'] = Hash::make($validated['password']);

        User::create($validated);

        return redirect()->route('sistema.usuarios')->with('success', 'Usuario creado exitosamente.');
    }

    public function updateUsuario(Request $request, User $usuario)
    {
        $validated = $request->validate([
            'names' => 'required|string|max:150',
            'father_surname' => 'required|string|max:100',
            'mother_surname' => 'required|string|max:100',
            'username' => ['required', 'string', 'max:100', Rule::unique('users')->ignore($usuario->id)],
            'email' => ['required', 'email', Rule::unique('users')->ignore($usuario->id)],
            'dni' => ['required', 'string', 'size:8', Rule::unique('users')->ignore($usuario->id)],
            'cui' => 'nullable|string|max:1',
            'rol_id' => 'required|exists:rols,id',
            'state_id' => 'required|exists:states,id',
            'password' => 'nullable|string|min:8',
        ]);

        if (!empty($validated['password'])) {
            $validated['password'] = Hash::make($validated['password']);
        } else {
            unset($validated['password']);
        }

        $usuario->update($validated);

        return redirect()->route('sistema.usuarios')->with('success', 'Usuario actualizado exitosamente.');
    }

    public function destroyUsuario(User $usuario)
    {
        $usuario->delete();
        return redirect()->route('sistema.usuarios')->with('success', 'Usuario eliminado exitosamente.');
    }

    public function requestPasswordReset(Request $request)
    {
        $email = $request->email;
        
        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            return response()->json([
                'success' => false,
                'message' => 'El correo electrónico no es válido.'
            ], 422);
        }
        
        $user = User::where('email', $email)->first();
        
        if (!$user) {
            return response()->json([
                'success' => false,
                'message' => 'El correo electrónico no está registrado en el sistema.'
            ], 422);
        }
        
        $existingPending = Notification::where('type', 'password_reset')
            ->where('requested_by', $user->id)
            ->where('status', 'pending')
            ->first();
            
        if ($existingPending) {
            return response()->json([
                'success' => false,
                'message' => 'Ya tienes una solicitud de recuperación de contraseña pendiente. Espera a que un administrador la procese.'
            ], 422);
        }
        
        Notification::createPasswordResetRequest($user);

        $unreadCount = Notification::where('requested_by', $user->id)
            ->where('is_seen', false)
            ->count();
        
        $label = $unreadCount > 9 ? '9+' : $unreadCount;

        return response()->json([
            'success' => true,
            'message' => 'Solicitud de recuperación enviada correctamente. Un administrador revisará tu solicitud.',
            'unreadCount' => $unreadCount,
            'label' => $label
        ]);
    }

    public function notifications()
    {
        $user = auth()->user();
        
        if (!$user || !$user->id) {
            return redirect()->route('login')->with('error', 'No hay usuario autenticado.');
        }
        
        $query = Notification::with(['user', 'requestedByUser', 'processedByUser'])
            ->orderBy('requested_at', 'desc');
        
        if ($user->rol_id != 1) {
            $query->where('requested_by', $user->id);
        }
        
        $solicitudes = $query->get();
        return view('sistema.notifications.index', compact('solicitudes'));
    }

    public function approveNotification(Notification $notification)
    {
        if ($notification->status !== 'pending') {
            return back()->with('error', 'La solicitud ya fue procesada.');
        }

        $requestedUser = User::find($notification->requested_by);

        if (!$requestedUser) {
            return back()->with('error', 'No se encontró el usuario de la solicitud.');
        }

        $this->restorePasswordToDni($requestedUser);
        
        $processedById = auth()->id();
        if (!$processedById) {
            return back()->with('error', 'No hay usuario autenticado.');
        }
        
        $notification->update([
            'status' => 'approved',
            'processed_at' => now(),
            'processed_by' => $processedById,
            'is_seen' => true,
            'seen_at' => now(),
        ]);

        return back()->with('success', 'Contraseña restaurada a DNI y solicitud aprobada para el usuario ' . $requestedUser->names);
    }

    public function rejectNotification(Notification $notification)
    {
        $processedById = auth()->id();
        if (!$processedById) {
            return back()->with('error', 'No hay usuario autenticado.');
        }
        
        $notification->update([
            'status' => 'rejected',
            'processed_at' => now(),
            'processed_by' => $processedById,
            'is_seen' => true,
            'seen_at' => now(),
        ]);

        return back()->with('success', 'Solicitud rechazada.');
    }

    public function markAllNotificationsAsSeen()
    {
        \App\Models\Notification::where('is_seen', false)->update([
            'is_seen' => true,
            'seen_at' => now(),
        ]);
        
        return response()->json(['success' => true]);
    }

    public function getUnreadNotificationsCount(Request $request)
    {
        $unreadCount = 0;
        $email = $request->query('email');

        try {
            // Si se proporciona un email, buscar notificaciones por ese usuario
            if ($email) {
                $user = User::where('email', $email)->first();
                if ($user) {
                    $unreadCount = Notification::unreadCountForUser($user);
                    \Log::debug("Notificaciones para email {$email}: {$unreadCount}");
                }
            } elseif (auth()->check()) {
                // Si hay usuario autenticado, usar su ID
                $user = auth()->user();
                $unreadCount = Notification::unreadCountForUser($user);

                if (!$user || !$user->id) {
                    \Log::debug("Usuario autenticado pero no tiene ID");
                } elseif ((int) $user->rol_id === 1) {
                    \Log::debug("Admin - Contador total de notificaciones no vistas: {$unreadCount}");
                } else {
                    \Log::debug("Usuario {$user->id} - Notificaciones no vistas: {$unreadCount}");
                }
            } else {
                \Log::debug("Usuario no autenticado");
            }
        } catch (\Exception $e) {
            \Log::error('Error al obtener contador de notificaciones: ' . $e->getMessage());
            $unreadCount = 0;
        }

        $label = $unreadCount > 9 ? '9+' : $unreadCount;

        return response()->json([
            'count' => $unreadCount,
            'label' => $label
        ]);
    }

    public function roles()
    {
        $roles = Rol::with('modules')->get();
        $modulos = Module::where('is_active', true)->orderBy('order')->get();
        return view('sistema.roles.index', compact('roles', 'modulos'));
    }

    public function storeRol(Request $request)
    {
        $validated = $request->validate([
            'title' => 'required|string|max:100|unique:rols',
            'description' => 'nullable|string|max:255',
        ]);

        $rol = Rol::create($validated);

        if ($request->has('modules')) {
            $modulosData = [];
            foreach ($request->modules as $moduleId => $permissions) {
                $modulosData[$moduleId] = [
                    'can_view' => isset($permissions['can_view']),
                    'can_create' => isset($permissions['can_create']),
                    'can_edit' => isset($permissions['can_edit']),
                    'can_delete' => isset($permissions['can_delete']),
                ];
            }
            $rol->modules()->attach($modulosData);
        }

        return redirect()->route('sistema.roles')->with('success', 'Rol creado exitosamente.');
    }

    public function updateRol(Request $request, Rol $rol)
    {
        $validated = $request->validate([
            'title' => ['required', 'string', 'max:100', Rule::unique('rols')->ignore($rol->id)],
            'description' => 'nullable|string|max:255',
        ]);

        $rol->update($validated);

        $rol->modules()->detach();

        if ($request->has('modules')) {
            $modulosData = [];
            foreach ($request->modules as $moduleId => $permissions) {
                $modulosData[$moduleId] = [
                    'can_view' => isset($permissions['can_view']),
                    'can_create' => isset($permissions['can_create']),
                    'can_edit' => isset($permissions['can_edit']),
                    'can_delete' => isset($permissions['can_delete']),
                ];
            }
            $rol->modules()->attach($modulosData);
        }

        return redirect()->route('sistema.roles')->with('success', 'Rol actualizado exitosamente.');
    }

    public function destroyRol(Rol $rol)
    {
        if ($rol->users()->count() > 0) {
            return redirect()->route('sistema.roles')->with('error', 'No se puede eliminar el rol porque tiene usuarios asociados.');
        }
        $rol->modules()->detach();
        $rol->delete();
        return redirect()->route('sistema.roles')->with('success', 'Rol eliminado exitosamente.');
    }

    public function modulos()
    {
        $modulos = Module::orderBy('order')->get();
        return view('sistema.modulos.index', compact('modulos'));
    }

    public function storeModulo(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:100',
            'slug' => 'required|string|max:100|unique:modules',
            'description' => 'nullable|string|max:255',
            'icon' => 'nullable|string|max:50',
            'route' => 'nullable|string|max:100',
            'order' => 'required|integer|min:0',
            'is_active' => 'boolean',
        ]);

        Module::create($validated);

        return redirect()->route('sistema.modulos')->with('success', 'Módulo creado exitosamente.');
    }

    public function updateModulo(Request $request, Module $modulo)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:100',
            'slug' => ['required', 'string', 'max:100', Rule::unique('modules')->ignore($modulo->id)],
            'description' => 'nullable|string|max:255',
            'icon' => 'nullable|string|max:50',
            'route' => 'nullable|string|max:100',
            'order' => 'required|integer|min:0',
            'is_active' => 'boolean',
        ]);

        $modulo->update($validated);

        return redirect()->route('sistema.modulos')->with('success', 'Módulo actualizado exitosamente.');
    }

    public function destroyModulo(Module $modulo)
    {
        $modulo->rols()->detach();
        $modulo->delete();
        return redirect()->route('sistema.modulos')->with('success', 'Módulo eliminado exitosamente.');
    }

    public function resetUserPassword(User $usuario)
    {
        $this->restorePasswordToDni($usuario);

        return back()->with('success', 'Contraseña restaurada a DNI para el usuario ' . $usuario->names);
    }

    private function restorePasswordToDni(User $user): void
    {
        $user->update([
            'password' => Hash::make($user->dni),
        ]);
    }
}
