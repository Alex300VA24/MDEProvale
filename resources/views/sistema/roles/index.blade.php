@extends('layouts.main')

@section('title', 'Gestión de Roles - PROVALE')

@section('content')
<div class="bg-white rounded-2xl border-2 border-wheat shadow-sm overflow-hidden">
    <div class="flex items-center justify-between px-6 py-5 border-b-2 border-wheat">
        <h3 class="font-extrabold text-charcoal text-xl flex items-center gap-3">
            <i class="fas fa-user-tag text-purple-600"></i> Gestión de Roles
        </h3>
        <button onclick="openModal('modal-crear-rol')" class="btn-primary">
            <i class="fas fa-plus mr-2"></i> Nuevo Rol
        </button>
    </div>

    <div class="p-6">
        @if(session('success'))
            <div class="bg-green-100 border border-green-400 text-green-700 px-4 py-3 rounded mb-4">
                {{ session('success') }}
            </div>
        @endif

        @if(session('error'))
            <div class="bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded mb-4">
                {{ session('error') }}
            </div>
        @endif

        <div class="overflow-x-auto">
            <table class="min-w-full divide-y divide-gray-200">
                <thead class="bg-gray-50">
                    <tr>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Rol</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Descripción</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Módulos Asignados</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Usuarios</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Acciones</th>
                    </tr>
                </thead>
                <tbody class="bg-white divide-y divide-gray-200">
                    @foreach($roles as $rol)
                    <tr>
                        <td class="px-6 py-4 whitespace-nowrap">
                            <div class="text-sm font-medium text-gray-900">{{ $rol->title }}</div>
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                            {{ $rol->description ?? 'Sin descripción' }}
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                            {{ $rol->modules->count() }} módulos
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                            {{ $rol->users->count() }} usuarios
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap text-sm font-medium">
                            <button onclick="openModal('modal-editar-rol-{{ $rol->id }}')" class="text-blue-600 hover:text-blue-900 mr-3">
                                <i class="fas fa-edit"></i>
                            </button>
                            <form action="{{ route('sistema.roles.destroy', $rol->id) }}" method="POST" class="inline">
                                @csrf
                                @method('DELETE')
                                <button type="submit" class="text-red-600 hover:text-red-900" onclick="return confirm('¿Estás seguro de eliminar este rol?')">
                                    <i class="fas fa-trash"></i>
                                </button>
                            </form>
                        </td>
                    </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    </div>
</div>

<div id="modal-crear-rol" class="fixed inset-0 bg-gray-600 bg-opacity-50 overflow-y-auto h-full w-full hidden z-50">
    <div class="relative mx-auto p-5 border w-full max-w-4xl shadow-lg rounded-md bg-white mt-10">
        <div class="flex justify-between items-center mb-4">
            <h3 class="text-lg font-bold text-gray-900">Crear Nuevo Rol</h3>
            <button onclick="closeModal('modal-crear-rol')" class="text-gray-400 hover:text-gray-600">
                <i class="fas fa-times text-xl"></i>
            </button>
        </div>
        <form action="{{ route('sistema.roles.store') }}" method="POST">
            @csrf
            <div class="mb-4">
                <label class="block text-sm font-bold text-gray-700 mb-1">Nombre del Rol</label>
                <input type="text" name="title" class="w-full border border-gray-300 rounded px-3 py-2 focus:outline-none focus:ring-2 focus:ring-purple-500" required>
            </div>
            <div class="mb-4">
                <label class="block text-sm font-bold text-gray-700 mb-1">Descripción</label>
                <textarea name="description" class="w-full border border-gray-300 rounded px-3 py-2 focus:outline-none focus:ring-2 focus:ring-purple-500" rows="2"></textarea>
            </div>
            <div class="mb-4">
                <label class="block text-sm font-bold text-gray-700 mb-2">Asignar Módulos y Permisos</label>
                <div class="border border-gray-300 rounded max-h-64 overflow-y-auto">
                    <table class="min-w-full divide-y divide-gray-200">
                        <thead class="bg-gray-50 sticky top-0">
                            <tr>
                                <th class="px-4 py-2 text-left text-xs font-medium text-gray-500 uppercase">Módulo</th>
                                <th class="px-4 py-2 text-center text-xs font-medium text-gray-500 uppercase">Ver</th>
                                <th class="px-4 py-2 text-center text-xs font-medium text-gray-500 uppercase">Crear</th>
                                <th class="px-4 py-2 text-center text-xs font-medium text-gray-500 uppercase">Editar</th>
                                <th class="px-4 py-2 text-center text-xs font-medium text-gray-500 uppercase">Eliminar</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-200">
                            @foreach($modulos as $modulo)
                            <tr>
                                <td class="px-4 py-2 text-sm text-gray-900">{{ $modulo->name }}</td>
                                <td class="px-4 py-2 text-center">
                                    <input type="checkbox" name="modules[{{ $modulo->id }}][can_view]" value="1" class="w-4 h-4 text-purple-600">
                                </td>
                                <td class="px-4 py-2 text-center">
                                    <input type="checkbox" name="modules[{{ $modulo->id }}][can_create]" value="1" class="w-4 h-4 text-purple-600">
                                </td>
                                <td class="px-4 py-2 text-center">
                                    <input type="checkbox" name="modules[{{ $modulo->id }}][can_edit]" value="1" class="w-4 h-4 text-purple-600">
                                </td>
                                <td class="px-4 py-2 text-center">
                                    <input type="checkbox" name="modules[{{ $modulo->id }}][can_delete]" value="1" class="w-4 h-4 text-purple-600">
                                </td>
                            </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            </div>
            <div class="flex justify-end gap-2">
                <button type="button" onclick="closeModal('modal-crear-rol')" class="px-4 py-2 bg-gray-300 text-gray-700 rounded hover:bg-gray-400">Cancelar</button>
                <button type="submit" class="px-4 py-2 bg-purple-600 text-white rounded hover:bg-purple-700">Guardar</button>
            </div>
        </form>
    </div>
</div>

@foreach($roles as $rol)
<div id="modal-editar-rol-{{ $rol->id }}" class="fixed inset-0 bg-gray-600 bg-opacity-50 overflow-y-auto h-full w-full hidden z-50">
    <div class="relative mx-auto p-5 border w-full max-w-4xl shadow-lg rounded-md bg-white mt-10">
        <div class="flex justify-between items-center mb-4">
            <h3 class="text-lg font-bold text-gray-900">Editar Rol</h3>
            <button onclick="closeModal('modal-editar-rol-{{ $rol->id }}')" class="text-gray-400 hover:text-gray-600">
                <i class="fas fa-times text-xl"></i>
            </button>
        </div>
        <form action="{{ route('sistema.roles.update', $rol->id) }}" method="POST">
            @csrf
            @method('PUT')
            <div class="mb-4">
                <label class="block text-sm font-bold text-gray-700 mb-1">Nombre del Rol</label>
                <input type="text" name="title" value="{{ $rol->title }}" class="w-full border border-gray-300 rounded px-3 py-2 focus:outline-none focus:ring-2 focus:ring-purple-500" required>
            </div>
            <div class="mb-4">
                <label class="block text-sm font-bold text-gray-700 mb-1">Descripción</label>
                <textarea name="description" class="w-full border border-gray-300 rounded px-3 py-2 focus:outline-none focus:ring-2 focus:ring-purple-500" rows="2">{{ $rol->description }}</textarea>
            </div>
            <div class="mb-4">
                <label class="block text-sm font-bold text-gray-700 mb-2">Asignar Módulos y Permisos</label>
                <div class="border border-gray-300 rounded max-h-64 overflow-y-auto">
                    <table class="min-w-full divide-y divide-gray-200">
                        <thead class="bg-gray-50 sticky top-0">
                            <tr>
                                <th class="px-4 py-2 text-left text-xs font-medium text-gray-500 uppercase">Módulo</th>
                                <th class="px-4 py-2 text-center text-xs font-medium text-gray-500 uppercase">Ver</th>
                                <th class="px-4 py-2 text-center text-xs font-medium text-gray-500 uppercase">Crear</th>
                                <th class="px-4 py-2 text-center text-xs font-medium text-gray-500 uppercase">Editar</th>
                                <th class="px-4 py-2 text-center text-xs font-medium text-gray-500 uppercase">Eliminar</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-200">
                            @php
                                $rolModules = $rol->modules->keyBy('id');
                            @endphp
                            @foreach($modulos as $modulo)
                            <tr>
                                <td class="px-4 py-2 text-sm text-gray-900">{{ $modulo->name }}</td>
                                <td class="px-4 py-2 text-center">
                                    <input type="checkbox" name="modules[{{ $modulo->id }}][can_view]" value="1" {{ isset($rolModules[$modulo->id]) && $rolModules[$modulo->id]->pivot->can_view ? 'checked' : '' }} class="w-4 h-4 text-purple-600">
                                </td>
                                <td class="px-4 py-2 text-center">
                                    <input type="checkbox" name="modules[{{ $modulo->id }}][can_create]" value="1" {{ isset($rolModules[$modulo->id]) && $rolModules[$modulo->id]->pivot->can_create ? 'checked' : '' }} class="w-4 h-4 text-purple-600">
                                </td>
                                <td class="px-4 py-2 text-center">
                                    <input type="checkbox" name="modules[{{ $modulo->id }}][can_edit]" value="1" {{ isset($rolModules[$modulo->id]) && $rolModules[$modulo->id]->pivot->can_edit ? 'checked' : '' }} class="w-4 h-4 text-purple-600">
                                </td>
                                <td class="px-4 py-2 text-center">
                                    <input type="checkbox" name="modules[{{ $modulo->id }}][can_delete]" value="1" {{ isset($rolModules[$modulo->id]) && $rolModules[$modulo->id]->pivot->can_delete ? 'checked' : '' }} class="w-4 h-4 text-purple-600">
                                </td>
                            </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            </div>
            <div class="flex justify-end gap-2">
                <button type="button" onclick="closeModal('modal-editar-rol-{{ $rol->id }}')" class="px-4 py-2 bg-gray-300 text-gray-700 rounded hover:bg-gray-400">Cancelar</button>
                <button type="submit" class="px-4 py-2 bg-purple-600 text-white rounded hover:bg-purple-700">Actualizar</button>
            </div>
        </form>
    </div>
</div>
@endforeach

<script>
function openModal(id) {
    document.getElementById(id).classList.remove('hidden');
}

function closeModal(id) {
    document.getElementById(id).classList.add('hidden');
}
</script>
@endsection
