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
            <a href="{{ route('socios-beneficiarios.beneficiarios.reportes') }}" class="btn-secondary flex items-center gap-2">
                <i class="fas fa-file-pdf"></i> Reportes
            </a>
            <a href="{{ route('socios-beneficiarios.beneficiarios.create') }}" class="btn-primary flex items-center gap-2">
                <i class="fas fa-plus"></i> Nuevo Beneficiario
            </a>
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
                    <option value="{{ $relationship->id }}" {{ request('relationship_id') == $relationship->id ? 'selected' : '' }}>
                        {{ $relationship->title }}
                    </option>
                @endforeach
            </select>
        </div>
        <div class="flex items-end gap-2">
            <button type="submit" class="btn-primary">
                <i class="fas fa-search mr-1"></i> Buscar
            </button>
            <a href="{{ route('socios-beneficiarios.beneficiarios.index') }}" class="btn-secondary">
                <i class="fas fa-times mr-1"></i> Limpiar
            </a>
        </div>
    </form>

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
                        @if($beneficiarie->person)
                            {{ $beneficiarie->person->names }} {{ $beneficiarie->person->father_lastname }} {{ $beneficiarie->person->mother_lastname }}
                        @else
                            Sin nombre
                        @endif
                    </td>
                    <td class="px-6 text-earth font-mono text-sm">
                        @if($beneficiarie->person)
                            {{ $beneficiarie->person->dni }}
                        @else
                            -
                        @endif
                    </td>
                    <td class="px-6">
                        @if($beneficiarie->partner && $beneficiarie->partner->people)
                            <span class="px-2 py-1 rounded-lg bg-leaf-light text-leaf text-xs font-bold">
                                {{ $beneficiarie->partner->people->names }} {{ $beneficiarie->partner->people->father_lastname }}
                            </span>
                        @else
                            -
                        @endif
                    </td>
                    <td class="px-6 text-earth text-sm">
                        {{ $beneficiarie->relationship->title ?? '-' }}
                    </td>
                    <td class="px-6">
                        <div class="flex gap-2">
                            <a href="{{ route('socios-beneficiarios.beneficiarios.show', $beneficiarie) }}" class="btn-action bg-sky-light text-[#0284C7] hover:bg-sky hover:text-white">
                                <i class="fas fa-eye"></i>
                            </a>
                            <a href="{{ route('socios-beneficiarios.beneficiarios.edit', $beneficiarie) }}" class="btn-action bg-sun-light text-[#D97706] hover:bg-sun hover:text-white">
                                <i class="fas fa-edit"></i>
                            </a>
                            <form action="{{ route('socios-beneficiarios.beneficiarios.destroy', $beneficiarie) }}" method="POST" class="inline">
                                @csrf
                                @method('DELETE')
                                <button type="submit" class="btn-action bg-clay-light text-clay hover:bg-clay hover:text-white" onclick="return confirm('¿Estás seguro de eliminar este beneficiario?')">
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
@endsection
