@extends('layouts.main')

@section('title', 'Personas - PROVALE')

@section('content')
<div class="bg-white rounded-2xl border-2 border-wheat shadow-sm overflow-hidden">
    <div class="flex flex-col sm:flex-row items-start sm:items-center justify-between px-4 sm:px-6 py-4 sm:py-5 border-b-2 border-wheat gap-3">
        <h3 class="font-extrabold text-charcoal text-xl flex items-center gap-3">
            <i class="fas fa-users text-leaf"></i> Personas Registradas
        </h3>
        <div class="flex items-center flex-wrap gap-2">
            <a href="{{ route('socios-beneficiarios.index') }}" class="btn-secondary flex items-center gap-2">
                <i class="fas fa-arrow-left"></i> Volver
            </a>
            <button onclick="openModal('modal-crear-persona')" class="btn-primary flex items-center gap-2">
                <i class="fas fa-plus"></i> Nueva Persona
            </button>
        </div>
    </div>

    <div class="p-4 sm:p-6">
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
            <div class="flex flex-col sm:flex-row gap-2 sm:gap-4">
                <div class="flex-1">
                    <input type="text" name="search" value="{{ request('search') }}" placeholder="Buscar por nombre o DNI" class="w-full px-4 py-2.5 border-2 border-wheat rounded-xl text-sm font-semibold text-charcoal bg-white focus:outline-none focus:border-leaf transition-all">
                </div>
                <div class="w-full sm:w-auto">
                    <select name="gender" class="w-full px-8 py-2.5 border-2 border-wheat rounded-xl text-sm font-semibold text-charcoal bg-white focus:outline-none focus:border-leaf transition-all">
                        <option value="">Género</option>
                        <option value="M" {{ request('gender') == 'M' ? 'selected' : '' }}>Masculino</option>
                        <option value="F" {{ request('gender') == 'F' ? 'selected' : '' }}>Femenino</option>
                    </select>
                </div>
                <div class="w-full sm:w-auto">
                    <select name="place_sector_id" class="w-full px-4 py-2.5 border-2 border-wheat rounded-xl text-sm font-semibold text-charcoal bg-white focus:outline-none focus:border-leaf transition-all">
                        <option value="">Todos los sectores</option>
                        @if(isset($placeSectors))
                        @foreach($placeSectors as $ps)
                        <option value="{{ $ps->id }}" {{ request('place_sector_id') == $ps->id ? 'selected' : '' }}>
                            {{ $ps->place->title ?? 'N/A' }} - {{ $ps->sector->title ?? 'N/A' }}
                        </option>
                        @endforeach
                        @endif
                    </select>
                </div>
                <div>
                    <button type="submit" class="btn-primary"><i class="fas fa-search mr-2"></i> Buscar</button>
                    <a href="{{ route('socios-beneficiarios.personas.index') }}" class="btn-secondary"><i class="fas fa-broom mr-2"></i> Limpiar</a>
                </div>
            </div>
        </form>

        <div class="overflow-x-auto -mx-4 sm:mx-0">
            <table class="w-full text-xs sm:text-sm min-w-[700px]">
                <thead class="bg-gray-50">
                    <tr>
                        <th class="px-3 sm:px-4 py-3 text-left font-bold text-earth">DNI</th>
                        <th class="px-3 sm:px-4 py-3 text-left font-bold text-earth">Nombres</th>
                        <th class="px-3 sm:px-4 py-3 text-left font-bold text-earth">Apellidos</th>
                        <th class="px-3 sm:px-4 py-3 text-left font-bold text-earth">Edad</th>
                        <th class="px-3 sm:px-4 py-3 text-left font-bold text-earth">Celular</th>
                        <th class="px-3 sm:px-4 py-3 text-left font-bold text-earth">Sector</th>
                        <th class="px-3 sm:px-4 py-3 text-center font-bold text-earth">Acciones</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-200">
                    @forelse($people as $person)
                    <tr class="hover:bg-gray-50">
                        <td class="px-3 sm:px-4 py-3 font-mono">{{ $person->dni }}</td>
                        <td class="px-3 sm:px-4 py-3 font-medium">{{ $person->names }}</td>
                        <td class="px-3 sm:px-4 py-3">{{ $person->father_lastname }} {{ $person->mother_lastname }}</td>
                        <td class="px-3 sm:px-4 py-3 text-leaf font-semibold">{{ $person->age_formatted }}</td>
                        <td class="px-3 sm:px-4 py-3">{{ $person->phone_number ?? 'Sin número' }}</td>
                        <td class="px-3 sm:px-4 py-3 text-xs">{{ $person->placeSector->sector->title ?? 'N/A' }}</td>
                        <td class="px-3 sm:px-4 py-3 text-center">
                            <div class="flex items-center justify-center gap-2">
                                <button onclick="openModal('modal-ver-persona-{{ $person->id }}')" class="btn-action bg-sky-light text-[#0284C7] hover:bg-sky hover:text-white" title="Ver">
                                    <i class="fas fa-eye"></i>
                                </button>
                                <button onclick="openModal('modal-editar-persona-{{ $person->id }}')" class="btn-action bg-sun-light text-[#D97706] hover:bg-sun hover:text-white" title="Editar">
                                    <i class="fas fa-edit"></i>
                                </button>
                                <form id="form-delete-persona-{{ $person->id }}" action="{{ route('socios-beneficiarios.personas.destroy', $person) }}" method="POST" class="inline">
                                    @csrf
                                    @method('DELETE')
                                    <button type="button" class="btn-action bg-clay-light text-clay hover:bg-clay hover:text-white" title="Eliminar"
                                        onclick="confirmDelete('form-delete-persona-{{ $person->id }}', 'Se eliminará esta persona de forma permanente.')">
                                        <i class="fas fa-trash"></i>
                                    </button>
                                </form>
                            </div>
                        </td>
                    </tr>
                    @empty
                    <tr>
                        <td colspan="8" class="px-4 py-8 text-center text-gray-500">
                            <i class="fas fa-users text-4xl mb-3"></i>
                            <p>No hay personas registradas</p>
                        </td>
                    </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        <div class="mt-4">{{ $people->links() }}</div>
    </div>
</div>

@include('socios-beneficiarios.personas._modal-crear')

@foreach($people as $person)
    @include('socios-beneficiarios.personas._modal-ver', ['person' => $person])
    @include('socios-beneficiarios.personas._modal-editar', ['person' => $person])
@endforeach
@endsection
