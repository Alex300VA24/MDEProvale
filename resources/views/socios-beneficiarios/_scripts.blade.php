<script>
let beneficiaryCountCreate = 0;
let allPeople = @json($allPeople);
let allRelationships = @json($relationships);
let allTypeBenefits = @json($typeBenefits);
let allStates = @json($states);
let allReasonDisqualifications = @json($reasonDisqualifications);

function buildOpts(items, placeholder, labelKey) {
    let html = `<option value="">${placeholder}</option>`;
    items.forEach(i => { html += `<option value="${i.id}">${i[labelKey]}</option>`; });
    return html;
}

function addBeneficiaryCreate() {
    const container = document.getElementById('beneficiaries-container-create');
    const idx = beneficiaryCountCreate;
    const div = document.createElement('div');
    div.className = 'p-3 bg-gray-50 rounded-lg border border-wheat';
    div.id = 'beneficiary-row-create-' + idx;

    const sc = 'w-full px-3 py-2 border-2 border-wheat rounded-lg text-sm font-semibold bg-white';
    const ic = 'w-full px-3 py-2 border-2 border-wheat rounded-lg text-sm font-semibold bg-white';
    const lc = 'block text-[10px] font-bold text-earth uppercase mb-1';

    let personOpts = '<option value="">Seleccionar persona...</option>';
    allPeople.forEach(p => { personOpts += `<option value="${p.id}">${p.names} ${p.father_lastname} (${p.dni})</option>`; });

    div.innerHTML = `
        <div class="flex items-center justify-between border-b border-wheat pb-2 mb-3">
            <span class="text-xs font-bold text-leaf uppercase">Beneficiario #${idx + 1}</span>
            <button type="button" onclick="removeBeneficiaryCreate(${idx})" class="text-red-500 hover:text-red-700">
                <i class="fas fa-times-circle"></i>
            </button>
        </div>
        <div class="grid grid-cols-1 md:grid-cols-2 gap-3 mb-3">
            <div>
                <label class="${lc}">Persona *</label>
                <select name="beneficiaries[${idx}][person_id]" class="${sc}" required>${personOpts}</select>
            </div>
            <div>
                <label class="${lc}">Parentesco *</label>
                <select name="beneficiaries[${idx}][relationship_id]" class="${sc}" required>${buildOpts(allRelationships, 'Seleccionar...', 'title')}</select>
            </div>
        </div>
        <p class="text-[10px] font-bold text-earth uppercase mb-2 border-t border-wheat pt-2">Datos Clínicos <span class="font-normal normal-case text-gray-400">(opcional)</span></p>
        <div class="grid grid-cols-3 gap-3 mb-3">
            <div>
                <label class="${lc}">Peso (kg)</label>
                <input type="number" step="0.01" min="0" name="beneficiaries[${idx}][weight]" class="${ic}" placeholder="65.50">
            </div>
            <div>
                <label class="${lc}">Talla (cm)</label>
                <input type="number" step="0.01" min="0" name="beneficiaries[${idx}][height]" class="${ic}" placeholder="160.00">
            </div>
            <div>
                <label class="${lc}">HMG (g/dL)</label>
                <input type="number" step="0.01" min="0" name="beneficiaries[${idx}][hmg]" class="${ic}" placeholder="12.50">
            </div>
        </div>
        <div class="grid grid-cols-2 gap-3 mb-3">
            <div>
                <label class="${lc}">F. Inicio Beneficio</label>
                <input type="date" name="beneficiaries[${idx}][date_begin]" class="${ic}">
            </div>
            <div>
                <label class="${lc}">F. Fin Beneficio</label>
                <input type="date" name="beneficiaries[${idx}][date_end]" class="${ic}">
            </div>
        </div>
        <div class="grid grid-cols-1 md:grid-cols-3 gap-3">
            <div>
                <label class="${lc}">Tipo de Beneficio</label>
                <select name="beneficiaries[${idx}][type_benefit_id]" class="${sc}">${buildOpts(allTypeBenefits, 'Seleccionar...', 'title')}</select>
            </div>
            <div>
                <label class="${lc}">Estado</label>
                <select name="beneficiaries[${idx}][history_state_id]" class="${sc}">${buildOpts(allStates, 'Seleccionar...', 'title')}</select>
            </div>
            <div>
                <label class="${lc}">Motivo Descalificación</label>
                <select name="beneficiaries[${idx}][reason_disqualification_id]" class="${sc}">${buildOpts(allReasonDisqualifications, 'Ninguno', 'title')}</select>
            </div>
        </div>
    `;

    container.appendChild(div);
    beneficiaryCountCreate++;
}

function removeBeneficiaryCreate(id) {
    document.getElementById('beneficiary-row-create-' + id).remove();
}

let beneficiaryCountEdit = 0;

function addBeneficiaryEdit(partnerId) {
    const container = document.getElementById('beneficiaries-container-edit-' + partnerId);
    const existingCount = container.querySelectorAll('[id^="beneficiary-row-edit-' + partnerId + '-"]').length;
    const div = document.createElement('div');
    div.className = 'p-3 bg-gray-50 rounded-lg border border-wheat';
    div.id = 'beneficiary-row-edit-' + partnerId + '-' + existingCount;

    const sc = 'w-full px-3 py-2 border-2 border-wheat rounded-lg text-sm font-semibold bg-white';
    const ic = 'w-full px-3 py-2 border-2 border-wheat rounded-lg text-sm font-semibold bg-white';
    const lc = 'block text-[10px] font-bold text-earth uppercase mb-1';

    let personOpts = '<option value="">Seleccionar persona...</option>';
    allPeople.forEach(p => { personOpts += `<option value="${p.id}">${p.names} ${p.father_lastname} (${p.dni})</option>`; });

    div.innerHTML = `
        <div class="flex items-center justify-between border-b border-wheat pb-2 mb-3">
            <span class="text-xs font-bold text-leaf uppercase">Beneficiario #${existingCount + 1}</span>
            <button type="button" onclick="removeBeneficiaryEdit(${partnerId}, ${existingCount})" class="text-red-500 hover:text-red-700">
                <i class="fas fa-times-circle"></i>
            </button>
        </div>
        <div class="grid grid-cols-1 md:grid-cols-2 gap-3 mb-3">
            <div>
                <label class="${lc}">Persona *</label>
                <select name="beneficiaries[${existingCount}][person_id]" class="${sc}" required>${personOpts}</select>
            </div>
            <div>
                <label class="${lc}">Parentesco *</label>
                <select name="beneficiaries[${existingCount}][relationship_id]" class="${sc}" required>${buildOpts(allRelationships, 'Seleccionar...', 'title')}</select>
            </div>
        </div>
        <p class="text-[10px] font-bold text-earth uppercase mb-2 border-t border-wheat pt-2">Datos Clínicos <span class="font-normal normal-case text-gray-400">(opcional)</span></p>
        <div class="grid grid-cols-3 gap-3 mb-3">
            <div>
                <label class="${lc}">Peso (kg)</label>
                <input type="number" step="0.01" min="0" name="beneficiaries[${existingCount}][weight]" class="${ic}" placeholder="65.50">
            </div>
            <div>
                <label class="${lc}">Talla (cm)</label>
                <input type="number" step="0.01" min="0" name="beneficiaries[${existingCount}][height]" class="${ic}" placeholder="160.00">
            </div>
            <div>
                <label class="${lc}">HMG (g/dL)</label>
                <input type="number" step="0.01" min="0" name="beneficiaries[${existingCount}][hmg]" class="${ic}" placeholder="12.50">
            </div>
        </div>
        <div class="grid grid-cols-2 gap-3 mb-3">
            <div>
                <label class="${lc}">F. Inicio Beneficio</label>
                <input type="date" name="beneficiaries[${existingCount}][date_begin]" class="${ic}">
            </div>
            <div>
                <label class="${lc}">F. Fin Beneficio</label>
                <input type="date" name="beneficiaries[${existingCount}][date_end]" class="${ic}">
            </div>
        </div>
        <div class="grid grid-cols-1 md:grid-cols-3 gap-3">
            <div>
                <label class="${lc}">Tipo de Beneficio</label>
                <select name="beneficiaries[${existingCount}][type_benefit_id]" class="${sc}">${buildOpts(allTypeBenefits, 'Seleccionar...', 'title')}</select>
            </div>
            <div>
                <label class="${lc}">Estado</label>
                <select name="beneficiaries[${existingCount}][history_state_id]" class="${sc}">${buildOpts(allStates, 'Seleccionar...', 'title')}</select>
            </div>
            <div>
                <label class="${lc}">Motivo Descalificación</label>
                <select name="beneficiaries[${existingCount}][reason_disqualification_id]" class="${sc}">${buildOpts(allReasonDisqualifications, 'Ninguno', 'title')}</select>
            </div>
        </div>
    `;

    container.appendChild(div);
}

function removeBeneficiaryEdit(partnerId, index) {
    document.getElementById('beneficiary-row-edit-' + partnerId + '-' + index).remove();
}
</script>
