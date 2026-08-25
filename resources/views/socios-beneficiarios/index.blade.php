@extends('layouts.main')

@section('title', 'Socios y Beneficiarios - PROVALE')

@section('content')
<div class="bg-white rounded-2xl border-2 border-wheat shadow-sm overflow-hidden">
    <div class="flex flex-col sm:flex-row items-start sm:items-center justify-between px-4 sm:px-6 py-4 sm:py-5 border-b-2 border-wheat gap-3">
        <h3 class="font-extrabold text-charcoal text-xl sm:text-2xl flex items-center gap-3">
            <i class="fas fa-users text-leaf"></i> Socios y Beneficiarios
        </h3>
        <div class="flex flex-wrap items-center gap-2">
            <a href="{{ route('socios-beneficiarios.personas.index') }}" class="btn-primary flex items-center gap-2 text-xs sm:text-sm">
                <i class="fas fa-users"></i> <span class="hidden xs:inline">Personas</span>
            </a>
            @if(Auth::user()->canCreateModule('socios-beneficiarios'))
            <button onclick="openModal('modal-crear-socio')" class="btn-primary flex items-center gap-2 text-xs sm:text-sm">
                <i class="fas fa-plus"></i> <span class="hidden sm:inline">Nuevo Socio</span><span class="sm:hidden">Nuevo</span>
            </button>
            @endif
            <a href="{{ route('socios-beneficiarios.beneficiarios.imprimir') }}" target="_blank" class="btn-secondary flex items-center gap-2 text-xs sm:text-sm">
                <i class="fas fa-print"></i> <span class="hidden sm:inline">Ficha</span>
            </a>
            <a href="{{ route('socios-beneficiarios.beneficiarios.padron') }}" class="btn-secondary flex items-center gap-2 text-xs sm:text-sm">
                <i class="fas fa-clipboard-list"></i> <span class="hidden sm:inline">Padrón</span>
            </a>
        </div>
    </div>

    <div class="p-4 sm:p-6">
        <form id="filtro-socios" method="GET" class="mb-4 sm:mb-6">
            <div class="flex flex-col sm:flex-row gap-2 sm:gap-4">
                <div class="w-full sm:w-72">
                    <input type="text" name="search" value="{{ request('search') }}"
                        placeholder="Buscar por nombre o DNI"
                        class="w-full px-4 sm:px-10 py-2.5 border-2 border-wheat rounded-xl text-xs sm:text-sm font-semibold text-charcoal bg-white focus:outline-none focus:border-leaf transition-all">
                </div>

                <div class="flex-1 sm:min-w-40">
                    <select name="association_id"
                        class="select2-filter w-full px-4 py-2.5 border-2 border-wheat rounded-xl text-xs sm:text-sm font-semibold text-charcoal bg-white focus:outline-none focus:border-leaf transition-all">
                        <option value="">Todos los Clubes</option>
                        @foreach($associations as $association)
                            <option value="{{ $association->id }}" {{ request('association_id') == $association->id ? 'selected' : '' }}>
                                {{ $association->name }}
                            </option>
                        @endforeach
                    </select>
                </div>

                <div class="flex-1 sm:min-w-36">
                    <select name="state_id"
                        class="select2-filter w-full px-4 py-2.5 border-2 border-wheat rounded-xl text-xs sm:text-sm font-semibold text-charcoal bg-white focus:outline-none focus:border-leaf transition-all">
                        <option value="">Todos los Estados</option>
                        @foreach($states as $state)
                            <option value="{{ $state->id }}" {{ request('state_id') == $state->id ? 'selected' : '' }}>
                                {{ $state->title }}
                            </option>
                        @endforeach
                    </select>
                </div>
                <div class="flex gap-2">
                    <a href="{{ route('socios-beneficiarios.index') }}" class="btn-secondary text-xs sm:text-sm"><i class="fas fa-broom mr-1 sm:mr-2"></i> <span class="hidden sm:inline">Limpiar</span></a>
                </div>

            </div>
        </form>

        <div id="socios-results">
        <div class="overflow-x-auto -mx-4 sm:mx-0">
            <table class="data-table w-full text-xs sm:text-sm min-w-[600px]">
                <thead>
                    <tr>
                        <th class="px-3 sm:px-4 py-3 text-left">ID</th>
                        <th class="px-3 sm:px-4 py-3 text-left">Socio</th>
                        <th class="px-3 sm:px-4 py-3 text-left">DNI</th>
                        <th class="px-3 sm:px-4 py-3 text-left">Club</th>
                        <th class="px-3 sm:px-4 py-3 text-left">Estado</th>
                        <th class="px-3 sm:px-4 py-3 text-left">Benef.</th>
                        <th class="px-3 sm:px-4 py-3 text-center">Acciones</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($partners as $partner)
                    <tr class="row-enter">
                        <td class="px-3 sm:px-4 py-3">#{{ $partner->id }}</td>
                        <td class="px-3 sm:px-4 py-3 font-medium">
                            {{ $partner->people ? $partner->people->names . ' ' . $partner->people->father_lastname : 'Sin nombre' }}
                        </td>
                        <td class="px-3 sm:px-4 py-3">{{ $partner->people->dni ?? 'Sin DNI' }}</td>
                        <td class="px-3 sm:px-4 py-3">{{ $partner->association->name ?? 'Sin club' }}</td>
                        <td class="px-3 sm:px-4 py-3">
                            <span class="badge {{ $partner->state?->abbreviation === 'VIG' ? 'badge-current' : ($partner->state?->abbreviation === 'VEN' ? 'badge-expired' : 'badge-unknown') }}">
                                {{ $partner->state->title ?? 'Sin estado' }}
                            </span>
                        </td>
                        <td class="px-3 sm:px-4 py-3 text-center">
                            <span class="font-bold text-leaf">{{ $partner->beneficiaries_count }}</span>
                        </td>
                        <td class="px-3 sm:px-4 py-3 text-center">
                            <div class="flex items-center justify-center gap-1 sm:gap-2">
                                <button onclick="openModal('modal-ver-socio-{{ $partner->id }}')" class="btn-action bg-sky-light text-[#0284C7] hover:bg-sky hover:text-white" title="Ver">
                                    <i class="fas fa-eye"></i>
                                </button>
                                @if(Auth::user()->canEditModule('socios-beneficiarios'))
                                <button onclick="openModal('modal-editar-socio-{{ $partner->id }}')" class="btn-action bg-sun-light text-[#D97706] hover:bg-sun hover:text-white" title="Editar">
                                    <i class="fas fa-edit"></i>
                                </button>
                                @endif
                                @if(Auth::user()->canDeleteModule('socios-beneficiarios'))
                                <form id="form-delete-socio-{{ $partner->id }}" action="{{ route('socios-beneficiarios.socios.destroy', $partner) }}" method="POST" class="inline">
                                    @csrf
                                    @method('DELETE')
                                    <button type="button" class="btn-action bg-clay-light text-clay hover:bg-clay hover:text-white" title="Eliminar"
                                        onclick="confirmDelete('form-delete-socio-{{ $partner->id }}', 'Se eliminará este socio y todos sus beneficiarios de forma permanente.')">
                                        <i class="fas fa-trash"></i>
                                    </button>
                                </form>
                                @endif
                            </div>
                        </td>
                    </tr>
                    @empty
                    <tr>
                        <td colspan="7">
                            <div class="empty-state">
                                <i class="fas fa-users"></i>
                                <p>No hay socios registrados</p>
                            </div>
                        </td>
                    </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        <div class="mt-4">{{ $partners->appends(request()->query())->links() }}</div>
@foreach($partners as $partner)
    @include('socios-beneficiarios._modal-ver', ['partner' => $partner])
    @include('socios-beneficiarios._modal-editar', ['partner' => $partner])
@endforeach
        </div>
    </div>
</div>

@include('socios-beneficiarios._modal-crear')


@include('socios-beneficiarios._scripts')

@push('scripts')
<script>
document.addEventListener('DOMContentLoaded', function() {
    window.initLiveFilter({
        formEl: document.getElementById('filtro-socios'),
        resultsSelector: '#socios-results',
        url: '{{ route("socios-beneficiarios.index") }}',
    });
});
</script>
@endpush
@endsection
