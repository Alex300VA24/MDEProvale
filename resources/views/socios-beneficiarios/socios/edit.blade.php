@extends('layouts.main')

@section('title', 'Editar Socio - PROVALE')

@section('content')
<div class="bg-white rounded-2xl border-2 border-wheat shadow-sm overflow-hidden">
    <div class="flex items-center justify-between px-6 py-5 border-b-2 border-wheat">
        <h3 class="font-extrabold text-charcoal text-xl flex items-center gap-3">
            <i class="fas fa-edit text-leaf"></i> Editar Socio
        </h3>
        <a href="{{ route('socios-beneficiarios.socios.index') }}" class="btn-secondary flex items-center gap-2">
            <i class="fas fa-arrow-left"></i> Volver
        </a>
    </div>

    <form action="{{ route('socios-beneficiarios.socios.update', $partner) }}" method="POST" class="p-6">
        @csrf
        @method('PUT')
        <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
            <div>
                <label class="block text-[11px] font-bold text-earth uppercase tracking-wider mb-2">Persona</label>
                <select name="person_id" class="w-full px-4 py-2.5 border-2 border-wheat rounded-xl text-sm font-semibold text-charcoal bg-white focus:outline-none focus:border-leaf transition-all" required>
                    <option value="">Seleccionar persona...</option>
                    @foreach($people as $person)
                        <option value="{{ $person->id }}" {{ $partner->person_id == $person->id ? 'selected' : '' }}>
                            {{ $person->names }} {{ $person->father_lastname }} {{ $person->mother_lastname }} - {{ $person->dni }}
                        </option>
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
                        <option value="{{ $association->id }}" {{ $partner->association_id == $association->id ? 'selected' : '' }}>{{ $association->name }}</option>
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
                        <option value="{{ $state->id }}" {{ $partner->state_id == $state->id ? 'selected' : '' }}>{{ $state->title }}</option>
                    @endforeach
                </select>
                @error('state_id')
                    <p class="text-red-500 text-xs mt-1">{{ $message }}</p>
                @enderror
            </div>
            <div>
                <label class="block text-[11px] font-bold text-earth uppercase tracking-wider mb-2">Fecha de Inicio</label>
                <input type="date" name="date_begin" value="{{ $partner->date_begin }}" class="w-full px-4 py-2.5 border-2 border-wheat rounded-xl text-sm font-semibold text-charcoal bg-white focus:outline-none focus:border-leaf transition-all" required>
                @error('date_begin')
                    <p class="text-red-500 text-xs mt-1">{{ $message }}</p>
                @enderror
            </div>
            <div>
                <label class="block text-[11px] font-bold text-earth uppercase tracking-wider mb-2">Fecha de Fin</label>
                <input type="date" name="date_end" value="{{ $partner->date_end }}" class="w-full px-4 py-2.5 border-2 border-wheat rounded-xl text-sm font-semibold text-charcoal bg-white focus:outline-none focus:border-leaf transition-all" required>
                @error('date_end')
                    <p class="text-red-500 text-xs mt-1">{{ $message }}</p>
                @enderror
            </div>
            <div class="md:col-span-2">
                <label class="block text-[11px] font-bold text-earth uppercase tracking-wider mb-2">Observaciones</label>
                <textarea name="observations" rows="3" class="w-full px-4 py-2.5 border-2 border-wheat rounded-xl text-sm font-semibold text-charcoal bg-white focus:outline-none focus:border-leaf transition-all" placeholder="Observaciones adicionales...">{{ $partner->observations }}</textarea>
            </div>
        </div>

        <div class="mt-8 border-t-2 border-wheat pt-6">
            <div class="flex items-center justify-between mb-4">
                <h4 class="font-extrabold text-charcoal text-lg flex items-center gap-2">
                    <i class="fas fa-users text-leaf"></i> Beneficiarios
                </h4>
                <button type="button" onclick="addBeneficiary()" class="btn-secondary text-sm">
                    <i class="fas fa-plus mr-1"></i> Agregar Beneficiario
                </button>
            </div>
            
            <div id="beneficiaries-container" class="space-y-4">
                @if($partner->beneficiaries && $partner->beneficiaries->count() > 0)
                    @foreach($partner->beneficiaries as $index => $beneficiary)
                        <div class="grid grid-cols-1 md:grid-cols-3 gap-4 p-4 bg-gray-50 rounded-lg border-2 border-wheat beneficiary-row">
                            <div>
                                <label class="block text-[11px] font-bold text-earth uppercase tracking-wider mb-2">Persona</label>
                                <select name="beneficiaries[{{ $index }}][person_id]" class="w-full px-4 py-2.5 border-2 border-wheat rounded-xl text-sm font-semibold text-charcoal bg-white focus:outline-none focus:border-leaf transition-all">
                                    <option value="">Seleccionar persona...</option>
                                    @foreach($people as $person)
                                        <option value="{{ $person->id }}" {{ $beneficiary->person_id == $person->id ? 'selected' : '' }}>
                                            {{ $person->names }} {{ $person->father_lastname }} {{ $person->mother_lastname }} - {{ $person->dni }}
                                        </option>
                                    @endforeach
                                </select>
                            </div>
                            <div>
                                <label class="block text-[11px] font-bold text-earth uppercase tracking-wider mb-2">Relación</label>
                                <select name="beneficiaries[{{ $index }}][relationship_id]" class="w-full px-4 py-2.5 border-2 border-wheat rounded-xl text-sm font-semibold text-charcoal bg-white focus:outline-none focus:border-leaf transition-all">
                                    <option value="">Seleccionar relación...</option>
                                    @foreach($relationships as $relationship)
                                        <option value="{{ $relationship->id }}" {{ $beneficiary->relationship_id == $relationship->id ? 'selected' : '' }}>{{ $relationship->title }}</option>
                                    @endforeach
                                </select>
                            </div>
                            <div class="flex items-end">
                                <button type="button" onclick="removeBeneficiary(this)" class="btn-danger w-full">
                                    <i class="fas fa-trash mr-1"></i> Eliminar
                                </button>
                            </div>
                        </div>
                    @endforeach
                @endif
            </div>
        </div>

        <div class="flex gap-3 mt-6">
            <button type="submit" class="btn-primary">
                <i class="fas fa-save mr-2"></i> Actualizar
            </button>
            <a href="{{ route('socios-beneficiarios.socios.index') }}" class="btn-secondary">Cancelar</a>
        </div>
    </form>
</div>

<?php $beneficiaryCount = $partner->beneficiaries ? $partner->beneficiaries->count() : 0; ?>
<script>
var beneficiaryCount = {{ $beneficiaryCount }};

function addBeneficiary() {
    const container = document.getElementById('beneficiaries-container');
    const div = document.createElement('div');
    div.className = 'grid grid-cols-1 md:grid-cols-3 gap-4 p-4 bg-gray-50 rounded-lg border-2 border-wheat beneficiary-row';
    
    div.innerHTML = `
        <div>
            <label class="block text-[11px] font-bold text-earth uppercase tracking-wider mb-2">Persona</label>
            <select name="beneficiaries[${beneficiaryCount}][person_id]" class="w-full px-4 py-2.5 border-2 border-wheat rounded-xl text-sm font-semibold text-charcoal bg-white focus:outline-none focus:border-leaf transition-all">
                <option value="">Seleccionar persona...</option>
                @foreach($people as $person)
                    <option value="{{ $person->id }}">{{ $person->names }} {{ $person->father_lastname }} {{ $person->mother_lastname }} - {{ $person->dni }}</option>
                @endforeach
            </select>
        </div>
        <div>
            <label class="block text-[11px] font-bold text-earth uppercase tracking-wider mb-2">Relación</label>
            <select name="beneficiaries[${beneficiaryCount}][relationship_id]" class="w-full px-4 py-2.5 border-2 border-wheat rounded-xl text-sm font-semibold text-charcoal bg-white focus:outline-none focus:border-leaf transition-all">
                <option value="">Seleccionar relación...</option>
                @foreach($relationships as $relationship)
                    <option value="{{ $relationship->id }}">{{ $relationship->title }}</option>
                @endforeach
            </select>
        </div>
        <div class="flex items-end">
            <button type="button" onclick="removeBeneficiary(this)" class="btn-danger w-full">
                <i class="fas fa-trash mr-1"></i> Eliminar
            </button>
        </div>
    `;
    
    container.appendChild(div);
    beneficiaryCount++;
}

function removeBeneficiary(button) {
    button.closest('.beneficiary-row').remove();
}
</script>
@endsection
