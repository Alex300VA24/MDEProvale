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
            @if(Auth::user()->canCreateModule('club-madres'))
            <button onclick="openModal('modal-crear-comite')" class="btn-primary flex items-center gap-2">
                <i class="fas fa-users"></i> Nuevo Comité
            </button>
            @endif
            <a href="{{ route('club-reconocimientos.club.padron') }}" target="_blank" class="btn-secondary flex items-center gap-2">
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
            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                <div>
                    <input type="text" name="search" value="{{ request('search') }}" placeholder="Buscar por nombre o código..." class="w-full px-4 py-2.5 border-2 border-wheat rounded-xl text-sm font-semibold text-charcoal bg-white focus:outline-none focus:border-leaf transition-all">
                </div>
            </div>
            <div class="flex gap-2 mt-4">
                <button type="submit" class="btn-primary"><i class="fas fa-search mr-2"></i> Buscar</button>
                <a href="{{ route('club-reconocimientos.index') }}" class="btn-secondary"><i class="fas fa-broom mr-2"></i> Limpiar</a>
            </div>
        </form>

        <div class="overflow-x-auto">
            <table class="w-full text-sm">
                <thead class="bg-gray-50">
                    <tr>
                        <th class="px-4 py-3 text-left font-bold text-earth">Código</th>
                        <th class="px-4 py-3 text-left font-bold text-earth">Nombre del Comité</th>
                        <th class="px-4 py-3 text-left font-bold text-earth">Última Resolución</th>
                        <th class="px-4 py-3 text-left font-bold text-earth">Vigencia</th>
                        <th class="px-4 py-3 text-center font-bold text-earth">Estado</th>
                        <th class="px-4 py-3 text-center font-bold text-earth">Acciones</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-200">
                    @forelse($associations as $association)
                    @php $latestResolution = $association->latestResolution; @endphp
                    <tr class="hover:bg-gray-50">
                        <td class="px-4 py-3 font-mono text-xs">{{ $association->code ?? 'S/C' }}</td>
                        <td class="px-4 py-3">
                            <div class="font-bold text-charcoal">{{ $association->name }}</div>
                            <div class="text-[12px] text-earth uppercase">{{ $association->address }}</div>
                        </td>
                        <td class="px-4 py-3">
                            @if($latestResolution)
                            <span class="px-2 py-1 bg-leaf-light text-leaf rounded text-[11px] font-bold border-2 border-leaf">
                                <i class="fas fa-star mr-1"></i> {{ $latestResolution->document }}
                            </span>
                            @else
                            <span class="text-gray-400">-</span>
                            @endif
                        </td>
                        <td class="px-4 py-3 text-xs">
                            @if($latestResolution)
                            <div>{{ \Carbon\Carbon::parse($latestResolution->date_start)->format('d/m/Y') }}</div>
                            <div class="text-earth font-bold">al {{ \Carbon\Carbon::parse($latestResolution->date_end)->format('d/m/Y') }}</div>
                            @else -
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
                                <button onclick="openModal('modal-ver-comite-{{ $association->id }}')" class="btn-action bg-sky-light text-[#0284C7] hover:bg-sky hover:text-white" title="Ver Detalles">
                                    <i class="fas fa-eye"></i>
                                </button>
                                @if(Auth::user()->canEditModule('club-madres'))
                                <button onclick="openModal('modal-editar-comite-{{ $association->id }}')" class="btn-action bg-sun-light text-[#D97706] hover:bg-sun hover:text-white" title="Editar">
                                    <i class="fas fa-edit"></i>
                                </button>
                                @endif
                                @if(Auth::user()->canEditModule('club-madres'))
                                <button onclick="openModal('modal-presidenta-{{ $association->id }}')" class="btn-action bg-leaf-light text-leaf hover:bg-leaf hover:text-white" title="Asignar Presidenta">
                                    <i class="fas fa-user-plus"></i>
                                </button>
                                @endif
                                @if(Auth::user()->canDeleteModule('club-madres'))
                                <form id="form-delete-club-{{ $association->id }}" action="{{ route('club-reconocimientos.destroy', $association) }}" method="POST" class="inline">
                                    @csrf
                                    @method('DELETE')
                                    <button type="button" class="btn-action bg-clay-light text-clay hover:bg-clay hover:text-white"
                                        onclick="confirmDelete('form-delete-club-{{ $association->id }}', 'Se eliminará este comité de forma permanente.')" title="Eliminar">
                                        <i class="fas fa-trash"></i>
                                    </button>
                                </form>
                                @endif
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
        <div class="mt-4">{{ $associations->links() }}</div>
    </div>
</div>

{{-- Modal Crear Comité --}}
<div id="modal-crear-comite" class="fixed inset-0 bg-black/40 backdrop-blur-sm overflow-y-auto h-full w-full z-40" style="display: none;">
    <div class="relative mx-auto w-full max-w-2xl mt-16 mb-8 px-4">
        <div class="bg-white rounded-2xl shadow-2xl border-2 border-wheat overflow-hidden">
            <div class="flex items-center justify-between px-6 py-5 border-b-2 border-wheat">
                <h3 class="font-extrabold text-charcoal text-lg flex items-center gap-2">
                    <i class="fas fa-building text-leaf"></i> Nuevo Comité
                </h3>
                <button onclick="closeModal('modal-crear-comite')" class="w-8 h-8 rounded-xl bg-cream border-2 border-wheat flex items-center justify-center text-earth hover:bg-wheat transition-all">
                    <i class="fas fa-times"></i>
                </button>
            </div>
            <form action="{{ route('club-reconocimientos.store') }}" method="POST" class="p-6 space-y-4">
                @csrf
                <div class="grid grid-cols-2 gap-4">
                    <div>
                        <label class="block text-[11px] font-bold text-earth uppercase tracking-wider mb-1">Resolución *</label>
                        <select name="resolution_id" class="w-full px-4 py-2.5 border-2 border-wheat rounded-xl text-sm font-semibold text-charcoal bg-white focus:outline-none focus:border-leaf transition-all" required>
                            <option value="">Seleccionar...</option>
                            @foreach($resolutions as $resolution)
                            <option value="{{ $resolution->id }}">{{ $resolution->document }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div>
                        <label class="block text-[11px] font-bold text-earth uppercase tracking-wider mb-1">Código</label>
                        <input type="text" name="code" class="w-full px-4 py-2.5 border-2 border-wheat rounded-xl text-sm font-semibold text-charcoal bg-white focus:outline-none focus:border-leaf transition-all" placeholder="Ej: CDM-001">
                    </div>
                </div>
                <div>
                    <label class="block text-[11px] font-bold text-earth uppercase tracking-wider mb-1">Nombre del Comité *</label>
                    <input type="text" name="name" class="w-full px-4 py-2.5 border-2 border-wheat rounded-xl text-sm font-semibold text-charcoal bg-white focus:outline-none focus:border-leaf transition-all" required>
                </div>
                <div>
                    <label class="block text-[11px] font-bold text-earth uppercase tracking-wider mb-1">Dirección *</label>
                    <input type="text" name="address" class="w-full px-4 py-2.5 border-2 border-wheat rounded-xl text-sm font-semibold text-charcoal bg-white focus:outline-none focus:border-leaf transition-all" required>
                </div>
                <div class="grid grid-cols-2 gap-4">
                    <div>
                        <label class="block text-[11px] font-bold text-earth uppercase tracking-wider mb-1">Sector *</label>
                        <select name="place_sector_id" class="w-full px-4 py-2.5 border-2 border-wheat rounded-xl text-sm font-semibold text-charcoal bg-white focus:outline-none focus:border-leaf transition-all" required>
                            <option value="">Seleccionar...</option>
                            @foreach($placeSectors as $ps)
                            <option value="{{ $ps->id }}">{{ $ps->place->title ?? '' }} - {{ $ps->sector->title ?? '' }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div>
                        <label class="block text-[11px] font-bold text-earth uppercase tracking-wider mb-1">Tipo de Local</label>
                        <select name="type_premises_id" class="w-full px-4 py-2.5 border-2 border-wheat rounded-xl text-sm font-semibold text-charcoal bg-white focus:outline-none focus:border-leaf transition-all">
                            <option value="">Seleccionar...</option>
                            @foreach($typePremises as $tp)
                            <option value="{{ $tp->id }}">{{ $tp->title }}</option>
                            @endforeach
                        </select>
                    </div>
                </div>
                <div>
                    <label class="block text-[11px] font-bold text-earth uppercase tracking-wider mb-1">Observación</label>
                    <textarea name="observation" rows="2" class="w-full px-4 py-2.5 border-2 border-wheat rounded-xl text-sm font-semibold text-charcoal bg-white focus:outline-none focus:border-leaf transition-all"></textarea>
                </div>
                <div class="flex gap-3 pt-2">
                    <button type="submit" class="btn-primary flex-1"><i class="fas fa-save mr-2"></i> Guardar</button>
                    <button type="button" onclick="closeModal('modal-crear-comite')" class="btn-secondary flex-1">Cancelar</button>
                </div>
            </form>
        </div>
    </div>
</div>

@foreach($associations as $association)

{{-- Modal Ver Comité --}}
<div id="modal-ver-comite-{{ $association->id }}" class="fixed inset-0 bg-black/40 backdrop-blur-sm overflow-y-auto h-full w-full z-40" style="display: none;">
    <div class="relative mx-auto w-full max-w-lg mt-16 mb-8 px-4">
        <div class="bg-white rounded-2xl shadow-2xl border-2 border-wheat overflow-hidden">
            <div class="flex items-center justify-between px-6 py-5 border-b-2 border-wheat">
                <h3 class="font-extrabold text-charcoal text-lg flex items-center gap-2">
                    <i class="fas fa-building text-leaf"></i> Detalle del Comité
                </h3>
                <button onclick="closeModal('modal-ver-comite-{{ $association->id }}')" class="w-8 h-8 rounded-xl bg-cream border-2 border-wheat flex items-center justify-center text-earth hover:bg-wheat transition-all">
                    <i class="fas fa-times"></i>
                </button>
            </div>
            <div class="p-6 space-y-3 text-sm">
                <div class="grid grid-cols-2 gap-3">
                    <div><span class="text-[11px] font-bold text-earth uppercase">Código</span><p class="font-semibold text-charcoal">{{ $association->code ?? 'S/C' }}</p></div>
                    <div><span class="text-[11px] font-bold text-earth uppercase">Estado</span>
                        <p><span class="px-2 py-1 text-[10px] font-bold rounded-full {{ $association->state && $association->state->title == 'Activo' ? 'bg-green-100 text-green-800' : 'bg-red-100 text-red-800' }}">{{ $association->state->title ?? 'N/A' }}</span></p>
                    </div>
                </div>
                <div><span class="text-[11px] font-bold text-earth uppercase">Nombre</span><p class="font-semibold text-charcoal">{{ $association->name }}</p></div>
                <div><span class="text-[11px] font-bold text-earth uppercase">Dirección</span><p class="text-charcoal">{{ $association->address ?? '-' }}</p></div>
                <div><span class="text-[11px] font-bold text-earth uppercase">Presidenta</span><p class="text-charcoal">{{ $association->getPresidentName() ?? 'Sin asignar' }}</p></div>
                @if($association->latestResolution)
                <div><span class="text-[11px] font-bold text-earth uppercase">Última Resolución</span>
                    <p class="font-semibold text-leaf">{{ $association->latestResolution->document }}</p>
                    <p class="text-xs text-earth">{{ \Carbon\Carbon::parse($association->latestResolution->date_start)->format('d/m/Y') }} al {{ \Carbon\Carbon::parse($association->latestResolution->date_end)->format('d/m/Y') }}</p>
                </div>
                @endif
                <div class="grid grid-cols-2 gap-3">
                    <div><span class="text-[11px] font-bold text-earth uppercase">Socios</span><p class="font-bold text-leaf text-lg">{{ $association->partners->count() }}</p></div>
                </div>
            </div>
            <div class="px-6 pb-6">
                <button onclick="closeModal('modal-ver-comite-{{ $association->id }}')" class="btn-secondary w-full">Cerrar</button>
            </div>
        </div>
    </div>
</div>

{{-- Modal Editar Comité --}}
<div id="modal-editar-comite-{{ $association->id }}" class="fixed inset-0 bg-black/40 backdrop-blur-sm overflow-y-auto h-full w-full z-40" style="display: none;">
    <div class="relative mx-auto w-full max-w-2xl mt-16 mb-8 px-4">
        <div class="bg-white rounded-2xl shadow-2xl border-2 border-wheat overflow-hidden">
            <div class="flex items-center justify-between px-6 py-5 border-b-2 border-wheat">
                <h3 class="font-extrabold text-charcoal text-lg flex items-center gap-2">
                    <i class="fas fa-edit text-sun"></i> Editar Comité
                </h3>
                <button onclick="closeModal('modal-editar-comite-{{ $association->id }}')" class="w-8 h-8 rounded-xl bg-cream border-2 border-wheat flex items-center justify-center text-earth hover:bg-wheat transition-all">
                    <i class="fas fa-times"></i>
                </button>
            </div>
            <form action="{{ route('club-reconocimientos.update', $association) }}" method="POST" class="p-6 space-y-4">
                @csrf
                @method('PUT')
                <div class="grid grid-cols-2 gap-4">
                    <div>
                        <label class="block text-[11px] font-bold text-earth uppercase tracking-wider mb-1">Resolución *</label>
                        <select name="resolution_id" class="w-full px-4 py-2.5 border-2 border-wheat rounded-xl text-sm font-semibold text-charcoal bg-white focus:outline-none focus:border-leaf transition-all" required>
                            <option value="">Seleccionar...</option>
                            @foreach($resolutions as $resolution)
                            <option value="{{ $resolution->id }}" {{ $association->resolution_id == $resolution->id ? 'selected' : '' }}>{{ $resolution->document }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div>
                        <label class="block text-[11px] font-bold text-earth uppercase tracking-wider mb-1">Código</label>
                        <input type="text" name="code" value="{{ $association->code ?? '' }}" class="w-full px-4 py-2.5 border-2 border-wheat rounded-xl text-sm font-semibold text-charcoal bg-white focus:outline-none focus:border-leaf transition-all" placeholder="Ej: CDM-001">
                    </div>
                </div>
                <div>
                    <label class="block text-[11px] font-bold text-earth uppercase tracking-wider mb-1">Nombre del Comité *</label>
                    <input type="text" name="name" value="{{ $association->name }}" class="w-full px-4 py-2.5 border-2 border-wheat rounded-xl text-sm font-semibold text-charcoal bg-white focus:outline-none focus:border-leaf transition-all" required>
                </div>
                <div>
                    <label class="block text-[11px] font-bold text-earth uppercase tracking-wider mb-1">Dirección *</label>
                    <input type="text" name="address" value="{{ $association->address }}" class="w-full px-4 py-2.5 border-2 border-wheat rounded-xl text-sm font-semibold text-charcoal bg-white focus:outline-none focus:border-leaf transition-all" required>
                </div>
                <div class="grid grid-cols-2 gap-4">
                    <div>
                        <label class="block text-[11px] font-bold text-earth uppercase tracking-wider mb-1">Sector *</label>
                        <select name="place_sector_id" class="w-full px-4 py-2.5 border-2 border-wheat rounded-xl text-sm font-semibold text-charcoal bg-white focus:outline-none focus:border-leaf transition-all" required>
                            <option value="">Seleccionar...</option>
                            @foreach($placeSectors as $ps)
                            <option value="{{ $ps->id }}" {{ $association->place_sector_id == $ps->id ? 'selected' : '' }}>{{ $ps->place->title ?? '' }} - {{ $ps->sector->title ?? '' }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div>
                        <label class="block text-[11px] font-bold text-earth uppercase tracking-wider mb-1">Tipo de Local</label>
                        <select name="type_premises_id" class="w-full px-4 py-2.5 border-2 border-wheat rounded-xl text-sm font-semibold text-charcoal bg-white focus:outline-none focus:border-leaf transition-all">
                            <option value="">Seleccionar...</option>
                            @foreach($typePremises as $tp)
                            <option value="{{ $tp->id }}" {{ $association->type_premises_id == $tp->id ? 'selected' : '' }}>{{ $tp->title }}</option>
                            @endforeach
                        </select>
                    </div>
                </div>
                <div>
                    <label class="block text-[11px] font-bold text-earth uppercase tracking-wider mb-1">Teléfono</label>
                    <input type="text" name="phone" value="{{ $association->phone ?? '' }}" class="w-full px-4 py-2.5 border-2 border-wheat rounded-xl text-sm font-semibold text-charcoal bg-white focus:outline-none focus:border-leaf transition-all">
                </div>
                <div>
                    <label class="block text-[11px] font-bold text-earth uppercase tracking-wider mb-1">Razón Social</label>
                    <input type="text" name="company_name" value="{{ $association->company_name ?? '' }}" class="w-full px-4 py-2.5 border-2 border-wheat rounded-xl text-sm font-semibold text-charcoal bg-white focus:outline-none focus:border-leaf transition-all">
                </div>
                <div>
                    <label class="block text-[11px] font-bold text-earth uppercase tracking-wider mb-1">Observación</label>
                    <textarea name="observation" rows="2" class="w-full px-4 py-2.5 border-2 border-wheat rounded-xl text-sm font-semibold text-charcoal bg-white focus:outline-none focus:border-leaf transition-all">{{ $association->observation ?? '' }}</textarea>
                </div>
                <div class="flex gap-3 pt-2">
                    <button type="submit" class="btn-primary flex-1"><i class="fas fa-save mr-2"></i> Actualizar</button>
                    <button type="button" onclick="closeModal('modal-editar-comite-{{ $association->id }}')" class="btn-secondary flex-1">Cancelar</button>
                </div>
            </form>
        </div>
    </div>
</div>

{{-- Modal Asignar Presidenta --}}
<div id="modal-presidenta-{{ $association->id }}" class="fixed inset-0 bg-black/50 backdrop-blur-sm overflow-y-auto h-full w-full z-[70]" style="display: none;">
    <div class="relative mx-auto w-full max-w-md mt-10 mb-10 px-4">
        <div class="bg-white rounded-2xl shadow-2xl border-2 border-wheat overflow-hidden mt-10">
            <div class="flex items-center justify-between px-6 py-4 border-b-2 border-wheat bg-cream">
                <h3 class="font-extrabold text-charcoal text-lg flex items-center gap-2">
                    <i class="fas fa-user-crown text-sun"></i> Asignar Presidenta
                </h3>
                <button onclick="closeModal('modal-presidenta-{{ $association->id }}')" class="w-8 h-8 rounded-xl bg-white border-2 border-wheat flex items-center justify-center text-earth hover:bg-wheat transition-all">
                    <i class="fas fa-times"></i>
                </button>
            </div>
            <form id="form-presidenta-{{ $association->id }}" action="{{ route('club-reconocimientos.club.asignar-presidenta', $association) }}" method="POST" class="p-6 space-y-4">
                @csrf
                <p class="text-sm text-earth">{{ $association->name }}</p>
                @if($association->getPresidentName())
                <div class="bg-leaf-light border-2 border-leaf rounded-xl p-4">
                    <p class="text-[11px] font-bold text-leaf uppercase tracking-wider mb-1">Presidenta Actual</p>
                    <p class="font-semibold text-charcoal text-lg">{{ $association->getPresidentName() }}</p>
                    <p class="text-xs text-earth mt-1">Para cambiar, selecciona una nueva socia abajo</p>
                </div>
                @endif
                <div>
                    <label class="block text-[11px] font-bold text-earth uppercase tracking-wider mb-1">{{ $association->getPresidentName() ? 'Nueva Presidenta' : 'Socia a asignar como Presidenta' }}</label>
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
                <div class="flex gap-3 pt-2">
                    <button type="button" onclick="confirmPresidenta('{{ $association->id }}', '{{ $association->name }}')" class="btn-primary flex-1" {{ $association->partners->isEmpty() ? 'disabled' : '' }}><i class="fas fa-save mr-2"></i> {{ $association->getPresidentName() ? 'Cambiar' : 'Asignar' }}</button>
                    <button type="button" onclick="closeModal('modal-presidenta-{{ $association->id }}')" class="btn-secondary flex-1">Cancelar</button>
                </div>
            </form>
        </div>
    </div>
</div>

@endforeach

<script>
function confirmPresidenta(associationId, associationName) {
    Swal.fire({
        title: '¿Estás seguro?',
        text: 'Se asignará una nueva presidenta al comité "' + associationName + '"',
        icon: 'question',
        showCancelButton: true,
        confirmButtonText: 'Sí, actualizar',
        cancelButtonText: 'Cancelar',
        confirmButtonColor: '#16a34a',
        cancelButtonColor: '#64748b'
    }).then((result) => {
        if (result.isConfirmed) {
            document.getElementById('form-presidenta-' + associationId).submit();
        }
    });
}
</script>
@endsection
