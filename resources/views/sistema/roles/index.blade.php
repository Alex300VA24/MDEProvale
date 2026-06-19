@extends('layouts.main')

@section('title', 'Gestión de Roles - PROVALE')

@section('content')
<div class="bg-white rounded-2xl border-2 border-wheat shadow-sm overflow-hidden">
    <div class="flex flex-col sm:flex-row items-start sm:items-center justify-between px-4 sm:px-6 py-4 sm:py-5 border-b-2 border-wheat gap-3">
        <h3 class="font-extrabold text-charcoal text-xl flex items-center gap-3">
            <i class="fas fa-user-tag text-purple-600"></i> Gestión de Roles
        </h3>
        <div class="flex flex-wrap gap-2">
            <a href="{{ route('sistema.index') }}" class="btn-secondary">
                <i class="fas fa-arrow-left mr-2"></i> Volver
            </a>
            @if(Auth::user()->canCreateModule('sistema'))
            <button onclick="openModal('modal-crear-rol')" class="btn-primary">
                <i class="fas fa-plus mr-2"></i> Nuevo Rol
            </button>
            @endif
        </div>
    </div>

    <div class="overflow-x-auto -mx-4 sm:mx-0">
        <table class="data-table w-full min-w-[600px] text-xs sm:text-sm">
            <thead>
                <tr>
                    <th class="px-3 sm:px-4 py-4 text-left">Rol</th>
                    <th class="px-3 sm:px-4 py-4 text-left">Descripción</th>
                    <th class="px-3 sm:px-4 py-4 text-left">Módulos Asignados</th>
                    <th class="px-3 sm:px-4 py-4 text-left">Usuarios</th>
                    <th class="px-3 sm:px-4 py-4 text-left">Acciones</th>
                </tr>
            </thead>
            <tbody>
                @foreach($roles as $rol)
                <tr>
                    <td class="px-3 sm:px-4 font-semibold text-charcoal">{{ $rol->title }}</td>
                    <td class="px-3 sm:px-4 text-earth text-sm">{{ $rol->description ?? 'Sin descripción' }}</td>
                    <td class="px-3 sm:px-4">
                        <span class="px-2 py-1 rounded-lg bg-purple-100 text-purple-700 text-xs font-bold">{{ $rol->modules->count() }} módulos</span>
                    </td>
                    <td class="px-3 sm:px-4 text-earth text-sm">{{ $rol->users->count() }} usuarios</td>
                    <td class="px-3 sm:px-4">
                        <div class="flex gap-2">
                            @if(Auth::user()->canEditModule('sistema'))
                            <button onclick="openModal('modal-editar-rol-{{ $rol->id }}')" class="btn-action bg-sun-light text-[#D97706] hover:bg-sun hover:text-white" title="Editar">
                                <i class="fas fa-edit"></i>
                            </button>
                            @endif
                            @if(Auth::user()->canDeleteModule('sistema'))
                            <form id="form-delete-rol-{{ $rol->id }}" action="{{ route('sistema.roles.destroy', $rol->id) }}" method="POST" class="inline">
                                @csrf
                                @method('DELETE')
                                <button type="button" class="btn-action bg-clay-light text-clay hover:bg-clay hover:text-white" title="Eliminar"
                                    onclick="confirmDelete('form-delete-rol-{{ $rol->id }}', 'Se eliminará el rol {{ addslashes($rol->title) }} de forma permanente.')">
                                    <i class="fas fa-trash"></i>
                                </button>
                            </form>
                            @endif
                        </div>
                    </td>
                </tr>
                @endforeach
            </tbody>
        </table>
    </div>
</div>

{{-- Modal Crear Rol --}}
<div id="modal-crear-rol" class="fixed inset-0 bg-black/40 backdrop-blur-sm overflow-y-auto h-full w-full hidden z-50">
    <div class="relative mx-auto w-full max-w-full sm:max-w-4xl mt-8 sm:mt-16 mb-8 px-2 sm:px-4">
        <div class="bg-white rounded-2xl shadow-2xl border-2 border-wheat overflow-hidden">
            <div class="flex items-center justify-between px-6 py-5 border-b-2 border-wheat">
                <h3 class="font-extrabold text-charcoal text-lg flex items-center gap-2">
                    <i class="fas fa-plus-circle text-purple-600"></i> Crear Nuevo Rol
                </h3>
                <button onclick="closeModal('modal-crear-rol')" class="w-8 h-8 rounded-xl bg-cream border-2 border-wheat flex items-center justify-center text-earth hover:bg-wheat transition-all">
                    <i class="fas fa-times"></i>
                </button>
            </div>
            <form action="{{ route('sistema.roles.store') }}" method="POST" class="p-6">
                @csrf
                <div class="mb-4">
                    <label class="block text-[11px] font-bold text-earth uppercase tracking-wider mb-2">Nombre del Rol</label>
                    <input type="text" name="title" class="w-full px-4 py-2.5 border-2 border-wheat rounded-xl text-sm font-semibold text-charcoal bg-white focus:outline-none focus:border-purple-500 transition-all" required>
                </div>
                <div class="mb-4">
                    <label class="block text-[11px] font-bold text-earth uppercase tracking-wider mb-2">Descripción</label>
                    <textarea name="description" class="w-full px-4 py-2.5 border-2 border-wheat rounded-xl text-sm font-semibold text-charcoal bg-white focus:outline-none focus:border-purple-500 transition-all" rows="2"></textarea>
                </div>
                <div class="mb-4">
                    <label class="block text-[11px] font-bold text-earth uppercase tracking-wider mb-2">Asignar Módulos y Permisos</label>
                    <div class="border-2 border-wheat rounded-xl max-h-64 overflow-y-auto">
                        <table class="min-w-full divide-y divide-wheat">
                            <thead class="bg-cream sticky top-0">
                                <tr>
                                    <th class="px-4 py-2 text-left text-[11px] font-bold text-earth uppercase tracking-wider">Módulo</th>
                                    <th class="px-4 py-2 text-center text-[11px] font-bold text-earth uppercase tracking-wider">Ver</th>
                                    <th class="px-4 py-2 text-center text-[11px] font-bold text-earth uppercase tracking-wider">Crear</th>
                                    <th class="px-4 py-2 text-center text-[11px] font-bold text-earth uppercase tracking-wider">Editar</th>
                                    <th class="px-4 py-2 text-center text-[11px] font-bold text-earth uppercase tracking-wider">Eliminar</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-wheat">
                                @foreach($modulos as $modulo)
                                <tr class="hover:bg-cream">
                                    <td class="px-4 py-2 text-sm font-semibold text-charcoal">{{ $modulo->name }}</td>
                                    <td class="px-4 py-2 text-center"><input type="checkbox" name="modules[{{ $modulo->id }}][can_view]" value="1" class="w-4 h-4 text-purple-600 rounded"></td>
                                    <td class="px-4 py-2 text-center"><input type="checkbox" name="modules[{{ $modulo->id }}][can_create]" value="1" class="w-4 h-4 text-purple-600 rounded"></td>
                                    <td class="px-4 py-2 text-center"><input type="checkbox" name="modules[{{ $modulo->id }}][can_edit]" value="1" class="w-4 h-4 text-purple-600 rounded"></td>
                                    <td class="px-4 py-2 text-center"><input type="checkbox" name="modules[{{ $modulo->id }}][can_delete]" value="1" class="w-4 h-4 text-purple-600 rounded"></td>
                                </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                </div>
                <div class="flex justify-end gap-3 pt-4 border-t-2 border-wheat">
                    <button type="button" onclick="closeModal('modal-crear-rol')" class="btn-secondary">Cancelar</button>
                    <button type="submit" class="btn-primary"><i class="fas fa-save mr-2"></i> Guardar</button>
                </div>
            </form>
        </div>
    </div>
</div>

{{-- Modales Editar Rol --}}
@foreach($roles as $rol)
<div id="modal-editar-rol-{{ $rol->id }}" class="fixed inset-0 bg-black/40 backdrop-blur-sm overflow-y-auto h-full w-full hidden z-50">
    <div class="relative mx-auto w-full max-w-full sm:max-w-4xl mt-8 sm:mt-16 mb-8 px-2 sm:px-4">
        <div class="bg-white rounded-2xl shadow-2xl border-2 border-wheat overflow-hidden">
            <div class="flex items-center justify-between px-6 py-5 border-b-2 border-wheat">
                <h3 class="font-extrabold text-charcoal text-lg flex items-center gap-2">
                    <i class="fas fa-user-tag text-[#D97706]"></i> Editar Rol
                </h3>
                <button onclick="closeModal('modal-editar-rol-{{ $rol->id }}')" class="w-8 h-8 rounded-xl bg-cream border-2 border-wheat flex items-center justify-center text-earth hover:bg-wheat transition-all">
                    <i class="fas fa-times"></i>
                </button>
            </div>
            <form action="{{ route('sistema.roles.update', $rol->id) }}" method="POST" class="p-6">
                @csrf
                @method('PUT')
                <div class="mb-4">
                    <label class="block text-[11px] font-bold text-earth uppercase tracking-wider mb-2">Nombre del Rol</label>
                    <input type="text" name="title" value="{{ $rol->title }}" class="w-full px-4 py-2.5 border-2 border-wheat rounded-xl text-sm font-semibold text-charcoal bg-white focus:outline-none focus:border-purple-500 transition-all" required>
                </div>
                <div class="mb-4">
                    <label class="block text-[11px] font-bold text-earth uppercase tracking-wider mb-2">Descripción</label>
                    <textarea name="description" class="w-full px-4 py-2.5 border-2 border-wheat rounded-xl text-sm font-semibold text-charcoal bg-white focus:outline-none focus:border-purple-500 transition-all" rows="2">{{ $rol->description }}</textarea>
                </div>
                <div class="mb-4">
                    <label class="block text-[11px] font-bold text-earth uppercase tracking-wider mb-2">Asignar Módulos y Permisos</label>
                    <div class="border-2 border-wheat rounded-xl max-h-64 overflow-y-auto">
                        <table class="min-w-full divide-y divide-wheat">
                            <thead class="bg-cream sticky top-0">
                                <tr>
                                    <th class="px-4 py-2 text-left text-[11px] font-bold text-earth uppercase tracking-wider">Módulo</th>
                                    <th class="px-4 py-2 text-center text-[11px] font-bold text-earth uppercase tracking-wider">Ver</th>
                                    <th class="px-4 py-2 text-center text-[11px] font-bold text-earth uppercase tracking-wider">Crear</th>
                                    <th class="px-4 py-2 text-center text-[11px] font-bold text-earth uppercase tracking-wider">Editar</th>
                                    <th class="px-4 py-2 text-center text-[11px] font-bold text-earth uppercase tracking-wider">Eliminar</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-wheat">
                                @php $rolModules = $rol->modules->keyBy('id'); @endphp
                                @foreach($modulos as $modulo)
                                <tr class="hover:bg-cream">
                                    <td class="px-4 py-2 text-sm font-semibold text-charcoal">{{ $modulo->name }}</td>
                                    <td class="px-4 py-2 text-center"><input type="checkbox" name="modules[{{ $modulo->id }}][can_view]" value="1" {{ isset($rolModules[$modulo->id]) && $rolModules[$modulo->id]->pivot->can_view ? 'checked' : '' }} class="w-4 h-4 text-purple-600 rounded"></td>
                                    <td class="px-4 py-2 text-center"><input type="checkbox" name="modules[{{ $modulo->id }}][can_create]" value="1" {{ isset($rolModules[$modulo->id]) && $rolModules[$modulo->id]->pivot->can_create ? 'checked' : '' }} class="w-4 h-4 text-purple-600 rounded"></td>
                                    <td class="px-4 py-2 text-center"><input type="checkbox" name="modules[{{ $modulo->id }}][can_edit]" value="1" {{ isset($rolModules[$modulo->id]) && $rolModules[$modulo->id]->pivot->can_edit ? 'checked' : '' }} class="w-4 h-4 text-purple-600 rounded"></td>
                                    <td class="px-4 py-2 text-center"><input type="checkbox" name="modules[{{ $modulo->id }}][can_delete]" value="1" {{ isset($rolModules[$modulo->id]) && $rolModules[$modulo->id]->pivot->can_delete ? 'checked' : '' }} class="w-4 h-4 text-purple-600 rounded"></td>
                                </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                </div>
                <div class="flex justify-end gap-3 pt-4 border-t-2 border-wheat">
                    <button type="button" onclick="closeModal('modal-editar-rol-{{ $rol->id }}')" class="btn-secondary">Cancelar</button>
                    <button type="submit" class="btn-primary"><i class="fas fa-save mr-2"></i> Actualizar</button>
                </div>
            </form>
        </div>
    </div>
</div>
@endforeach
@endsection
