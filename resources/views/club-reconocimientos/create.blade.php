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

    <div class="p-6">
        @if(session('success'))
            <div class="mb-4 p-4 bg-green-50 border-2 border-green-200 rounded-xl text-green-700">
                <i class="fas fa-check-circle mr-2"></i>{{ session('success') }}
            </div>
        @endif

        @if(session('error'))
            <div class="mb-4 p-4 bg-red-50 border-2 border-red-200 rounded-xl text-red-700">
                <i class="fas fa-exclamation-circle mr-2"></i>{{ session('error') }}
            </div>
        @endif

        <form action="{{ route('club-reconocimientos.store') }}" method="POST" class="p-6">
        @csrf
        
        <!-- Seleccionar Resolución Existente -->
        <div class="mb-10 p-6 bg-sun-light/30 rounded-2xl border-2 border-sun/20">
            <h4 class="font-extrabold text-charcoal text-lg mb-6 flex items-center gap-2">
                <div class="w-10 h-10 bg-sun rounded-xl flex items-center justify-center text-white shadow-sm">
                    <i class="fas fa-file-contract"></i>
                </div>
                <span>Seleccionar Resolución (Reconocimiento)</span>
            </h4>
            
            <div class="mb-4 p-4 bg-blue-50 rounded-xl border border-blue-200">
                <p class="text-sm text-blue-700">
                    <i class="fas fa-info-circle mr-1"></i>
                    Si la resolución no existe, <a href="{{ route('club-reconocimientos.reconocimientos.create') }}" class="font-bold underline">crear resolución primero aquí</a>
                </p>
            </div>

            <div class="grid grid-cols-1 gap-6">
                <div>
                    <label class="block text-[11px] font-bold text-earth uppercase tracking-wider mb-2">Resolución *</label>
                    <select name="resolution_id" id="resolution_select" class="w-full px-4 py-2.5 border-2 border-wheat rounded-xl text-sm font-semibold text-charcoal bg-white focus:outline-none focus:border-sun transition-all" required>
                        <option value="">Seleccionar resolución...</option>
                        @foreach($resolutions as $resolution)
                            <option value="{{ $resolution->id }}" 
                                data-document="{{ $resolution->document }}"
                                data-date-start="{{ \Carbon\Carbon::parse($resolution->date_start)->format('d/m/Y') }}"
                                data-date-end="{{ \Carbon\Carbon::parse($resolution->date_end)->format('d/m/Y') }}"
                                {{ old('resolution_id') == $resolution->id ? 'selected' : '' }}>
                                {{ $resolution->document }} ({{ \Carbon\Carbon::parse($resolution->date_start)->format('d/m/Y') }} - {{ \Carbon\Carbon::parse($resolution->date_end)->format('d/m/Y') }})
                            </option>
                        @endforeach
                    </select>
                    @error('resolution_id')
                        <p class="text-red-500 text-xs mt-1">{{ $message }}</p>
                    @enderror
                </div>
                
                <div id="resolution-details" class="hidden p-4 bg-white rounded-xl border-2 border-sun/30">
                    <h5 class="text-xs font-bold text-earth uppercase mb-3">Detalles de la Resolución Seleccionada:</h5>
                    <div class="grid grid-cols-1 md:grid-cols-3 gap-4 text-sm">
                        <div>
                            <span class="text-xs text-earth font-bold">Documento:</span>
                            <p id="detail-document" class="text-charcoal font-semibold">-</p>
                        </div>
                        <div>
                            <span class="text-xs text-earth font-bold">Vigencia Inicio:</span>
                            <p id="detail-start" class="text-charcoal font-semibold">-</p>
                        </div>
                        <div>
                            <span class="text-xs text-earth font-bold">Vigencia Fin:</span>
                            <p id="detail-end" class="text-charcoal font-semibold">-</p>
                        </div>
                    </div>
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
                <div class="md:col-span-1">
                    <label class="block text-[11px] font-bold text-earth uppercase tracking-wider mb-2">R.S. (Razón Social)</label>
                    <input type="text" name="company_name" value="{{ old('company_name') }}" class="w-full px-4 py-2.5 border-2 border-wheat rounded-xl text-sm font-semibold text-charcoal bg-white focus:outline-none focus:border-leaf transition-all" placeholder="Ej: OSB, CVL, CDM">
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
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    const resolutionSelect = document.getElementById('resolution_select');
    const resolutionDetails = document.getElementById('resolution-details');
    const detailDocument = document.getElementById('detail-document');
    const detailStart = document.getElementById('detail-start');
    const detailEnd = document.getElementById('detail-end');

    resolutionSelect.addEventListener('change', function() {
        const selectedOption = this.options[this.selectedIndex];
        
        if (this.value) {
            const document = selectedOption.getAttribute('data-document');
            const dateStart = selectedOption.getAttribute('data-date-start');
            const dateEnd = selectedOption.getAttribute('data-date-end');
            
            detailDocument.textContent = document;
            detailStart.textContent = dateStart;
            detailEnd.textContent = dateEnd;
            
            resolutionDetails.classList.remove('hidden');
        } else {
            resolutionDetails.classList.add('hidden');
        }
    });
});
</script>
@endsection
