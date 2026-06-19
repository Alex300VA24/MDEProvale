@extends('layouts.main')

@section('title', 'Gestión de Usuarios - PROVALE')

@section('content')
<div class="bg-white rounded-2xl border-2 border-wheat shadow-sm overflow-hidden">
    <div class="flex flex-col sm:flex-row items-start sm:items-center justify-between px-4 sm:px-6 py-4 sm:py-5 border-b-2 border-wheat gap-3">
        <h3 class="font-extrabold text-charcoal text-xl flex items-center gap-3">
            <i class="fas fa-users-cog text-[#0284C7]"></i> Gestión de Usuarios
        </h3>
        <div class="flex flex-wrap gap-2">
            <a href="{{ route('sistema.index') }}" class="btn-secondary">
                <i class="fas fa-arrow-left mr-2"></i> Volver
            </a>
            @if(Auth::user()->canCreateModule('sistema'))
            <button onclick="openModal('modal-crear-usuario')" class="btn-primary">
                <i class="fas fa-plus mr-2"></i> Nuevo Usuario
            </button>
            @endif
        </div>
    </div>

    <div class="overflow-x-auto -mx-4 sm:mx-0">
        <table class="data-table w-full min-w-[700px] text-xs sm:text-sm">
            <thead>
                <tr>
                    <th class="px-3 sm:px-4 py-4 text-left">Usuario</th>
                    <th class="px-3 sm:px-4 py-4 text-left">Email</th>
                    <th class="px-3 sm:px-4 py-4 text-left">DNI</th>
                    <th class="px-3 sm:px-4 py-4 text-left">Rol</th>
                    <th class="px-3 sm:px-4 py-4 text-left">Estado</th>
                    <th class="px-3 sm:px-4 py-4 text-left">Acciones</th>
                </tr>
            </thead>
            <tbody>
                @foreach($usuarios as $usuario)
                <tr>
                    <td class="px-3 sm:px-4">
                        <div class="font-semibold text-charcoal">{{ $usuario->names }} {{ $usuario->father_surname }} {{ $usuario->mother_surname }}</div>
                        <div class="text-xs text-earth">{{ $usuario->username }}</div>
                    </td>
                    <td class="px-3 sm:px-4 text-earth text-sm">{{ $usuario->email }}</td>
                    <td class="px-3 sm:px-4 text-earth text-sm">{{ $usuario->dni }}</td>
                    <td class="px-3 sm:px-4">
                        <span class="px-2 py-1 rounded-lg bg-sky-light text-[#0284C7] text-xs font-bold">
                            {{ $usuario->rol->title ?? 'Sin rol' }}
                        </span>
                    </td>
                    <td class="px-3 sm:px-4">
                        <span class="px-3 py-1 rounded-full text-xs font-bold {{ $usuario->state_id == 1 ? 'badge-active' : 'badge-inactive' }}">
                            {{ $usuario->state->title ?? 'Sin estado' }}
                        </span>
                    </td>
                    <td class="px-3 sm:px-4">
                        <div class="flex gap-2">
                            @if(Auth::user()->canEditModule('sistema'))
                            <button onclick="openModal('modal-editar-usuario-{{ $usuario->id }}')" class="btn-action bg-sun-light text-[#D97706] hover:bg-sun hover:text-white" title="Editar">
                                <i class="fas fa-edit"></i>
                            </button>
                            @endif
                            @if(Auth::user()->canEditModule('sistema'))
                            <button onclick="confirmResetPassword('{{ $usuario->id }}', '{{ addslashes($usuario->names) }} {{ addslashes($usuario->father_surname) }}', '{{ $usuario->dni }}')" class="btn-action bg-red-100 text-red-600 hover:bg-red-500 hover:text-white" title="Restablecer contraseña a DNI">
                                <i class="fas fa-key"></i>
                            </button>
                            <form id="form-reset-password-{{ $usuario->id }}" action="{{ route('sistema.usuarios.reset-password', $usuario->id) }}" method="POST" class="hidden">
                                @csrf
                            </form>
                            @endif
                            @if(Auth::user()->canDeleteModule('sistema'))
                            <form id="form-delete-usuario-{{ $usuario->id }}" action="{{ route('sistema.usuarios.destroy', $usuario->id) }}" method="POST" class="inline">
                                @csrf
                                @method('DELETE')
                                <button type="button" class="btn-action bg-clay-light text-clay hover:bg-clay hover:text-white" title="Eliminar"
                                    onclick="confirmDelete('form-delete-usuario-{{ $usuario->id }}', 'Se eliminará el usuario {{ addslashes($usuario->username) }} de forma permanente.')">
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

{{-- Modal Crear Usuario --}}
<div id="modal-crear-usuario" class="fixed inset-0 bg-black/40 backdrop-blur-sm overflow-y-auto h-full w-full hidden z-50">
    <div class="relative mx-auto w-full max-w-full sm:max-w-2xl mt-8 sm:mt-16 mb-8 px-2 sm:px-4">
        <div class="bg-white rounded-2xl shadow-2xl border-2 border-wheat overflow-hidden">
            <div class="flex items-center justify-between px-6 py-5 border-b-2 border-wheat">
                <h3 class="font-extrabold text-charcoal text-lg flex items-center gap-2">
                    <i class="fas fa-user-plus text-[#0284C7]"></i> Crear Nuevo Usuario
                </h3>
                <button onclick="closeModal('modal-crear-usuario')" class="w-8 h-8 rounded-xl bg-cream border-2 border-wheat flex items-center justify-center text-earth hover:bg-wheat transition-all">
                    <i class="fas fa-times"></i>
                </button>
            </div>
            <form action="{{ route('sistema.usuarios.store') }}" method="POST" class="p-6">
                @csrf
                <div class="grid grid-cols-2 gap-4">
                    <div>
                        <label class="block text-[11px] font-bold text-earth uppercase tracking-wider mb-2">Nombres</label>
                        <input type="text" name="names" class="w-full px-4 py-2.5 border-2 border-wheat rounded-xl text-sm font-semibold text-charcoal bg-white focus:outline-none focus:border-[#0284C7] transition-all" required>
                    </div>
                    <div>
                        <label class="block text-[11px] font-bold text-earth uppercase tracking-wider mb-2">Apellido Paterno</label>
                        <input type="text" name="father_surname" class="w-full px-4 py-2.5 border-2 border-wheat rounded-xl text-sm font-semibold text-charcoal bg-white focus:outline-none focus:border-[#0284C7] transition-all" required>
                    </div>
                    <div>
                        <label class="block text-[11px] font-bold text-earth uppercase tracking-wider mb-2">Apellido Materno</label>
                        <input type="text" name="mother_surname" class="w-full px-4 py-2.5 border-2 border-wheat rounded-xl text-sm font-semibold text-charcoal bg-white focus:outline-none focus:border-[#0284C7] transition-all" required>
                    </div>
                    <div>
                        <label class="block text-[11px] font-bold text-earth uppercase tracking-wider mb-2">Username</label>
                        <input type="text" name="username" class="w-full px-4 py-2.5 border-2 border-wheat rounded-xl text-sm font-semibold text-charcoal bg-white focus:outline-none focus:border-[#0284C7] transition-all" required>
                    </div>
                    <div>
                        <label class="block text-[11px] font-bold text-earth uppercase tracking-wider mb-2">Email</label>
                        <input type="email" name="email" class="w-full px-4 py-2.5 border-2 border-wheat rounded-xl text-sm font-semibold text-charcoal bg-white focus:outline-none focus:border-[#0284C7] transition-all" required>
                    </div>
                    <div>
                        <label class="block text-[11px] font-bold text-earth uppercase tracking-wider mb-2">DNI</label>
                        <input type="text" name="dni" class="w-full px-4 py-2.5 border-2 border-wheat rounded-xl text-sm font-semibold text-charcoal bg-white focus:outline-none focus:border-[#0284C7] transition-all" required maxlength="8">
                    </div>
                    <div>
                        <label class="block text-[11px] font-bold text-earth uppercase tracking-wider mb-2">CUI</label>
                        <input type="text" name="cui" class="w-full px-4 py-2.5 border-2 border-wheat rounded-xl text-sm font-semibold text-charcoal bg-white focus:outline-none focus:border-[#0284C7] transition-all" maxlength="1">
                    </div>
                    <div>
                        <label class="block text-[11px] font-bold text-earth uppercase tracking-wider mb-2">Rol</label>
                        <select name="rol_id" class="w-full px-4 py-2.5 border-2 border-wheat rounded-xl text-sm font-semibold text-charcoal bg-white focus:outline-none focus:border-[#0284C7] transition-all" required>
                            <option value="">Seleccionar rol</option>
                            @foreach($roles as $rol)
                                <option value="{{ $rol->id }}">{{ $rol->title }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div>
                        <label class="block text-[11px] font-bold text-earth uppercase tracking-wider mb-2">Estado</label>
                        <select name="state_id" class="w-full px-4 py-2.5 border-2 border-wheat rounded-xl text-sm font-semibold text-charcoal bg-white focus:outline-none focus:border-[#0284C7] transition-all" required>
                            @foreach($estados as $estado)
                                <option value="{{ $estado->id }}">{{ $estado->title }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="col-span-2">
                        <label class="block text-[11px] font-bold text-earth uppercase tracking-wider mb-2">Password</label>
                        <input type="password" name="password" class="w-full px-4 py-2.5 border-2 border-wheat rounded-xl text-sm font-semibold text-charcoal bg-white focus:outline-none focus:border-[#0284C7] transition-all" required minlength="8">
                    </div>
                </div>
                <div class="flex justify-end gap-3 mt-6 pt-4 border-t-2 border-wheat">
                    <button type="button" onclick="closeModal('modal-crear-usuario')" class="btn-secondary">Cancelar</button>
                    <button type="submit" class="btn-primary"><i class="fas fa-save mr-2"></i> Guardar</button>
                </div>
            </form>
        </div>
    </div>
</div>

{{-- Modales Editar Usuario --}}
@foreach($usuarios as $usuario)
<div id="modal-editar-usuario-{{ $usuario->id }}" class="fixed inset-0 bg-black/40 backdrop-blur-sm overflow-y-auto h-full w-full hidden z-50">
    <div class="relative mx-auto w-full max-w-full sm:max-w-2xl mt-8 sm:mt-16 mb-8 px-2 sm:px-4">
        <div class="bg-white rounded-2xl shadow-2xl border-2 border-wheat overflow-hidden">
            <div class="flex items-center justify-between px-6 py-5 border-b-2 border-wheat">
                <h3 class="font-extrabold text-charcoal text-lg flex items-center gap-2">
                    <i class="fas fa-user-edit text-[#D97706]"></i> Editar Usuario
                </h3>
                <button onclick="closeModal('modal-editar-usuario-{{ $usuario->id }}')" class="w-8 h-8 rounded-xl bg-cream border-2 border-wheat flex items-center justify-center text-earth hover:bg-wheat transition-all">
                    <i class="fas fa-times"></i>
                </button>
            </div>
            <form action="{{ route('sistema.usuarios.update', $usuario->id) }}" method="POST" class="p-6">
                @csrf
                @method('PUT')
                <div class="grid grid-cols-2 gap-4">
                    <div>
                        <label class="block text-[11px] font-bold text-earth uppercase tracking-wider mb-2">Nombres</label>
                        <input type="text" name="names" value="{{ $usuario->names }}" class="w-full px-4 py-2.5 border-2 border-wheat rounded-xl text-sm font-semibold text-charcoal bg-white focus:outline-none focus:border-[#0284C7] transition-all" required>
                    </div>
                    <div>
                        <label class="block text-[11px] font-bold text-earth uppercase tracking-wider mb-2">Apellido Paterno</label>
                        <input type="text" name="father_surname" value="{{ $usuario->father_surname }}" class="w-full px-4 py-2.5 border-2 border-wheat rounded-xl text-sm font-semibold text-charcoal bg-white focus:outline-none focus:border-[#0284C7] transition-all" required>
                    </div>
                    <div>
                        <label class="block text-[11px] font-bold text-earth uppercase tracking-wider mb-2">Apellido Materno</label>
                        <input type="text" name="mother_surname" value="{{ $usuario->mother_surname }}" class="w-full px-4 py-2.5 border-2 border-wheat rounded-xl text-sm font-semibold text-charcoal bg-white focus:outline-none focus:border-[#0284C7] transition-all" required>
                    </div>
                    <div>
                        <label class="block text-[11px] font-bold text-earth uppercase tracking-wider mb-2">Username</label>
                        <input type="text" name="username" value="{{ $usuario->username }}" class="w-full px-4 py-2.5 border-2 border-wheat rounded-xl text-sm font-semibold text-charcoal bg-white focus:outline-none focus:border-[#0284C7] transition-all" required>
                    </div>
                    <div>
                        <label class="block text-[11px] font-bold text-earth uppercase tracking-wider mb-2">Email</label>
                        <input type="email" name="email" value="{{ $usuario->email }}" class="w-full px-4 py-2.5 border-2 border-wheat rounded-xl text-sm font-semibold text-charcoal bg-white focus:outline-none focus:border-[#0284C7] transition-all" required>
                    </div>
                    <div>
                        <label class="block text-[11px] font-bold text-earth uppercase tracking-wider mb-2">DNI</label>
                        <input type="text" name="dni" value="{{ $usuario->dni }}" class="w-full px-4 py-2.5 border-2 border-wheat rounded-xl text-sm font-semibold text-charcoal bg-white focus:outline-none focus:border-[#0284C7] transition-all" required maxlength="8">
                    </div>
                    <div>
                        <label class="block text-[11px] font-bold text-earth uppercase tracking-wider mb-2">CUI</label>
                        <input type="text" name="cui" value="{{ $usuario->cui }}" class="w-full px-4 py-2.5 border-2 border-wheat rounded-xl text-sm font-semibold text-charcoal bg-white focus:outline-none focus:border-[#0284C7] transition-all" maxlength="1">
                    </div>
                    <div>
                        <label class="block text-[11px] font-bold text-earth uppercase tracking-wider mb-2">Rol</label>
                        <select name="rol_id" class="w-full px-4 py-2.5 border-2 border-wheat rounded-xl text-sm font-semibold text-charcoal bg-white focus:outline-none focus:border-[#0284C7] transition-all" required>
                            @foreach($roles as $rol)
                                <option value="{{ $rol->id }}" {{ $usuario->rol_id == $rol->id ? 'selected' : '' }}>{{ $rol->title }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div>
                        <label class="block text-[11px] font-bold text-earth uppercase tracking-wider mb-2">Estado</label>
                        <select name="state_id" class="w-full px-4 py-2.5 border-2 border-wheat rounded-xl text-sm font-semibold text-charcoal bg-white focus:outline-none focus:border-[#0284C7] transition-all" required>
                            @foreach($estados as $estado)
                                <option value="{{ $estado->id }}" {{ $usuario->state_id == $estado->id ? 'selected' : '' }}>{{ $estado->title }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="col-span-2">
                        <label class="block text-[11px] font-bold text-earth uppercase tracking-wider mb-2">Password <span class="text-earth font-normal normal-case">(dejar en blanco para mantener)</span></label>
                        <input type="password" name="password" class="w-full px-4 py-2.5 border-2 border-wheat rounded-xl text-sm font-semibold text-charcoal bg-white focus:outline-none focus:border-[#0284C7] transition-all" minlength="8">
                    </div>
                </div>
                <div class="flex justify-end gap-3 mt-6 pt-4 border-t-2 border-wheat">
                    <button type="button" onclick="closeModal('modal-editar-usuario-{{ $usuario->id }}')" class="btn-secondary">Cancelar</button>
                    <button type="submit" class="btn-primary"><i class="fas fa-save mr-2"></i> Actualizar</button>
                </div>
            </form>
        </div>
    </div>
</div>
@endforeach
@endsection
