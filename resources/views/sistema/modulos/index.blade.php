@extends('layouts.main')

@section('title', 'Gestión de Módulos - PROVALE')

@section('content')
<div class="bg-white rounded-2xl border-2 border-wheat shadow-sm overflow-hidden">
    <div class="flex items-center justify-between px-6 py-5 border-b-2 border-wheat">
        <h3 class="font-extrabold text-charcoal text-xl flex items-center gap-3">
            <i class="fas fa-th-large text-green-600"></i> Gestión de Módulos
        </h3>
        <div class="flex gap-2">
            <a href="{{ route('sistema.index') }}" class="btn-secondary">
                <i class="fas fa-arrow-left mr-2"></i> Volver
            </a>
            @if(Auth::user()->canCreateModule('sistema'))
            <button onclick="openModal('modal-crear-modulo')" class="btn-primary">
                <i class="fas fa-plus mr-2"></i> Nuevo Módulo
            </button>
            @endif
        </div>
    </div>

    <div class="overflow-x-auto">
        <table class="data-table w-full">
            <thead>
                <tr>
                    <th class="px-6 py-4 text-left">Orden</th>
                    <th class="px-6 py-4 text-left">Módulo</th>
                    <th class="px-6 py-4 text-left">Slug</th>
                    <th class="px-6 py-4 text-left">Ruta</th>
                    <th class="px-6 py-4 text-left">Roles Asignados</th>
                    <th class="px-6 py-4 text-left">Estado</th>
                    <th class="px-6 py-4 text-left">Acciones</th>
                </tr>
            </thead>
            <tbody>
                @foreach($modulos as $modulo)
                <tr>
                    <td class="px-6 text-earth font-mono text-sm">{{ $modulo->order }}</td>
                    <td class="px-6">
                        <div class="font-semibold text-charcoal flex items-center gap-2">
                            @if($modulo->icon)<i class="fas {{ $modulo->icon }} text-green-600"></i>@endif
                            {{ $modulo->name }}
                        </div>
                    </td>
                    <td class="px-6 text-earth text-sm font-mono">{{ $modulo->slug }}</td>
                    <td class="px-6 text-earth text-sm">{{ $modulo->route ?? 'Sin ruta' }}</td>
                    <td class="px-6">
                        <span class="px-2 py-1 rounded-lg bg-green-100 text-green-700 text-xs font-bold">{{ $modulo->rols->count() }} roles</span>
                    </td>
                    <td class="px-6">
                        <span class="px-3 py-1 rounded-full text-xs font-bold {{ $modulo->is_active ? 'badge-active' : 'badge-inactive' }}">
                            {{ $modulo->is_active ? 'Activo' : 'Inactivo' }}
                        </span>
                    </td>
                    <td class="px-6">
                        <div class="flex gap-2">
                            @if(Auth::user()->canEditModule('sistema'))
                            <button onclick="openModal('modal-editar-modulo-{{ $modulo->id }}')" class="btn-action bg-sun-light text-[#D97706] hover:bg-sun hover:text-white" title="Editar">
                                <i class="fas fa-edit"></i>
                            </button>
                            @endif
                            @if(Auth::user()->canDeleteModule('sistema'))
                            <form id="form-delete-modulo-{{ $modulo->id }}" action="{{ route('sistema.modulos.destroy', $modulo->id) }}" method="POST" class="inline">
                                @csrf
                                @method('DELETE')
                                <button type="button" class="btn-action bg-clay-light text-clay hover:bg-clay hover:text-white" title="Eliminar"
                                    onclick="confirmDelete('form-delete-modulo-{{ $modulo->id }}', 'Se eliminará el módulo de forma permanente.')">
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

{{-- Modal Crear Módulo --}}
<div id="modal-crear-modulo" class="fixed inset-0 bg-black/40 backdrop-blur-sm overflow-y-auto h-full w-full hidden z-50">
    <div class="relative mx-auto w-full max-w-2xl mt-16 mb-8 px-4">
        <div class="bg-white rounded-2xl shadow-2xl border-2 border-wheat overflow-hidden">
            <div class="flex items-center justify-between px-6 py-5 border-b-2 border-wheat">
                <h3 class="font-extrabold text-charcoal text-lg flex items-center gap-2">
                    <i class="fas fa-plus-circle text-green-600"></i> Crear Nuevo Módulo
                </h3>
                <button onclick="closeModal('modal-crear-modulo')" class="w-8 h-8 rounded-xl bg-cream border-2 border-wheat flex items-center justify-center text-earth hover:bg-wheat transition-all">
                    <i class="fas fa-times"></i>
                </button>
            </div>
            <form action="{{ route('sistema.modulos.store') }}" method="POST" class="p-6">
                @csrf
                <div class="grid grid-cols-2 gap-4">
                    <div>
                        <label class="block text-[11px] font-bold text-earth uppercase tracking-wider mb-2">Nombre del Módulo</label>
                        <input type="text" name="name" class="w-full px-4 py-2.5 border-2 border-wheat rounded-xl text-sm font-semibold text-charcoal bg-white focus:outline-none focus:border-green-500 transition-all" required>
                    </div>
                    <div>
                        <label class="block text-[11px] font-bold text-earth uppercase tracking-wider mb-2">Slug</label>
                        <input type="text" name="slug" class="w-full px-4 py-2.5 border-2 border-wheat rounded-xl text-sm font-semibold text-charcoal bg-white focus:outline-none focus:border-green-500 transition-all" required placeholder="ej: socios-beneficiarios">
                    </div>
                    <div>
                        <label class="block text-[11px] font-bold text-earth uppercase tracking-wider mb-2">Icono (FontAwesome)</label>
                        <input type="text" name="icon" class="w-full px-4 py-2.5 border-2 border-wheat rounded-xl text-sm font-semibold text-charcoal bg-white focus:outline-none focus:border-green-500 transition-all" placeholder="ej: fa-users">
                    </div>
                    <div>
                        <label class="block text-[11px] font-bold text-earth uppercase tracking-wider mb-2">Ruta</label>
                        <input type="text" name="route" class="w-full px-4 py-2.5 border-2 border-wheat rounded-xl text-sm font-semibold text-charcoal bg-white focus:outline-none focus:border-green-500 transition-all" placeholder="ej: socios-beneficiarios">
                    </div>
                    <div>
                        <label class="block text-[11px] font-bold text-earth uppercase tracking-wider mb-2">Orden</label>
                        <input type="number" name="order" value="0" class="w-full px-4 py-2.5 border-2 border-wheat rounded-xl text-sm font-semibold text-charcoal bg-white focus:outline-none focus:border-green-500 transition-all" required min="0">
                    </div>
                    <div>
                        <label class="block text-[11px] font-bold text-earth uppercase tracking-wider mb-2">Estado</label>
                        <select name="is_active" class="w-full px-4 py-2.5 border-2 border-wheat rounded-xl text-sm font-semibold text-charcoal bg-white focus:outline-none focus:border-green-500 transition-all">
                            <option value="1">Activo</option>
                            <option value="0">Inactivo</option>
                        </select>
                    </div>
                    <div class="col-span-2">
                        <label class="block text-[11px] font-bold text-earth uppercase tracking-wider mb-2">Descripción</label>
                        <textarea name="description" class="w-full px-4 py-2.5 border-2 border-wheat rounded-xl text-sm font-semibold text-charcoal bg-white focus:outline-none focus:border-green-500 transition-all" rows="2"></textarea>
                    </div>
                </div>
                <div class="flex justify-end gap-3 mt-4 pt-4 border-t-2 border-wheat">
                    <button type="button" onclick="closeModal('modal-crear-modulo')" class="btn-secondary">Cancelar</button>
                    <button type="submit" class="btn-primary"><i class="fas fa-save mr-2"></i> Guardar</button>
                </div>
            </form>
        </div>
    </div>
</div>

{{-- Modales Editar Módulo --}}
@foreach($modulos as $modulo)
<div id="modal-editar-modulo-{{ $modulo->id }}" class="fixed inset-0 bg-black/40 backdrop-blur-sm overflow-y-auto h-full w-full hidden z-50">
    <div class="relative mx-auto w-full max-w-2xl mt-16 mb-8 px-4">
        <div class="bg-white rounded-2xl shadow-2xl border-2 border-wheat overflow-hidden">
            <div class="flex items-center justify-between px-6 py-5 border-b-2 border-wheat">
                <h3 class="font-extrabold text-charcoal text-lg flex items-center gap-2">
                    <i class="fas fa-th-large text-[#D97706]"></i> Editar Módulo
                </h3>
                <button onclick="closeModal('modal-editar-modulo-{{ $modulo->id }}')" class="w-8 h-8 rounded-xl bg-cream border-2 border-wheat flex items-center justify-center text-earth hover:bg-wheat transition-all">
                    <i class="fas fa-times"></i>
                </button>
            </div>
            <form action="{{ route('sistema.modulos.update', $modulo->id) }}" method="POST" class="p-6">
                @csrf
                @method('PUT')
                <div class="grid grid-cols-2 gap-4">
                    <div>
                        <label class="block text-[11px] font-bold text-earth uppercase tracking-wider mb-2">Nombre del Módulo</label>
                        <input type="text" name="name" value="{{ $modulo->name }}" class="w-full px-4 py-2.5 border-2 border-wheat rounded-xl text-sm font-semibold text-charcoal bg-white focus:outline-none focus:border-green-500 transition-all" required>
                    </div>
                    <div>
                        <label class="block text-[11px] font-bold text-earth uppercase tracking-wider mb-2">Slug</label>
                        <input type="text" name="slug" value="{{ $modulo->slug }}" class="w-full px-4 py-2.5 border-2 border-wheat rounded-xl text-sm font-semibold text-charcoal bg-white focus:outline-none focus:border-green-500 transition-all" required>
                    </div>
                    <div>
                        <label class="block text-[11px] font-bold text-earth uppercase tracking-wider mb-2">Icono (FontAwesome)</label>
                        <input type="text" name="icon" value="{{ $modulo->icon }}" class="w-full px-4 py-2.5 border-2 border-wheat rounded-xl text-sm font-semibold text-charcoal bg-white focus:outline-none focus:border-green-500 transition-all">
                    </div>
                    <div>
                        <label class="block text-[11px] font-bold text-earth uppercase tracking-wider mb-2">Ruta</label>
                        <input type="text" name="route" value="{{ $modulo->route }}" class="w-full px-4 py-2.5 border-2 border-wheat rounded-xl text-sm font-semibold text-charcoal bg-white focus:outline-none focus:border-green-500 transition-all">
                    </div>
                    <div>
                        <label class="block text-[11px] font-bold text-earth uppercase tracking-wider mb-2">Orden</label>
                        <input type="number" name="order" value="{{ $modulo->order }}" class="w-full px-4 py-2.5 border-2 border-wheat rounded-xl text-sm font-semibold text-charcoal bg-white focus:outline-none focus:border-green-500 transition-all" required min="0">
                    </div>
                    <div>
                        <label class="block text-[11px] font-bold text-earth uppercase tracking-wider mb-2">Estado</label>
                        <select name="is_active" class="w-full px-4 py-2.5 border-2 border-wheat rounded-xl text-sm font-semibold text-charcoal bg-white focus:outline-none focus:border-green-500 transition-all">
                            <option value="1" {{ $modulo->is_active ? 'selected' : '' }}>Activo</option>
                            <option value="0" {{ !$modulo->is_active ? 'selected' : '' }}>Inactivo</option>
                        </select>
                    </div>
                    <div class="col-span-2">
                        <label class="block text-[11px] font-bold text-earth uppercase tracking-wider mb-2">Descripción</label>
                        <textarea name="description" class="w-full px-4 py-2.5 border-2 border-wheat rounded-xl text-sm font-semibold text-charcoal bg-white focus:outline-none focus:border-green-500 transition-all" rows="2">{{ $modulo->description }}</textarea>
                    </div>
                </div>
                <div class="flex justify-end gap-3 mt-4 pt-4 border-t-2 border-wheat">
                    <button type="button" onclick="closeModal('modal-editar-modulo-{{ $modulo->id }}')" class="btn-secondary">Cancelar</button>
                    <button type="submit" class="btn-primary"><i class="fas fa-save mr-2"></i> Actualizar</button>
                </div>
            </form>
        </div>
    </div>
</div>
@endforeach
@endsection
