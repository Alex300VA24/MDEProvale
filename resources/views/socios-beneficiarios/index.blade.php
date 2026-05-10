@extends('layouts.main')

@section('title', 'Socios y Beneficiarios - PROVALE')

@section('content')
<div class="bg-white rounded-2xl border-2 border-wheat shadow-sm overflow-hidden">
    <div class="flex items-center justify-between px-6 py-5 border-b-2 border-wheat">
        <h3 class="font-extrabold text-charcoal text-xl flex items-center gap-3">
            <i class="fas fa-users text-leaf"></i> Socios y Beneficiarios
        </h3>
        <div class="flex items-center gap-2">
            <a href="{{ route('socios-beneficiarios.personas.index') }}" class="btn-primary flex items-center gap-2">
                <i class="fas fa-users"></i> Personas
            </a>
            @if(Auth::user()->canCreateModule('socios-beneficiarios'))
            <button onclick="openModal('modal-crear-socio')" class="btn-primary flex items-center gap-2">
                <i class="fas fa-plus"></i> Nuevo Socio
            </button>
            @endif
            <a href="{{ route('socios-beneficiarios.beneficiarios.imprimir') }}" target="_blank" class="btn-secondary flex items-center gap-2">
                <i class="fas fa-id-card"></i> Ficha
            </a>
            <a href="{{ route('socios-beneficiarios.beneficiarios.padron') }}" class="btn-secondary flex items-center gap-2">
                <i class="fas fa-clipboard-list"></i> Padrón
            </a>
        </div>
    </div>

    <div class="p-6">
        @if(session('success'))
            <div class="mb-4 p-4 bg-green-50 border-2 border-green-200 rounded-xl text-green-700">
                <i class="fas fa-check-circle mr-2"></i>{{ session('success') }}
            </div>
        @endif
        @if(session('error'))
            <div class="mb-4 p-4 bg-red-50 border-2 border-red-200 rounded-xl text-red-700">
                <i class="fas fa-exclamation-circle mr-2"></i>{{ session('error') }}
            </div>
        @endif

        <form method="GET" class="mb-6">
            <div class="flex gap-4">
                <div class="flex-1">
                    <input type="text" name="search" value="{{ request('search') }}" 
                        placeholder="Buscar por nombre o DNI..." 
                        class="w-full px-10 py-2.5 border-2 border-wheat rounded-xl text-sm font-semibold text-charcoal bg-white focus:outline-none focus:border-leaf transition-all">
                </div>

                <div>
                    <select name="association_id" 
                        class="w-32 px-4 py-2.5 border-2 border-wheat rounded-xl text-sm font-semibold text-charcoal bg-white focus:outline-none focus:border-leaf transition-all">
                        <option value="">Todos los Clubes</option>
                        @foreach($associations as $association)
                            <option value="{{ $association->id }}" {{ request('association_id') == $association->id ? 'selected' : '' }}>
                                {{ $association->name }}
                            </option>
                        @endforeach
                    </select>
                </div>

                <div>
                    <select name="state_id" 
                        class="w-full px-4 py-2.5 border-2 border-wheat rounded-xl text-sm font-semibold text-charcoal bg-white focus:outline-none focus:border-leaf transition-all">
                        <option value="">Todos los Estados</option>
                        @foreach($states as $state)
                            <option value="{{ $state->id }}" {{ request('state_id') == $state->id ? 'selected' : '' }}>
                                {{ $state->title }}
                            </option>
                        @endforeach
                    </select>
                </div>
                <div>
                    <button type="submit" class="btn-primary"><i class="fas fa-search mr-2"></i> Buscar</button>
                    <a href="{{ route('socios-beneficiarios.index') }}" class="btn-secondary"><i class="fas fa-broom mr-2"></i> Limpiar</a>
                </div>
                
            </div>
        </form>

        <div class="overflow-x-auto">
            <table class="w-full text-sm">
                <thead class="bg-gray-50">
                    <tr>
                        <th class="px-4 py-3 text-left font-bold text-earth">ID</th>
                        <th class="px-4 py-3 text-left font-bold text-earth">Socio</th>
                        <th class="px-4 py-3 text-left font-bold text-earth">DNI</th>
                        <th class="px-4 py-3 text-left font-bold text-earth">Club</th>
                        <th class="px-4 py-3 text-left font-bold text-earth">Estado</th>
                        <th class="px-4 py-3 text-left font-bold text-earth">Beneficiarios</th>
                        <th class="px-4 py-3 text-center font-bold text-earth">Acciones</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-200">
                    @forelse($partners as $partner)
                    <tr class="hover:bg-gray-50">
                        <td class="px-4 py-3">#{{ $partner->id }}</td>
                        <td class="px-4 py-3 font-medium">
                            {{ $partner->people ? $partner->people->names . ' ' . $partner->people->father_lastname : 'Sin nombre' }}
                        </td>
                        <td class="px-4 py-3">{{ $partner->people->dni ?? 'Sin DNI' }}</td>
                        <td class="px-4 py-3">{{ $partner->association->name ?? 'Sin club' }}</td>
                        <td class="px-4 py-3">
                            <span class="px-2 py-1 text-xs font-semibold rounded-full 
                                @if($partner->state && $partner->state->title == 'Activo') bg-green-100 text-green-800
                                @else bg-red-100 text-red-800 @endif">
                                {{ $partner->state->title ?? 'Sin estado' }}
                            </span>
                        </td>
                        <td class="px-4 py-3 text-center">
                            <span class="font-bold text-leaf">{{ $partner->beneficiaries->count() }}</span>
                        </td>
                        <td class="px-4 py-3 text-center">
                            <div class="flex items-center justify-center gap-2">
                                <button onclick="openModal('modal-ver-socio-{{ $partner->id }}')" class="btn-action bg-sky-light text-[#0284C7] hover:bg-sky hover:text-white" title="Ver">
                                    <i class="fas fa-eye"></i>
                                </button>
                                @if(Auth::user()->canEditModule('socios-beneficiarios'))
                                <button onclick="openModal('modal-editar-socio-{{ $partner->id }}')" class="btn-action bg-sun-light text-[#D97706] hover:bg-sun hover:text-white" title="Editar">
                                    <i class="fas fa-edit"></i>
                                </button>
                                @endif
                                @if(Auth::user()->canDeleteModule('socios-beneficiarios'))
                                <form id="form-delete-socio-{{ $partner->id }}" action="{{ route('socios-beneficiarios.socios.destroy', $partner) }}" method="POST" class="inline">
                                    @csrf
                                    @method('DELETE')
                                    <button type="button" class="btn-action bg-clay-light text-clay hover:bg-clay hover:text-white" title="Eliminar"
                                        onclick="confirmDelete('form-delete-socio-{{ $partner->id }}', 'Se eliminará este socio y todos sus beneficiarios de forma permanente.')">
                                        <i class="fas fa-trash"></i>
                                    </button>
                                </form>
                                @endif
                            </div>
                        </td>
                    </tr>
                    @empty
                    <tr>
                        <td colspan="7" class="px-4 py-8 text-center text-gray-500">
                            <i class="fas fa-users text-4xl mb-3"></i>
                            <p>No hay socios registrados</p>
                        </td>
                    </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        <div class="mt-4">{{ $partners->links() }}</div>
    </div>
</div>

@include('socios-beneficiarios._modal-crear')

@foreach($partners as $partner)
    @include('socios-beneficiarios._modal-ver', ['partner' => $partner])
    @include('socios-beneficiarios._modal-editar', ['partner' => $partner])
@endforeach

@include('socios-beneficiarios._scripts')
@endsection
