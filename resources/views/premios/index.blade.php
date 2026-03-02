@extends('layouts.main')

@section('title', 'Reconocimientos - PROVALE')

@section('content')
<div class="bg-white rounded-2xl border-2 border-wheat shadow-sm overflow-hidden">
    <div class="flex items-center justify-between px-6 py-5 border-b-2 border-wheat flex-wrap gap-4">
        <h3 class="font-extrabold text-charcoal text-xl flex items-center gap-3">
            <i class="fas fa-award text-leaf"></i> Gestión de Reconocimientos
        </h3>
        <div class="flex gap-3">
            <a href="{{ route('premios.reportes') }}" class="btn-secondary flex items-center gap-2">
                <i class="fas fa-file-pdf"></i> Reportes
            </a>
            <a href="{{ route('premios.create') }}" class="btn-primary flex items-center gap-2">
                <i class="fas fa-plus"></i> Nuevo Reconocimiento
            </a>
        </div>
    </div>

    <form method="GET" action="{{ route('premios.index') }}" class="flex gap-4 px-6 py-4 flex-wrap border-b border-wheat bg-cream">
        <div class="flex-1 min-w-48">
            <label class="block text-[11px] font-bold text-earth uppercase tracking-wider mb-2">Buscar Documento</label>
            <input type="text" name="search" value="{{ request('search') }}" placeholder="Buscar por documento..." class="w-full px-4 py-2.5 border-2 border-wheat rounded-xl text-sm font-semibold text-charcoal bg-white focus:outline-none focus:border-leaf transition-all">
        </div>
        <div class="min-w-40">
            <label class="block text-[11px] font-bold text-earth uppercase tracking-wider mb-2">Club de Madres</label>
            <select name="association_id" class="w-full px-4 py-2.5 border-2 border-wheat rounded-xl text-sm font-semibold text-charcoal bg-white focus:outline-none focus:border-leaf transition-all">
                <option value="">Todos</option>
                @foreach($associations as $association)
                    <option value="{{ $association->id }}" {{ request('association_id') == $association->id ? 'selected' : '' }}>
                        {{ $association->name }}
                    </option>
                @endforeach
            </select>
        </div>
        <div class="flex items-end gap-2">
            <button type="submit" class="btn-primary">
                <i class="fas fa-search mr-1"></i> Buscar
            </button>
            <a href="{{ route('premios.index') }}" class="btn-secondary">
                <i class="fas fa-times mr-1"></i> Limpiar
            </a>
        </div>
    </form>

    <div class="overflow-x-auto">
        <table class="data-table w-full">
            <thead>
                <tr>
                    <th class="px-6 py-4 text-left">ID</th>
                    <th class="px-6 py-4 text-left">Documento</th>
                    <th class="px-6 py-4 text-left">Club de Madres</th>
                    <th class="px-6 py-4 text-left">Fecha Documento</th>
                    <th class="px-6 py-4 text-left">Vigencia</th>
                    <th class="px-6 py-4 text-left">Estado</th>
                    <th class="px-6 py-4 text-left">Acciones</th>
                </tr>
            </thead>
            <tbody>
                @forelse($awards as $award)
                <tr>
                    <td class="px-6 text-earth font-mono text-sm">#{{ $award->id }}</td>
                    <td class="px-6 font-semibold">{{ $award->document ?? 'Sin documento' }}</td>
                    <td class="px-6">
                        @if($award->association)
                            <span class="px-2 py-1 rounded-lg bg-leaf-light text-leaf text-xs font-bold">{{ $award->association->name }}</span>
                        @else
                            -
                        @endif
                    </td>
                    <td class="px-6 text-earth text-sm">
                        {{ $award->date_document ? \Carbon\Carbon::parse($award->date_document)->format('d/m/Y') : '-' }}
                    </td>
                    <td class="px-6 text-sm text-earth">
                        {{ $award->date_start ? \Carbon\Carbon::parse($award->date_start)->format('d/m/Y') : '-' }} - 
                        {{ $award->date_end ? \Carbon\Carbon::parse($award->date_end)->format('d/m/Y') : '-' }}
                    </td>
                    <td class="px-6">
                        @if($award->state)
                            @if($award->state->title == 'Activo')
                                <span class="badge-active px-3 py-1 rounded-full text-xs font-bold">Activo</span>
                            @else
                                <span class="badge-inactive px-3 py-1 rounded-full text-xs font-bold">{{ $award->state->title }}</span>
                            @endif
                        @else
                            <span class="badge-inactive px-3 py-1 rounded-full text-xs font-bold">Sin estado</span>
                        @endif
                    </td>
                    <td class="px-6">
                        <div class="flex gap-2">
                            <a href="{{ route('premios.show', $award) }}" class="btn-action bg-sky-light text-[#0284C7] hover:bg-sky hover:text-white">
                                <i class="fas fa-eye"></i>
                            </a>
                            <a href="{{ route('premios.edit', $award) }}" class="btn-action bg-sun-light text-[#D97706] hover:bg-sun hover:text-white">
                                <i class="fas fa-edit"></i>
                            </a>
                            <form action="{{ route('premios.destroy', $award) }}" method="POST" class="inline">
                                @csrf
                                @method('DELETE')
                                <button type="submit" class="btn-action bg-clay-light text-clay hover:bg-clay hover:text-white" onclick="return confirm('¿Estás seguro de eliminar este reconocimiento?')">
                                    <i class="fas fa-trash"></i>
                                </button>
                            </form>
                        </div>
                    </td>
                </tr>
                @empty
                <tr>
                    <td colspan="7" class="px-6 py-8 text-center text-earth">No hay registros</td>
                </tr>
                @endforelse
            </tbody>
        </table>
    </div>

    <div class="flex items-center justify-between px-6 py-4 border-t-2 border-wheat">
        <span class="text-sm text-earth font-medium">Mostrando {{ $awards->firstItem() ?? 0 }} - {{ $awards->lastItem() ?? 0 }} de {{ $awards->total() }} registros</span>
        {{ $awards->appends(request()->query())->links() }}
    </div>
</div>
@endsection
