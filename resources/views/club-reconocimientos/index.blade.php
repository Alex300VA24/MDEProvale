@extends('layouts.main')

@section('title', 'Comités y Resoluciones - PROVALE')

@section('content')
<div class="bg-white rounded-2xl border-2 border-wheat shadow-sm overflow-hidden">
    <div class="flex items-center justify-between px-6 py-5 border-b-2 border-wheat">
        <h3 class="font-extrabold text-charcoal text-xl flex items-center gap-3">
            <i class="fas fa-building text-leaf"></i> Comités y Resoluciones
        </h3>
        <div class="flex items-center gap-2">
            <a href="{{ route('club-reconocimientos.reconocimientos.index') }}" class="btn-primary flex items-center gap-2">
                <i class="fas fa-file-contract"></i> Resoluciones
            </a>
            <a href="{{ route('club-reconocimientos.create') }}" class="btn-primary flex items-center gap-2">
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
                            <div class="text-[12px] text-earth uppercase">{{ $association->address }}</div>
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
                                <a href="{{ route('club-reconocimientos.show', $association) }}" class="btn-action bg-sky-light text-[#0284C7] hover:bg-sky hover:text-white" title="Ver Detalles">
                                    <i class="fas fa-eye"></i>
                                </a>
                                <a href="{{ route('club-reconocimientos.edit', $association) }}" class="btn-action bg-sun-light text-[#D97706] hover:bg-sun hover:text-white" title="Editar">
                                    <i class="fas fa-edit"></i>
                                </a>
                                <button onclick="openModal('modal-presidenta-{{ $association->id }}')" class="btn-action bg-leaf-light text-leaf hover:bg-leaf hover:text-white" title="Asignar Presidenta">
                                    <i class="fas fa-user-plus"></i>
                                </button>
                                <form action="{{ route('club-reconocimientos.destroy', $association) }}" method="POST" class="inline">
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

@foreach($associations as $association)
<div id="modal-presidenta-{{ $association->id }}" class="fixed inset-0 bg-gray-600 bg-opacity-50 overflow-y-auto h-full w-full hidden z-50">
    <div class="relative top-20 mx-auto p-5 border w-full max-w-md shadow-lg rounded-2xl bg-white">
        <button onclick="closeModal('modal-presidenta-{{ $association->id }}')" class="absolute top-4 right-4 text-gray-400 hover:text-gray-600">
            <i class="fas fa-times text-xl"></i>
        </button>
        <div class="mb-4">
            <h3 class="font-extrabold text-charcoal text-lg flex items-center gap-2">
                <i class="fas fa-user-crown text-sun"></i>
                Asignar Presidenta
            </h3>
            <p class="text-sm text-earth mt-1">{{ $association->name }}</p>
        </div>
        <form action="{{ route('club-reconocimientos.club.asignar-presidenta', $association) }}" method="POST">
            @csrf
            <div class="space-y-4">
                <div>
                    <label class="block text-[11px] font-bold text-earth uppercase tracking-wider mb-2">Socia a asignar como Presidenta</label>
                    <select name="partner_id" class="w-full px-4 py-2.5 border-2 border-wheat rounded-xl text-sm font-semibold text-charcoal bg-white focus:outline-none focus:border-leaf transition-all" required>
                        <option value="">Seleccionar socia...</option>
                        @forelse($association->partners as $partner)
                            @if($partner->people)
                            <option value="{{ $partner->id }}">{{ $partner->people->names }} {{ $partner->people->father_lastname }} ({{ $partner->people->dni }})</option>
                            @endif
                        @empty
                        @endforelse
                    </select>
                    @if($association->partners->isEmpty())
                    <p class="text-xs text-clay mt-1">No hay socios registrados en este comité</p>
                    @endif
                </div>
            </div>
            <div class="flex gap-3 mt-6">
                <button type="submit" class="btn-primary flex-1" {{ $association->partners->isEmpty() ? 'disabled' : '' }}>
                    <i class="fas fa-save mr-2"></i> Asignar
                </button>
                <button type="button" onclick="closeModal('modal-presidenta-{{ $association->id }}')" class="btn-secondary flex-1">
                    Cancelar
                </button>
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
