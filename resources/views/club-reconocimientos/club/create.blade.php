@extends('layouts.main')

@section('title', 'Registrar Comité y Reconocimiento - PROVALE')

@section('content')
<div class="bg-white rounded-2xl border-2 border-wheat shadow-sm overflow-hidden">
    <div class="flex items-center justify-between px-6 py-5 border-b-2 border-wheat">
        <h3 class="font-extrabold text-charcoal text-xl flex items-center gap-3">
            <i class="fas fa-file-signature text-leaf"></i> Registrar Nuevo Comité
        </h3>
        <a href="{{ route('club-reconocimientos.index') }}" class="btn-secondary flex items-center gap-2">
            <i class="fas fa-arrow-left"></i> Volver
        </a>
    </div>

    <form action="{{ route('club-reconocimientos.club.store') }}" method="POST" class="p-6">
        @csrf
        
        <!-- Datos de la Resolución (Reconocimiento) -->
        <div class="mb-10 p-6 bg-sun-light/30 rounded-2xl border-2 border-sun/20">
            <h4 class="font-extrabold text-charcoal text-lg mb-6 flex items-center gap-2">
                <div class="w-10 h-10 bg-sun rounded-xl flex items-center justify-center text-white shadow-sm">
                    <i class="fas fa-file-contract"></i>
                </div>
                <span>Datos de la Resolución (Reconocimiento)</span>
            </h4>
            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                <div class="md:col-span-1">
                    <label class="block text-[11px] font-bold text-earth uppercase tracking-wider mb-2">Número de Resolución / Documento</label>
                    <input type="text" name="resolution_document" value="{{ old('resolution_document') }}" class="w-full px-4 py-2.5 border-2 border-wheat rounded-xl text-sm font-semibold text-charcoal bg-white focus:outline-none focus:border-sun transition-all" required placeholder="Ej: R.A. N° 123-2024-MDE">
                </div>
                <div class="md:col-span-1">
                    <label class="block text-[11px] font-bold text-earth uppercase tracking-wider mb-2">Fecha de Emisión</label>
                    <input type="date" name="resolution_date_document" value="{{ old('resolution_date_document') }}" class="w-full px-4 py-2.5 border-2 border-wheat rounded-xl text-sm font-semibold text-charcoal bg-white focus:outline-none focus:border-sun transition-all" required>
                </div>
                <div>
                    <label class="block text-[11px] font-bold text-earth uppercase tracking-wider mb-2">Vigencia: Fecha Inicio</label>
                    <input type="date" name="resolution_date_start" value="{{ old('resolution_date_start') }}" class="w-full px-4 py-2.5 border-2 border-wheat rounded-xl text-sm font-semibold text-charcoal bg-white focus:outline-none focus:border-sun transition-all" required>
                </div>
                <div>
                    <label class="block text-[11px] font-bold text-earth uppercase tracking-wider mb-2">Vigencia: Fecha Fin</label>
                    <input type="date" name="resolution_date_end" value="{{ old('resolution_date_end') }}" class="w-full px-4 py-2.5 border-2 border-wheat rounded-xl text-sm font-semibold text-charcoal bg-white focus:outline-none focus:border-sun transition-all" required>
                </div>
            </div>
        </div>

        <!-- Datos del Club de Madres -->
        <div class="mb-8">
            <h4 class="font-extrabold text-charcoal text-lg mb-6 flex items-center gap-2">
                <div class="w-10 h-10 bg-leaf rounded-xl flex items-center justify-center text-white shadow-sm">
                    <i class="fas fa-users"></i>
                </div>
                <span>Datos del Nuevo Comité (Club de Madres)</span>
            </h4>
            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                <div class="md:col-span-1">
                    <label class="block text-[11px] font-bold text-earth uppercase tracking-wider mb-2">Nombre del Comité</label>
                    <input type="text" name="name" value="{{ old('name') }}" class="w-full px-4 py-2.5 border-2 border-wheat rounded-xl text-sm font-semibold text-charcoal bg-white focus:outline-none focus:border-leaf transition-all" required placeholder="Nombre completo del comité autorizado">
                </div>
                <div class="md:col-span-1">
                    <label class="block text-[11px] font-bold text-earth uppercase tracking-wider mb-2">Código Interno</label>
                    <input type="text" name="code" value="{{ old('code') }}" class="w-full px-4 py-2.5 border-2 border-wheat rounded-xl text-sm font-semibold text-charcoal bg-white focus:outline-none focus:border-leaf transition-all" placeholder="Ej: CM-001">
                </div>
                <div class="md:col-span-2">
                    <label class="block text-[11px] font-bold text-earth uppercase tracking-wider mb-2">Dirección del Comité</label>
                    <input type="text" name="address" value="{{ old('address') }}" class="w-full px-4 py-2.5 border-2 border-wheat rounded-xl text-sm font-semibold text-charcoal bg-white focus:outline-none focus:border-leaf transition-all" required placeholder="Ubicación física según resolución">
                </div>
                <div>
                    <label class="block text-[11px] font-bold text-earth uppercase tracking-wider mb-2">Lugar / Sector</label>
                    <select name="place_sector_id" class="w-full px-4 py-2.5 border-2 border-wheat rounded-xl text-sm font-semibold text-charcoal bg-white focus:outline-none focus:border-leaf transition-all" required>
                        <option value="">Seleccionar lugar...</option>
                        @foreach($placeSectors as $ps)
                            <option value="{{ $ps->id }}" {{ old('place_sector_id') == $ps->id ? 'selected' : '' }}>
                                {{ $ps->place->title }} - {{ $ps->sector->title }}
                            </option>
                        @endforeach
                    </select>
                </div>
                <div>
                    <label class="block text-[11px] font-bold text-earth uppercase tracking-wider mb-2">Tipo de Local</label>
                    <select name="type_premises_id" class="w-full px-4 py-2.5 border-2 border-wheat rounded-xl text-sm font-semibold text-charcoal bg-white focus:outline-none focus:border-leaf transition-all" required>
                        <option value="">Seleccionar tipo de local...</option>
                        @foreach($typePremises as $tp)
                            <option value="{{ $tp->id }}" {{ old('type_premises_id') == $tp->id ? 'selected' : '' }}>
                                {{ $tp->title }}
                            </option>
                        @endforeach
                    </select>
                </div>
                <div class="md:col-span-2">
                    <label class="block text-[11px] font-bold text-earth uppercase tracking-wider mb-2">Observaciones Adicionales</label>
                    <textarea name="observation" rows="2" class="w-full px-4 py-2.5 border-2 border-wheat rounded-xl text-sm font-semibold text-charcoal bg-white focus:outline-none focus:border-leaf transition-all">{{ old('observation') }}</textarea>
                </div>
            </div>
        </div>

        <div class="flex gap-3 mt-10 p-6 bg-gray-50 rounded-2xl border-2 border-wheat/50">
            <button type="submit" class="btn-primary px-8 py-3 text-lg">
                <i class="fas fa-save mr-2"></i> Finalizar Registro
            </button>
            <a href="{{ route('club-reconocimientos.index') }}" class="btn-secondary px-8 py-3 text-lg">Cancelar</a>
        </div>
    </form>
</div>
@endsection
