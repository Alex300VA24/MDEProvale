@extends('layouts.main')

@section('title', 'Comités y Resoluciones - PROVALE')

@section('content')
<div class="bg-white rounded-2xl border-2 border-wheat shadow-sm overflow-hidden">
    <div class="flex flex-col sm:flex-row items-start sm:items-center justify-between px-4 sm:px-6 py-4 sm:py-5 border-b-2 border-wheat gap-3">
        <h3 class="font-extrabold text-charcoal text-2xl flex items-center gap-3">
            <i class="fas fa-building text-leaf"></i> Comités y Resoluciones
        </h3>
        <div class="flex items-center flex-wrap gap-2">
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

    <div class="p-4 sm:p-6">
        <form id="filtro-comites" method="GET" class="mb-6">
            <div class="grid grid-cols-1 sm:grid-cols-2 md:grid-cols-3 gap-3 sm:gap-4">
                <div>
                    <input type="text" name="search" value="{{ request('search') }}" placeholder="Buscar por nombre o código..." class="w-full px-4 py-2.5 border-2 border-wheat rounded-xl text-sm font-semibold text-charcoal bg-white focus:outline-none focus:border-leaf transition-all">
                </div>
                <div>
                    <select name="state_id" class="select2-filter w-full px-4 py-2.5 border-2 border-wheat rounded-xl text-sm font-semibold text-charcoal bg-white focus:outline-none focus:border-leaf transition-all">
                        <option value="">Todos los Estados</option>
                        @foreach($states as $state)
                        <option value="{{ $state->id }}" {{ request('state_id') == $state->id ? 'selected' : '' }}>{{ $state->title }}</option>
                        @endforeach
                    </select>
                </div>
                <div>
                    <select name="resolution_id" class="select2-filter w-full px-4 py-2.5 border-2 border-wheat rounded-xl text-sm font-semibold text-charcoal bg-white focus:outline-none focus:border-leaf transition-all">
                        <option value="">Todas las Resoluciones</option>
                        @foreach($resolutions as $resolution)
                        <option value="{{ $resolution->id }}" {{ request('resolution_id') == $resolution->id ? 'selected' : '' }}>{{ $resolution->document }}</option>
                        @endforeach
                    </select>
                </div>
            </div>
            <div class="flex gap-2 mt-4">
                <a href="{{ route('club-reconocimientos.index') }}" class="btn-secondary"><i class="fas fa-broom mr-2"></i> Limpiar</a>
            </div>
        </form>

        <div id="comites-results">
        <div class="overflow-x-auto -mx-4 sm:mx-0">
            <table class="w-full text-xs sm:text-sm min-w-[700px]">
                <thead class="bg-gray-50">
                    <tr>
                        <th class="px-3 sm:px-4 py-3 text-left font-bold text-earth">Código</th>
                        <th class="px-3 sm:px-4 py-3 text-left font-bold text-earth">Nombre del Comité</th>
                        <th class="px-3 sm:px-4 py-3 text-left font-bold text-earth">Última Resolución</th>
                        <th class="px-3 sm:px-4 py-3 text-center font-bold text-earth">Estado</th>
                        <th class="px-3 sm:px-4 py-3 text-center font-bold text-earth">Acciones</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-200">
                    @forelse($associations as $association)
                    @php $latestResolution = $association->latestResolution; @endphp
                    <tr class="hover:bg-gray-50">
                        <td class="px-3 sm:px-4 py-3 font-mono text-xs">{{ $association->code ?? 'S/C' }}</td>
                        <td class="px-3 sm:px-4 py-3">
                            <div class="font-bold text-charcoal">{{ $association->name }}</div>
                            <div class="text-[12px] text-earth uppercase">{{ $association->address }}</div>
                        </td>
                        <td class="px-3 sm:px-4 py-3">
                            @if($latestResolution)
                            <span class="px-2 py-1 bg-leaf-light text-leaf rounded text-[11px] font-bold border-2 border-leaf">
                                <i class="fas fa-star mr-1"></i> {{ $latestResolution->document }}
                            </span>
                            @else
                            <span class="text-gray-400">-</span>
                            @endif
                        </td>
                        <td class="px-3 sm:px-4 py-3 text-center">
                            <span class="badge {{ $association->state?->abbreviation === 'VIG' ? 'badge-current' : ($association->state?->abbreviation === 'PEN' ? 'badge-pending' : ($association->state?->abbreviation === 'VEN' ? 'badge-expired' : 'badge-unknown')) }}">{{ $association->state->title ?? 'N/A' }}</span>
                        </td>
                        <td class="px-3 sm:px-4 py-3 text-center">
                            <div class="flex items-center justify-center gap-2">
                                <button onclick="openModal('modal-ver-comite-{{ $association->id }}')" class="btn-action bg-sky-light text-[#0284C7] hover:bg-sky hover:text-white" title="Ver Detalles">
                                    <i class="fas fa-eye"></i>
                                </button>
                                @if($latestResolution)
                                <button type="button" onclick="abrirResolucionExterna({{ $association->id }}, '{{ route('club-reconocimientos.reconocimientos.externa.buscar', $latestResolution) }}')" class="btn-action bg-teal-light text-teal hover:bg-teal hover:text-white" title="Descargar Resolución (Portal Municipal)">
                                    <i class="fas fa-file-pdf"></i>
                                </button>
                                @endif
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
                        <td colspan="5" class="px-4 py-8 text-center text-gray-500">
                            <i class="fas fa-building text-4xl mb-3"></i>
                            <p>No hay comités registrados</p>
                        </td>
                    </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        <div class="mt-4">{{ $associations->appends(request()->query())->links() }}</div>
@foreach($associations as $association)

{{-- Modal Ver Comité --}}
<div id="modal-ver-comite-{{ $association->id }}" class="fixed inset-0 bg-black/40 backdrop-blur-sm overflow-y-auto h-full w-full z-40" style="display: none;">
    <div class="relative mx-auto w-full max-w-full sm:max-w-lg mt-8 sm:mt-16 mb-8 px-2 sm:px-4">
        <div class="bg-white rounded-2xl shadow-2xl border-2 border-wheat overflow-hidden">
            <div class="flex items-center justify-between px-6 py-5 border-b-2 border-wheat">
                <h3 class="font-extrabold text-charcoal text-lg flex items-center gap-2">
                    <i class="fas fa-building text-leaf"></i> Detalle del Comité
                </h3>
                <button type="button" onclick="closeModal('modal-ver-comite-{{ $association->id }}')" class="w-8 h-8 rounded-xl bg-cream border-2 border-wheat flex items-center justify-center text-earth hover:bg-wheat transition-all" aria-label="Cerrar modal">
                    <i class="fas fa-times"></i>
                </button>
            </div>
            <div class="p-6 space-y-3 text-sm">
                <div class="grid grid-cols-2 gap-3">
                    <div><span class="text-[11px] font-bold text-earth uppercase">Código</span><p class="font-semibold text-charcoal">{{ $association->code ?? 'S/C' }}</p></div>
                    <div><span class="text-[11px] font-bold text-earth uppercase">Estado</span>
                        <p><span class="badge {{ $association->state?->abbreviation === 'VIG' ? 'badge-current' : ($association->state?->abbreviation === 'PEN' ? 'badge-pending' : ($association->state?->abbreviation === 'VEN' ? 'badge-expired' : 'badge-unknown')) }}">{{ $association->state->title ?? 'N/A' }}</span></p>
                    </div>
                </div>
                <div><span class="text-[11px] font-bold text-earth uppercase">Nombre</span><p class="font-semibold text-charcoal">{{ $association->name }}</p></div>
                <div><span class="text-[11px] font-bold text-earth uppercase">Dirección</span><p class="text-charcoal">{{ $association->address ?? '-' }}</p></div>
                <div><span class="text-[11px] font-bold text-earth uppercase">Presidenta</span><p class="text-charcoal">{{ $association->getPresidentName() ?? 'Sin asignar' }}</p></div>
                @php $allResolutions = $association->getAllResolutions(); @endphp
                @if($allResolutions->count() > 0)
                <div><span class="text-[11px] font-bold text-earth uppercase">Resoluciones</span>
                    <div class="mt-1 space-y-1">
                        @foreach($allResolutions as $index => $res)
                        <p class="font-semibold text-leaf{{ $loop->last ? '' : '' }}">
                            {{ $index + 1 }}. {{ $res->document }}
                            <span class="text-xs text-earth">({{ \Carbon\Carbon::parse($res->date_start)->format('d/m/Y') }} - {{ \Carbon\Carbon::parse($res->date_end)->format('d/m/Y') }})</span>
                            @if($res->id === $association->resolution_id)
                            <span class="text-[10px] bg-leaf text-white px-1 rounded">Original</span>
                            @endif
                        </p>
                        @endforeach
                    </div>
                </div>
                @endif
                <div class="grid grid-cols-2 gap-3">
                    <div><span class="text-[11px] font-bold text-earth uppercase">Socios</span><p class="font-bold text-leaf text-lg">{{ $association->partners->count() }}</p></div>
                </div>
            </div>
        </div>
    </div>
</div>

{{-- Modal Editar Comité --}}
<div id="modal-editar-comite-{{ $association->id }}" class="fixed inset-0 bg-black/40 backdrop-blur-sm overflow-y-auto h-full w-full z-40" style="display: none;">
    <div class="relative mx-auto w-full max-w-full sm:max-w-2xl mt-8 sm:mt-16 mb-8 px-2 sm:px-4">
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
                    <button type="button" onclick="closeModal('modal-editar-comite-{{ $association->id }}')" class="btn-secondary flex-1">Cancelar</button>
                    <button type="submit" class="btn-primary flex-1"><i class="fas fa-save mr-2"></i> Actualizar</button>
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
                    <button type="button" onclick="closeModal('modal-presidenta-{{ $association->id }}')" class="btn-secondary flex-1">Cancelar</button>
                    <button type="button" onclick="confirmPresidenta('{{ $association->id }}', '{{ $association->name }}')" class="btn-primary flex-1" {{ $association->partners->isEmpty() ? 'disabled' : '' }}><i class="fas fa-save mr-2"></i> {{ $association->getPresidentName() ? 'Cambiar' : 'Asignar' }}</button>
                </div>
            </form>
        </div>
    </div>
</div>

@if($association->latestResolution)
{{-- Modal Descargar Resolución Externa --}}
<div id="modal-resolucion-{{ $association->id }}" class="fixed inset-0 bg-black/40 backdrop-blur-sm overflow-y-auto h-full w-full z-40" style="display: none;">
    <div class="relative mx-auto w-full max-w-full sm:max-w-3xl mt-4 sm:mt-10 mb-8 px-2 sm:px-4">
        <div class="bg-white rounded-2xl shadow-2xl border-2 border-wheat overflow-hidden">
            <div class="flex items-center justify-between px-6 py-5 border-b-2 border-wheat">
                <h3 class="font-extrabold text-charcoal text-lg flex items-center gap-2">
                    <i class="fas fa-file-pdf text-teal"></i> Resolución {{ $association->latestResolution->document }}
                </h3>
                <button onclick="cerrarModalResolucion({{ $association->id }})" class="w-8 h-8 rounded-xl bg-cream border-2 border-wheat flex items-center justify-center text-earth hover:bg-wheat transition-all">
                    <i class="fas fa-times"></i>
                </button>
            </div>
            <div class="p-4 sm:p-6">
                <div id="loading-resolucion-{{ $association->id }}" class="py-16 text-center text-earth">
                    <i class="fas fa-spinner fa-spin text-3xl mb-3"></i>
                    <p class="font-semibold">Buscando la resolución en el portal de la Municipalidad...</p>
                </div>
                <div id="error-resolucion-{{ $association->id }}" class="hidden py-10 text-center text-clay bg-clay-light rounded-xl border-2 border-clay px-4">
                    <i class="fas fa-exclamation-triangle text-2xl mb-2"></i>
                    <p class="font-semibold" id="error-resolucion-msg-{{ $association->id }}"></p>
                </div>
                <iframe id="iframe-resolucion-{{ $association->id }}" class="hidden w-full rounded-xl border-2 border-wheat" style="height: 65vh;"></iframe>
            </div>
            <div class="px-6 pb-6 flex justify-end gap-3">
                <button type="button" onclick="cerrarModalResolucion({{ $association->id }})" class="btn-secondary">Cerrar</button>
                <a id="btn-descargar-resolucion-{{ $association->id }}" href="#" target="_blank" class="btn-primary opacity-50 pointer-events-none">
                    <i class="fas fa-download mr-2"></i> Descargar PDF
                </a>
            </div>
        </div>
    </div>
</div>
@endif

@endforeach
        </div>
    </div>
</div>

{{-- Modal Crear Comité --}}
<div id="modal-crear-comite" class="fixed inset-0 bg-black/40 backdrop-blur-sm overflow-y-auto h-full w-full z-40" style="display: none;">
    <div class="relative mx-auto w-full max-w-full sm:max-w-2xl mt-8 sm:mt-16 mb-8 px-2 sm:px-4">
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
                    <button type="button" onclick="closeModal('modal-crear-comite')" class="btn-secondary flex-1">Cancelar</button>
                    <button type="submit" class="btn-primary flex-1"><i class="fas fa-save mr-2"></i> Guardar</button>
                </div>
            </form>
        </div>
    </div>
</div>


<script>
document.addEventListener('DOMContentLoaded', function() {
    window.initLiveFilter({
        formEl: document.getElementById('filtro-comites'),
        resultsSelector: '#comites-results',
        url: '{{ route("club-reconocimientos.index") }}',
    });
});

function abrirResolucionExterna(associationId, buscarUrl) {
    const loading = document.getElementById('loading-resolucion-' + associationId);
    const errorBox = document.getElementById('error-resolucion-' + associationId);
    const errorMsg = document.getElementById('error-resolucion-msg-' + associationId);
    const frame = document.getElementById('iframe-resolucion-' + associationId);
    const downloadBtn = document.getElementById('btn-descargar-resolucion-' + associationId);

    openModal('modal-resolucion-' + associationId);
    loading.classList.remove('hidden');
    errorBox.classList.add('hidden');
    frame.classList.add('hidden');
    frame.src = 'about:blank';
    downloadBtn.classList.add('opacity-50', 'pointer-events-none');
    downloadBtn.href = '#';

    fetch(buscarUrl)
        .then(response => response.json().then(data => ({ ok: response.ok, data })))
        .then(({ ok, data }) => {
            loading.classList.add('hidden');
            if (ok && data.success) {
                frame.src = data.preview_url;
                frame.classList.remove('hidden');
                downloadBtn.href = data.download_url;
                downloadBtn.classList.remove('opacity-50', 'pointer-events-none');
            } else {
                errorMsg.textContent = data.message || 'No se encontró la resolución en el portal municipal.';
                errorBox.classList.remove('hidden');
            }
        })
        .catch(() => {
            loading.classList.add('hidden');
            errorMsg.textContent = 'Error de conexión al buscar la resolución en el portal municipal.';
            errorBox.classList.remove('hidden');
        });
}

function cerrarModalResolucion(associationId) {
    closeModal('modal-resolucion-' + associationId);
    const frame = document.getElementById('iframe-resolucion-' + associationId);
    if (frame) frame.src = 'about:blank';
}

function confirmPresidenta(associationId, associationName) {
    Swal.fire({
        title: '¿Estás seguro?',
        text: 'Se asignará una nueva presidenta al comité "' + associationName + '"',
        icon: 'question',
        showCancelButton: true,
        reverseButtons: true,
        buttonsStyling: false,
        confirmButtonText: 'Sí, actualizar',
        cancelButtonText: 'Cancelar',
        customClass: { confirmButton: 'btn-primary', cancelButton: 'btn-secondary', actions: 'gap-3' }
    }).then((result) => {
        if (result.isConfirmed) {
            document.getElementById('form-presidenta-' + associationId).submit();
        }
    });
}
</script>
@endsection
