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
                <a href="{{ route('socios-beneficiarios.index') }}" class="btn-secondary">Cancelar</a>
            </div>
        </form>
    </div>
</div>

<script>
let beneficiaryCount = 0;
let registeredPeople = @json($people);
let relationships = @json($relationships);
let typeBenefits = @json($typeBenefits);
let states = @json($states);
let reasonDisqualifications = @json($reasonDisqualifications);

function buildOptions(items, placeholder, valueKey, labelKey) {
    let html = `<option value="">${placeholder}</option>`;
    items.forEach(i => { html += `<option value="${i[valueKey]}">${i[labelKey]}</option>`; });
    return html;
}

function addBeneficiary() {
    const container = document.getElementById('beneficiaries-container');
    const idx = beneficiaryCount;
    const div = document.createElement('div');
    div.className = 'p-4 bg-gray-50 rounded-xl border-2 border-wheat mb-4';
    div.id = 'beneficiary-row-' + idx;

    let personOptions = buildOptions(registeredPeople, 'Seleccionar persona...', 'id',
        null /* custom */);
    let personOpts = '<option value="">Seleccionar persona...</option>';
    registeredPeople.forEach(p => {
        personOpts += `<option value="${p.id}">${p.names} ${p.father_lastname} ${p.mother_lastname} - ${p.dni}</option>`;
    });
    let relOpts = buildOptions(relationships, 'Seleccionar relación...', 'id', 'title');
    let typeBenOpts = buildOptions(typeBenefits, 'Seleccionar tipo...', 'id', 'title');
    let stateOpts = buildOptions(states, 'Seleccionar estado...', 'id', 'title');
    let reasonOpts = buildOptions(reasonDisqualifications, 'Ninguno', 'id', 'title');

    const selectClass = 'w-full px-4 py-2.5 border-2 border-wheat rounded-xl text-sm font-semibold text-charcoal bg-white focus:outline-none focus:border-leaf transition-all';
    const inputClass = 'w-full px-4 py-2.5 border-2 border-wheat rounded-xl text-sm font-semibold text-charcoal bg-white focus:outline-none focus:border-leaf transition-all';
    const labelClass = 'block text-[11px] font-bold text-earth uppercase tracking-wider mb-2';

    div.innerHTML = `
        <div class="flex items-center justify-between border-b border-wheat pb-2 mb-3">
            <span class="text-xs font-bold text-leaf uppercase">Beneficiario #${idx + 1}</span>
            <button type="button" onclick="removeBeneficiary(${idx})" class="text-red-500 hover:text-red-700">
                <i class="fas fa-times-circle"></i>
            </button>
        </div>

        <p class="text-[11px] font-bold text-earth uppercase tracking-wider mb-3">Datos del Beneficiario</p>
        <div class="grid grid-cols-1 md:grid-cols-2 gap-4 mb-4">
            <div>
                <label class="${labelClass}">Persona</label>
                <select name="beneficiaries[${idx}][person_id]" class="${selectClass}" required>${personOpts}</select>
            </div>
            <div>
                <label class="${labelClass}">Relación</label>
                <select name="beneficiaries[${idx}][relationship_id]" class="${selectClass}" required>${relOpts}</select>
            </div>
        </div>

        <p class="text-[11px] font-bold text-earth uppercase tracking-wider mb-3 mt-2 border-t border-wheat pt-3">Historial / Datos Clínicos <span class="text-gray-400 font-normal normal-case">(opcional)</span></p>
        <div class="grid grid-cols-1 md:grid-cols-3 gap-4 mb-4">
            <div>
                <label class="${labelClass}">Peso (kg)</label>
                <input type="number" step="0.01" min="0" name="beneficiaries[${idx}][weight]" class="${inputClass}" placeholder="Ej: 65.50">
            </div>
            <div>
                <label class="${labelClass}">Talla (cm)</label>
                <input type="number" step="0.01" min="0" name="beneficiaries[${idx}][height]" class="${inputClass}" placeholder="Ej: 160.00">
            </div>
            <div>
                <label class="${labelClass}">HMG (g/dL)</label>
                <input type="number" step="0.01" min="0" name="beneficiaries[${idx}][hmg]" class="${inputClass}" placeholder="Ej: 12.50">
            </div>
        </div>
        <div class="grid grid-cols-1 md:grid-cols-2 gap-4 mb-4">
            <div>
                <label class="${labelClass}">F. Inicio Beneficio</label>
                <input type="date" name="beneficiaries[${idx}][date_begin]" class="${inputClass}">
            </div>
            <div>
                <label class="${labelClass}">F. Fin Beneficio</label>
                <input type="date" name="beneficiaries[${idx}][date_end]" class="${inputClass}">
            </div>
        </div>
        <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
            <div>
                <label class="${labelClass}">Tipo de Beneficio</label>
                <select name="beneficiaries[${idx}][type_benefit_id]" class="${selectClass}">${typeBenOpts}</select>
            </div>
            <div>
                <label class="${labelClass}">Estado</label>
                <select name="beneficiaries[${idx}][history_state_id]" class="${selectClass}">${stateOpts}</select>
            </div>
            <div>
                <label class="${labelClass}">Motivo Descalificación</label>
                <select name="beneficiaries[${idx}][reason_disqualification_id]" class="${selectClass}">${reasonOpts}</select>
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
