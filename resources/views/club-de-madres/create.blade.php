@extends('layouts.main')

@section('title', 'Crear Club de Madres - PROVALE')

@section('content')
<div class="bg-white rounded-2xl border-2 border-wheat shadow-sm overflow-hidden">
    <div class="flex items-center justify-between px-6 py-5 border-b-2 border-wheat">
        <h3 class="font-extrabold text-charcoal text-xl flex items-center gap-3">
            <i class="fas fa-plus-circle text-leaf"></i> Nuevo Club de Madres
        </h3>
        <a href="{{ route('club-de-madres.index') }}" class="btn-secondary flex items-center gap-2">
            <i class="fas fa-arrow-left"></i> Volver
        </a>
    </div>

    <form action="{{ route('club-de-madres.store') }}" method="POST" class="p-6">
        @csrf
        <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
            <div class="md:col-span-2">
                <label class="block text-[11px] font-bold text-earth uppercase tracking-wider mb-2">Nombre del Club</label>
                <input type="text" name="name" value="{{ old('name') }}" class="w-full px-4 py-2.5 border-2 border-wheat rounded-xl text-sm font-semibold text-charcoal bg-white focus:outline-none focus:border-leaf transition-all" required>
            </div>
            <div class="md:col-span-2">
                <label class="block text-[11px] font-bold text-earth uppercase tracking-wider mb-2">Dirección</label>
                <input type="text" name="address" value="{{ old('address') }}" class="w-full px-4 py-2.5 border-2 border-wheat rounded-xl text-sm font-semibold text-charcoal bg-white focus:outline-none focus:border-leaf transition-all">
            </div>
            <div>
                <label class="block text-[11px] font-bold text-earth uppercase tracking-wider mb-2">Teléfono</label>
                <input type="text" name="phone" value="{{ old('phone') }}" class="w-full px-4 py-2.5 border-2 border-wheat rounded-xl text-sm font-semibold text-charcoal bg-white focus:outline-none focus:border-leaf transition-all">
            </div>
        </div>

        <div class="flex gap-3 mt-6">
            <button type="submit" class="btn-primary">
                <i class="fas fa-save mr-2"></i> Guardar
            </button>
            <a href="{{ route('club-de-madres.index') }}" class="btn-secondary">Cancelar</a>
        </div>
    </form>
</div>
@endsection
