@extends('layouts.main')

@section('title', 'Ver Club de Madres - PROVALE')

@section('content')
<div class="bg-white rounded-2xl border-2 border-wheat shadow-sm overflow-hidden">
    <div class="flex items-center justify-between px-6 py-5 border-b-2 border-wheat">
        <h3 class="font-extrabold text-charcoal text-xl flex items-center gap-3">
            <i class="fas fa-eye text-leaf"></i> Ver Club de Madres
        </h3>
        <a href="{{ route('club-de-madres.index') }}" class="btn-secondary flex items-center gap-2">
            <i class="fas fa-arrow-left"></i> Volver
        </a>
    </div>

    <div class="p-6">
        <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
            <div>
                <label class="block text-[11px] font-bold text-earth uppercase tracking-wider mb-2">ID</label>
                <p class="text-charcoal font-semibold">#{{ $association->id }}</p>
            </div>
            <div>
                <label class="block text-[11px] font-bold text-earth uppercase tracking-wider mb-2">Nombre</label>
                <p class="text-charcoal font-semibold">{{ $association->name ?? 'Sin nombre' }}</p>
            </div>
            <div class="md:col-span-2">
                <label class="block text-[11px] font-bold text-earth uppercase tracking-wider mb-2">Dirección</label>
                <p class="text-charcoal font-semibold">{{ $association->address ?? 'Sin dirección' }}</p>
            </div>
            <div>
                <label class="block text-[11px] font-bold text-earth uppercase tracking-wider mb-2">Teléfono</label>
                <p class="text-charcoal font-semibold">{{ $association->phone ?? 'Sin teléfono' }}</p>
            </div>
        </div>

        <div class="flex gap-3 mt-6">
            <a href="{{ route('club-de-madres.edit', $association) }}" class="btn-primary">
                <i class="fas fa-edit mr-2"></i> Editar
            </a>
            <form action="{{ route('club-de-madres.destroy', $association) }}" method="POST" class="inline">
                @csrf
                @method('DELETE')
                <button type="submit" class="btn-danger" onclick="return confirm('¿Estás seguro de eliminar este club?')">
                    <i class="fas fa-trash mr-2"></i> Eliminar
                </button>
            </form>
        </div>
    </div>
</div>
@endsection
