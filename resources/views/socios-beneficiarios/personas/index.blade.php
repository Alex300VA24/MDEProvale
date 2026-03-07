@extends('layouts.main')

@section('title', 'Personas - PROVALE')

@section('content')
<div class="bg-white rounded-2xl border-2 border-wheat shadow-sm overflow-hidden">
    <div class="flex items-center justify-between px-6 py-5 border-b-2 border-wheat">
        <h3 class="font-extrabold text-charcoal text-xl flex items-center gap-3">
            <i class="fas fa-users text-leaf"></i> Personas Registradas
        </h3>
        <div class="flex items-center gap-2">
            <a href="{{ route('socios-beneficiarios.index') }}" class="btn-secondary flex items-center gap-2">
                <i class="fas fa-arrow-left"></i> Volver
            </a>
            <a href="{{ route('socios-beneficiarios.personas.create') }}" class="btn-primary flex items-center gap-2">
                <i class="fas fa-plus"></i> Nueva Persona
            </a>
        </div>
    </div>

    <div class="p-6">
        <form method="GET" class="mb-6">
            <div class="flex gap-4">
                <div class="flex-1">
                    <input type="text" name="search" value="{{ request('search') }}" placeholder="Buscar por nombre o DNI..." class="w-full px-4 py-2.5 border-2 border-wheat rounded-xl text-sm font-semibold text-charcoal bg-white focus:outline-none focus:border-leaf transition-all">
                </div>
                <button type="submit" class="btn-primary">
                    <i class="fas fa-search mr-2"></i> Buscar
                </button>
                <a href="{{ route('socios-beneficiarios.personas.index') }}" class="btn-secondary">
                    <i class="fas fa-broom mr-2"></i> Limpiar
                </a>
            </div>
        </form>

        <div class="overflow-x-auto">
            <table class="w-full text-sm">
                <thead class="bg-gray-50">
                    <tr>
                        <th class="px-4 py-3 text-left font-bold text-earth">ID</th>
                        <th class="px-4 py-3 text-left font-bold text-earth">DNI</th>
                        <th class="px-4 py-3 text-left font-bold text-earth">Nombres</th>
                        <th class="px-4 py-3 text-left font-bold text-earth">Apellidos</th>
                        <th class="px-4 py-3 text-left font-bold text-earth">Edad</th>
                        <th class="px-4 py-3 text-left font-bold text-earth">Celular</th>
                        <th class="px-4 py-3 text-left font-bold text-earth">Sector</th>
                        <th class="px-4 py-3 text-center font-bold text-earth">Acciones</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-200">
                    @forelse($people as $person)
                    <tr class="hover:bg-gray-50">
                        <td class="px-4 py-3">#{{ $person->id }}</td>
                        <td class="px-4 py-3 font-mono">{{ $person->dni }}</td>
                        <td class="px-4 py-3 font-medium">{{ $person->names }}</td>
                        <td class="px-4 py-3">{{ $person->father_lastname }} {{ $person->mother_lastname }}</td>
                        <td class="px-4 py-3 text-leaf font-semibold">{{ $person->age_formatted }}</td>
                        <td class="px-4 py-3">{{ $person->phone_number ?? '-' }}</td>
                        <td class="px-4 py-3 text-xs">{{ $person->placeSector->place->title ?? 'N/A' }} - {{ $person->placeSector->sector->title ?? 'N/A' }}</td>
                        <td class="px-4 py-3 text-center">
                            <div class="flex items-center justify-center gap-2">
                                <a href="{{ route('socios-beneficiarios.personas.show', $person) }}" class="text-blue-600 hover:text-blue-800" title="Ver">
                                    <i class="fas fa-eye"></i>
                                </a>
                                <a href="{{ route('socios-beneficiarios.personas.edit', $person) }}" class="text-yellow-600 hover:text-yellow-800" title="Editar">
                                    <i class="fas fa-edit"></i>
                                </a>
                                <form action="{{ route('socios-beneficiarios.personas.destroy', $person) }}" method="POST" class="inline">
                                    @csrf
                                    @method('DELETE')
                                    <button type="submit" class="text-red-600 hover:text-red-800" onclick="return confirm('¿Estás seguro de eliminar esta persona?')" title="Eliminar">
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

        <div class="mt-4">
            {{ $people->links() }}
        </div>
    </div>
</div>
@endsection
