@extends('layouts.main')

@section('title', 'Beneficiarios - PROVALE')

@section('content')
<div class="bg-white rounded-2xl border-2 border-wheat shadow-sm overflow-hidden">
    <div class="flex flex-col sm:flex-row items-start sm:items-center justify-between px-4 sm:px-6 py-4 sm:py-5 border-b-2 border-wheat flex-wrap gap-3">
        <h3 class="font-extrabold text-charcoal text-xl flex items-center gap-3">
            <i class="fas fa-hand-holding-heart text-leaf"></i> Gestión de Beneficiarios
        </h3>
        <div class="flex flex-wrap gap-3">
            <a href="{{ route('socios-beneficiarios.beneficiarios.imprimir') }}" target="_blank" class="btn-secondary flex items-center gap-2">
                <i class="fas fa-print"></i> Imprimir Ficha
            </a>
            <button onclick="openModal('modal-crear-beneficiario')" class="btn-primary flex items-center gap-2">
                <i class="fas fa-plus"></i> Nuevo Beneficiario
            </button>
        </div>
    </div>

    <form id="filtro-beneficiarios" method="GET" action="{{ route('socios-beneficiarios.beneficiarios.index') }}" class="flex flex-col sm:flex-row gap-2 sm:gap-4 px-4 sm:px-6 py-3 sm:py-4 flex-wrap border-b border-wheat bg-cream">
        <div class="w-full sm:w-72">
            <label class="block text-[11px] font-bold text-earth uppercase tracking-wider mb-2">Buscar (Nombre/DNI)</label>
            <input type="text" name="search" value="{{ request('search') }}" placeholder="Buscar por nombre o DNI..." class="w-full px-4 py-2.5 border-2 border-wheat rounded-xl text-sm font-semibold text-charcoal bg-white focus:outline-none focus:border-leaf transition-all">
        </div>
        <div class="flex-1 min-w-0 sm:min-w-48">
            <label class="block text-[11px] font-bold text-earth uppercase tracking-wider mb-2">Socio</label>
            <select name="partner_id" class="select2-filter w-full px-4 py-2.5 border-2 border-wheat rounded-xl text-sm font-semibold text-charcoal bg-white focus:outline-none focus:border-leaf transition-all">
                <option value="">Todos los socios</option>
                @foreach($partners as $partner)
                    <option value="{{ $partner->id }}" {{ request('partner_id') == $partner->id ? 'selected' : '' }}>
                        {{ $partner->people->names ?? 'Sin nombre' }} {{ $partner->people->father_lastname ?? '' }}
                    </option>
                @endforeach
            </select>
        </div>
        <div class="flex-1 min-w-0 sm:min-w-36">
            <label class="block text-[11px] font-bold text-earth uppercase tracking-wider mb-2">Parentesco</label>
            <select name="relationship_id" class="select2-filter w-full px-4 py-2.5 border-2 border-wheat rounded-xl text-sm font-semibold text-charcoal bg-white focus:outline-none focus:border-leaf transition-all">
                <option value="">Todos</option>
                @foreach($relationships as $relationship)
                    <option value="{{ $relationship->id }}" {{ request('relationship_id') == $relationship->id ? 'selected' : '' }}>{{ $relationship->title }}</option>
                @endforeach
            </select>
        </div>
        <div class="flex items-end gap-2">
            <a href="{{ route('socios-beneficiarios.beneficiarios.index') }}" class="btn-secondary"><i class="fas fa-broom mr-1"></i> Limpiar</a>
        </div>
    </form>

    <div id="beneficiarios-results">
        <div class="overflow-x-auto -mx-4 sm:mx-0">
            <table class="data-table w-full min-w-[600px] text-xs sm:text-sm">
                <thead>
                    <tr>
                        <th class="px-3 sm:px-4 py-4 text-left">ID</th>
                        <th class="px-3 sm:px-4 py-4 text-left">Beneficiario</th>
                        <th class="px-3 sm:px-4 py-4 text-left">DNI</th>
                        <th class="px-3 sm:px-4 py-4 text-left">Socio</th>
                        <th class="px-3 sm:px-4 py-4 text-left">Parentesco</th>
                        <th class="px-3 sm:px-4 py-4 text-left">Acciones</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($beneficiaries as $beneficiarie)
                    <tr class="row-enter">
                        <td class="px-3 sm:px-4 text-earth font-mono text-sm">#{{ $beneficiarie->id }}</td>
                        <td class="px-3 sm:px-4 font-semibold">
                            {{ $beneficiarie->person ? $beneficiarie->person->names . ' ' . $beneficiarie->person->father_lastname . ' ' . $beneficiarie->person->mother_lastname : 'Sin nombre' }}
                        </td>
                        <td class="px-3 sm:px-4 text-earth font-mono text-sm">{{ $beneficiarie->person->dni ?? '-' }}</td>
                        <td class="px-3 sm:px-4">
                            @if($beneficiarie->partner && $beneficiarie->partner->people)
                                <span class="px-2 py-1 rounded-lg bg-leaf-light text-leaf text-xs font-bold">
                                    {{ $beneficiarie->partner->people->names }} {{ $beneficiarie->partner->people->father_lastname }}
                                </span>
                            @else -
                            @endif
                        </td>
                        <td class="px-3 sm:px-4 text-earth text-sm">{{ $beneficiarie->relationship->title ?? '-' }}</td>
                        <td class="px-3 sm:px-4">
                            <div class="flex gap-2">
                                <button type="button"
                                    onclick="openViewBeneficiario(this)"
                                    data-nombre="{{ $beneficiarie->person ? $beneficiarie->person->names . ' ' . $beneficiarie->person->father_lastname . ' ' . $beneficiarie->person->mother_lastname : 'Sin nombre' }}"
                                    data-dni="{{ $beneficiarie->person->dni ?? '-' }}"
                                    data-parentesco="{{ $beneficiarie->relationship->title ?? '-' }}"
                                    data-socio="{{ $beneficiarie->partner && $beneficiarie->partner->people ? $beneficiarie->partner->people->names . ' ' . $beneficiarie->partner->people->father_lastname : '-' }}"
                                    data-nacimiento="{{ $beneficiarie->person && $beneficiarie->person->birthdate ? \Carbon\Carbon::parse($beneficiarie->person->birthdate)->format('d/m/Y') : '' }}"
                                    class="btn-action bg-sky-light text-[#0284C7] hover:bg-sky hover:text-white" title="Ver">
                                    <i class="fas fa-eye"></i>
                                </button>
                                <button type="button"
                                    onclick="openEditBeneficiario(this)"
                                    data-update-url="{{ route('socios-beneficiarios.beneficiarios.update', $beneficiarie) }}"
                                    data-person-id="{{ $beneficiarie->person->id ?? '' }}"
                                    data-person-label="{{ $beneficiarie->person ? $beneficiarie->person->names . ' ' . $beneficiarie->person->father_lastname . ' (' . $beneficiarie->person->dni . ')' : '' }}"
                                    data-partner-id="{{ $beneficiarie->partner_id }}"
                                    data-relationship-id="{{ $beneficiarie->relationship_id }}"
                                    class="btn-action bg-sun-light text-[#D97706] hover:bg-sun hover:text-white" title="Editar">
                                    <i class="fas fa-edit"></i>
                                </button>
                                <form id="form-delete-benef-{{ $beneficiarie->id }}" action="{{ route('socios-beneficiarios.beneficiarios.destroy', $beneficiarie) }}" method="POST" class="inline">
                                    @csrf
                                    @method('DELETE')
                                    <button type="button" class="btn-action bg-clay-light text-clay hover:bg-clay hover:text-white"
                                        onclick="confirmDelete('form-delete-benef-{{ $beneficiarie->id }}', 'Se eliminará este beneficiario de forma permanente.')">
                                        <i class="fas fa-trash"></i>
                                    </button>
                                </form>
                            </div>
                        </td>
                    </tr>
                    @empty
                    <tr>
                        <td colspan="6">
                            <div class="empty-state">
                                <i class="fas fa-hand-holding-heart"></i>
                                <p>No hay beneficiarios registrados</p>
                            </div>
                        </td>
                    </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        <div class="flex items-center justify-between px-4 sm:px-6 py-3 sm:py-4 border-t-2 border-wheat">
            <span class="text-xs sm:text-sm text-earth font-medium">Mostrando {{ $beneficiaries->firstItem() ?? 0 }} - {{ $beneficiaries->lastItem() ?? 0 }} de {{ $beneficiaries->total() }} registros</span>
            {{ $beneficiaries->appends(request()->query())->links() }}
        </div>
    </div>
</div>

{{-- Modal Crear Beneficiario --}}
<div id="modal-crear-beneficiario" class="modal-overlay">
    <div class="modal-panel sm:max-w-lg">
        <div class="modal-card">
            <div class="modal-header">
                <h3 class="font-extrabold text-charcoal text-lg flex items-center gap-2">
                    <i class="fas fa-hand-holding-heart text-leaf"></i> Nuevo Beneficiario
                </h3>
                <button onclick="closeModal('modal-crear-beneficiario')" class="modal-close-btn">
                    <i class="fas fa-times"></i>
                </button>
            </div>
            <form action="{{ route('socios-beneficiarios.beneficiarios.store') }}" method="POST" class="p-6 space-y-4">
                @csrf
                <div>
                    <label class="block text-[11px] font-bold text-earth uppercase tracking-wider mb-1">Persona *</label>
                    <select name="person_id" id="select-person-create" class="w-full px-4 py-2.5 border-2 border-wheat rounded-xl text-sm font-semibold text-charcoal bg-white focus:outline-none focus:border-leaf transition-all" required>
                        <option value="">Buscar persona por nombre o DNI...</option>
                    </select>
                </div>
                <div>
                    <label class="block text-[11px] font-bold text-earth uppercase tracking-wider mb-1">Socio *</label>
                    <select name="partner_id" class="w-full px-4 py-2.5 border-2 border-wheat rounded-xl text-sm font-semibold text-charcoal bg-white focus:outline-none focus:border-leaf transition-all" required>
                        <option value="">Seleccionar socio...</option>
                        @foreach($partners as $partner)
                        <option value="{{ $partner->id }}">{{ $partner->people->names ?? '' }} {{ $partner->people->father_lastname ?? '' }}</option>
                        @endforeach
                    </select>
                </div>
                <div>
                    <label class="block text-[11px] font-bold text-earth uppercase tracking-wider mb-1">Parentesco *</label>
                    <select name="relationship_id" class="w-full px-4 py-2.5 border-2 border-wheat rounded-xl text-sm font-semibold text-charcoal bg-white focus:outline-none focus:border-leaf transition-all" required>
                        <option value="">Seleccionar...</option>
                        @foreach($relationships as $relationship)
                        <option value="{{ $relationship->id }}">{{ $relationship->title }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="flex gap-3 pt-2">
                    <button type="button" onclick="closeModal('modal-crear-beneficiario')" class="btn-secondary flex-1">Cancelar</button>
                    <button type="submit" class="btn-primary flex-1"><i class="fas fa-save mr-2"></i> Guardar</button>
                </div>
            </form>
        </div>
    </div>
</div>

{{-- Modal Ver Beneficiario (único, poblado por JS al hacer clic en una fila) --}}
<div id="modal-ver-beneficiario" class="modal-overlay">
    <div class="modal-panel sm:max-w-md">
        <div class="modal-card">
            <div class="modal-header">
                <h3 class="font-extrabold text-charcoal text-lg flex items-center gap-2">
                    <i class="fas fa-hand-holding-heart text-leaf"></i> Detalle del Beneficiario
                </h3>
                <button type="button" onclick="closeModal('modal-ver-beneficiario')" class="modal-close-btn" aria-label="Cerrar modal">
                    <i class="fas fa-times"></i>
                </button>
            </div>
            <div class="p-6 space-y-3 text-sm">
                <div><span class="text-[11px] font-bold text-earth uppercase">Nombre</span>
                    <p class="font-semibold text-charcoal" id="ver-benef-nombre"></p>
                </div>
                <div class="grid grid-cols-2 gap-3">
                    <div><span class="text-[11px] font-bold text-earth uppercase">DNI</span><p id="ver-benef-dni"></p></div>
                    <div><span class="text-[11px] font-bold text-earth uppercase">Parentesco</span><p id="ver-benef-parentesco"></p></div>
                </div>
                <div><span class="text-[11px] font-bold text-earth uppercase">Socio</span>
                    <p id="ver-benef-socio"></p>
                </div>
                <div id="ver-benef-nacimiento-wrap" class="hidden">
                    <span class="text-[11px] font-bold text-earth uppercase">Fecha Nacimiento</span>
                    <p id="ver-benef-nacimiento"></p>
                </div>
            </div>
        </div>
    </div>
</div>

{{-- Modal Editar Beneficiario (único, poblado por JS al hacer clic en una fila) --}}
<div id="modal-editar-beneficiario" class="modal-overlay">
    <div class="modal-panel sm:max-w-lg">
        <div class="modal-card">
            <div class="modal-header">
                <h3 class="font-extrabold text-charcoal text-lg flex items-center gap-2">
                    <i class="fas fa-edit text-sun"></i> Editar Beneficiario
                </h3>
                <button onclick="closeModal('modal-editar-beneficiario')" class="modal-close-btn">
                    <i class="fas fa-times"></i>
                </button>
            </div>
            <form id="form-editar-beneficiario" method="POST" class="p-6 space-y-4">
                @csrf
                @method('PUT')
                <div>
                    <label class="block text-[11px] font-bold text-earth uppercase tracking-wider mb-1">Persona *</label>
                    <select name="person_id" id="select-person-edit-shared" class="w-full px-4 py-2.5 border-2 border-wheat rounded-xl text-sm font-semibold text-charcoal bg-white focus:outline-none focus:border-leaf transition-all" required></select>
                </div>
                <div>
                    <label class="block text-[11px] font-bold text-earth uppercase tracking-wider mb-1">Socio *</label>
                    <select name="partner_id" id="select-partner-edit-shared" class="w-full px-4 py-2.5 border-2 border-wheat rounded-xl text-sm font-semibold text-charcoal bg-white focus:outline-none focus:border-leaf transition-all" required>
                        @foreach($partners as $partner)
                        <option value="{{ $partner->id }}">{{ $partner->people->names ?? '' }} {{ $partner->people->father_lastname ?? '' }}</option>
                        @endforeach
                    </select>
                </div>
                <div>
                    <label class="block text-[11px] font-bold text-earth uppercase tracking-wider mb-1">Parentesco *</label>
                    <select name="relationship_id" id="select-relationship-edit-shared" class="w-full px-4 py-2.5 border-2 border-wheat rounded-xl text-sm font-semibold text-charcoal bg-white focus:outline-none focus:border-leaf transition-all" required>
                        @foreach($relationships as $relationship)
                        <option value="{{ $relationship->id }}">{{ $relationship->title }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="flex gap-3 pt-2">
                    <button type="button" onclick="closeModal('modal-editar-beneficiario')" class="btn-secondary flex-1">Cancelar</button>
                    <button type="submit" class="btn-primary flex-1"><i class="fas fa-save mr-2"></i> Actualizar</button>
                </div>
            </form>
        </div>
    </div>
</div>

@push('scripts')
<script>
document.addEventListener('DOMContentLoaded', function() {
    function initSelect2Ajax(selector, url) {
        $(selector).select2({
            ajax: {
                url: url,
                dataType: 'json',
                delay: 300,
                data: function(params) { return { q: params.term || '', limit: 30 }; },
                processResults: function(data) { return data; },
                cache: true,
            },
            minimumInputLength: 2,
            placeholder: 'Buscar por nombre o DNI...',
            allowClear: true,
            width: '100%',
        });
    }

    var peopleUrl = '{{ route("api.search.people") }}';
    initSelect2Ajax('#select-person-create', peopleUrl);
    initSelect2Ajax('#select-person-edit-shared', peopleUrl);

    window.initLiveFilter({
        formEl: document.getElementById('filtro-beneficiarios'),
        resultsSelector: '#beneficiarios-results',
        url: '{{ route("socios-beneficiarios.beneficiarios.index") }}',
    });
});

function openViewBeneficiario(btn) {
    document.getElementById('ver-benef-nombre').textContent = btn.dataset.nombre || 'Sin nombre';
    document.getElementById('ver-benef-dni').textContent = btn.dataset.dni || '-';
    document.getElementById('ver-benef-parentesco').textContent = btn.dataset.parentesco || '-';
    document.getElementById('ver-benef-socio').textContent = btn.dataset.socio || '-';

    const nacWrap = document.getElementById('ver-benef-nacimiento-wrap');
    if (btn.dataset.nacimiento) {
        nacWrap.classList.remove('hidden');
        document.getElementById('ver-benef-nacimiento').textContent = btn.dataset.nacimiento;
    } else {
        nacWrap.classList.add('hidden');
    }

    openModal('modal-ver-beneficiario');
}

function openEditBeneficiario(btn) {
    document.getElementById('form-editar-beneficiario').action = btn.dataset.updateUrl;

    const personSelect = $('#select-person-edit-shared');
    personSelect.empty();
    if (btn.dataset.personId) {
        const opt = new Option(btn.dataset.personLabel, btn.dataset.personId, true, true);
        personSelect.append(opt).trigger('change');
    }

    document.getElementById('select-partner-edit-shared').value = btn.dataset.partnerId || '';
    document.getElementById('select-relationship-edit-shared').value = btn.dataset.relationshipId || '';

    openModal('modal-editar-beneficiario');
}
</script>
@endpush
@endsection
