@extends('layouts.main')

@section('title', 'Ver Reconocimiento - PROVALE')

@section('content')
<div class="bg-white rounded-2xl border-2 border-wheat shadow-sm overflow-hidden">
    <div class="flex items-center justify-between px-6 py-5 border-b-2 border-wheat">
        <h3 class="font-extrabold text-charcoal text-xl flex items-center gap-3">
            <i class="fas fa-eye text-leaf"></i> Ver Reconocimiento
        </h3>
        <a href="{{ route('premios.index') }}" class="btn-secondary flex items-center gap-2">
            <i class="fas fa-arrow-left"></i> Volver
        </a>
    </div>

    <div class="p-6">
        <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
            <div>
                <label class="block text-[11px] font-bold text-earth uppercase tracking-wider mb-2">ID</label>
                <p class="text-charcoal font-semibold">#{{ $award->id }}</p>
            </div>
            <div>
                <label class="block text-[11px] font-bold text-earth uppercase tracking-wider mb-2">Documento</label>
                <p class="text-charcoal font-semibold">{{ $award->document ?? 'Sin documento' }}</p>
            </div>
            <div>
                <label class="block text-[11px] font-bold text-earth uppercase tracking-wider mb-2">Fecha del Documento</label>
                <p class="text-charcoal font-semibold">{{ $award->date_document ? \Carbon\Carbon::parse($award->date_document)->format('d/m/Y') : '-' }}</p>
            </div>
            <div>
                <label class="block text-[11px] font-bold text-earth uppercase tracking-wider mb-2">Club de Madres</label>
                <p class="text-charcoal font-semibold">{{ $award->association->name ?? 'Sin club' }}</p>
            </div>
            <div>
                <label class="block text-[11px] font-bold text-earth uppercase tracking-wider mb-2">Fecha de Inicio</label>
                <p class="text-charcoal font-semibold">{{ $award->date_start ? \Carbon\Carbon::parse($award->date_start)->format('d/m/Y') : '-' }}</p>
            </div>
            <div>
                <label class="block text-[11px] font-bold text-earth uppercase tracking-wider mb-2">Fecha de Fin</label>
                <p class="text-charcoal font-semibold">{{ $award->date_end ? \Carbon\Carbon::parse($award->date_end)->format('d/m/Y') : '-' }}</p>
            </div>
            <div>
                <label class="block text-[11px] font-bold text-earth uppercase tracking-wider mb-2">Estado</label>
                <p class="text-charcoal font-semibold">{{ $award->state->title ?? 'Sin estado' }}</p>
            </div>
        </div>

        <div class="flex gap-3 mt-6">
            <a href="{{ route('premios.edit', $award) }}" class="btn-primary">
                <i class="fas fa-edit mr-2"></i> Editar
            </a>
            <form action="{{ route('premios.destroy', $award) }}" method="POST" class="inline">
                @csrf
                @method('DELETE')
                <button type="submit" class="btn-danger" onclick="return confirm('¿Estás seguro de eliminar este reconocimiento?')">
                    <i class="fas fa-trash mr-2"></i> Eliminar
                </button>
            </form>
        </div>
    </div>
</div>
@endsection
