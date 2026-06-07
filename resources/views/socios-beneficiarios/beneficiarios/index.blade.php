@extends('layouts.main')

@section('title', 'Beneficiarios - PROVALE')

@section('content')
<div class="bg-white rounded-2xl border-2 border-wheat shadow-sm overflow-hidden">
    <div class="flex items-center justify-between px-6 py-5 border-b-2 border-wheat flex-wrap gap-4">
        <h3 class="font-extrabold text-charcoal text-xl flex items-center gap-3">
            <i class="fas fa-hand-holding-heart text-leaf"></i> Gestión de Beneficiarios
        </h3>
        <div class="flex gap-3">
            <a href="{{ route('socios-beneficiarios.beneficiarios.imprimir') }}" target="_blank" class="btn-secondary flex items-center gap-2">
                <i class="fas fa-print"></i> Imprimir Ficha
            </a>
            <button onclick="openModal('modal-crear-beneficiario')" class="btn-primary flex items-center gap-2">
                <i class="fas fa-plus"></i> Nuevo Beneficiario
            </button>
        </div>
    </div>

    <form method="GET" action="{{ route('socios-beneficiarios.beneficiarios.index') }}" class="flex gap-4 px-6 py-4 flex-wrap border-b border-wheat bg-cream">
        <div class="flex-1 min-w-48">
            <label class="block text-[11px] font-bold text-earth uppercase tracking-wider mb-2">Buscar (Nombre/DNI)</label>
            <input type="text" name="search" value="{{ request('search') }}" placeholder="Buscar por nombre o DNI..." class="w-full px-4 py-2.5 border-2 border-wheat rounded-xl text-sm font-semibold text-charcoal bg-white focus:outline-none focus:border-leaf transition-all">
        </div>
        <div class="min-w-40">
            <label class="block text-[11px] font-bold text-earth uppercase tracking-wider mb-2">Socio</label>
            <select name="partner_id" class="w-full px-4 py-2.5 border-2 border-wheat rounded-xl text-sm font-semibold text-charcoal bg-white focus:outline-none focus:border-leaf transition-all">
                <option value="">Todos los socios</option>
                @foreach($partners as $partner)
                    <option value="{{ $partner->id }}" {{ request('partner_id') == $partner->id ? 'selected' : '' }}>
                        {{ $partner->people->names ?? 'Sin nombre' }} {{ $partner->people->father_lastname ?? '' }}
                    </option>
                @endforeach
            </select>
        </div>
        <div class="min-w-32">
            <label class="block text-[11px] font-bold text-earth uppercase tracking-wider mb-2">Parentesco</label>
            <select name="relationship_id" class="w-full px-4 py-2.5 border-2 border-wheat rounded-xl text-sm font-semibold text-charcoal bg-white focus:outline-none focus:border-leaf transition-all">
                <option value="">Todos</option>
                @foreach($relationships as $relationship)
                    <option value="{{ $relationship->id }}" {{ request('relationship_id') == $relationship->id ? 'selected' : '' }}>{{ $relationship->title }}</option>
                @endforeach
            </select>
        </div>
        <div class="flex items-end gap-2">
            <button type="submit" class="btn-primary"><i class="fas fa-search mr-1"></i> Buscar</button>
            <a href="{{ route('socios-beneficiarios.beneficiarios.index') }}" class="btn-secondary"><i class="fas fa-times mr-1"></i> Limpiar</a>
        </div>
    </form>

    @if(session('success'))
        <div class="mx-6 mt-4 p-4 bg-green-50 border-2 border-green-200 rounded-xl text-green-700">
            <i class="fas fa-check-circle mr-2"></i>{{ session('success') }}
        </div>
    @endif

    <div class="overflow-x-auto">
        <table class="data-table w-full">
            <thead>
                <tr>
                    <th class="px-6 py-4 text-left">ID</th>
                    <th class="px-6 py-4 text-left">Beneficiario</th>
                    <th class="px-6 py-4 text-left">DNI</th>
                    <th class="px-6 py-4 text-left">Socio</th>
                    <th class="px-6 py-4 text-left">Parentesco</th>
                    <th class="px-6 py-4 text-left">Acciones</th>
                </tr>
            </thead>
            <tbody>
                @forelse($beneficiaries as $beneficiarie)
                <tr>
                    <td class="px-6 text-earth font-mono text-sm">#{{ $beneficiarie->id }}</td>
                    <td class="px-6 font-semibold">
                        {{ $beneficiarie->person ? $beneficiarie->person->names . ' ' . $beneficiarie->person->father_lastname . ' ' . $beneficiarie->person->mother_lastname : 'Sin nombre' }}
                    </td>
                    <td class="px-6 text-earth font-mono text-sm">{{ $beneficiarie->person->dni ?? '-' }}</td>
                    <td class="px-6">
                        @if($beneficiarie->partner && $beneficiarie->partner->people)
                            <span class="px-2 py-1 rounded-lg bg-leaf-light text-leaf text-xs font-bold">
                                {{ $beneficiarie->partner->people->names }} {{ $beneficiarie->partner->people->father_lastname }}
                            </span>
                        @else -
                        @endif
                    </td>
                    <td class="px-6 text-earth text-sm">{{ $beneficiarie->relationship->title ?? '-' }}</td>
                    <td class="px-6">
                        <div class="flex gap-2">
                            <button onclick="openModal('modal-ver-beneficiario-{{ $beneficiarie->id }}')" class="btn-action bg-sky-light text-[#0284C7] hover:bg-sky hover:text-white" title="Ver">
                                <i class="fas fa-eye"></i>
                            </button>
                            <button onclick="openModal('modal-editar-beneficiario-{{ $beneficiarie->id }}')" class="btn-action bg-sun-light text-[#D97706] hover:bg-sun hover:text-white" title="Editar">
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
                    <td colspan="6" class="px-6 py-8 text-center text-earth">No hay registros</td>
                </tr>
                @endforelse
            </tbody>
        </table>
    </div>

    <div class="flex items-center justify-between px-6 py-4 border-t-2 border-wheat">
        <span class="text-sm text-earth font-medium">Mostrando {{ $beneficiaries->firstItem() ?? 0 }} - {{ $beneficiaries->lastItem() ?? 0 }} de {{ $beneficiaries->total() }} registros</span>
        {{ $beneficiaries->appends(request()->query())->links() }}
    </div>
</div>

{{-- Modal Crear Beneficiario --}}
<div id="modal-crear-beneficiario" class="fixed inset-0 bg-black/40 backdrop-blur-sm overflow-y-auto h-full w-full hidden z-50">
    <div class="relative mx-auto w-full max-w-lg mt-16 mb-8 px-4">
        <div class="bg-white rounded-2xl shadow-2xl border-2 border-wheat overflow-hidden">
            <div class="flex items-center justify-between px-6 py-5 border-b-2 border-wheat">
                <h3 class="font-extrabold text-charcoal text-lg flex items-center gap-2">
                    <i class="fas fa-hand-holding-heart text-leaf"></i> Nuevo Beneficiario
                </h3>
                <button onclick="closeModal('modal-crear-beneficiario')" class="w-8 h-8 rounded-xl bg-cream border-2 border-wheat flex items-center justify-center text-earth hover:bg-wheat transition-all">
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
                    <button type="submit" class="btn-primary flex-1"><i class="fas fa-save mr-2"></i> Guardar</button>
                    <button type="button" onclick="closeModal('modal-crear-beneficiario')" class="btn-secondary flex-1">Cancelar</button>
                </div>
            </form>
        </div>
    </div>
</div>

@foreach($beneficiaries as $beneficiarie)

{{-- Modal Ver Beneficiario --}}
<div id="modal-ver-beneficiario-{{ $beneficiarie->id }}" class="fixed inset-0 bg-black/40 backdrop-blur-sm overflow-y-auto h-full w-full hidden z-50">
    <div class="relative mx-auto w-full max-w-md mt-16 mb-8 px-4">
        <div class="bg-white rounded-2xl shadow-2xl border-2 border-wheat overflow-hidden">
            <div class="flex items-center justify-between px-6 py-5 border-b-2 border-wheat">
                <h3 class="font-extrabold text-charcoal text-lg flex items-center gap-2">
                    <i class="fas fa-hand-holding-heart text-leaf"></i> Detalle del Beneficiario
                </h3>
                <button onclick="closeModal('modal-ver-beneficiario-{{ $beneficiarie->id }}')" class="w-8 h-8 rounded-xl bg-cream border-2 border-wheat flex items-center justify-center text-earth hover:bg-wheat transition-all">
                    <i class="fas fa-times"></i>
                </button>
            </div>
            <div class="p-6 space-y-3 text-sm">
                <div><span class="text-[11px] font-bold text-earth uppercase">Nombre</span>
                    <p class="font-semibold text-charcoal">{{ $beneficiarie->person ? $beneficiarie->person->names . ' ' . $beneficiarie->person->father_lastname . ' ' . $beneficiarie->person->mother_lastname : 'Sin nombre' }}</p>
                </div>
                <div class="grid grid-cols-2 gap-3">
                    <div><span class="text-[11px] font-bold text-earth uppercase">DNI</span><p>{{ $beneficiarie->person->dni ?? '-' }}</p></div>
                    <div><span class="text-[11px] font-bold text-earth uppercase">Parentesco</span><p>{{ $beneficiarie->relationship->title ?? '-' }}</p></div>
                </div>
                <div><span class="text-[11px] font-bold text-earth uppercase">Socio</span>
                    <p>{{ $beneficiarie->partner && $beneficiarie->partner->people ? $beneficiarie->partner->people->names . ' ' . $beneficiarie->partner->people->father_lastname : '-' }}</p>
                </div>
                @if($beneficiarie->person && $beneficiarie->person->birthdate)
                <div><span class="text-[11px] font-bold text-earth uppercase">Fecha Nacimiento</span>
                    <p>{{ \Carbon\Carbon::parse($beneficiarie->person->birthdate)->format('d/m/Y') }}</p>
                </div>
                @endif
            </div>
            <div class="px-6 pb-6">
                <button onclick="closeModal('modal-ver-beneficiario-{{ $beneficiarie->id }}')" class="btn-secondary w-full">Cerrar</button>
            </div>
        </div>
    </div>
</div>

{{-- Modal Editar Beneficiario --}}
<div id="modal-editar-beneficiario-{{ $beneficiarie->id }}" class="fixed inset-0 bg-black/40 backdrop-blur-sm overflow-y-auto h-full w-full hidden z-50">
    <div class="relative mx-auto w-full max-w-lg mt-16 mb-8 px-4">
        <div class="bg-white rounded-2xl shadow-2xl border-2 border-wheat overflow-hidden">
            <div class="flex items-center justify-between px-6 py-5 border-b-2 border-wheat">
                <h3 class="font-extrabold text-charcoal text-lg flex items-center gap-2">
                    <i class="fas fa-edit text-sun"></i> Editar Beneficiario
                </h3>
                <button onclick="closeModal('modal-editar-beneficiario-{{ $beneficiarie->id }}')" class="w-8 h-8 rounded-xl bg-cream border-2 border-wheat flex items-center justify-center text-earth hover:bg-wheat transition-all">
                    <i class="fas fa-times"></i>
                </button>
            </div>
            <form action="{{ route('socios-beneficiarios.beneficiarios.update', $beneficiarie) }}" method="POST" class="p-6 space-y-4">
                @csrf
                @method('PUT')
                <div>
                    <label class="block text-[11px] font-bold text-earth uppercase tracking-wider mb-1">Persona *</label>
                    <select name="person_id" class="select-person-edit w-full px-4 py-2.5 border-2 border-wheat rounded-xl text-sm font-semibold text-charcoal bg-white focus:outline-none focus:border-leaf transition-all" required>
                        @if($beneficiarie->person)
                        <option value="{{ $beneficiarie->person->id }}" selected>{{ $beneficiarie->person->names }} {{ $beneficiarie->person->father_lastname }} ({{ $beneficiarie->person->dni }})</option>
                        @endif
                    </select>
                </div>
                <div>
                    <label class="block text-[11px] font-bold text-earth uppercase tracking-wider mb-1">Socio *</label>
                    <select name="partner_id" class="w-full px-4 py-2.5 border-2 border-wheat rounded-xl text-sm font-semibold text-charcoal bg-white focus:outline-none focus:border-leaf transition-all" required>
                        @foreach($partners as $partner)
                        <option value="{{ $partner->id }}" {{ $beneficiarie->partner_id == $partner->id ? 'selected' : '' }}>{{ $partner->people->names ?? '' }} {{ $partner->people->father_lastname ?? '' }}</option>
                        @endforeach
                    </select>
                </div>
                <div>
                    <label class="block text-[11px] font-bold text-earth uppercase tracking-wider mb-1">Parentesco *</label>
                    <select name="relationship_id" class="w-full px-4 py-2.5 border-2 border-wheat rounded-xl text-sm font-semibold text-charcoal bg-white focus:outline-none focus:border-leaf transition-all" required>
                        @foreach($relationships as $relationship)
                        <option value="{{ $relationship->id }}" {{ $beneficiarie->relationship_id == $relationship->id ? 'selected' : '' }}>{{ $relationship->title }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="flex gap-3 pt-2">
                    <button type="submit" class="btn-primary flex-1"><i class="fas fa-save mr-2"></i> Actualizar</button>
                    <button type="button" onclick="closeModal('modal-editar-beneficiario-{{ $beneficiarie->id }}')" class="btn-secondary flex-1">Cancelar</button>
                </div>
            </form>
        </div>
    </div>
</div>

@endforeach

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
    initSelect2Ajax('.select-person-edit', peopleUrl);
});
</script>
@endpush
@endsection
