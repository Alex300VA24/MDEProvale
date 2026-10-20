import { useMemo, useState } from 'react';
import http from '../../http';
import Modal from '../../Components/Modal';
import Combobox from '../../Components/Combobox';
import { useToast } from '../../Components/Toast';
import errorMessage from '../../errorMessage';
import PersonaInlineFields, { submitInlinePersona } from './PersonaInlineFields';
import DocumentosSection, { PendingDocumentSection } from './DocumentosSection';
import { personLabel } from './format';
import { associationLabel } from '../comites/format';

const BASE = '/api/dashboard/socios-beneficiarios';
const TODAY = new Date().toISOString().split('T')[0];
const inputCls = 'w-full px-4 py-2 border-2 border-wheat rounded-xl text-xs sm:text-sm font-semibold text-charcoal bg-white focus:outline-none focus:border-leaf transition-all';
const labelCls = 'block text-[11px] font-bold text-slate-600 uppercase tracking-wider mb-1';
const Optional = () => <span className="text-earth font-normal normal-case tracking-normal">(opcional)</span>;

const emptyPerson = (person = null, isNew = true) => ({
    isNew,
    personId: person?.id ?? null,
    personLabel: person ? personLabel(person) : '',
    personData: {
        dni: person?.dni || '',
        names: person?.names || '',
        father_lastname: person?.father_lastname || '',
        mother_lastname: person?.mother_lastname || '',
        birthdate: person?.birthdate || '',
        gender: person?.gender || '',
        address: person?.address || '',
    },
    reniecPhotoToken: null,
});

function benefitEndDate(birthdate, abbreviation) {
    if (!birthdate || !['NI0', 'NI7'].includes(abbreviation)) return '';
    const [year, month, day] = birthdate.split('-').map(Number);
    const date = new Date(Date.UTC(year + (abbreviation === 'NI0' ? 7 : 14), month - 1, day));
    date.setUTCDate(date.getUTCDate() - 1);
    return date.toISOString().split('T')[0];
}

function ageFrom(birthdate) {
    if (!birthdate) return null;
    const birth = new Date(`${birthdate}T00:00:00`);
    const today = new Date();
    let age = today.getFullYear() - birth.getFullYear();
    if (today.getMonth() < birth.getMonth() || (today.getMonth() === birth.getMonth() && today.getDate() < birth.getDate())) age -= 1;
    return age;
}

function createBeneficiary(options) {
    const type = (options.type_benefits || []).find((item) => ['NI0', 'NI7', 'TBC', 'DIS'].includes(item.abbreviation));
    return {
        key: `${Date.now()}-${Math.random()}`,
        priority: 1,
        personState: emptyPerson(),
        relationshipId: '',
        typeBenefitId: type?.id ?? '',
        dateBegin: TODAY,
        pendingDocument: { documentType: 'expediente_unico', file: null },
    };
}

function BeneficiaryPanel({ item, index, total, options, onChange, onAdd, onRemove, onPrevious, onNext }) {
    const relationships = (options.relationships || [])
        .filter((value) => ['apoderado', 'hijo', 'hijos', 'nieto', 'nietos'].includes(value.title.toLowerCase()))
        .map((value) => ({ id: value.id, label: value.title.replace(/^Hijos$/i, 'Hijo').replace(/^Nietos$/i, 'Nieto') }));
    const benefits = (options.type_benefits || [])
        .filter((value) => ['NI0', 'NI7', 'TBC', 'DIS'].includes(value.abbreviation))
        .map((value) => ({ id: value.id, label: value.title }));
    const selectedBenefit = (options.type_benefits || []).find((value) => String(value.id) === String(item.typeBenefitId));
    const dateEnd = benefitEndDate(item.personState.personData.birthdate, selectedBenefit?.abbreviation);
    const set = (field, value) => onChange({ ...item, [field]: value });

    return (
        <section className="rounded-2xl border-2 border-blue/20 bg-blue-light/30 p-3 sm:p-4 space-y-4">
            <div className="flex flex-col gap-3 border-b border-blue/15 pb-3 sm:flex-row sm:items-center sm:justify-between">
                <div>
                    <h4 className="font-extrabold text-navy"><i className="fas fa-hand-holding-heart text-blue mr-2" />Beneficiario {index + 1} de {total}</h4>
                    <p className="mt-1 text-xs text-earth">Cada beneficiario queda vinculado a la socia titular.</p>
                </div>
                <button type="button" onClick={onAdd} className="btn-secondary text-xs font-bold"><i className="fas fa-plus mr-1.5" />Agregar beneficiario</button>
            </div>

            <fieldset className="rounded-xl border border-blue/20 bg-white p-3">
                <legend className="px-1 text-[11px] font-bold uppercase tracking-wider text-slate-600">Prioridad *</legend>
                <div className="grid grid-cols-2 gap-2">
                    {[1, 2].map((priority) => (
                        <label key={priority} className={`cursor-pointer rounded-xl border-2 px-3 py-2 text-center text-xs font-bold transition ${item.priority === priority ? 'border-leaf bg-leaf-light text-leaf' : 'border-wheat text-earth'}`}>
                            <input type="radio" name={`priority-${item.key}`} value={priority} checked={item.priority === priority} onChange={() => set('priority', priority)} className="sr-only" />
                            {priority === 1 ? 'Primera prioridad' : 'Segunda prioridad'}
                        </label>
                    ))}
                </div>
            </fieldset>

            <PersonaInlineFields value={item.personState} onChange={(value) => set('personState', value)} title="Datos del Beneficiario" />

            <div className="grid grid-cols-1 gap-3 rounded-xl border border-wheat bg-white p-3 sm:grid-cols-2">
                <div>
                    <label className={labelCls}>Parentesco *</label>
                    <Combobox value={item.relationshipId} onChange={(value) => set('relationshipId', value ?? '')} options={relationships} placeholder="Apoderado, hijo o nieto" />
                </div>
                <div>
                    <label className={labelCls}>Tipo de beneficio *</label>
                    <Combobox value={item.typeBenefitId} onChange={(value) => set('typeBenefitId', value ?? '')} options={benefits} placeholder="Seleccionar beneficio" />
                </div>
                <div>
                    <label className={labelCls}>Inicio del beneficio</label>
                    <div className="rounded-xl border-2 border-wheat bg-base px-4 py-2 text-xs font-semibold text-earth">{item.dateBegin}</div>
                </div>
                <div>
                    <label className={labelCls}>Fin calculado</label>
                    <div className="rounded-xl border-2 border-wheat bg-base px-4 py-2 text-xs font-semibold text-earth">{dateEnd || 'Sin fecha de fin automática'}</div>
                </div>
            </div>

            <PendingDocumentSection attachableType="beneficiarie" value={item.pendingDocument} onChange={(value) => set('pendingDocument', value)} inputId={`beneficiary-file-${item.key}`} allowedTypes={['expediente_unico']} showTypeSelect={false} title="Expediente único del beneficiario" pdfOnly required />

            <div className="flex items-center justify-between gap-2 border-t border-blue/15 pt-3">
                <button type="button" onClick={onPrevious} disabled={index === 0} className="btn-secondary text-xs disabled:opacity-40"><i className="fas fa-chevron-left mr-1" />Anterior</button>
                <div className="flex items-center gap-1" aria-label="Paginación de beneficiarios">
                    {Array.from({ length: total }, (_, page) => <span key={page} className={`h-2 rounded-full transition-all ${page === index ? 'w-6 bg-leaf' : 'w-2 bg-wheat'}`} />)}
                </div>
                <div className="flex gap-2">
                    {total > 1 && <button type="button" onClick={onRemove} className="btn-secondary text-xs text-clay"><i className="fas fa-trash mr-1" />Quitar</button>}
                    <button type="button" onClick={onNext} disabled={index === total - 1} className="btn-secondary text-xs disabled:opacity-40">Siguiente<i className="fas fa-chevron-right ml-1" /></button>
                </div>
            </div>
        </section>
    );
}

export default function SocioFormModal({ mode, partner, options, onClose, onSaved }) {
    const toast = useToast();
    const [personState, setPersonState] = useState(() => emptyPerson(partner?.person, mode === 'create'));
    const [associationId, setAssociationId] = useState(partner?.association_id ?? '');
    const [stateId] = useState(partner?.state_id ?? (options.states || []).find((state) => state.abbreviation === 'VIG')?.id ?? '');
    const [maritalStatus, setMaritalStatus] = useState(partner?.marital_status || '');
    const [educationLevel, setEducationLevel] = useState(partner?.education_level || '');
    const [occupation, setOccupation] = useState(partner?.occupation || '');
    const [childrenCount, setChildrenCount] = useState(partner?.children_count ?? '');
    const [condition, setCondition] = useState(partner?.socioeconomic_condition || (partner?.is_pregnant ? 'GESTANTE' : partner?.is_lactating ? 'LACTANTE' : ''));
    const [familyIncome, setFamilyIncome] = useState(partner?.family_income || '');
    const [observations, setObservations] = useState(partner?.observations || '');
    const [familyDocument, setFamilyDocument] = useState({ documentType: 'expediente_unico', file: null });
    const [conadisDocument, setConadisDocument] = useState({ documentType: 'conadis', file: null });
    const [beneficiaries, setBeneficiaries] = useState(() => [createBeneficiary(options)]);
    const [activeBeneficiary, setActiveBeneficiary] = useState(0);
    const [submitting, setSubmitting] = useState(false);

    const associationOptions = (options.associations || []).map((value) => ({
        id: value.id,
        label: associationLabel(value),
    }));
    const currentBeneficiary = beneficiaries[activeBeneficiary];
    const hasConadis = partner?.documents?.some((document) => document.document_type === 'conadis');
    const conditionOptions = [
        ['GESTANTE', 'Gestante'], ['LACTANTE', 'Lactante'], ['ADULTO_MAYOR', 'Adulto Mayor'],
        ['DISCAPACITADO', 'Discapacitado'], ['OTROS', 'Otros'],
    ];
    const partnerAge = useMemo(() => ageFrom(personState.personData.birthdate), [personState.personData.birthdate]);

    const updateBeneficiary = (value) => setBeneficiaries((current) => current.map((item, index) => index === activeBeneficiary ? value : item));
    const addBeneficiary = () => {
        setBeneficiaries((current) => [...current, createBeneficiary(options)]);
        setActiveBeneficiary(beneficiaries.length);
    };
    const removeBeneficiary = () => {
        setBeneficiaries((current) => current.filter((_, index) => index !== activeBeneficiary));
        setActiveBeneficiary((current) => Math.max(0, current - 1));
    };

    const resolvePerson = async (state) => {
        if (state.isNew) return submitInlinePersona(state);
        await http.put(`${BASE}/personas/${state.personId}`, {
            names: state.personData.names,
            father_lastname: state.personData.father_lastname,
            mother_lastname: state.personData.mother_lastname,
            dni: state.personData.dni,
            birthdate: state.personData.birthdate || null,
            gender: state.personData.gender || null,
            address: state.personData.address || null,
        });
        return state.personId;
    };

    const handleSubmit = async (event) => {
        event.preventDefault();
        if (!associationId) return toast.error('Seleccione el club o comité.');
        if (condition === 'ADULTO_MAYOR' && (partnerAge === null || partnerAge <= 65)) return toast.error('Adulto Mayor requiere ser mayor de 65 años.');
        if (condition === 'OTROS' && !observations.trim()) return toast.error('Indique en observaciones si corresponde a TBC, desnutrición u otra condición.');
        if (condition === 'DISCAPACITADO' && !hasConadis && !conadisDocument.file) return toast.error('Adjunte el documento CONADIS en PDF.');
        if (mode === 'create' && !familyDocument.file) return toast.error('Adjunte el expediente único de la socia en PDF.');

        if (mode === 'create') {
            const invalidIndex = beneficiaries.findIndex((item) => !item.relationshipId || !item.typeBenefitId || !item.pendingDocument.file);
            if (invalidIndex >= 0) {
                setActiveBeneficiary(invalidIndex);
                return toast.error(`Complete parentesco, beneficio y expediente PDF del beneficiario ${invalidIndex + 1}.`);
            }
        }

        setSubmitting(true);
        try {
            const personId = await resolvePerson(personState);
            const resolvedBeneficiaries = mode === 'create'
                ? await Promise.all(beneficiaries.map(async (item) => ({ ...item, personId: await submitInlinePersona(item.personState) })))
                : [];
            const payload = {
                person_id: personId,
                association_id: associationId,
                state_id: stateId,
                date_begin: partner?.date_begin || TODAY,
                date_end: null,
                observations: condition === 'OTROS' ? observations.trim() : null,
                marital_status: maritalStatus || null,
                education_level: educationLevel || null,
                occupation: occupation || null,
                children_count: childrenCount === '' ? null : Number(childrenCount),
                socioeconomic_condition: condition || null,
                is_pregnant: condition === 'GESTANTE',
                is_lactating: condition === 'LACTANTE',
                family_income: familyIncome === '' ? null : familyIncome,
                ...(mode === 'create' ? {
                    beneficiaries: resolvedBeneficiaries.map((item) => ({
                        person_id: item.personId,
                        relationship_id: item.relationshipId,
                        priority: item.priority,
                        type_benefit_id: item.typeBenefitId,
                        history_state_id: stateId,
                        date_begin: TODAY,
                    })),
                } : {}),
            };

            const formData = new FormData();
            Object.entries(payload).forEach(([key, value]) => {
                if (key === 'beneficiaries') return;
                if (value === null || value === undefined) formData.append(key, '');
                else if (typeof value === 'boolean') formData.append(key, value ? '1' : '0');
                else formData.append(key, String(value));
            });
            resolvedBeneficiaries.forEach((item, index) => {
                const values = {
                    person_id: item.personId,
                    relationship_id: item.relationshipId,
                    priority: item.priority,
                    type_benefit_id: item.typeBenefitId,
                    history_state_id: stateId,
                    date_begin: TODAY,
                };
                Object.entries(values).forEach(([key, value]) => formData.append(`beneficiaries[${index}][${key}]`, String(value)));
                formData.append(`beneficiary_documents[${index}]`, item.pendingDocument.file);
            });
            if (familyDocument.file) formData.append('family_document', familyDocument.file);
            if (condition === 'DISCAPACITADO' && conadisDocument.file) formData.append('conadis_document', conadisDocument.file);
            if (mode === 'edit') formData.append('_method', 'PUT');

            const response = await http.post(
                mode === 'edit' ? `${BASE}/partners/${partner.id}` : `${BASE}/partners`,
                formData,
                { headers: { 'Content-Type': 'multipart/form-data' } }
            );
            const savedPartner = response.data?.data;
            toast.success('Ficha familiar guardada correctamente.');
            onSaved(savedPartner);
        } catch (error) {
            toast.error(errorMessage(error, 'No se pudo guardar la ficha familiar.'));
        } finally {
            setSubmitting(false);
        }
    };

    return (
        <Modal open onClose={onClose} title={mode === 'edit' ? 'Editar Ficha del Socio' : 'Registrar Socio y Beneficiarios'} icon={mode === 'edit' ? 'fa-edit' : 'fa-user-plus'} iconClass={mode === 'edit' ? 'text-sun' : 'text-leaf'} maxWidth={mode === 'edit' ? 'sm:max-w-4xl' : 'sm:max-w-7xl'}>
            <form onSubmit={handleSubmit} className="max-h-[85vh] overflow-y-auto p-4 sm:p-6">
                <div className={mode === 'create' ? 'grid grid-cols-1 items-start gap-5 xl:grid-cols-2' : ''}>
                    <div className="space-y-4">
                        <section className="rounded-xl border border-wheat bg-white p-3 sm:p-4">
                            <span className="mb-3 block border-b border-wheat/60 pb-2 text-xs font-bold uppercase tracking-wider text-charcoal"><i className="fas fa-building text-leaf mr-1.5" />Datos del Comité</span>
                            <label className={labelCls}>Club / Comité *</label>
                            <Combobox value={associationId} onChange={setAssociationId} options={associationOptions} placeholder="Seleccionar club o comité" />
                        </section>

                        <PersonaInlineFields value={personState} onChange={setPersonState} title="Datos de la Socia" />

                        <section className="rounded-xl border border-wheat bg-white p-3 sm:p-4 space-y-3">
                            <span className="block border-b border-wheat/60 pb-2 text-xs font-bold uppercase tracking-wider text-charcoal"><i className="fas fa-file-invoice text-leaf mr-1.5" />Datos Socioeconómicos y Familiares</span>
                            <div className="grid grid-cols-1 gap-3 sm:grid-cols-3">
                                <div><label className={labelCls}>Estado civil <Optional /></label><select value={maritalStatus} onChange={(event) => setMaritalStatus(event.target.value)} className={inputCls}><option value="">Seleccionar...</option><option value="SOLTERA">Soltera/o</option><option value="CASADA">Casada/o</option><option value="CONVIVIENTE">Conviviente</option><option value="SEPARADA">Separada/o</option><option value="VIUDA">Viuda/o</option></select></div>
                                <div><label className={labelCls}>Grado de instrucción <Optional /></label><select value={educationLevel} onChange={(event) => setEducationLevel(event.target.value)} className={inputCls}><option value="">Seleccionar...</option><option value="NINGUNO">Ninguno</option><option value="INICIAL">Inicial</option><option value="PRIMARIA">Primaria</option><option value="SECUNDARIA">Secundaria</option><option value="SUPERIOR">Superior</option></select></div>
                                <div><label className={labelCls}>Ocupación de la socia <Optional /></label><input value={occupation} onChange={(event) => setOccupation(event.target.value)} className={inputCls} /></div>
                                <div><label className={labelCls}>N.º de hijos <Optional /></label><input type="number" min="0" value={childrenCount} onChange={(event) => setChildrenCount(event.target.value)} className={inputCls} /></div>
                                <div><label className={labelCls}>Ingreso familiar (S/) <Optional /></label><input type="number" min="0" step="0.01" value={familyIncome} onChange={(event) => setFamilyIncome(event.target.value)} className={inputCls} /></div>
                                <div><label className={labelCls}>Condición <Optional /></label><select value={condition} onChange={(event) => setCondition(event.target.value)} className={inputCls}><option value="">Ninguna</option>{conditionOptions.map(([value, label]) => <option key={value} value={value}>{label}</option>)}</select>{condition === 'ADULTO_MAYOR' && <p className={`mt-1 text-xs font-semibold ${partnerAge !== null && partnerAge > 65 ? 'text-leaf' : 'text-clay'}`}>{partnerAge === null ? 'Ingrese fecha de nacimiento.' : `Edad calculada: ${partnerAge} años.`}</p>}</div>
                            </div>
                            {condition === 'OTROS' && <div><label className={labelCls}>Observaciones / condición específica *</label><textarea rows="3" value={observations} onChange={(event) => setObservations(event.target.value)} placeholder="Ej.: Persona con TBC o desnutrición" className={inputCls} required /></div>}
                        </section>

                        <PendingDocumentSection attachableType="partner" value={familyDocument} onChange={setFamilyDocument} inputId="partner-expediente-unico" allowedTypes={['expediente_unico']} showTypeSelect={false} title="Expediente único de la socia" pdfOnly required={mode === 'create'} />
                        {condition === 'DISCAPACITADO' && <PendingDocumentSection attachableType="partner" value={conadisDocument} onChange={setConadisDocument} inputId="partner-conadis" allowedTypes={['conadis']} showTypeSelect={false} title="Documento CONADIS" pdfOnly required={!hasConadis} />}
                        {mode === 'edit' && partner && <DocumentosSection attachableType="partner" attachableId={partner.id} initialDocuments={partner.documents || []} allowedTypes={['expediente_unico', 'conadis']} title="Expediente de la socia" />}
                    </div>

                    {mode === 'create' && currentBeneficiary && <BeneficiaryPanel item={currentBeneficiary} index={activeBeneficiary} total={beneficiaries.length} options={options} onChange={updateBeneficiary} onAdd={addBeneficiary} onRemove={removeBeneficiary} onPrevious={() => setActiveBeneficiary((value) => Math.max(0, value - 1))} onNext={() => setActiveBeneficiary((value) => Math.min(beneficiaries.length - 1, value + 1))} />}
                </div>

                <div className="mt-5 flex gap-3 border-t border-wheat pt-5">
                    <button type="button" onClick={onClose} className="btn-secondary flex-1">Cancelar</button>
                    <button type="submit" disabled={submitting} className="btn-primary flex-1 disabled:opacity-50"><i className={`fas ${submitting ? 'fa-spinner fa-spin' : 'fa-save'} mr-2`} />{mode === 'edit' ? 'Actualizar Socio' : `Guardar ficha (${beneficiaries.length} beneficiario/s)`}</button>
                </div>
            </form>
        </Modal>
    );
}
