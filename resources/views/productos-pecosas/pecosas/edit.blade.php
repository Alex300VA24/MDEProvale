@extends('layouts.main')

@section('title', 'Editar Pecosa - PROVALE')

@section('content')
<div class="bg-white rounded-2xl border-2 border-wheat shadow-sm overflow-hidden">
    <div class="flex items-center justify-between px-6 py-5 border-b-2 border-wheat">
        <h3 class="font-extrabold text-charcoal text-xl flex items-center gap-3">
            <i class="fas fa-edit text-leaf"></i> Editar Pecosa
        </h3>
        <a href="{{ route('productos-pecosas.pecosas.index') }}" class="btn-secondary flex items-center gap-2">
            <i class="fas fa-arrow-left"></i> Volver
        </a>
    </div>

    <form action="{{ route('productos-pecosas.pecosas.update', $pecosa) }}" method="POST" class="p-6">
        @csrf
        @method('PUT')
        <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
            <div>
                <label class="block text-[11px] font-bold text-earth uppercase tracking-wider mb-2">Número de Pecosa</label>
                <input type="text" name="pecosa_number" value="{{ $pecosa->pecosa_number ?? '' }}" class="w-full px-4 py-2.5 border-2 border-wheat rounded-xl text-sm font-semibold text-charcoal bg-white focus:outline-none focus:border-leaf transition-all" required>
            </div>
            <div>
                <label class="block text-[11px] font-bold text-earth uppercase tracking-wider mb-2">Fecha de Entrega</label>
                <input type="date" name="delivery_date" value="{{ $pecosa->delivery_date ? \Illuminate\Support\Str::of($pecosa->delivery_date)->substr(0,10) : '' }}" class="w-full px-4 py-2.5 border-2 border-wheat rounded-xl text-sm font-semibold text-charcoal bg-white focus:outline-none focus:border-leaf transition-all" required>
            </div>
            <div class="md:col-span-2">
                <label class="block text-[11px] font-bold text-earth uppercase tracking-wider mb-2">Observación</label>
                <textarea name="observation" rows="3" class="w-full px-4 py-2.5 border-2 border-wheat rounded-xl text-sm font-semibold text-charcoal bg-white focus:outline-none focus:border-leaf transition-all">{{ $pecosa->observation ?? '' }}</textarea>
            </div>
            <div>
                <label class="block text-[11px] font-bold text-earth uppercase tracking-wider mb-2">Club de Madres</label>
                <select name="association_id" class="w-full px-4 py-2.5 border-2 border-wheat rounded-xl text-sm font-semibold text-charcoal bg-white focus:outline-none focus:border-leaf transition-all" required>
                    <option value="">Seleccione</option>
                    @foreach($associations as $association)
                        <option value="{{ $association->id }}" @if($pecosa->association_id == $association->id) selected @endif>{{ $association->name }}</option>
                    @endforeach
                </select>
            </div>
            <div>
                <label class="block text-[11px] font-bold text-earth uppercase tracking-wider mb-2">Socio Encargado</label>
                <select name="managing_partner_id" class="w-full px-4 py-2.5 border-2 border-wheat rounded-xl text-sm font-semibold text-charcoal bg-white focus:outline-none focus:border-leaf transition-all">
                    <option value="">Seleccione</option>
                    @foreach($partners as $partner)
                        <option value="{{ $partner->id }}" @if($pecosa->managing_partner_id == $partner->id) selected @endif>{{ $partner->name }}</option>
                    @endforeach
                </select>
            </div>
            <div>
                <label class="block text-[11px] font-bold text-earth uppercase tracking-wider mb-2">Estado</label>
                <select name="state_id" class="w-full px-4 py-2.5 border-2 border-wheat rounded-xl text-sm font-semibold text-charcoal bg-white focus:outline-none focus:border-leaf transition-all" required>
                    <option value="">Seleccione</option>
                    @foreach($states as $state)
                        <option value="{{ $state->id }}" @if($pecosa->state_id == $state->id) selected @endif>{{ $state->title }}</option>
                    @endforeach
                </select>
            </div>
        </div>

        <div class="flex gap-3 mt-6">
            <button type="submit" class="btn-primary">
                <i class="fas fa-save mr-2"></i> Actualizar
            </button>
            <a href="{{ route('productos-pecosas.pecosas.index') }}" class="btn-secondary">Cancelar</a>
        </div>
    </form>
</div>
@endsection
