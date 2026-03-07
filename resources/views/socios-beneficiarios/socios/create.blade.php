@extends('layouts.main')

@section('title', 'Crear Socio - PROVALE')

@section('content')
<div class="bg-white rounded-2xl border-2 border-wheat shadow-sm overflow-hidden">
    <div class="flex items-center justify-between px-6 py-5 border-b-2 border-wheat">
        <h3 class="font-extrabold text-charcoal text-xl flex items-center gap-3">
            <i class="fas fa-plus-circle text-leaf"></i> Nuevo Socio
        </h3>
        <a href="{{ route('socios-beneficiarios.index') }}" class="btn-secondary flex items-center gap-2">
            <i class="fas fa-arrow-left"></i> Volver
        </a>
    </div>

    <div class="p-6">
        <div class="mb-4 p-4 bg-blue-50 rounded-xl border border-blue-200">
            <p class="text-sm text-blue-700">
                <i class="fas fa-info-circle mr-1"></i>
                Si la persona no existe, <a href="{{ route('socios-beneficiarios.personas.create') }}" class="font-bold underline">crear persona primero aquí</a>
            </p>
        </div>

        <form action="{{ route('socios-beneficiarios.socios.store') }}" method="POST">
            @csrf
            <h4 class="font-extrabold text-charcoal text-lg mb-4 flex items-center gap-2">
                <i class="fas fa-user-tag text-leaf"></i> Información del Socio
            </h4>
            
            <div class="grid grid-cols-1 md:grid-cols-2 gap-6 mb-8">
                <div>
                    <label class="block text-[11px] font-bold text-earth uppercase tracking-wider mb-2">Seleccionar Persona (Socio)</label>
                    <select name="person_id" id="main_person_id" class="person-select w-full px-4 py-2.5 border-2 border-wheat rounded-xl text-sm font-semibold text-charcoal bg-white focus:outline-none focus:border-leaf transition-all" required>
                        <option value="">Seleccionar persona...</option>
                        @foreach($people as $person)
                            <option value="{{ $person->id }}">{{ $person->names }} {{ $person->father_lastname }} {{ $person->mother_lastname }} - {{ $person->dni }}</option>
                        @endforeach
                    </select>
                </div>
                <div>
                    <label class="block text-[11px] font-bold text-earth uppercase tracking-wider mb-2">Club de Madres</label>
                    <select name="association_id" class="w-full px-4 py-2.5 border-2 border-wheat rounded-xl text-sm font-semibold text-charcoal bg-white focus:outline-none focus:border-leaf transition-all" required>
                        <option value="">Seleccionar club...</option>
                        @foreach($associations as $association)
                            <option value="{{ $association->id }}">{{ $association->name }}</option>
                        @endforeach
                    </select>
                </div>
                <div>
                    <label class="block text-[11px] font-bold text-earth uppercase tracking-wider mb-2">Estado</label>
                    <select name="state_id" class="w-full px-4 py-2.5 border-2 border-wheat rounded-xl text-sm font-semibold text-charcoal bg-white focus:outline-none focus:border-leaf transition-all" required>
                        @foreach($states as $state)
                            <option value="{{ $state->id }}">{{ $state->title }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="grid grid-cols-2 gap-4">
                    <div>
                        <label class="block text-[11px] font-bold text-earth uppercase tracking-wider mb-2">F. Inicio</label>
                        <input type="date" name="date_begin" class="w-full px-4 py-2.5 border-2 border-wheat rounded-xl text-sm font-semibold text-charcoal bg-white focus:outline-none focus:border-leaf transition-all" required>
                    </div>
                    <div>
                        <label class="block text-[11px] font-bold text-earth uppercase tracking-wider mb-2">F. Fin</label>
                        <input type="date" name="date_end" class="w-full px-4 py-2.5 border-2 border-wheat rounded-xl text-sm font-semibold text-charcoal bg-white focus:outline-none focus:border-leaf transition-all" required>
                    </div>
                </div>
                <div class="md:col-span-2">
                    <label class="block text-[11px] font-bold text-earth uppercase tracking-wider mb-2">Observaciones</label>
                    <textarea name="observations" rows="2" class="w-full px-4 py-2.5 border-2 border-wheat rounded-xl text-sm font-semibold text-charcoal bg-white focus:outline-none focus:border-leaf transition-all"></textarea>
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
                </div>
            </div>

            <div class="flex gap-3 mt-10">
                <button type="submit" class="btn-primary">
                    <i class="fas fa-save mr-2"></i> Guardar Todo
                </button>
                <a href="{{ route('socios-beneficiarios.socios.index') }}" class="btn-secondary">Cancelar</a>
            </div>
        </form>
    </div>
</div>

<script>
let beneficiaryCount = 0;
let registeredPeople = @json($people);

function addBeneficiary() {
    const container = document.getElementById('beneficiaries-container');
    const div = document.createElement('div');
    div.className = 'grid grid-cols-1 gap-4 p-4 bg-gray-50 rounded-lg border-2 border-wheat mb-4 animate-fade-in';
    div.id = 'beneficiary-row-' + beneficiaryCount;
    
    let personOptions = '<option value="">Seleccionar persona...</option>';
    registeredPeople.forEach(p => {
        personOptions += `<option value="${p.id}">${p.names} ${p.father_lastname} ${p.mother_lastname} - ${p.dni}</option>`;
    });

    div.innerHTML = `
        <div class="flex items-center justify-between border-b border-wheat pb-2 mb-2">
            <span class="text-xs font-bold text-leaf uppercase">Beneficiario #${beneficiaryCount + 1}</span>
            <button type="button" onclick="removeBeneficiary(${beneficiaryCount})" class="text-red-500 hover:text-red-700">
                <i class="fas fa-times-circle"></i>
            </button>
        </div>
        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
            <div>
                <label class="block text-[11px] font-bold text-earth uppercase tracking-wider mb-2">Persona</label>
                <select name="beneficiaries[${beneficiaryCount}][person_id]" class="person-select w-full px-4 py-2.5 border-2 border-wheat rounded-xl text-sm font-semibold text-charcoal bg-white focus:outline-none focus:border-leaf transition-all" required>
                    ${personOptions}
                </select>
            </div>
            <div>
                <label class="block text-[11px] font-bold text-earth uppercase tracking-wider mb-2">Relación</label>
                <select name="beneficiaries[${beneficiaryCount}][relationship_id]" class="w-full px-4 py-2.5 border-2 border-wheat rounded-xl text-sm font-semibold text-charcoal bg-white focus:outline-none focus:border-leaf transition-all" required>
                    <option value="">Seleccionar relación...</option>
                    @foreach($relationships as $relationship)
                        <option value="{{ $relationship->id }}">{{ $relationship->title }}</option>
                    @endforeach
                </select>
            </div>
        </div>
    `;
    
    container.appendChild(div);
    beneficiaryCount++;
}

function removeBeneficiary(id) {
    document.getElementById('beneficiary-row-' + id).remove();
}
</script>
@endsection
