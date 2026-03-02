@extends('layouts.main')

@section('title', 'Club de Madres - PROVALE')

@section('content')
<div class="bg-white rounded-2xl border-2 border-wheat shadow-sm overflow-hidden">
    <div class="flex items-center justify-between px-6 py-5 border-b-2 border-wheat flex-wrap gap-4">
        <h3 class="font-extrabold text-charcoal text-xl flex items-center gap-3">
            <i class="fas fa-users text-leaf"></i> Gestión de Club de Madres
        </h3>
        <div class="flex gap-3">
            <a href="{{ route('club-de-madres.reportes') }}" class="btn-secondary flex items-center gap-2">
                <i class="fas fa-file-pdf"></i> Reportes
            </a>
            <a href="{{ route('club-de-madres.create') }}" class="btn-primary flex items-center gap-2">
                <i class="fas fa-plus"></i> Nuevo Club
            </a>
        </div>
    </div>

    <form method="GET" action="{{ route('club-de-madres.index') }}" class="flex gap-4 px-6 py-4 flex-wrap border-b border-wheat bg-cream">
        <div class="flex-1 min-w-48">
            <label class="block text-[11px] font-bold text-earth uppercase tracking-wider mb-2">Buscar</label>
            <input type="text" name="search" value="{{ request('search') }}" placeholder="Buscar por nombre..." class="w-full px-4 py-2.5 border-2 border-wheat rounded-xl text-sm font-semibold text-charcoal bg-white focus:outline-none focus:border-leaf transition-all">
        </div>
        <div class="flex items-end gap-2">
            <button type="submit" class="btn-primary">
                <i class="fas fa-search mr-1"></i> Buscar
            </button>
            <a href="{{ route('club-de-madres.index') }}" class="btn-secondary">
                <i class="fas fa-times mr-1"></i> Limpiar
            </a>
        </div>
    </form>

    <div class="overflow-x-auto">
        <table class="data-table w-full">
            <thead>
                <tr>
                    <th class="px-6 py-4 text-left">ID</th>
                    <th class="px-6 py-4 text-left">Nombre</th>
                    <th class="px-6 py-4 text-left">Estado</th>
                    <th class="px-6 py-4 text-left">Acciones</th>
                </tr>
            </thead>
            <tbody>
                @forelse($associations as $association)
                <tr>
                    <td class="px-6 text-earth font-mono text-sm">#{{ $association->id }}</td>
                    <td class="px-6 font-semibold">{{ $association->name ?? 'Sin nombre' }}</td>
                    <td class="px-6"><span class="badge-active px-3 py-1 rounded-full text-xs font-bold">Activo</span></td>
                    <td class="px-6">
                        <div class="flex gap-2">
                            <a href="{{ route('club-de-madres.show', $association) }}" class="btn-action bg-sky-light text-[#0284C7] hover:bg-sky hover:text-white">
                                <i class="fas fa-eye"></i>
                            </a>
                            <a href="{{ route('club-de-madres.edit', $association) }}" class="btn-action bg-sun-light text-[#D97706] hover:bg-sun hover:text-white">
                                <i class="fas fa-edit"></i>
                            </a>
                            <form action="{{ route('club-de-madres.destroy', $association) }}" method="POST" class="inline">
                                @csrf
                                @method('DELETE')
                                <button type="submit" class="btn-action bg-clay-light text-clay hover:bg-clay hover:text-white" onclick="return confirm('¿Estás seguro?')">
                                    <i class="fas fa-trash"></i>
                                </button>
                            </form>
                        </div>
                    </td>
                </tr>
                @empty
                <tr>
                    <td colspan="4" class="px-6 py-8 text-center text-earth">No hay registros</td>
                </tr>
                @endforelse
            </tbody>
        </table>
    </div>

    <div class="flex items-center justify-between px-6 py-4 border-t-2 border-wheat">
        <span class="text-sm text-earth font-medium">Mostrando {{ $associations->firstItem() ?? 0 }} - {{ $associations->lastItem() ?? 0 }} de {{ $associations->total() }} registros</span>
        {{ $associations->appends(request()->query())->links() }}
    </div>
</div>
@endsection
