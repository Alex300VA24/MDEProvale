@extends('layouts.main')

@section('title', 'Resoluciones - PROVALE')

@section('content')
<div class="bg-white rounded-2xl border-2 border-wheat shadow-sm overflow-hidden">
    <div class="flex flex-col sm:flex-row items-start sm:items-center justify-between px-4 sm:px-6 py-4 sm:py-5 border-b-2 border-wheat gap-3">
        <h3 class="font-extrabold text-charcoal text-xl flex items-center gap-3">
            <i class="fas fa-file-contract text-leaf"></i> Resoluciones
        </h3>
        <div class="flex flex-wrap gap-3">
            <button onclick="openModal('modal-crear-resolucion')" class="btn-primary flex items-center gap-2">
                <i class="fas fa-plus"></i> Nueva Resolución
            </button>
            <a href="{{ route('club-reconocimientos.index') }}" class="btn-secondary flex items-center gap-2">
                <i class="fas fa-arrow-left"></i> Volver
            </a>
        </div>
    </div>

    <div class="p-4 sm:p-6">
        <form id="filtro-resoluciones" method="GET" class="mb-6">
            <div class="grid grid-cols-1 sm:grid-cols-2 md:grid-cols-3 gap-3 sm:gap-4">
                <div>
                    <label class="block text-[11px] font-bold text-earth uppercase tracking-wider mb-2">Buscar</label>
                    <input type="text" name="search" value="{{ request('search') }}" placeholder="Buscar por documento..." class="w-full px-4 py-2.5 border-2 border-wheat rounded-xl text-sm font-semibold text-charcoal bg-white focus:outline-none focus:border-leaf transition-all">
                </div>
                <div>
                    <label class="block text-[11px] font-bold text-earth uppercase tracking-wider mb-2">Estado</label>
                    <select name="state_id" class="select2-filter w-full px-4 py-2.5 border-2 border-wheat rounded-xl text-sm font-semibold text-charcoal bg-white focus:outline-none focus:border-leaf transition-all">
                        <option value="">Todos los estados</option>
                        @foreach($states as $state)
                            <option value="{{ $state->id }}" {{ request('state_id') == $state->id ? 'selected' : '' }}>{{ $state->title }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="flex gap-2 mt-4">
                    <a href="{{ route('club-reconocimientos.reconocimientos.index') }}" class="btn-secondary"><i class="fas fa-broom mr-1"></i> Limpiar</a>
                </div>
            </div>
        </form>

        <div id="resoluciones-results">
        <div class="overflow-x-auto -mx-4 sm:mx-0">
            <table class="w-full min-w-[600px] text-xs sm:text-sm">
                <thead>
                    <tr class="bg-wheat/30 border-b-2 border-wheat">
                        <th class="px-4 py-3 text-left text-xs font-bold text-earth uppercase tracking-wider">Comités</th>
                        <th class="px-4 py-3 text-left text-xs font-bold text-earth uppercase tracking-wider">Documento</th>
                        <th class="px-4 py-3 text-left text-xs font-bold text-earth uppercase tracking-wider">Fecha Emisión</th>
                        <th class="px-4 py-3 text-left text-xs font-bold text-earth uppercase tracking-wider">Estado</th>
                        <th class="px-4 py-3 text-center text-xs font-bold text-earth uppercase tracking-wider">Acciones</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-wheat">
                    @forelse($resolutions as $resolution)
                    <tr class="hover:bg-wheat/10 transition-colors">
                        <td class="px-4 py-3 text-sm text-charcoal">
                            @php $resolutionAssociations = $resolution->getAllAssociations(); @endphp
                            @if($resolutionAssociations->count() > 0)
                                <div class="flex flex-col gap-1">
                                    @foreach($resolutionAssociations as $association)
                                        <span class="badge badge-unknown"><i class="fas fa-users mr-1"></i>{{ $association->code ? $association->code . ' · ' : '' }}{{ $association->name }}</span>
                                    @endforeach
                                </div>
                            @else
                                <span class="text-gray-400 text-xs">Sin comités</span>
                            @endif
                        </td>
                        <td class="px-4 py-3 text-sm font-semibold text-charcoal">{{ $resolution->document }}</td>
                        <td class="px-4 py-3 text-sm text-charcoal">{{ \Carbon\Carbon::parse($resolution->date_document)->format('d/m/Y') }}</td>
                        <td class="px-4 py-3">
                            <span class="badge {{ $resolution->state?->abbreviation === 'VIG' ? 'badge-current' : ($resolution->state?->abbreviation === 'VEN' ? 'badge-expired' : 'badge-unknown') }}">
                                {{ $resolution->state->title ?? 'N/A' }}
                            </span>
                        </td>
                        <td class="px-4 py-3 text-center">
                            <div class="flex items-center justify-center gap-2">
                                <button onclick="openModal('modal-ver-resolucion-{{ $resolution->id }}')" class="btn-action bg-sky-light text-[#0284C7] hover:bg-sky hover:text-white" title="Ver">
                                    <i class="fas fa-eye"></i>
                                </button>
                                <button onclick="openModal('modal-editar-resolucion-{{ $resolution->id }}')" class="btn-action bg-sun-light text-[#D97706] hover:bg-sun hover:text-white" title="Editar">
                                    <i class="fas fa-edit"></i>
                                </button>
                                <form id="form-delete-resolucion-{{ $resolution->id }}" action="{{ route('club-reconocimientos.reconocimientos.destroy', $resolution) }}" method="POST" class="inline">
                                    @csrf
                                    @method('DELETE')
                                    <button type="button" class="btn-action bg-clay-light text-clay hover:bg-clay hover:text-white" title="Eliminar"
                                        onclick="confirmDelete('form-delete-resolucion-{{ $resolution->id }}', 'Se eliminará esta resolución de forma permanente.')">
                                        <i class="fas fa-trash"></i>
                                    </button>
                                </form>
                            </div>
                        </td>
                    </tr>
                    @empty
                    <tr>
                        <td colspan="5" class="px-4 py-8 text-center text-gray-500">
                            <i class="fas fa-inbox text-4xl mb-2"></i>
                            <p>No hay resoluciones registradas</p>
                        </td>
                    </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        <div class="mt-6">{{ $resolutions->appends(request()->query())->links() }}</div>
@foreach($resolutions as $resolution)

{{-- Modal Ver Resolución --}}
<div id="modal-ver-resolucion-{{ $resolution->id }}" class="fixed inset-0 bg-black/40 backdrop-blur-sm overflow-y-auto h-full w-full hidden z-50">
    <div class="relative mx-auto w-full max-w-full sm:max-w-md mt-8 sm:mt-16 mb-8 px-2 sm:px-4">
        <div class="bg-white rounded-2xl shadow-2xl border-2 border-wheat overflow-hidden">
            <div class="flex items-center justify-between px-6 py-5 border-b-2 border-wheat">
                <h3 class="font-extrabold text-charcoal text-lg flex items-center gap-2">
                    <i class="fas fa-file-contract text-leaf"></i> Detalle de Resolución
                </h3>
                <button type="button" onclick="closeModal('modal-ver-resolucion-{{ $resolution->id }}')" class="w-8 h-8 rounded-xl bg-cream border-2 border-wheat flex items-center justify-center text-earth hover:bg-wheat transition-all" aria-label="Cerrar modal">
                    <i class="fas fa-times"></i>
                </button>
            </div>
            <div class="p-6 space-y-3 text-sm">
                <div><span class="text-[11px] font-bold text-earth uppercase">Documento</span><p class="font-semibold text-charcoal">{{ $resolution->document }}</p></div>
                <div class="grid grid-cols-2 gap-3">
                    <div><span class="text-[11px] font-bold text-earth uppercase">Fecha Emisión</span><p>{{ \Carbon\Carbon::parse($resolution->date_document)->format('d/m/Y') }}</p></div>
                    <div><span class="text-[11px] font-bold text-earth uppercase">Estado</span>
                        <p><span class="badge {{ $resolution->state?->abbreviation === 'VIG' ? 'badge-current' : ($resolution->state?->abbreviation === 'VEN' ? 'badge-expired' : 'badge-unknown') }}">{{ $resolution->state->title ?? 'N/A' }}</span></p>
                    </div>
                </div>
                <div class="grid grid-cols-2 gap-3">
                    <div><span class="text-[11px] font-bold text-earth uppercase">Fecha Inicio</span><p>{{ \Carbon\Carbon::parse($resolution->date_start)->format('d/m/Y') }}</p></div>
                    <div><span class="text-[11px] font-bold text-earth uppercase">Fecha Fin</span><p>{{ \Carbon\Carbon::parse($resolution->date_end)->format('d/m/Y') }}</p></div>
                </div>
                <div><span class="text-[11px] font-bold text-earth uppercase">Comités asociados</span>
                    @php $modalAssociations = $resolution->getAllAssociations(); @endphp
                    @if($modalAssociations->isNotEmpty())
                        <div class="flex flex-wrap gap-2 mt-1">
                            @foreach($modalAssociations as $association)
                                <span class="badge badge-unknown">{{ $association->code ? $association->code . ' · ' : '' }}{{ $association->name }}</span>
                            @endforeach
                        </div>
                    @else
                        <p class="text-earth">Sin comités asociados</p>
                    @endif
                </div>
            </div>
        </div>
    </div>
</div>

{{-- Modal Editar Resolución --}}
<div id="modal-editar-resolucion-{{ $resolution->id }}" class="fixed inset-0 bg-black/40 backdrop-blur-sm overflow-y-auto h-full w-full hidden z-50">
    <div class="relative mx-auto w-full max-w-full sm:max-w-lg mt-8 sm:mt-16 mb-8 px-2 sm:px-4">
        <div class="bg-white rounded-2xl shadow-2xl border-2 border-wheat overflow-hidden">
            <div class="flex items-center justify-between px-6 py-5 border-b-2 border-wheat">
                <h3 class="font-extrabold text-charcoal text-lg flex items-center gap-2">
                    <i class="fas fa-edit text-sun"></i> Editar Resolución
                </h3>
                <button onclick="closeModal('modal-editar-resolucion-{{ $resolution->id }}')" class="w-8 h-8 rounded-xl bg-cream border-2 border-wheat flex items-center justify-center text-earth hover:bg-wheat transition-all">
                    <i class="fas fa-times"></i>
                </button>
            </div>
            <form action="{{ route('club-reconocimientos.reconocimientos.update', $resolution) }}" method="POST" class="p-6 space-y-4">
                @csrf
                @method('PUT')
                <div>
                    <label class="block text-[11px] font-bold text-earth uppercase tracking-wider mb-1">Documento *</label>
                    <input type="text" name="document" value="{{ $resolution->document }}" class="w-full px-4 py-2.5 border-2 border-wheat rounded-xl text-sm font-semibold text-charcoal bg-white focus:outline-none focus:border-leaf transition-all" required>
                </div>
                <div class="grid grid-cols-2 gap-4">
                    <div class="col-span-2">
                        <label class="block text-[11px] font-bold text-earth uppercase tracking-wider mb-1">Fecha Emisión *</label>
                        <input type="date" name="date_document" value="{{ $resolution->date_document ? \Carbon\Carbon::parse($resolution->date_document)->format('Y-m-d') : '' }}" class="w-full px-4 py-2.5 border-2 border-wheat rounded-xl text-sm font-semibold text-charcoal bg-white focus:outline-none focus:border-leaf transition-all" required>
                    </div>
                </div>
                <div class="grid grid-cols-2 gap-4">
                    <div>
                        <label class="block text-[11px] font-bold text-earth uppercase tracking-wider mb-1">Fecha Inicio *</label>
                        <input type="date" name="date_start" value="{{ $resolution->date_start ? \Carbon\Carbon::parse($resolution->date_start)->format('Y-m-d') : '' }}" class="w-full px-4 py-2.5 border-2 border-wheat rounded-xl text-sm font-semibold text-charcoal bg-white focus:outline-none focus:border-leaf transition-all" required>
                    </div>
                    <div>
                        <label class="block text-[11px] font-bold text-earth uppercase tracking-wider mb-1">Fecha Fin *</label>
                        <input type="date" name="date_end" value="{{ $resolution->date_end ? \Carbon\Carbon::parse($resolution->date_end)->format('Y-m-d') : '' }}" class="w-full px-4 py-2.5 border-2 border-wheat rounded-xl text-sm font-semibold text-charcoal bg-white focus:outline-none focus:border-leaf transition-all" required>
                    </div>
                </div>
                <div class="flex gap-3 pt-2">
                    <button type="button" onclick="closeModal('modal-editar-resolucion-{{ $resolution->id }}')" class="btn-secondary flex-1">Cancelar</button>
                    <button type="submit" class="btn-primary flex-1"><i class="fas fa-save mr-2"></i> Actualizar</button>
                </div>
            </form>
        </div>
    </div>
</div>

@endforeach
        </div>
    </div>
</div>

{{-- Modal Crear Resolución --}}
<div id="modal-crear-resolucion" class="fixed inset-0 bg-black/40 backdrop-blur-sm overflow-y-auto h-full w-full hidden z-50">
    <div class="relative mx-auto w-full max-w-full sm:max-w-lg mt-8 sm:mt-16 mb-8 px-2 sm:px-4">
        <div class="bg-white rounded-2xl shadow-2xl border-2 border-wheat overflow-hidden">
            <div class="flex items-center justify-between px-6 py-5 border-b-2 border-wheat">
                <h3 class="font-extrabold text-charcoal text-lg flex items-center gap-2">
                    <i class="fas fa-file-contract text-leaf"></i> Nueva Resolución
                </h3>
                <button onclick="closeModal('modal-crear-resolucion')" class="w-8 h-8 rounded-xl bg-cream border-2 border-wheat flex items-center justify-center text-earth hover:bg-wheat transition-all">
                    <i class="fas fa-times"></i>
                </button>
            </div>
            <form action="{{ route('club-reconocimientos.reconocimientos.store') }}" method="POST" class="p-6 space-y-4">
                @csrf
                <div>
                    <label class="block text-[11px] font-bold text-earth uppercase tracking-wider mb-1">Documento *</label>
                    <input type="text" name="document" class="w-full px-4 py-2.5 border-2 border-wheat rounded-xl text-sm font-semibold text-charcoal bg-white focus:outline-none focus:border-leaf transition-all" required>
                </div>
                <div class="grid grid-cols-2 gap-4">
                    <div class="col-span-2">
                        <label class="block text-[11px] font-bold text-earth uppercase tracking-wider mb-1">Fecha Emisión *</label>
                        <input type="date" name="date_document" class="w-full px-4 py-2.5 border-2 border-wheat rounded-xl text-sm font-semibold text-charcoal bg-white focus:outline-none focus:border-leaf transition-all" required>
                    </div>
                </div>
                <div class="grid grid-cols-2 gap-4">
                    <div>
                        <label class="block text-[11px] font-bold text-earth uppercase tracking-wider mb-1">Fecha Inicio *</label>
                        <input type="date" name="date_start" class="w-full px-4 py-2.5 border-2 border-wheat rounded-xl text-sm font-semibold text-charcoal bg-white focus:outline-none focus:border-leaf transition-all" required>
                    </div>
                    <div>
                        <label class="block text-[11px] font-bold text-earth uppercase tracking-wider mb-1">Fecha Fin *</label>
                        <input type="date" name="date_end" class="w-full px-4 py-2.5 border-2 border-wheat rounded-xl text-sm font-semibold text-charcoal bg-white focus:outline-none focus:border-leaf transition-all" required>
                    </div>
                </div>
                <div class="flex gap-3 pt-2">
                    <button type="button" onclick="closeModal('modal-crear-resolucion')" class="btn-secondary flex-1">Cancelar</button>
                    <button type="submit" class="btn-primary flex-1"><i class="fas fa-save mr-2"></i> Guardar</button>
                </div>
            </form>
        </div>
    </div>
</div>

@push('scripts')
<script>
document.addEventListener('DOMContentLoaded', function() {
    window.initLiveFilter({
        formEl: document.getElementById('filtro-resoluciones'),
        resultsSelector: '#resoluciones-results',
        url: '{{ route("club-reconocimientos.reconocimientos.index") }}',
    });
});
</script>
@endpush
@endsection
