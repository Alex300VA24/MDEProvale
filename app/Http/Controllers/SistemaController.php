<?php

namespace App\Http\Controllers;

use App\Models\Module;
use App\Models\Rol;
use App\Models\User;
use App\Models\State;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
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
}
