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
        $request->validate([
            'email' => 'required|email|exists:users,email',
        ]);

        $user = User::where('email', $request->email)->first();
        
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

        return response()->json([
            'success' => true,
            'message' => 'Solicitud de recuperación enviada correctamente. Un administrador revisará tu solicitud.'
        ]);
    }

    public function notifications()
    {
        $user = auth()->user();
        
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
        $requestedUser = User::find($notification->requested_by);
        
        if ($requestedUser) {
            $requestedUser->update([
                'password' => Hash::make($requestedUser->dni),
            ]);
        }
        
        $notification->update([
            'status' => 'approved',
            'processed_at' => now(),
            'processed_by' => auth()->id(),
        ]);

        return redirect()->route('login')->with('status', 'Su solicitud de recuperación de contraseña ha sido aprobada. Su contraseña ha sido restablecida a su número de DNI.');
    }

    public function rejectNotification(Notification $notification)
    {
        $notification->update([
            'status' => 'rejected',
            'processed_at' => now(),
            'processed_by' => auth()->id(),
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
        $dni = $usuario->dni;
        
        $usuario->update([
            'password' => Hash::make($dni),
        ]);

        return back()->with('success', 'Contraseña restaurada a DNI para el usuario ' . $usuario->names);
    }
}
