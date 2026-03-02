@extends('layouts.main')

@section('title', 'Crear Socio - PROVALE')

@section('content')
<div class="bg-white rounded-2xl border-2 border-wheat shadow-sm overflow-hidden">
    <div class="flex items-center justify-between px-6 py-5 border-b-2 border-wheat">
        <h3 class="font-extrabold text-charcoal text-xl flex items-center gap-3">
            <i class="fas fa-plus-circle text-leaf"></i> Nuevo Socio
        </h3>
        <a href="{{ route('socios.index') }}" class="btn-secondary flex items-center gap-2">
            <i class="fas fa-arrow-left"></i> Volver
        </a>
    </div>

    <form action="{{ route('socios.store') }}" method="POST" class="p-6">
        @csrf
        <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
            <div>
                <label class="block text-[11px] font-bold text-earth uppercase tracking-wider mb-2">Persona</label>
                <select name="person_id" class="w-full px-4 py-2.5 border-2 border-wheat rounded-xl text-sm font-semibold text-charcoal bg-white focus:outline-none focus:border-leaf transition-all" required>
                    <option value="">Seleccionar persona...</option>
                    @foreach($people as $person)
                        <option value="{{ $person->id }}">{{ $person->names }} {{ $person->father_lastname }} {{ $person->mother_lastname }} - {{ $person->dni }}</option>
                    @endforeach
                </select>
                @error('person_id')
                    <p class="text-red-500 text-xs mt-1">{{ $message }}</p>
                @enderror
            </div>
            <div>
                <label class="block text-[11px] font-bold text-earth uppercase tracking-wider mb-2">Club de Madres</label>
                <select name="association_id" class="w-full px-4 py-2.5 border-2 border-wheat rounded-xl text-sm font-semibold text-charcoal bg-white focus:outline-none focus:border-leaf transition-all" required>
                    <option value="">Seleccionar club...</option>
                    @foreach($associations as $association)
                        <option value="{{ $association->id }}">{{ $association->name }}</option>
                    @endforeach
                </select>
                @error('association_id')
                    <p class="text-red-500 text-xs mt-1">{{ $message }}</p>
                @enderror
            </div>
            <div>
                <label class="block text-[11px] font-bold text-earth uppercase tracking-wider mb-2">Estado</label>
                <select name="state_id" class="w-full px-4 py-2.5 border-2 border-wheat rounded-xl text-sm font-semibold text-charcoal bg-white focus:outline-none focus:border-leaf transition-all" required>
                    <option value="">Seleccionar estado...</option>
                    @foreach($states as $state)
                        <option value="{{ $state->id }}">{{ $state->title }}</option>
                    @endforeach
                </select>
                @error('state_id')
                    <p class="text-red-500 text-xs mt-1">{{ $message }}</p>
                @enderror
            </div>
            <div>
                <label class="block text-[11px] font-bold text-earth uppercase tracking-wider mb-2">Fecha de Inicio</label>
                <input type="date" name="date_begin" class="w-full px-4 py-2.5 border-2 border-wheat rounded-xl text-sm font-semibold text-charcoal bg-white focus:outline-none focus:border-leaf transition-all" required>
                @error('date_begin')
                    <p class="text-red-500 text-xs mt-1">{{ $message }}</p>
                @enderror
            </div>
            <div>
                <label class="block text-[11px] font-bold text-earth uppercase tracking-wider mb-2">Fecha de Fin</label>
                <input type="date" name="date_end" class="w-full px-4 py-2.5 border-2 border-wheat rounded-xl text-sm font-semibold text-charcoal bg-white focus:outline-none focus:border-leaf transition-all" required>
                @error('date_end')
                    <p class="text-red-500 text-xs mt-1">{{ $message }}</p>
                @enderror
            </div>
            <div class="md:col-span-2">
                <label class="block text-[11px] font-bold text-earth uppercase tracking-wider mb-2">Observaciones</label>
                <textarea name="observations" rows="3" class="w-full px-4 py-2.5 border-2 border-wheat rounded-xl text-sm font-semibold text-charcoal bg-white focus:outline-none focus:border-leaf transition-all" placeholder="Observaciones adicionales..."></textarea>
            </div>
        </div>

        <div class="flex gap-3 mt-6">
            <button type="submit" class="btn-primary">
                <i class="fas fa-save mr-2"></i> Guardar
            </button>
            <a href="{{ route('socios.index') }}" class="btn-secondary">Cancelar</a>
        </div>
    </form>
</div>
@endsection
