@extends('layouts.main')

@section('title', 'Socios y Beneficiarios - PROVALE')

@section('content')
<div class="bg-white rounded-2xl border-2 border-wheat shadow-sm overflow-hidden">
    <div class="flex items-center justify-between px-6 py-5 border-b-2 border-wheat">
        <h3 class="font-extrabold text-charcoal text-xl flex items-center gap-3">
            <i class="fas fa-users text-leaf"></i> Socios y Beneficiarios
        </h3>
        <div class="flex items-center gap-2">
            <a href="{{ route('socios-beneficiarios.personas.create') }}" class="btn-secondary flex items-center gap-2" title="Crear Persona">
                <i class="fas fa-user-plus"></i> Crear Persona
            </a>
            <a href="{{ route('socios-beneficiarios.beneficiarios.reportes') }}" class="btn-secondary flex items-center gap-2" title="Reportes">
                <i class="fas fa-file-pdf"></i> Reportes
            </a>
            <a href="{{ route('socios-beneficiarios.beneficiarios.padron') }}" class="btn-secondary flex items-center gap-2" title="Padrón de Beneficiarios PVL">
                <i class="fas fa-clipboard-list"></i> Padrón PVL
            </a>
            <a href="{{ route('socios-beneficiarios.beneficiarios.imprimir') }}" target="_blank" class="btn-secondary flex items-center gap-2" title="Ficha de Beneficiario">
                <i class="fas fa-id-card"></i> Ficha Beneficiario
            </a>
            <a href="{{ route('socios-beneficiarios.socios.create') }}" class="btn-primary flex items-center gap-2">
                <i class="fas fa-plus"></i> Nuevo Socio
            </a>
        </div>
    </div>

    <div class="p-6">
        <form method="GET" class="mb-6">
            <div class="grid grid-cols-1 md:grid-cols-4 gap-4">
                <div class="md:col-span-2">
                    <input type="text" name="search" value="{{ request('search') }}" placeholder="Buscar por nombre o DNI..." class="w-full px-4 py-2.5 border-2 border-wheat rounded-xl text-sm font-semibold text-charcoal bg-white focus:outline-none focus:border-leaf transition-all">
                </div>
                <div>
                    <select name="association_id" class="w-full px-4 py-2.5 border-2 border-wheat rounded-xl text-sm font-semibold text-charcoal bg-white focus:outline-none focus:border-leaf transition-all">
                        <option value="">Todos los Clubes</option>
                        @foreach($associations as $association)
                        <option value="{{ $association->id }}" {{ request('association_id') == $association->id ? 'selected' : '' }}>{{ $association->name }}</option>
                        @endforeach
                    </select>
                </div>
                <div>
                    <select name="state_id" class="w-full px-4 py-2.5 border-2 border-wheat rounded-xl text-sm font-semibold text-charcoal bg-white focus:outline-none focus:border-leaf transition-all">
                        <option value="">Todos los Estados</option>
                        @foreach($states as $state)
                        <option value="{{ $state->id }}" {{ request('state_id') == $state->id ? 'selected' : '' }}>{{ $state->title }}</option>
                        @endforeach
                    </select>
                </div>
            </div>
            <div class="flex gap-2 mt-4">
                <button type="submit" class="btn-primary">
                    <i class="fas fa-search mr-2"></i> Buscar
                </button>
                <a href="{{ route('socios-beneficiarios.index') }}" class="btn-secondary">
                    <i class="fas fa-broom mr-2"></i> Limpiar
                </a>
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
                            @if($partner->people)
                            {{ $partner->people->names }} {{ $partner->people->father_lastname }}
                            @else
                            Sin nombre
                            @endif
                        </td>
                        <td class="px-4 py-3">{{ $partner->people->dni ?? 'Sin DNI' }}</td>
                        <td class="px-4 py-3">{{ $partner->association->name ?? 'Sin club' }}</td>
                        <td class="px-4 py-3">
                            <span class="px-2 py-1 text-xs font-semibold rounded-full 
                                    @if($partner->state->title == 'Activo') bg-green-100 text-green-800
                                    @else bg-red-100 text-red-800 @endif">
                                {{ $partner->state->title ?? 'Sin estado' }}
                            </span>
                        </td>
                        <td class="px-4 py-3 text-center">
                            <span class="font-bold text-leaf">{{ $partner->beneficiaries->count() }}</span>
                        </td>
                        <td class="px-4 py-3 text-center">
                            <div class="flex items-center justify-center gap-2">
                                <a href="{{ route('socios-beneficiarios.socios.show', $partner) }}" class="text-blue-600 hover:text-blue-800" title="Ver">
                                    <i class="fas fa-eye"></i>
                                </a>
                                <a href="{{ route('socios-beneficiarios.socios.edit', $partner) }}" class="text-yellow-600 hover:text-yellow-800" title="Editar">
                                    <i class="fas fa-edit"></i>
                                </a>
                                <form action="{{ route('socios-beneficiarios.socios.destroy', $partner) }}" method="POST" class="inline">
                                    @csrf
                                    @method('DELETE')
                                    <button type="submit" class="text-red-600 hover:text-red-800" onclick="return confirm('¿Estás seguro de eliminar este socio y todos sus beneficiarios?')" title="Eliminar">
                                        <i class="fas fa-trash"></i>
                                    </button>
                                </form>
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

        <div class="mt-4">
            {{ $partners->links() }}
        </div>
    </div>
</div>
@endsection