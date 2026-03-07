@extends('layouts.main')

@section('title', 'Editar Resolución - PROVALE')

@section('content')
<div class="bg-white rounded-2xl border-2 border-wheat shadow-sm overflow-hidden">
    <div class="flex items-center justify-between px-6 py-5 border-b-2 border-wheat">
        <h3 class="font-extrabold text-charcoal text-xl flex items-center gap-3">
            <i class="fas fa-edit text-leaf"></i> Editar Resolución
        </h3>
        <a href="{{ route('club-reconocimientos.reconocimientos.index') }}" class="btn-secondary flex items-center gap-2">
            <i class="fas fa-arrow-left"></i> Volver
        </a>
    </div>

    <form action="{{ route('club-reconocimientos.reconocimientos.update', $resolution) }}" method="POST" class="p-6">
        @csrf
        @method('PUT')
        <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
            <div>
                <label class="block text-[11px] font-bold text-earth uppercase tracking-wider mb-2">Documento</label>
                <input type="text" name="document" value="{{ old('document', $resolution->document) }}" class="w-full px-4 py-2.5 border-2 border-wheat rounded-xl text-sm font-semibold text-charcoal bg-white focus:outline-none focus:border-leaf transition-all" required>
            </div>
            <div>
                <label class="block text-[11px] font-bold text-earth uppercase tracking-wider mb-2">Fecha del Documento</label>
                <input type="date" name="date_document" value="{{ old('date_document', $resolution->date_document ? \Illuminate\Support\Str::of($resolution->date_document)->substr(0,10) : '') }}" class="w-full px-4 py-2.5 border-2 border-wheat rounded-xl text-sm font-semibold text-charcoal bg-white focus:outline-none focus:border-leaf transition-all" required>
            </div>
            <div>
                <label class="block text-[11px] font-bold text-earth uppercase tracking-wider mb-2">Fecha de Inicio</label>
                <input type="date" name="date_start" value="{{ old('date_start', $resolution->date_start ? \Illuminate\Support\Str::of($resolution->date_start)->substr(0,10) : '') }}" class="w-full px-4 py-2.5 border-2 border-wheat rounded-xl text-sm font-semibold text-charcoal bg-white focus:outline-none focus:border-leaf transition-all" required>
            </div>
            <div>
                <label class="block text-[11px] font-bold text-earth uppercase tracking-wider mb-2">Fecha de Fin</label>
                <input type="date" name="date_end" value="{{ old('date_end', $resolution->date_end ? \Illuminate\Support\Str::of($resolution->date_end)->substr(0,10) : '') }}" class="w-full px-4 py-2.5 border-2 border-wheat rounded-xl text-sm font-semibold text-charcoal bg-white focus:outline-none focus:border-leaf transition-all" required>
            </div>
            <div>
                <label class="block text-[11px] font-bold text-earth uppercase tracking-wider mb-2">Comité</label>
                <select name="association_id" class="w-full px-4 py-2.5 border-2 border-wheat rounded-xl text-sm font-semibold text-charcoal bg-white focus:outline-none focus:border-leaf transition-all" required>
                    <option value="">Seleccione</option>
                    @foreach($associations as $association)
                        <option value="{{ $association->id }}" {{ old('association_id', $resolution->associations->first()->id ?? '') == $association->id ? 'selected' : '' }}>{{ $association->name }}</option>
                    @endforeach
                </select>
            </div>
            <div>
                <label class="block text-[11px] font-bold text-earth uppercase tracking-wider mb-2">Estado</label>
                <select name="state_id" class="w-full px-4 py-2.5 border-2 border-wheat rounded-xl text-sm font-semibold text-charcoal bg-white focus:outline-none focus:border-leaf transition-all" required>
                    <option value="">Seleccione</option>
                    @foreach($states as $state)
                        <option value="{{ $state->id }}" {{ old('state_id', $resolution->state_id) == $state->id ? 'selected' : '' }}>{{ $state->title }}</option>
                    @endforeach
                </select>
            </div>
        </div>

        <div class="flex gap-3 mt-6">
            <button type="submit" class="btn-primary">
                <i class="fas fa-save mr-2"></i> Actualizar
            </button>
            <a href="{{ route('club-reconocimientos.reconocimientos.index') }}" class="btn-secondary">Cancelar</a>
        </div>
    </form>
</div>
@endsection
