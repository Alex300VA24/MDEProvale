@extends('layouts.main')

@section('title', 'Mantenimiento - PROVALE')

@section('content')
<div class="bg-white rounded-2xl border-2 border-wheat shadow-sm overflow-hidden">
    <div class="flex items-center justify-between px-6 py-5 border-b-2 border-wheat">
        <h3 class="font-extrabold text-charcoal text-xl flex items-center gap-3">
            <i class="fas fa-tools text-leaf"></i> Mantenimiento
        </h3>
    </div>

    <div class="p-6 space-y-8">

        @if(session('success'))
            <div class="p-4 bg-green-50 border-2 border-green-200 rounded-xl text-green-700">
                <i class="fas fa-check-circle mr-2"></i>{{ session('success') }}
            </div>
        @endif

        {{-- Responsables de Almacén --}}
        <div>
            <h4 class="font-extrabold text-charcoal text-lg mb-4 flex items-center gap-2">
                <i class="fas fa-user-tie text-leaf"></i> Responsables de Almacén
            </h4>
            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">

                @php
                    $chief      = $responsibles->where('type', 'chief')->where('active', true)->first();
                    $storekeeper = $responsibles->where('type', 'storekeeper')->where('active', true)->first();
                @endphp

                {{-- Jefe de Almacén --}}
                <div class="bg-cream rounded-xl p-6 border-2 border-wheat">
                    <div class="flex items-center gap-3 mb-4">
                        <div class="w-10 h-10 rounded-xl bg-leaf-light flex items-center justify-center text-leaf">
                            <i class="fas fa-user-shield"></i>
                        </div>
                        <div>
                            <p class="text-[11px] font-bold text-earth uppercase tracking-wider">Jefe de Almacén</p>
                            <p class="font-bold text-charcoal">
                                {{ $chief && $chief->person ? $chief->person->names . ' ' . $chief->person->father_lastname . ' ' . $chief->person->mother_lastname : 'Sin asignar' }}
                            </p>
                            @if($chief && $chief->person)
                                <p class="text-xs text-earth">DNI: {{ $chief->person->dni }}</p>
                            @endif
                        </div>
                    </div>
                    <button onclick="openModal('modal-chief')" class="btn-primary w-full text-sm">
                        <i class="fas fa-exchange-alt mr-2"></i> Cambiar Jefe de Almacén
                    </button>
                </div>

                {{-- Almacenero --}}
                <div class="bg-cream rounded-xl p-6 border-2 border-wheat">
                    <div class="flex items-center gap-3 mb-4">
                        <div class="w-10 h-10 rounded-xl bg-sky-light flex items-center justify-center text-[#0284C7]">
                            <i class="fas fa-warehouse"></i>
                        </div>
                        <div>
                            <p class="text-[11px] font-bold text-earth uppercase tracking-wider">Almacenero</p>
                            <p class="font-bold text-charcoal">
                                {{ $storekeeper && $storekeeper->person ? $storekeeper->person->names . ' ' . $storekeeper->person->father_lastname . ' ' . $storekeeper->person->mother_lastname : 'Sin asignar' }}
                            </p>
                            @if($storekeeper && $storekeeper->person)
                                <p class="text-xs text-earth">DNI: {{ $storekeeper->person->dni }}</p>
                            @endif
                        </div>
                    </div>
                    <button onclick="openModal('modal-storekeeper')" class="btn-primary w-full text-sm">
                        <i class="fas fa-exchange-alt mr-2"></i> Cambiar Almacenero
                    </button>
                </div>

            </div>
        </div>

        {{-- Raciones por Año --}}
        <div>
            <h4 class="font-extrabold text-charcoal text-lg mb-4 flex items-center gap-2">
                <i class="fas fa-utensils text-leaf"></i> Raciones por Año
            </h4>
            <div class="mb-4">
                <button onclick="openModal('modal-crear-racion')" class="btn-primary text-sm">
                    <i class="fas fa-plus mr-2"></i> Nueva Ración
                </button>
            </div>
            <div class="overflow-x-auto">
                <table class="w-full text-sm">
                    <thead class="bg-gray-50">
                        <tr>
                            <th class="px-4 py-2 text-left font-bold text-earth">Año</th>
                            <th class="px-4 py-2 text-left font-bold text-earth">Ración Hojuelas (g)</th>
                            <th class="px-4 py-2 text-left font-bold text-earth">Ración Leche (ml)</th>
                            <th class="px-4 py-2 text-center font-bold text-earth">Estado</th>
                            <th class="px-4 py-2 text-center font-bold text-earth">Acciones</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-200">
                        @forelse($raciones as $racion)
                        <tr>
                            <td class="px-4 py-3 font-bold">{{ $racion->year }}</td>
                            <td class="px-4 py-3">{{ $racion->racion_hojuelas_gramos }} g</td>
                            <td class="px-4 py-3">{{ $racion->racion_leche_militros }} ml</td>
                            <td class="px-4 py-3 text-center">
                                <span class="px-2 py-1 text-xs font-semibold rounded-full {{ $racion->active ? 'bg-green-100 text-green-800' : 'bg-gray-100 text-gray-800' }}">
                                    {{ $racion->active ? 'Activo' : 'Inactivo' }}
                                </span>
                            </td>
                            <td class="px-4 py-3 text-center">
                                <button onclick="openModal('modal-editar-racion-{{ $racion->id }}')" class="btn-action bg-sun-light text-[#D97706] hover:bg-sun hover:text-white" title="Editar">
                                    <i class="fas fa-edit"></i>
                                </button>
                                <form action="{{ route('mantenimiento.raciones.destroy', $racion->id) }}" method="POST" class="inline">
                                    @csrf
                                    @method('DELETE')
                                    <button type="button" class="btn-action bg-clay-light text-clay hover:bg-clay hover:text-white" title="Eliminar"
                                        onclick="confirmDelete('racion-{{ $racion->id }}', '¿Eliminar esta ración?')">
                                        <i class="fas fa-trash"></i>
                                    </button>
                                </form>
                            </td>
                        </tr>
                        @empty
                        <tr>
                            <td colspan="5" class="px-4 py-8 text-center text-gray-500">
                                No hay raciones registradas
                            </td>
                        </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
        </div>
        <div>
            <h4 class="font-extrabold text-charcoal text-lg mb-4 flex items-center gap-2">
                <i class="fas fa-cog text-leaf"></i> Configuración General
            </h4>
            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
                <div class="bg-cream rounded-xl p-6 border-2 border-wheat">
                    <div class="w-12 h-12 rounded-xl bg-leaf-light flex items-center justify-center text-leaf text-xl mb-4">
                        <i class="fas fa-building"></i>
                    </div>
                    <h4 class="font-bold text-charcoal mb-2">Organizaciones</h4>
                    <p class="text-sm text-earth mb-4">Gestionar organizaciones y sedes</p>
                    <a href="{{ route('club-reconocimientos.index') }}" class="btn-primary w-full block text-center">Administrar</a>
                </div>
                <div class="bg-cream rounded-xl p-6 border-2 border-wheat">
                    <div class="w-12 h-12 rounded-xl bg-sky-light flex items-center justify-center text-[#0284C7] text-xl mb-4">
                        <i class="fas fa-map-marker-alt"></i>
                    </div>
                    <h4 class="font-bold text-charcoal mb-2">Ubigeo</h4>
                    <p class="text-sm text-earth mb-4">Configurar departamentos, provincias y distritos</p>
                    <button class="btn-secondary w-full">Configurar</button>
                </div>
                <div class="bg-cream rounded-xl p-6 border-2 border-wheat">
                    <div class="w-12 h-12 rounded-xl bg-sun-light flex items-center justify-center text-[#D97706] text-xl mb-4">
                        <i class="fas fa-tags"></i>
                    </div>
                    <h4 class="font-bold text-charcoal mb-2">Tablas Auxiliares</h4>
                    <p class="text-sm text-earth mb-4">Administrar tablas de datos auxiliar</p>
                    <button class="btn-secondary w-full">Ver Tablas</button>
                </div>
            </div>
        </div>

    </div>
</div>

{{-- Modal Cambiar Jefe de Almacén --}}
<div id="modal-chief" class="fixed inset-0 bg-black/40 backdrop-blur-sm overflow-y-auto h-full w-full hidden z-50">
    <div class="relative mx-auto w-full max-w-md mt-32 mb-8 px-4">
        <div class="bg-white rounded-2xl shadow-2xl border-2 border-wheat overflow-hidden">
            <div class="flex items-center justify-between px-6 py-5 border-b-2 border-wheat">
                <h3 class="font-extrabold text-charcoal text-lg flex items-center gap-2">
                    <i class="fas fa-user-shield text-leaf"></i> Cambiar Jefe de Almacén
                </h3>
                <button onclick="closeModal('modal-chief')" class="w-8 h-8 rounded-xl bg-cream border-2 border-wheat flex items-center justify-center text-earth hover:bg-wheat transition-all">
                    <i class="fas fa-times"></i>
                </button>
            </div>
            <form action="{{ route('mantenimiento.responsibles.update', 'chief') }}" method="POST" class="p-6 space-y-4">
                @csrf
                <div>
                    <label class="block text-[11px] font-bold text-earth uppercase tracking-wider mb-2">Seleccionar Persona</label>
                    <select name="person_id" class="w-full px-4 py-2.5 border-2 border-wheat rounded-xl text-sm font-semibold text-charcoal bg-white focus:outline-none focus:border-leaf transition-all" required>
                        <option value="">Seleccionar...</option>
                        @foreach($people as $person)
                            <option value="{{ $person->id }}" {{ $chief && $chief->person_id == $person->id ? 'selected' : '' }}>
                                {{ $person->names }} {{ $person->father_lastname }} {{ $person->mother_lastname }} - {{ $person->dni }}
                            </option>
                        @endforeach
                    </select>
                </div>
                <div class="flex gap-3 pt-2">
                    <button type="submit" class="btn-primary flex-1"><i class="fas fa-save mr-2"></i> Guardar</button>
                    <button type="button" onclick="closeModal('modal-chief')" class="btn-secondary flex-1">Cancelar</button>
                </div>
            </form>
        </div>
    </div>
</div>

{{-- Modal Cambiar Almacenero --}}
<div id="modal-storekeeper" class="fixed inset-0 bg-black/40 backdrop-blur-sm overflow-y-auto h-full w-full hidden z-50">
    <div class="relative mx-auto w-full max-w-md mt-32 mb-8 px-4">
        <div class="bg-white rounded-2xl shadow-2xl border-2 border-wheat overflow-hidden">
            <div class="flex items-center justify-between px-6 py-5 border-b-2 border-wheat">
                <h3 class="font-extrabold text-charcoal text-lg flex items-center gap-2">
                    <i class="fas fa-warehouse text-[#0284C7]"></i> Cambiar Almacenero
                </h3>
                <button onclick="closeModal('modal-storekeeper')" class="w-8 h-8 rounded-xl bg-cream border-2 border-wheat flex items-center justify-center text-earth hover:bg-wheat transition-all">
                    <i class="fas fa-times"></i>
                </button>
            </div>
            <form action="{{ route('mantenimiento.responsibles.update', 'storekeeper') }}" method="POST" class="p-6 space-y-4">
                @csrf
                <div>
                    <label class="block text-[11px] font-bold text-earth uppercase tracking-wider mb-2">Seleccionar Persona</label>
                    <select name="person_id" class="w-full px-4 py-2.5 border-2 border-wheat rounded-xl text-sm font-semibold text-charcoal bg-white focus:outline-none focus:border-leaf transition-all" required>
                        <option value="">Seleccionar...</option>
                        @foreach($people as $person)
                            <option value="{{ $person->id }}" {{ $storekeeper && $storekeeper->person_id == $person->id ? 'selected' : '' }}>
                                {{ $person->names }} {{ $person->father_lastname }} {{ $person->mother_lastname }} - {{ $person->dni }}
                            </option>
                        @endforeach
                    </select>
                </div>
                <div class="flex gap-3 pt-2">
                    <button type="submit" class="btn-primary flex-1"><i class="fas fa-save mr-2"></i> Guardar</button>
                    <button type="button" onclick="closeModal('modal-storekeeper')" class="btn-secondary flex-1">Cancelar</button>
                </div>
            </form>
        </div>
    </div>
</div>

{{-- Modal Crear Ración --}}
<div id="modal-crear-racion" class="fixed inset-0 bg-black/40 backdrop-blur-sm overflow-y-auto h-full w-full hidden z-50">
    <div class="relative mx-auto w-full max-w-md mt-32 mb-8 px-4">
        <div class="bg-white rounded-2xl shadow-2xl border-2 border-wheat overflow-hidden">
            <div class="flex items-center justify-between px-6 py-5 border-b-2 border-wheat">
                <h3 class="font-extrabold text-charcoal text-lg flex items-center gap-2">
                    <i class="fas fa-utensils text-leaf"></i> Nueva Ración
                </h3>
                <button onclick="closeModal('modal-crear-racion')" class="w-8 h-8 rounded-xl bg-cream border-2 border-wheat flex items-center justify-center text-earth hover:bg-wheat transition-all">
                    <i class="fas fa-times"></i>
                </button>
            </div>
            <form action="{{ route('mantenimiento.raciones.store') }}" method="POST" class="p-6 space-y-4">
                @csrf
                <div>
                    <label class="block text-[11px] font-bold text-earth uppercase tracking-wider mb-2">Año</label>
                    <input type="number" name="year" value="{{ date('Y') }}" class="w-full px-4 py-2.5 border-2 border-wheat rounded-xl text-sm font-semibold text-charcoal bg-white focus:outline-none focus:border-leaf transition-all" required min="2000" max="2100">
                </div>
                <div>
                    <label class="block text-[11px] font-bold text-earth uppercase tracking-wider mb-2">Ración Hojuelas (gramos)</label>
                    <input type="number" step="0.01" name="racion_hojuelas_gramos" class="w-full px-4 py-2.5 border-2 border-wheat rounded-xl text-sm font-semibold text-charcoal bg-white focus:outline-none focus:border-leaf transition-all" required min="0" placeholder="Ej: 500.00">
                </div>
                <div>
                    <label class="block text-[11px] font-bold text-earth uppercase tracking-wider mb-2">Ración Leche (mililitros)</label>
                    <input type="number" step="0.01" name="racion_leche_militros" class="w-full px-4 py-2.5 border-2 border-wheat rounded-xl text-sm font-semibold text-charcoal bg-white focus:outline-none focus:border-leaf transition-all" required min="0" placeholder="Ej: 1000.00">
                </div>
                <div class="flex gap-3 pt-2">
                    <button type="submit" class="btn-primary flex-1"><i class="fas fa-save mr-2"></i> Guardar</button>
                    <button type="button" onclick="closeModal('modal-crear-racion')" class="btn-secondary flex-1">Cancelar</button>
                </div>
            </form>
        </div>
    </div>
</div>

{{-- Modales Editar Ración --}}
@foreach($raciones as $racion)
<div id="modal-editar-racion-{{ $racion->id }}" class="fixed inset-0 bg-black/40 backdrop-blur-sm overflow-y-auto h-full w-full hidden z-50">
    <div class="relative mx-auto w-full max-w-md mt-32 mb-8 px-4">
        <div class="bg-white rounded-2xl shadow-2xl border-2 border-wheat overflow-hidden">
            <div class="flex items-center justify-between px-6 py-5 border-b-2 border-wheat">
                <h3 class="font-extrabold text-charcoal text-lg flex items-center gap-2">
                    <i class="fas fa-utensils text-leaf"></i> Editar Ración {{ $racion->year }}
                </h3>
                <button onclick="closeModal('modal-editar-racion-{{ $racion->id }}')" class="w-8 h-8 rounded-xl bg-cream border-2 border-wheat flex items-center justify-center text-earth hover:bg-wheat transition-all">
                    <i class="fas fa-times"></i>
                </button>
            </div>
            <form action="{{ route('mantenimiento.raciones.update', $racion->id) }}" method="POST" class="p-6 space-y-4">
                @csrf
                @method('PUT')
                <div>
                    <label class="block text-[11px] font-bold text-earth uppercase tracking-wider mb-2">Año</label>
                    <input type="number" name="year" value="{{ $racion->year }}" class="w-full px-4 py-2.5 border-2 border-wheat rounded-xl text-sm font-semibold text-charcoal bg-white focus:outline-none focus:border-leaf transition-all" required min="2000" max="2100" readonly>
                </div>
                <div>
                    <label class="block text-[11px] font-bold text-earth uppercase tracking-wider mb-2">Ración Hojuelas (gramos)</label>
                    <input type="number" step="0.01" name="racion_hojuelas_gramos" value="{{ $racion->racion_hojuelas_gramos }}" class="w-full px-4 py-2.5 border-2 border-wheat rounded-xl text-sm font-semibold text-charcoal bg-white focus:outline-none focus:border-leaf transition-all" required min="0">
                </div>
                <div>
                    <label class="block text-[11px] font-bold text-earth uppercase tracking-wider mb-2">Ración Leche (mililitros)</label>
                    <input type="number" step="0.01" name="racion_leche_militros" value="{{ $racion->racion_leche_militros }}" class="w-full px-4 py-2.5 border-2 border-wheat rounded-xl text-sm font-semibold text-charcoal bg-white focus:outline-none focus:border-leaf transition-all" required min="0">
                </div>
                <div class="flex gap-3 pt-2">
                    <button type="submit" class="btn-primary flex-1"><i class="fas fa-save mr-2"></i> Actualizar</button>
                    <button type="button" onclick="closeModal('modal-editar-racion-{{ $racion->id }}')" class="btn-secondary flex-1">Cancelar</button>
                </div>
            </form>
        </div>
    </div>
</div>
@endforeach

@endsection
