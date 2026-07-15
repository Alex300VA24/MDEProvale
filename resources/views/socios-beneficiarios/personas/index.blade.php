@extends('layouts.main')

@section('title', 'Personas - PROVALE')

@section('content')
<div class="bg-white rounded-2xl border-2 border-wheat shadow-sm overflow-hidden">
    <div class="flex flex-col sm:flex-row items-start sm:items-center justify-between px-4 sm:px-6 py-4 sm:py-5 border-b-2 border-wheat gap-3">
        <h3 class="font-extrabold text-charcoal text-xl flex items-center gap-3">
            <i class="fas fa-users text-leaf"></i> Personas Registradas
        </h3>
        <div class="flex items-center flex-wrap gap-2">
            <a href="{{ route('socios-beneficiarios.index') }}" class="btn-secondary flex items-center gap-2">
                <i class="fas fa-arrow-left"></i> Volver
            </a>
            <button onclick="openModal('modal-crear-persona')" class="btn-primary flex items-center gap-2">
                <i class="fas fa-plus"></i> Nueva Persona
            </button>
        </div>
    </div>

    <div class="p-4 sm:p-6">
        <form id="filtro-personas" method="GET" action="{{ route('socios-beneficiarios.personas.index') }}" class="mb-6">
            <div class="flex flex-col sm:flex-row gap-2 sm:gap-4">
                <div class="w-full sm:w-72">
                    <input type="text" name="search" value="{{ request('search') }}" placeholder="Buscar por nombre o DNI" class="w-full px-4 py-2.5 border-2 border-wheat rounded-xl text-sm font-semibold text-charcoal bg-white focus:outline-none focus:border-leaf transition-all">
                </div>
                <div class="flex-1 sm:min-w-36">
                    <select name="gender" class="select2-filter w-full px-8 py-2.5 border-2 border-wheat rounded-xl text-sm font-semibold text-charcoal bg-white focus:outline-none focus:border-leaf transition-all">
                        <option value="">Género</option>
                        <option value="M" {{ request('gender') == 'M' ? 'selected' : '' }}>Masculino</option>
                        <option value="F" {{ request('gender') == 'F' ? 'selected' : '' }}>Femenino</option>
                    </select>
                </div>
                <div class="flex-1 sm:min-w-48">
                    <select name="place_sector_id" class="select2-filter w-full px-4 py-2.5 border-2 border-wheat rounded-xl text-sm font-semibold text-charcoal bg-white focus:outline-none focus:border-leaf transition-all">
                        <option value="">Todos los sectores</option>
                        @if(isset($placeSectors))
                        @foreach($placeSectors as $ps)
                        <option value="{{ $ps->id }}" {{ request('place_sector_id') == $ps->id ? 'selected' : '' }}>
                            {{ $ps->place->title ?? 'N/A' }} - {{ $ps->sector->title ?? 'N/A' }}
                        </option>
                        @endforeach
                        @endif
                    </select>
                </div>
                <div>
                    <a href="{{ route('socios-beneficiarios.personas.index') }}" class="btn-secondary"><i class="fas fa-broom mr-2"></i> Limpiar</a>
                </div>
            </div>
        </form>

        <div id="personas-results">
            <div class="overflow-x-auto -mx-4 sm:mx-0">
                <table class="data-table w-full text-xs sm:text-sm min-w-[700px]">
                    <thead>
                        <tr>
                            <th class="px-3 sm:px-4 py-3 text-left">DNI</th>
                            <th class="px-3 sm:px-4 py-3 text-left">Nombres</th>
                            <th class="px-3 sm:px-4 py-3 text-left">Apellidos</th>
                            <th class="px-3 sm:px-4 py-3 text-left">Edad</th>
                            <th class="px-3 sm:px-4 py-3 text-left">Celular</th>
                            <th class="px-3 sm:px-4 py-3 text-left">Sector</th>
                            <th class="px-3 sm:px-4 py-3 text-center">Acciones</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($people as $person)
                        <tr class="row-enter">
                            <td class="px-3 sm:px-4 font-mono">{{ $person->dni }}</td>
                            <td class="px-3 sm:px-4 font-medium">{{ $person->names }}</td>
                            <td class="px-3 sm:px-4">{{ $person->father_lastname }} {{ $person->mother_lastname }}</td>
                            <td class="px-3 sm:px-4 text-leaf font-semibold">{{ $person->age_formatted }}</td>
                            <td class="px-3 sm:px-4">{{ $person->phone_number ?? 'Sin número' }}</td>
                            <td class="px-3 sm:px-4 text-xs">{{ $person->placeSector->sector->title ?? 'N/A' }}</td>
                            <td class="px-3 sm:px-4 text-center">
                                <div class="flex items-center justify-center gap-2">
                                    <button type="button" onclick="openViewPersona(this)"
                                        data-nombre="{{ $person->names }} {{ $person->father_lastname }} {{ $person->mother_lastname }}"
                                        data-dni="{{ $person->dni }}"
                                        data-genero="{{ $person->gender == 'F' ? 'Femenino' : 'Masculino' }}"
                                        data-edad="{{ $person->age_formatted }}"
                                        data-celular="{{ $person->phone_number ?? 'Sin número' }}"
                                        data-direccion="{{ $person->address ?? 'Sin dirección' }}"
                                        data-sector="{{ ($person->placeSector->place->title ?? 'N/A') . ' - ' . ($person->placeSector->sector->title ?? 'N/A') }}"
                                        class="btn-action bg-sky-light text-[#0284C7] hover:bg-sky hover:text-white" title="Ver">
                                        <i class="fas fa-eye"></i>
                                    </button>
                                    <button type="button" onclick="openEditPersona(this)"
                                        data-update-url="{{ route('socios-beneficiarios.personas.update', $person) }}"
                                        data-names="{{ $person->names }}"
                                        data-dni="{{ $person->dni }}"
                                        data-father-lastname="{{ $person->father_lastname }}"
                                        data-mother-lastname="{{ $person->mother_lastname }}"
                                        data-birthdate="{{ $person->birthdate }}"
                                        data-gender="{{ $person->gender }}"
                                        data-address="{{ $person->address }}"
                                        data-phone="{{ $person->phone_number }}"
                                        data-place-sector-id="{{ $person->place_sector_id }}"
                                        class="btn-action bg-sun-light text-[#D97706] hover:bg-sun hover:text-white" title="Editar">
                                        <i class="fas fa-edit"></i>
                                    </button>
                                    <form id="form-delete-persona-{{ $person->id }}" action="{{ route('socios-beneficiarios.personas.destroy', $person) }}" method="POST" class="inline">
                                        @csrf
                                        @method('DELETE')
                                        <button type="button" class="btn-action bg-clay-light text-clay hover:bg-clay hover:text-white" title="Eliminar"
                                            onclick="confirmDelete('form-delete-persona-{{ $person->id }}', 'Se eliminará esta persona de forma permanente.')">
                                            <i class="fas fa-trash"></i>
                                        </button>
                                    </form>
                                </div>
                            </td>
                        </tr>
                        @empty
                        <tr>
                            <td colspan="7">
                                <div class="empty-state">
                                    <i class="fas fa-users"></i>
                                    <p>No hay personas registradas</p>
                                </div>
                            </td>
                        </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
            <div class="mt-4">{{ $people->appends(request()->query())->links() }}</div>
        </div>
    </div>
</div>

@include('socios-beneficiarios.personas._modal-crear')
@include('socios-beneficiarios.personas._modal-ver')
@include('socios-beneficiarios.personas._modal-editar')

@push('scripts')
<script>
document.addEventListener('DOMContentLoaded', function() {
    window.initLiveFilter({
        formEl: document.getElementById('filtro-personas'),
        resultsSelector: '#personas-results',
        url: '{{ route("socios-beneficiarios.personas.index") }}',
    });
});

function openViewPersona(btn) {
    document.getElementById('ver-persona-nombre').textContent = btn.dataset.nombre;
    document.getElementById('ver-persona-dni').textContent = btn.dataset.dni;
    document.getElementById('ver-persona-genero').textContent = btn.dataset.genero;
    document.getElementById('ver-persona-edad').textContent = btn.dataset.edad;
    document.getElementById('ver-persona-celular').textContent = btn.dataset.celular;
    document.getElementById('ver-persona-direccion').textContent = btn.dataset.direccion;
    document.getElementById('ver-persona-sector').textContent = btn.dataset.sector;
    openModal('modal-ver-persona');
}

function openEditPersona(btn) {
    document.getElementById('form-editar-persona').action = btn.dataset.updateUrl;
    document.getElementById('editar-persona-names').value = btn.dataset.names || '';
    document.getElementById('editar-persona-dni').value = btn.dataset.dni || '';
    document.getElementById('editar-persona-father-lastname').value = btn.dataset.fatherLastname || '';
    document.getElementById('editar-persona-mother-lastname').value = btn.dataset.motherLastname || '';
    document.getElementById('editar-persona-birthdate').value = btn.dataset.birthdate || '';
    document.getElementById('editar-persona-gender').value = btn.dataset.gender || 'F';
    document.getElementById('editar-persona-address').value = btn.dataset.address || '';
    document.getElementById('editar-persona-phone').value = btn.dataset.phone || '';
    document.getElementById('editar-persona-place-sector').value = btn.dataset.placeSectorId || '';
    openModal('modal-editar-persona');
}
</script>
@endpush
@endsection
