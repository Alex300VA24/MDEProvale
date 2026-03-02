@extends('layouts.main')

@section('title', 'Ver Socio - PROVALE')

@section('content')
<div class="bg-white rounded-2xl border-2 border-wheat shadow-sm overflow-hidden">
    <div class="flex items-center justify-between px-6 py-5 border-b-2 border-wheat">
        <h3 class="font-extrabold text-charcoal text-xl flex items-center gap-3">
            <i class="fas fa-eye text-leaf"></i> Ver Socio
        </h3>
        <a href="{{ route('socios.index') }}" class="btn-secondary flex items-center gap-2">
            <i class="fas fa-arrow-left"></i> Volver
        </a>
    </div>

    <div class="p-6">
        <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
            <div>
                <label class="block text-[11px] font-bold text-earth uppercase tracking-wider mb-2">ID</label>
                <p class="text-charcoal font-semibold">#{{ $partner->id }}</p>
            </div>
            <div>
                <label class="block text-[11px] font-bold text-earth uppercase tracking-wider mb-2">Nombre</label>
                <p class="text-charcoal font-semibold">
                    @if($partner->people)
                        {{ $partner->people->names }} {{ $partner->people->father_lastname }} {{ $partner->people->mother_lastname }}
                    @else
                        Sin nombre
                    @endif
                </p>
            </div>
            <div>
                <label class="block text-[11px] font-bold text-earth uppercase tracking-wider mb-2">DNI</label>
                <p class="text-charcoal font-semibold">{{ $partner->people->dni ?? 'Sin DNI' }}</p>
            </div>
            <div>
                <label class="block text-[11px] font-bold text-earth uppercase tracking-wider mb-2">Club de Madres</label>
                <p class="text-charcoal font-semibold">{{ $partner->association->name ?? 'Sin club' }}</p>
            </div>
            <div>
                <label class="block text-[11px] font-bold text-earth uppercase tracking-wider mb-2">Estado</label>
                <p class="text-charcoal font-semibold">{{ $partner->state->title ?? 'Sin estado' }}</p>
            </div>
            <div>
                <label class="block text-[11px] font-bold text-earth uppercase tracking-wider mb-2">Fecha de Inicio</label>
                <p class="text-charcoal font-semibold">{{ \Carbon\Carbon::parse($partner->date_begin)->format('d/m/Y') }}</p>
            </div>
            <div>
                <label class="block text-[11px] font-bold text-earth uppercase tracking-wider mb-2">Fecha de Fin</label>
                <p class="text-charcoal font-semibold">{{ \Carbon\Carbon::parse($partner->date_end)->format('d/m/Y') }}</p>
            </div>
            <div class="md:col-span-2">
                <label class="block text-[11px] font-bold text-earth uppercase tracking-wider mb-2">Observaciones</label>
                <p class="text-charcoal font-semibold">{{ $partner->observations ?? 'Sin observaciones' }}</p>
            </div>
        </div>

        <div class="flex gap-3 mt-6">
            <a href="{{ route('socios.edit', $partner) }}" class="btn-primary">
                <i class="fas fa-edit mr-2"></i> Editar
            </a>
            <form action="{{ route('socios.destroy', $partner) }}" method="POST" class="inline">
                @csrf
                @method('DELETE')
                <button type="submit" class="btn-danger" onclick="return confirm('¿Estás seguro de eliminar este socio?')">
                    <i class="fas fa-trash mr-2"></i> Eliminar
                </button>
            </form>
        </div>
    </div>
</div>
@endsection
