@extends('layouts.main')

@section('title', 'Ver Club de Madres - PROVALE')

@section('content')
<div class="space-y-6">
    <div class="bg-white rounded-2xl border-2 border-wheat shadow-sm overflow-hidden">
        <div class="flex items-center justify-between px-6 py-5 border-b-2 border-wheat">
            <h3 class="font-extrabold text-charcoal text-xl flex items-center gap-3">
                <i class="fas fa-building text-leaf"></i> Datos del Comité
            </h3>
            <a href="{{ route('club-reconocimientos.index') }}" class="btn-secondary flex items-center gap-2">
                <i class="fas fa-arrow-left"></i> Volver
            </a>
        </div>

        <div class="p-6">
            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
                <div>
                    <label class="block text-[11px] font-bold text-earth uppercase tracking-wider mb-2">ID</label>
                    <p class="text-charcoal font-semibold">#{{ $association->id }}</p>
                </div>
                <div>
                    <label class="block text-[11px] font-bold text-earth uppercase tracking-wider mb-2">Código</label>
                    <p class="text-charcoal font-semibold">{{ $association->code ?? 'Sin código' }}</p>
                </div>
                <div>
                    <label class="block text-[11px] font-bold text-earth uppercase tracking-wider mb-2">Estado</label>
                    <span class="px-2 py-1 text-xs font-bold rounded-full 
                        @if($association->state && $association->state->abbreviation == 'ACTI') bg-green-100 text-green-800
                        @else bg-red-100 text-red-800 @endif">
                        {{ $association->state->title ?? 'N/A' }}
                    </span>
                </div>
                <div class="md:col-span-2">
                    <label class="block text-[11px] font-bold text-earth uppercase tracking-wider mb-2">Nombre del Comité</label>
                    <p class="text-charcoal font-semibold text-lg">{{ $association->name ?? 'Sin nombre' }}</p>
                </div>
                <div>
                    <label class="block text-[11px] font-bold text-earth uppercase tracking-wider mb-2">Nombre de la Presidenta</label>
                    <p class="text-charcoal font-semibold">{{ $association->president ?? 'Sin presidenta asignada' }}</p>
                </div>
                <div>
                    <label class="block text-[11px] font-bold text-earth uppercase tracking-wider mb-2">Teléfono</label>
                    <p class="text-charcoal font-semibold">{{ $association->phone ?? 'Sin teléfono' }}</p>
                </div>
                <div class="md:col-span-2">
                    <label class="block text-[11px] font-bold text-earth uppercase tracking-wider mb-2">Dirección</label>
                    <p class="text-charcoal font-semibold">{{ $association->address ?? 'Sin dirección' }}</p>
                </div>
                <div>
                    <label class="block text-[11px] font-bold text-earth uppercase tracking-wider mb-2">Lugar / Sector</label>
                    <p class="text-charcoal font-semibold">
                        @if($association->placeSector)
                            {{ $association->placeSector->place->title ?? '' }} - {{ $association->placeSector->sector->title ?? '' }}
                        @else
                            Sin sector
                        @endif
                    </p>
                </div>
                <div>
                    <label class="block text-[11px] font-bold text-earth uppercase tracking-wider mb-2">Tipo de Local</label>
                    <p class="text-charcoal font-semibold">{{ $association->typePremises->title ?? 'No especificado' }}</p>
                </div>
                <div class="md:col-span-2">
                    <label class="block text-[11px] font-bold text-earth uppercase tracking-wider mb-2">Observaciones</label>
                    <p class="text-charcoal font-semibold">{{ $association->observation ?? 'Sin observaciones' }}</p>
                </div>
            </div>

            <div class="flex gap-3 mt-6">
                <a href="{{ route('club-reconocimientos.edit', $association) }}" class="btn-primary">
                    <i class="fas fa-edit mr-2"></i> Editar
                </a>
                <button onclick="openModal('modal-presidenta-{{ $association->id }}')" class="btn-secondary">
                    <i class="fas fa-user-crown mr-2"></i> Asignar Presidenta
                </button>
                <form action="{{ route('club-reconocimientos.destroy', $association) }}" method="POST" class="inline">
                    @csrf
                    @method('DELETE')
                    <button type="submit" class="btn-danger" onclick="return confirm('¿Estás seguro de eliminar este club?')">
                        <i class="fas fa-trash mr-2"></i> Eliminar
                    </button>
                </form>
            </div>
        </div>
    </div>

    @if($association->resolution)
    <div class="bg-white rounded-2xl border-2 border-wheat shadow-sm overflow-hidden">
        <div class="flex items-center justify-between px-6 py-5 border-b-2 border-wheat">
            <h3 class="font-extrabold text-charcoal text-xl flex items-center gap-3">
                <i class="fas fa-file-contract text-sun"></i> Resolución de Reconocimiento
            </h3>
        </div>
        <div class="p-6">
            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6">
                <div>
                    <label class="block text-[11px] font-bold text-earth uppercase tracking-wider mb-2">Documento</label>
                    <p class="text-charcoal font-semibold">{{ $association->resolution->document }}</p>
                </div>
                <div>
                    <label class="block text-[11px] font-bold text-earth uppercase tracking-wider mb-2">Fecha de Emisión</label>
                    <p class="text-charcoal font-semibold">{{ \Carbon\Carbon::parse($association->resolution->date_document)->format('d/m/Y') }}</p>
                </div>
                <div>
                    <label class="block text-[11px] font-bold text-earth uppercase tracking-wider mb-2">Vigencia Inicio</label>
                    <p class="text-charcoal font-semibold">{{ \Carbon\Carbon::parse($association->resolution->date_start)->format('d/m/Y') }}</p>
                </div>
                <div>
                    <label class="block text-[11px] font-bold text-earth uppercase tracking-wider mb-2">Vigencia Fin</label>
                    <p class="text-charcoal font-semibold">{{ \Carbon\Carbon::parse($association->resolution->date_end)->format('d/m/Y') }}</p>
                </div>
            </div>
        </div>
    </div>
    @endif

    <div class="bg-white rounded-2xl border-2 border-wheat shadow-sm overflow-hidden">
        <div class="flex items-center justify-between px-6 py-5 border-b-2 border-wheat">
            <h3 class="font-extrabold text-charcoal text-xl flex items-center gap-3">
                <i class="fas fa-users text-leaf"></i> Socios del Comité
            </h3>
            <span class="px-3 py-1 bg-leaf-light text-leaf font-bold rounded-full">{{ $association->partners->count() }}</span>
        </div>
        <div class="p-6">
            @if($association->partners->isEmpty())
            <p class="text-center text-gray-500 py-4">No hay socios registrados en este comité</p>
            @else
            <div class="overflow-x-auto">
                <table class="w-full text-sm">
                    <thead class="bg-gray-50">
                        <tr>
                            <th class="px-4 py-3 text-left font-bold text-earth">DNI</th>
                            <th class="px-4 py-3 text-left font-bold text-earth">Nombre</th>
                            <th class="px-4 py-3 text-left font-bold text-earth">Estado</th>
                            <th class="px-4 py-3 text-center font-bold text-earth">Beneficiarios</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-200">
                        @foreach($association->partners as $partner)
                        <tr class="hover:bg-gray-50">
                            <td class="px-4 py-3 font-mono">{{ $partner->people->dni ?? 'Sin DNI' }}</td>
                            <td class="px-4 py-3 font-medium">
                                @if($partner->people)
                                    {{ $partner->people->names }} {{ $partner->people->father_lastname }} {{ $partner->people->mother_lastname }}
                                @else
                                    Sin nombre
                                @endif
                            </td>
                            <td class="px-4 py-3">
                                <span class="px-2 py-1 text-xs font-semibold rounded-full 
                                    @if($partner->state && $partner->state->abbreviation == 'ACTI') bg-green-100 text-green-800
                                    @else bg-red-100 text-red-800 @endif">
                                    {{ $partner->state->title ?? 'Sin estado' }}
                                </span>
                            </td>
                            <td class="px-4 py-3 text-center font-bold text-leaf">{{ $partner->beneficiaries->count() }}</td>
                        </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
            @endif
        </div>
    </div>
</div>

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

<script>
function openModal(id) {
    document.getElementById(id).classList.remove('hidden');
}

function closeModal(id) {
    document.getElementById(id).classList.add('hidden');
}
</script>
@endsection
