@extends('layouts.main')

@section('title', 'Comités y Resoluciones - PROVALE')

@section('content')
<div class="bg-white rounded-2xl border-2 border-wheat shadow-sm overflow-hidden">
    <div class="flex items-center justify-between px-6 py-5 border-b-2 border-wheat">
        <h3 class="font-extrabold text-charcoal text-xl flex items-center gap-3">
            <i class="fas fa-building text-leaf"></i> Comités y Resoluciones
        </h3>
        <div class="flex items-center gap-2">
            <a href="{{ route('club-reconocimientos.reconocimientos.create') }}" class="btn-secondary flex items-center gap-2" title="Crear Resolución">
                <i class="fas fa-file-contract"></i> Nueva Resolución
            </a>
            <a href="{{ route('club-reconocimientos.club.create') }}" class="btn-primary flex items-center gap-2">
                <i class="fas fa-users"></i> Nuevo Comité
            </a>
            <a href="{{ route('club-reconocimientos.club.reportes') }}" class="btn-secondary flex items-center gap-2" title="Reportes">
                <i class="fas fa-file-pdf"></i> Reportes
            </a>
            <a href="{{ route('club-reconocimientos.club.padron') }}" target="_blank" class="btn-secondary flex items-center gap-2" title="Padrón de Comités">
                <i class="fas fa-clipboard-list"></i> Padrón
            </a>
        </div>
    </div>

    <div class="p-6">
        <form method="GET" class="mb-6">
            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                <div>
                    <input type="text" name="search" value="{{ request('search') }}" placeholder="Buscar por nombre o código..." class="w-full px-4 py-2.5 border-2 border-wheat rounded-xl text-sm font-semibold text-charcoal bg-white focus:outline-none focus:border-leaf transition-all">
                </div>
            </div>
            <div class="flex gap-2 mt-4">
                <button type="submit" class="btn-primary">
                    <i class="fas fa-search mr-2"></i> Buscar
                </button>
                <a href="{{ route('club-reconocimientos.index') }}" class="btn-secondary">
                    <i class="fas fa-broom mr-2"></i> Limpiar
                </a>
            </div>
        </form>

        <div class="overflow-x-auto">
            <table class="w-full text-sm">
                <thead class="bg-gray-50">
                    <tr>
                        <th class="px-4 py-3 text-left font-bold text-earth">Código</th>
                        <th class="px-4 py-3 text-left font-bold text-earth">Nombre del Comité</th>
                        <th class="px-4 py-3 text-left font-bold text-earth">Resolución</th>
                        <th class="px-4 py-3 text-left font-bold text-earth">Vigencia</th>
                        <th class="px-4 py-3 text-center font-bold text-earth">Estado</th>
                        <th class="px-4 py-3 text-center font-bold text-earth">Acciones</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-200">
                    @forelse($associations as $association)
                    @php
                    $latestResolution = $association->resolution;
                    @endphp
                    <tr class="hover:bg-gray-50">
                        <td class="px-4 py-3 font-mono text-xs">{{ $association->code ?? 'S/C' }}</td>
                        <td class="px-4 py-3">
                            <div class="font-bold text-charcoal">{{ $association->name }}</div>
                            <div class="text-[10px] text-earth uppercase">{{ $association->address }}</div>
                        </td>
                        <td class="px-4 py-3">
                            @if($latestResolution)
                            <span class="px-2 py-1 bg-sun-light text-sun-dark rounded text-[11px] font-bold">
                                <i class="fas fa-file-invoice mr-1"></i> {{ $latestResolution->document }}
                            </span>
                            @else
                            <span class="text-gray-400">Sin resolución</span>
                            @endif
                        </td>
                        <td class="px-4 py-3 text-xs">
                            @if($latestResolution)
                            <div>{{ \Carbon\Carbon::parse($latestResolution->date_start)->format('d/m/Y') }}</div>
                            <div class="text-earth font-bold">al {{ \Carbon\Carbon::parse($latestResolution->date_end)->format('d/m/Y') }}</div>
                            @else
                            -
                            @endif
                        </td>
                        <td class="px-4 py-3 text-center">
                            <span class="px-2 py-1 text-[10px] font-bold rounded-full 
                                    @if($association->state && $association->state->title == 'Activo') bg-green-100 text-green-800
                                    @else bg-red-100 text-red-800 @endif">
                                {{ $association->state->title ?? 'N/A' }}
                            </span>
                        </td>
                        <td class="px-4 py-3 text-center">
                            <div class="flex items-center justify-center gap-2">
                                <a href="{{ route('club-reconocimientos.club.show', $association) }}" class="btn-action bg-sky-light text-[#0284C7] hover:bg-sky hover:text-white" title="Ver Detalles">
                                    <i class="fas fa-eye"></i>
                                </a>
                                <a href="{{ route('club-reconocimientos.club.edit', $association) }}" class="btn-action bg-sun-light text-[#D97706] hover:bg-sun hover:text-white" title="Editar">
                                    <i class="fas fa-edit"></i>
                                </a>
                                <form action="{{ route('club-reconocimientos.club.destroy', $association) }}" method="POST" class="inline">
                                    @csrf
                                    @method('DELETE')
                                    <button type="submit" class="btn-action bg-clay-light text-clay hover:bg-clay hover:text-white" onclick="return confirm('¿Estás seguro de eliminar este comité?')" title="Eliminar">
                                        <i class="fas fa-trash"></i>
                                    </button>
                                </form>
                            </div>
                        </td>
                    </tr>
                    @empty
                    <tr>
                        <td colspan="6" class="px-4 py-8 text-center text-gray-500">
                            <i class="fas fa-building text-4xl mb-3"></i>
                            <p>No hay comités registrados</p>
                        </td>
                    </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        <div class="mt-4">
            {{ $associations->links() }}
        </div>
    </div>
</div>
@endsection