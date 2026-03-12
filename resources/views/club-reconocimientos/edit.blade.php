@extends('layouts.main')

@section('title', 'Editar Club de Madres - PROVALE')

@section('content')
<div class="bg-white rounded-2xl border-2 border-wheat shadow-sm overflow-hidden">
    <div class="flex items-center justify-between px-6 py-5 border-b-2 border-wheat">
        <h3 class="font-extrabold text-charcoal text-xl flex items-center gap-3">
            <i class="fas fa-edit text-leaf"></i> Editar Club de Madres
        </h3>
        <a href="{{ route('club-reconocimientos.index') }}" class="btn-secondary flex items-center gap-2">
            <i class="fas fa-arrow-left"></i> Volver
        </a>
    </div>

    <form action="{{ route('club-reconocimientos.update', $association) }}" method="POST" class="p-6">
        @csrf
        @method('PUT')
        
        <div class="mb-10 p-6 bg-sun-light/30 rounded-2xl border-2 border-sun/20">
            <h4 class="font-extrabold text-charcoal text-lg mb-6 flex items-center gap-2">
                <div class="w-10 h-10 bg-sun rounded-xl flex items-center justify-center text-white shadow-sm">
                    <i class="fas fa-file-contract"></i>
                </div>
                <span>Resolución de Reconocimiento</span>
            </h4>
            @if($association->resolution)
            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                <div class="md:col-span-1">
                    <label class="block text-[11px] font-bold text-earth uppercase tracking-wider mb-2">Número de Resolución / Documento</label>
                    <p class="text-charcoal font-semibold px-4 py-2.5 bg-white border-2 border-wheat rounded-xl">
                        {{ $association->resolution->document }}
                    </p>
                </div>
                <div class="md:col-span-1">
                    <label class="block text-[11px] font-bold text-earth uppercase tracking-wider mb-2">Fecha de Emisión</label>
                    <p class="text-charcoal font-semibold px-4 py-2.5 bg-white border-2 border-wheat rounded-xl">
                        {{ \Carbon\Carbon::parse($association->resolution->date_document)->format('d/m/Y') }}
                    </p>
                </div>
                <div>
                    <label class="block text-[11px] font-bold text-earth uppercase tracking-wider mb-2">Vigencia: Fecha Inicio</label>
                    <p class="text-charcoal font-semibold px-4 py-2.5 bg-white border-2 border-wheat rounded-xl">
                        {{ \Carbon\Carbon::parse($association->resolution->date_start)->format('d/m/Y') }}
                    </p>
                </div>
                <div>
                    <label class="block text-[11px] font-bold text-earth uppercase tracking-wider mb-2">Vigencia: Fecha Fin</label>
                    <p class="text-charcoal font-semibold px-4 py-2.5 bg-white border-2 border-wheat rounded-xl">
                        {{ \Carbon\Carbon::parse($association->resolution->date_end)->format('d/m/Y') }}
                    </p>
                </div>
            </div>
            @else
            <p class="text-earth">No hay resolución asociada a este comité.</p>
            @endif
        </div>

        <div class="mb-8">
            <h4 class="font-extrabold text-charcoal text-lg mb-6 flex items-center gap-2">
                <div class="w-10 h-10 bg-leaf rounded-xl flex items-center justify-center text-white shadow-sm">
                    <i class="fas fa-users"></i>
                </div>
                <span>Datos del Comité (Club de Madres)</span>
            </h4>
            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                <div class="md:col-span-1">
                    <label class="block text-[11px] font-bold text-earth uppercase tracking-wider mb-2">Nombre del Comité</label>
                    <input type="text" name="name" value="{{ old('name', $association->name) }}" class="w-full px-4 py-2.5 border-2 border-wheat rounded-xl text-sm font-semibold text-charcoal bg-white focus:outline-none focus:border-leaf transition-all" required placeholder="Nombre completo del comité autorizado">
                </div>
                <div class="md:col-span-1">
                    <label class="block text-[11px] font-bold text-earth uppercase tracking-wider mb-2">Código Interno</label>
                    <input type="text" name="code" value="{{ old('code', $association->code) }}" class="w-full px-4 py-2.5 border-2 border-wheat rounded-xl text-sm font-semibold text-charcoal bg-white focus:outline-none focus:border-leaf transition-all" placeholder="Ej: CM-001">
                </div>
                <div class="md:col-span-2">
                    <label class="block text-[11px] font-bold text-earth uppercase tracking-wider mb-2">Dirección del Comité</label>
                    <input type="text" name="address" value="{{ old('address', $association->address) }}" class="w-full px-4 py-2.5 border-2 border-wheat rounded-xl text-sm font-semibold text-charcoal bg-white focus:outline-none focus:border-leaf transition-all" required placeholder="Ubicación física según resolución">
                </div>
                <div>
                    <label class="block text-[11px] font-bold text-earth uppercase tracking-wider mb-2">Teléfono</label>
                    <input type="text" name="phone" value="{{ old('phone', $association->phone) }}" class="w-full px-4 py-2.5 border-2 border-wheat rounded-xl text-sm font-semibold text-charcoal bg-white focus:outline-none focus:border-leaf transition-all" placeholder="Teléfono de contacto">
                </div>
                <div>
                    <label class="block text-[11px] font-bold text-earth uppercase tracking-wider mb-2">Estado</label>
                    <p class="px-4 py-2.5 bg-white border-2 border-wheat rounded-xl text-sm font-semibold text-charcoal">
                        {{ $association->state->title ?? 'Sin estado' }}
                    </p>
                </div>
                <div class="md:col-span-2">
                    <label class="block text-[11px] font-bold text-earth uppercase tracking-wider mb-2">Observaciones Adicionales</label>
                    <textarea name="observation" rows="2" class="w-full px-4 py-2.5 border-2 border-wheat rounded-xl text-sm font-semibold text-charcoal bg-white focus:outline-none focus:border-leaf transition-all">{{ old('observation', $association->observation) }}</textarea>
                </div>
            </div>
        </div>

        <div class="flex gap-3 mt-10 p-6 bg-gray-50 rounded-2xl border-2 border-wheat/50">
            <button type="submit" class="btn-primary px-8 py-3 text-lg">
                <i class="fas fa-save mr-2"></i> Actualizar
            </button>
            <a href="{{ route('club-reconocimientos.index') }}" class="btn-secondary px-8 py-3 text-lg">Cancelar</a>
        </div>
    </form>
</div>
@endsection
