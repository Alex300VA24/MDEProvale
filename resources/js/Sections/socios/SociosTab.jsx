import { forwardRef, useCallback, useEffect, useImperativeHandle, useState } from 'react';
import http from '../../http';
import { useToast } from '../../Components/Toast';
import Modal from '../../Components/Modal';
import DetailModal, { DetailGroup, Field, FieldGrid } from '../../Components/DetailModal';
import ConfirmDialog from '../../Components/ConfirmDialog';
import Combobox from '../../Components/Combobox';
import Pagination from '../../Components/Pagination';
import PdfLinkButton from '../../Components/PdfLinkButton';
import { useDebounced } from './hooks';
import { formatDate, personFullName, personLabel, stateClass } from './format';
import errorMessage from '../../errorMessage';
import ReniecPhoto from './ReniecPhoto';
import PersonaInlineFields, { submitInlinePersona } from './PersonaInlineFields';
import DocumentosSection, { PendingDocumentSection, uploadDocumentAttachment } from './DocumentosSection';
import SocioRegistrationModal from './SocioFormModal';

const BASE = '/api/dashboard/socios-beneficiarios';

const selectCls =
    'w-full px-4 py-2 border-2 border-wheat rounded-xl text-xs sm:text-sm font-semibold text-charcoal bg-white focus:outline-none focus:border-leaf transition-all';
const labelCls = 'block text-[11px] font-bold text-slate-600 uppercase tracking-wider mb-1';
const inputCls = selectCls;
const Optional = () => <span className="text-earth font-normal normal-case tracking-normal">(opcional)</span>;

function createBeneficiaryDraft(options) {
    return {
        personState: {
            isNew: true,
            personId: null,
            personLabel: '',
            personData: {
                dni: '', names: '', father_lastname: '', mother_lastname: '', birthdate: '',
                gender: '', address: '', phone_number: '', place_sector_id: '',
            },
            reniecPhotoToken: null,
        },
        relationshipId: '',
        typeBenefitId: (options.type_benefits || [])[0]?.id ?? '',
        historyStateId: (options.states || []).find((state) => state.abbreviation === 'VIG')?.id ?? '',
        dateBegin: new Date().toISOString().split('T')[0],
        dateEnd: '',
        weight: '',
        height: '',
        hmg: '',
        isMalnourished: false,
        isDisabled: false,
        pendingDocument: { documentType: 'dni_beneficiario', file: null },
    };
}

function BeneficiaryRegistrationPanel({ beneficiary, onChange, options, relationshipOptions, typeBenefitOptions }) {
    const update = (field, value) => onChange((current) => ({ ...current, [field]: value }));

    return (
        <section className="space-y-4 rounded-xl border-2 border-blue/20 bg-blue-light/30 p-3 sm:p-4" aria-labelledby="beneficiary-registration-title">
            <div className="border-b border-blue/15 pb-2">
                <h4 id="beneficiary-registration-title" className="font-extrabold text-navy flex items-center gap-2">
                    <i className="fas fa-hand-holding-heart text-blue" aria-hidden="true" />Datos del Beneficiario
                </h4>
                <p className="text-xs text-earth mt-1">Este beneficiario quedará vinculado al nuevo socio.</p>
            </div>

            <PersonaInlineFields
                value={beneficiary.personState}
                onChange={(value) => update('personState', value)}
                options={options}
                title="Identificación del Beneficiario"
            />

            <div className="rounded-xl border border-wheat bg-white p-3 sm:p-4 space-y-3">
                <span className="text-xs font-bold text-charcoal uppercase tracking-wider block border-b border-wheat/60 pb-1.5">
                    <i className="fas fa-heart-pulse text-leaf mr-1.5" aria-hidden="true" />Beneficio y parentesco
                </span>
                <div className="grid grid-cols-1 sm:grid-cols-2 gap-3">
                    <div>
                        <label className={labelCls}>Parentesco *</label>
                        <Combobox value={beneficiary.relationshipId} onChange={(value) => update('relationshipId', value ?? '')} options={relationshipOptions} placeholder="Seleccionar parentesco..." />
                    </div>
                    <div>
                        <label className={labelCls}>Tipo de Beneficio <Optional /></label>
                        <Combobox value={beneficiary.typeBenefitId} onChange={(value) => update('typeBenefitId', value ?? '')} options={typeBenefitOptions} placeholder="Seleccionar beneficio..." allowClear />
                    </div>
                    <div>
                        <label className={labelCls}>Fecha Inicio Beneficio *</label>
                        <input type="date" value={beneficiary.dateBegin} onChange={(event) => update('dateBegin', event.target.value)} className={inputCls} required />
                    </div>
                    <div>
                        <label className={labelCls}>Fecha Fin <Optional /></label>
                        <input type="date" min={beneficiary.dateBegin || undefined} value={beneficiary.dateEnd} onChange={(event) => update('dateEnd', event.target.value)} className={inputCls} />
                    </div>
                </div>

                <div className="grid grid-cols-1 sm:grid-cols-3 gap-3 border-t border-wheat/60 pt-3">
                    <div><label className={labelCls}>Peso (kg) <Optional /></label><input type="number" min="0" step="0.01" value={beneficiary.weight} onChange={(event) => update('weight', event.target.value)} className={inputCls} /></div>
                    <div><label className={labelCls}>Talla (cm) <Optional /></label><input type="number" min="0" step="0.01" value={beneficiary.height} onChange={(event) => update('height', event.target.value)} className={inputCls} /></div>
                    <div><label className={labelCls}>HMG <Optional /></label><input type="number" min="0" step="0.01" value={beneficiary.hmg} onChange={(event) => update('hmg', event.target.value)} className={inputCls} /></div>
                </div>

                <div className="flex flex-wrap gap-5 border-t border-wheat/60 pt-3">
                    <label className="flex items-center gap-2 cursor-pointer text-xs font-bold text-charcoal">
                        <input type="checkbox" checked={beneficiary.isMalnourished} onChange={(event) => update('isMalnourished', event.target.checked)} className="rounded border-wheat text-leaf focus:ring-leaf h-4 w-4" />
                        Desnutrido 7-13 años <Optional />
                    </label>
                    <label className="flex items-center gap-2 cursor-pointer text-xs font-bold text-charcoal">
                        <input type="checkbox" checked={beneficiary.isDisabled} onChange={(event) => update('isDisabled', event.target.checked)} className="rounded border-wheat text-leaf focus:ring-leaf h-4 w-4" />
                        Discapacitado <Optional />
                    </label>
                </div>
            </div>

            <div className="rounded-xl border border-wheat bg-white p-3 sm:p-4">
                <div className="mb-3">
                    <label className={labelCls}>Documento Presentado <Optional /></label>
                    <select value={beneficiary.pendingDocument.documentType} onChange={(event) => update('pendingDocument', { ...beneficiary.pendingDocument, documentType: event.target.value })} className={inputCls}>
                        <option value="dni_beneficiario">DNI</option>
                        <option value="partida_nacimiento">Partida de Nacimiento</option>
                        <option value="constancia_medica">Constancia Médica</option>
                    </select>
                </div>
                <PendingDocumentSection
                    attachableType="beneficiarie"
                    value={beneficiary.pendingDocument}
                    onChange={(document) => update('pendingDocument', document)}
                    inputId="new-family-beneficiary-document"
                    showTypeSelect={false}
                    title="Archivo del Beneficiario"
                />
            </div>
        </section>
    );
}

function SocioFormModal({ mode, partner, options, onClose, onSaved }) {
    const toast = useToast();
    const [personState, setPersonState] = useState({
        isNew: mode === 'create',
        personId: mode === 'edit' && partner ? partner.person_id : null,
        personLabel: mode === 'edit' && partner?.person ? personLabel(partner.person) : '',
        personData: {
            dni: partner?.person?.dni || '',
            names: partner?.person?.names || '',
            father_lastname: partner?.person?.father_lastname || '',
            mother_lastname: partner?.person?.mother_lastname || '',
            birthdate: partner?.person?.birthdate || '',
            gender: partner?.person?.gender || 'F',
            address: partner?.person?.address || '',
            phone_number: partner?.person?.phone_number || '',
            place_sector_id: partner?.person?.place_sector_id || '',
        },
        reniecPhotoToken: null,
    });

    const [associationId, setAssociationId] = useState(mode === 'edit' && partner ? partner.association_id : '');
    const [dateBegin, setDateBegin] = useState(partner?.date_begin || new Date().toISOString().split('T')[0]);
    const [dateEnd, setDateEnd] = useState(partner?.date_end || '');
    const [stateId, setStateId] = useState(
        mode === 'edit' && partner ? partner.state_id : ((options.states || []).find((s) => s.abbreviation === 'VIG')?.id ?? '')
    );
    const [observations, setObservations] = useState(partner?.observations || '');

    // Ficha fields
    const [maritalStatus, setMaritalStatus] = useState(partner?.marital_status || '');
    const [educationLevel, setEducationLevel] = useState(partner?.education_level || '');
    const [occupation, setOccupation] = useState(partner?.occupation || '');
    const [childrenCount, setChildrenCount] = useState(partner?.children_count ?? '');
    const [isPregnant, setIsPregnant] = useState(Boolean(partner?.is_pregnant));
    const [isLactating, setIsLactating] = useState(Boolean(partner?.is_lactating));
    const [spouseOccupation, setSpouseOccupation] = useState(partner?.spouse_occupation || '');
    const [spouseEducationLevel, setSpouseEducationLevel] = useState(partner?.spouse_education_level || '');
    const [familyIncome, setFamilyIncome] = useState(partner?.family_income || '');
    const [pendingDocument, setPendingDocument] = useState({ documentType: 'ficha_fisica', file: null });
    const [beneficiary, setBeneficiary] = useState(() => createBeneficiaryDraft(options));
    const [submitting, setSubmitting] = useState(false);

    const associationOptions = (options.associations || []).map((a) => ({ id: a.id, label: a.name }));
    const stateOptions = (options.states || []).map((s) => ({ id: s.id, label: s.title }));
    const relationshipOptions = (options.relationships || []).map((relationship) => ({ id: relationship.id, label: relationship.title }));
    const typeBenefitOptions = (options.type_benefits || []).map((type) => ({ id: type.id, label: type.title }));

    const handleSubmit = async (e) => {
        e.preventDefault();
        if (!associationId || !dateBegin) {
            toast.error('Complete el club y la fecha de inicio.');
            return;
        }
        if (mode === 'create' && !beneficiary.relationshipId) {
            toast.error('Seleccione el parentesco del beneficiario.');
            return;
        }

        setSubmitting(true);
        try {
            const resolvedPersonId = await submitInlinePersona(personState);
            const resolvedBeneficiaryPersonId = mode === 'create'
                ? await submitInlinePersona(beneficiary.personState)
                : null;

            const payload = {
                person_id: resolvedPersonId,
                association_id: associationId,
                state_id: stateId || (options.states || [])[0]?.id,
                date_begin: dateBegin,
                date_end: dateEnd || null,
                observations: observations || null,
                marital_status: maritalStatus || null,
                education_level: educationLevel || null,
                occupation: occupation || null,
                children_count: childrenCount === '' ? null : Number(childrenCount),
                is_pregnant: Boolean(isPregnant),
                is_lactating: Boolean(isLactating),
                spouse_occupation: spouseOccupation || null,
                spouse_education_level: spouseEducationLevel || null,
                family_income: familyIncome === '' ? null : familyIncome,
                ...(mode === 'create' ? {
                    beneficiaries: [{
                        person_id: resolvedBeneficiaryPersonId,
                        relationship_id: beneficiary.relationshipId,
                        type_benefit_id: beneficiary.typeBenefitId || null,
                        history_state_id: beneficiary.historyStateId || null,
                        date_begin: beneficiary.dateBegin || null,
                        date_end: beneficiary.dateEnd || null,
                        weight: beneficiary.weight === '' ? null : beneficiary.weight,
                        height: beneficiary.height === '' ? null : beneficiary.height,
                        hmg: beneficiary.hmg === '' ? null : beneficiary.hmg,
                        is_malnourished: Boolean(beneficiary.isMalnourished),
                        is_disabled: Boolean(beneficiary.isDisabled),
                    }],
                } : {}),
            };

            let savedPartner;
            if (mode === 'edit') {
                const response = await http.put(`${BASE}/partners/${partner.id}`, payload);
                savedPartner = response.data?.data;
                toast.success('Socio actualizado correctamente.');
            } else {
                const response = await http.post(`${BASE}/partners`, payload);
                savedPartner = response.data?.data;

                let attachmentFailures = 0;
                if (pendingDocument.file) {
                    try {
                        await uploadDocumentAttachment('partner', savedPartner?.id, pendingDocument);
                    } catch {
                        attachmentFailures += 1;
                    }
                }

                const savedBeneficiary = savedPartner?.beneficiaries?.[0];
                if (beneficiary.pendingDocument.file && savedBeneficiary?.id) {
                    try {
                        await uploadDocumentAttachment('beneficiarie', savedBeneficiary.id, beneficiary.pendingDocument);
                    } catch {
                        attachmentFailures += 1;
                    }
                }

                if (attachmentFailures > 0) {
                    toast.error(`Socio y beneficiario registrados, pero ${attachmentFailures} archivo(s) no se subieron. Puede reintentarlo desde Editar.`);
                } else {
                    toast.success('Socio y beneficiario registrados exitosamente.');
                }
            }

            onSaved(savedPartner);
        } catch (err) {
            toast.error(errorMessage(err, 'Ocurrió un error al guardar el socio.'));
        } finally {
            setSubmitting(false);
        }
    };

    return (
        <Modal
            open
            onClose={onClose}
            title={mode === 'edit' ? 'Editar Ficha del Socio' : 'Registrar Socio y Beneficiario'}
            icon={mode === 'edit' ? 'fa-edit' : 'fa-user-plus'}
            iconClass={mode === 'edit' ? 'text-sun' : 'text-leaf'}
            maxWidth={mode === 'edit' ? 'sm:max-w-4xl' : 'sm:max-w-7xl'}
        >
            <form onSubmit={handleSubmit} className="p-4 sm:p-6 max-h-[85vh] overflow-y-auto">
                <div className={mode === 'create' ? 'grid grid-cols-1 xl:grid-cols-2 gap-5 items-start' : ''}>
                    <div className="space-y-4">
                {/* Selector / Creador Inline de Persona */}
                <PersonaInlineFields
                    value={personState}
                    onChange={setPersonState}
                    options={options}
                    title="Datos de la Socia / Madre Titular"
                />

                {/* Datos de Afiliación */}
                <div className="rounded-xl border border-wheat bg-white p-3 sm:p-4 space-y-3">
                    <span className="text-xs font-bold text-charcoal uppercase tracking-wider block border-b border-wheat/60 pb-1.5">
                        <i className="fas fa-building text-leaf mr-1.5" /> Datos del Comité y Período
                    </span>
                    <div className="grid grid-cols-1 sm:grid-cols-2 gap-3">
                        <div className="sm:col-span-2">
                            <label className={labelCls}>Club / Comité del Vaso de Leche *</label>
                            <Combobox
                                value={associationId}
                                onChange={setAssociationId}
                                options={associationOptions}
                                placeholder="Seleccionar club..."
                            />
                        </div>
                        <div>
                            <label className={labelCls}>Fecha Inicio *</label>
                            <input
                                type="date"
                                value={dateBegin}
                                onChange={(e) => setDateBegin(e.target.value)}
                                className={inputCls}
                                required
                            />
                        </div>
                        <div>
                            <label className={labelCls}>Fecha Fin <Optional /></label>
                            <input
                                type="date"
                                value={dateEnd}
                                onChange={(e) => setDateEnd(e.target.value)}
                                className={inputCls}
                            />
                        </div>
                        {mode === 'edit' && (
                            <div className="sm:col-span-2">
                                <label className={labelCls}>Estado *</label>
                                <Combobox
                                    value={stateId}
                                    onChange={setStateId}
                                    options={stateOptions}
                                    placeholder="Seleccionar..."
                                />
                            </div>
                        )}
                    </div>
                </div>

                {/* Datos Socioeconómicos de la Ficha */}
                <div className="rounded-xl border border-wheat bg-white p-3 sm:p-4 space-y-3">
                    <span className="text-xs font-bold text-charcoal uppercase tracking-wider block border-b border-wheat/60 pb-1.5">
                        <i className="fas fa-file-invoice text-leaf mr-1.5" /> Datos Socioeconómicos y Familiares (Ficha)
                    </span>
                    <div className="grid grid-cols-1 sm:grid-cols-3 gap-3">
                        <div>
                            <label className={labelCls}>Estado Civil <Optional /></label>
                            <select
                                value={maritalStatus}
                                onChange={(e) => setMaritalStatus(e.target.value)}
                                className={inputCls}
                            >
                                <option value="">Seleccionar...</option>
                                <option value="SOLTERA">Soltera/o</option>
                                <option value="CASADA">Casada/o</option>
                                <option value="CONVIVIENTE">Conviviente</option>
                                <option value="SEPARADA">Separada/o</option>
                                <option value="VIUDA">Viuda/o</option>
                            </select>
                        </div>
                        <div>
                            <label className={labelCls}>Grado de Instrucción <Optional /></label>
                            <select
                                value={educationLevel}
                                onChange={(e) => setEducationLevel(e.target.value)}
                                className={inputCls}
                            >
                                <option value="">Seleccionar...</option>
                                <option value="NINGUNO">Ninguno</option>
                                <option value="INICIAL">Inicial</option>
                                <option value="PRIMARIA">Primaria</option>
                                <option value="SECUNDARIA">Secundaria</option>
                                <option value="SUPERIOR">Superior</option>
                            </select>
                        </div>
                        <div>
                            <label className={labelCls}>Ocupación de la Socia <Optional /></label>
                            <input
                                type="text"
                                value={occupation}
                                onChange={(e) => setOccupation(e.target.value)}
                                placeholder="Ej: Comerciante, Su casa"
                                className={inputCls}
                            />
                        </div>
                    </div>

                    <div className="grid grid-cols-1 sm:grid-cols-3 gap-3">
                        <div>
                            <label className={labelCls}>Nº de Hijos <Optional /></label>
                            <input
                                type="number"
                                min="0"
                                value={childrenCount}
                                onChange={(e) => setChildrenCount(e.target.value)}
                                placeholder="0"
                                className={inputCls}
                            />
                        </div>
                        <div className="sm:col-span-2 flex items-center gap-6 pt-5">
                            <label className="flex items-center gap-2 cursor-pointer text-xs font-bold text-charcoal">
                                <input
                                    type="checkbox"
                                    checked={isPregnant}
                                    onChange={(e) => setIsPregnant(e.target.checked)}
                                    className="rounded border-wheat text-leaf focus:ring-leaf h-4 w-4"
                                />
                                Gestando <Optional />
                            </label>
                            <label className="flex items-center gap-2 cursor-pointer text-xs font-bold text-charcoal">
                                <input
                                    type="checkbox"
                                    checked={isLactating}
                                    onChange={(e) => setIsLactating(e.target.checked)}
                                    className="rounded border-wheat text-leaf focus:ring-leaf h-4 w-4"
                                />
                                Lactando <Optional />
                            </label>
                        </div>
                    </div>

                    <div className="border-t border-wheat/60 pt-3">
                        <span className="text-[11px] font-bold text-slate-500 uppercase tracking-wider block mb-2">
                            Datos del Cónyuge e Ingresos
                        </span>
                        <div className="grid grid-cols-1 sm:grid-cols-3 gap-3">
                            <div>
                                <label className={labelCls}>Ocupación del Cónyuge <Optional /></label>
                                <input
                                    type="text"
                                    value={spouseOccupation}
                                    onChange={(e) => setSpouseOccupation(e.target.value)}
                                    placeholder="Ej: Obrero, Conductor"
                                    className={inputCls}
                                />
                            </div>
                            <div>
                                <label className={labelCls}>Grado Instr. Cónyuge <Optional /></label>
                                <input
                                    type="text"
                                    value={spouseEducationLevel}
                                    onChange={(e) => setSpouseEducationLevel(e.target.value)}
                                    placeholder="Ej: Secundaria"
                                    className={inputCls}
                                />
                            </div>
                            <div>
                                <label className={labelCls}>Ingreso Familiar (S/) <Optional /></label>
                                <input
                                    type="number"
                                    step="0.01"
                                    min="0"
                                    value={familyIncome}
                                    onChange={(e) => setFamilyIncome(e.target.value)}
                                    placeholder="1025.00"
                                    className={inputCls}
                                />
                            </div>
                        </div>
                    </div>

                </div>

                <div>
                    <label className={labelCls}>Observaciones <Optional /></label>
                    <textarea
                        rows="2"
                        value={observations}
                        onChange={(e) => setObservations(e.target.value)}
                        placeholder="Notas u observaciones adicionales..."
                        className={inputCls}
                    />
                </div>

                {mode === 'create' && (
                    <PendingDocumentSection
                        attachableType="partner"
                        value={pendingDocument}
                        onChange={setPendingDocument}
                        inputId="new-partner-document"
                        title="Expediente de la Socia"
                    />
                )}

                {mode === 'edit' && partner && (
                    <div className="rounded-xl border border-wheat bg-white p-3 sm:p-4">
                        <DocumentosSection
                            attachableType="partner"
                            attachableId={partner.id}
                            initialDocuments={partner.documents || []}
                            highlightType="ficha_fisica"
                            title="Expediente de la Socia (Ficha física, DNI, carnet)"
                        />
                    </div>
                )}

                    </div>

                    {mode === 'create' && (
                        <BeneficiaryRegistrationPanel
                            beneficiary={beneficiary}
                            onChange={setBeneficiary}
                            options={options}
                            relationshipOptions={relationshipOptions}
                            typeBenefitOptions={typeBenefitOptions}
                        />
                    )}
                </div>

                <div className="flex gap-3 pt-5 mt-5 border-t border-wheat">
                    <button type="button" onClick={onClose} className="btn-secondary flex-1 text-xs sm:text-sm">
                        Cancelar
                    </button>
                    <button
                        type="submit"
                        disabled={submitting}
                        className="btn-primary flex-1 text-xs sm:text-sm disabled:opacity-50"
                    >
                        <i className={`fas ${submitting ? 'fa-spinner fa-spin' : 'fa-save'} mr-2`} />
                        {mode === 'edit' ? 'Actualizar Socio' : 'Guardar Socio y Beneficiario'}
                    </button>
                </div>
            </form>
        </Modal>
    );
}

function SocioViewModal({ partner, onClose }) {
    if (!partner) return null;
    const [photo, setPhoto] = useState(null);
    const [loadingPhoto, setLoadingPhoto] = useState(false);
    const latestHistory = (b) => (b.histories && b.histories.length ? b.histories[0] : null);
    const stateCls = stateClass(partner.state);

    useEffect(() => {
        if (!partner?.person?.id) return;
        setLoadingPhoto(true);
        http.get(`${BASE}/personas/${partner.person.id}/reniec-photo`, { timeout: 22000 })
            .then((res) => setPhoto(res.data?.data?.photo ?? null))
            .catch(() => setPhoto(null))
            .finally(() => setLoadingPhoto(false));
    }, [partner?.id, partner?.person?.id]);

    return (
        <DetailModal open onClose={onClose} title="Detalle del Socio / Ficha" icon="fa-user" maxWidth="sm:max-w-4xl">
            <DetailGroup>
                <div className="flex flex-col sm:flex-row items-center sm:items-start gap-4 mb-3">
                    <ReniecPhoto persona={{ ...partner.person, photo }} loading={loadingPhoto} />
                    <div className="min-w-0 flex-1 w-full space-y-2">
                        <Field label="Socia Titular" wide>
                            <span className="text-base font-bold text-charcoal">{personFullName(partner.person) || 'Sin nombre'}</span>
                        </Field>
                        <FieldGrid cols={3}>
                            <Field label="DNI" value={partner.person?.dni} mono />
                            <Field label="Estado">
                                <span className={`badge ${stateCls}`}>{partner.state?.title || 'N/A'}</span>
                            </Field>
                            <Field label="Club" value={partner.association?.name} />
                        </FieldGrid>
                        <FieldGrid cols={3}>
                            <Field label="Fecha de inicio" value={formatDate(partner.date_begin)} />
                            <Field label="Fecha de fin" value={formatDate(partner.date_end)} />
                            <Field label="Beneficiarios">
                                <span className="text-base font-bold text-leaf">{partner.beneficiaries_count ?? 0}</span>
                            </Field>
                        </FieldGrid>
                    </div>
                </div>
            </DetailGroup>

            {/* Datos de Ficha */}
            <DetailGroup title="Datos de la Ficha Individual" icon="fa-id-card">
                <FieldGrid cols={3}>
                    <Field label="Estado Civil" value={partner.marital_status} />
                    <Field label="Grado de Instrucción" value={partner.education_level} />
                    <Field label="Ocupación" value={partner.occupation} />
                    <Field label="Nº de Hijos" value={partner.children_count} />
                    <Field label="Condición Actual" value={{ GESTANTE: 'Gestante', LACTANTE: 'Lactante', ADULTO_MAYOR: 'Adulto Mayor', DISCAPACITADO: 'Discapacitado', OTROS: 'Otros' }[partner.socioeconomic_condition] || 'Ninguna'} />
                    <Field label="Ingreso Familiar" value={partner.family_income ? `S/ ${partner.family_income}` : ''} />
                </FieldGrid>
            </DetailGroup>

            {/* Sección Documentos Adjuntos y Ficha Física */}
            <DetailGroup title="Ficha Física y Documentos de la Socia" icon="fa-paperclip">
                <DocumentosSection
                    attachableType="partner"
                    attachableId={partner.id}
                    initialDocuments={partner.documents || []}
                    highlightType="ficha_fisica"
                    title="Expediente de la Socia (Ficha física, DNI, carnet)"
                    canUpload={false}
                    canDelete={false}
                    canDownload={false}
                />
            </DetailGroup>

            {partner.beneficiaries_count > 0 && (
                <DetailGroup title="Beneficiarios Registrados" icon="fa-hand-holding-heart">
                    <div className="space-y-3">
                        {(partner.beneficiaries || []).map((b) => {
                            const h = latestHistory(b);
                            return (
                                <div key={b.id} className="rounded-xl border border-mist bg-base/60 p-3">
                                    <FieldGrid cols={3}>
                                        <Field label="Nombre" value={personFullName(b.person)} />
                                        <Field label="DNI" value={b.person?.dni} mono />
                                        <Field label="Parentesco" value={b.relationship?.title} />
                                    </FieldGrid>
                                    <div className="mt-2 border-t border-mist/80 pt-2">
                                        <FieldGrid cols={4}>
                                            <Field label="Prioridad" value={b.priority === 2 ? 'Segunda' : 'Primera'} />
                                            <Field label="Tipo beneficio" value={h?.type_benefit?.title} />
                                            <Field label="Estado" value={h?.state?.title} />
                                            <Field label="Inicio" value={formatDate(h?.date_begin)} />
                                            <Field label="Fin" value={formatDate(h?.date_end)} />
                                        </FieldGrid>
                                    </div>
                                </div>
                            );
                        })}
                    </div>
                </DetailGroup>
            )}

            {partner.observations && (
                <DetailGroup title="Observaciones" icon="fa-comment-dots">
                    <p className="text-sm text-earth leading-relaxed">{partner.observations}</p>
                </DetailGroup>
            )}
        </DetailModal>
    );
}

const SociosTab = forwardRef(function SociosTab({ options, can, onPartnerSaved }, ref) {
    const toast = useToast();
    const [data, setData] = useState(null);
    const [loading, setLoading] = useState(true);
    const [filters, setFilters] = useState({ search: '', association_id: '', state_id: '' });
    const [page, setPage] = useState(1);
    const [formOpen, setFormOpen] = useState(false);
    const [formMode, setFormMode] = useState('create');
    const [editing, setEditing] = useState(null);
    const [viewing, setViewing] = useState(null);
    const [deleting, setDeleting] = useState(null);

    const debouncedFilters = useDebounced(filters, 400);

    useEffect(() => {
        setPage(1);
    }, [debouncedFilters]);

    const load = useCallback(async () => {
        setLoading(true);
        try {
            const params = { per_page: 10, page };
            if (debouncedFilters.search) params.search = debouncedFilters.search;
            if (debouncedFilters.association_id) params.association_id = debouncedFilters.association_id;
            if (debouncedFilters.state_id) params.state_id = debouncedFilters.state_id;
            const res = await http.get(`${BASE}/partners`, { params });
            setData(res.data);
        } catch {
            toast.error('No se pudo cargar la lista de socios.');
        } finally {
            setLoading(false);
        }
    }, [debouncedFilters, page, toast]);

    useEffect(() => {
        let active = true;
        (async () => {
            await load();
            active = false;
        })();
        return () => {
            active = false;
        };
    }, [load]);

    useImperativeHandle(ref, () => ({
        openCreate: () => {
            setFormMode('create');
            setEditing(null);
            setFormOpen(true);
        },
    }));

    const setFilter = (key, value) => setFilters((prev) => ({ ...prev, [key]: value }));

    const openEdit = (partner) => {
        setEditing(partner);
        setFormMode('edit');
        setFormOpen(true);
    };

    const confirmDelete = async () => {
        if (!deleting) return;
        try {
            await http.delete(`${BASE}/partners/${deleting.id}`);
            toast.success('Socio y beneficiarios eliminados exitosamente.');
            setDeleting(null);
            load();
        } catch (err) {
            toast.error(errorMessage(err, 'No se pudo eliminar el socio.'));
            setDeleting(null);
        }
    };

    const associationOptions = (options.associations || []).map((a) => ({ id: a.id, label: a.name }));
    const stateOptions = (options.states || []).map((s) => ({ id: s.id, label: s.title }));

    return (
        <>
            <div className="bg-gray-50 rounded-xl p-3 sm:p-4 mb-4 sm:mb-6">
                <form
                    onSubmit={(e) => e.preventDefault()}
                    className="flex flex-col lg:flex-row flex-wrap items-end gap-2 sm:gap-3"
                >
                    <div className="w-full lg:flex-1 min-w-[160px]">
                        <label className={labelCls}>Buscar</label>
                        <div className="relative">
                            <i className="fas fa-search absolute left-3.5 top-1/2 -translate-y-1/2 text-earth pointer-events-none" />
                            <input
                                type="text"
                                value={filters.search}
                                onChange={(e) => setFilter('search', e.target.value)}
                                placeholder="Buscar por nombre o DNI"
                                className="w-full pl-10 pr-4 py-2 border-2 border-wheat rounded-xl text-xs sm:text-sm font-semibold text-charcoal bg-white focus:outline-none focus:border-leaf transition-all"
                            />
                        </div>
                    </div>

                    <div className="w-full sm:w-[400px] lg:w-[400px] shrink-0">
                        <label className={labelCls}>Club</label>
                        <Combobox
                            value={filters.association_id}
                            onChange={(v) => setFilter('association_id', v ?? '')}
                            options={associationOptions}
                            placeholder="Comités"
                            allowClear
                        />
                    </div>

                    <div className="w-full sm:w-40 lg:w-40 shrink-0">
                        <label className={labelCls}>Estado</label>
                        <Combobox
                            value={filters.state_id}
                            onChange={(v) => setFilter('state_id', v ?? '')}
                            options={stateOptions}
                            placeholder="Estados"
                            allowClear
                        />
                    </div>

                    <div className="w-full sm:w-auto shrink-0 flex flex-col">
                        <button
                            type="button"
                            onClick={() => {
                                setFilters({ search: '', association_id: '', state_id: '' });
                                setPage(1);
                            }}
                            className="flex items-center gap-1.5 text-xs sm:text-sm font-bold text-leaf border border-leaf rounded-md px-2.5 py-1.5 hover:opacity-80 whitespace-nowrap"
                        >
                            <i className="fa-solid fa-eraser" /> Limpiar
                        </button>
                        <p style={{ visibility: 'hidden', height: 6, margin: 0, padding: 0 }}>_</p>
                    </div>
                </form>
            </div>

            {loading && !data && (
                <div className="flex items-center justify-center py-10 text-earth">
                    <i className="fas fa-spinner fa-spin mr-2" /> Cargando socios...
                </div>
            )}

            {data && (
                <div className="overflow-x-auto -mx-4 sm:mx-0">
                    <table className="data-table w-full text-xs sm:text-sm min-w-[600px]">
                        <thead>
                            <tr>
                                <th className="px-3 sm:px-4 py-3 text-left">Socio</th>
                                <th className="px-3 sm:px-4 py-3 text-left">DNI</th>
                                <th className="px-3 sm:px-4 py-3 text-left">Club</th>
                                <th className="px-3 sm:px-4 py-3 text-center">Beneficiarios</th>
                                <th className="px-3 sm:px-4 py-3 text-center">Acciones</th>
                            </tr>
                        </thead>
                        <tbody>
                            {data.data.length === 0 ? (
                                <tr>
                                    <td colSpan={5}>
                                        <div className="empty-state">
                                            <i className="fas fa-users" />
                                            <p>No hay socios registrados</p>
                                        </div>
                                    </td>
                                </tr>
                            ) : (
                                data.data.map((partner) => (
                                    <tr key={partner.id} className="row-enter">
                                        <td className="px-3 sm:px-4 py-3 font-medium">
                                            {partner.person
                                                ? `${partner.person.names} ${partner.person.father_lastname}`
                                                : 'Sin nombre'}
                                        </td>
                                        <td className="px-3 sm:px-4 py-3">{partner.person?.dni || 'Sin DNI'}</td>
                                        <td className="px-3 sm:px-4 py-3">{partner.association?.name || 'Sin club'}</td>
                                        <td className="px-3 sm:px-4 py-3 text-center">
                                            <span className="font-bold text-leaf">{partner.beneficiaries_count ?? 0}</span>
                                        </td>
                                        <td className="px-3 sm:px-4 py-3 text-center">
                                            <div className="inline-grid grid-cols-[repeat(4,2.25rem)] items-center justify-items-center gap-1 sm:gap-2">
                                                {/* Imprimir ficha */}
                                                <PdfLinkButton
                                                    href={`${window.APP_URL}/socios-beneficiarios/socios/${partner.id}/ficha`}
                                                    loadingTitle="Generando Ficha del Socio"
                                                    icon="fa-print"
                                                    className="btn-action bg-leaf-light text-leaf hover:bg-leaf hover:text-white disabled:opacity-60"
                                                    title="Imprimir ficha A4"
                                                />

                                                {/* Ver */}
                                                <button
                                                    type="button"
                                                    onClick={() => setViewing(partner)}
                                                    className="btn-action bg-sky-light text-[#0284C7] hover:bg-sky hover:text-white"
                                                    title="Ver detalle"
                                                >
                                                    <i className="fas fa-eye" />
                                                </button>

                                                {/* Editar */}
                                                {can.edit && (
                                                    <button
                                                        type="button"
                                                        onClick={() => openEdit(partner)}
                                                        className="btn-action bg-sun-light text-[#D97706] hover:bg-sun hover:text-white"
                                                        title="Editar"
                                                    >
                                                        <i className="fas fa-edit" />
                                                    </button>
                                                )}

                                                {/* Eliminar */}
                                                {can.del && (
                                                    <button
                                                        type="button"
                                                        onClick={() => setDeleting(partner)}
                                                        className="btn-action bg-clay-light text-clay hover:bg-clay hover:text-white"
                                                        title="Eliminar"
                                                    >
                                                        <i className="fas fa-trash" />
                                                    </button>
                                                )}
                                            </div>
                                        </td>
                                    </tr>
                                ))
                            )}
                        </tbody>
                    </table>
                </div>
            )}

            {data && (
                <div className="flex items-center justify-between px-1 sm:px-2 py-3 border-t-2 border-wheat mt-2">
                    <span className="text-xs sm:text-sm text-earth font-medium">
                        Mostrando {data.meta?.from ?? 0} - {data.meta?.to ?? 0} de {data.meta?.total ?? 0} registros
                    </span>
                    <Pagination links={data.meta?.links} meta={data.meta} onPage={setPage} loading={loading} />
                </div>
            )}

            {formOpen && (
                <SocioRegistrationModal
                    key={editing ? editing.id : 'create'}
                    mode={formMode}
                    partner={editing}
                    options={options}
                    onClose={() => setFormOpen(false)}
                    onSaved={(savedPartner) => {
                        setFormOpen(false);
                        load();
                        onPartnerSaved?.(savedPartner);
                    }}
                />
            )}

            <SocioViewModal partner={viewing} onClose={() => setViewing(null)} />

            <ConfirmDialog
                open={!!deleting}
                onCancel={() => setDeleting(null)}
                onConfirm={confirmDelete}
                title="Eliminar Socio"
                message="Se eliminará este socio y todos sus beneficiarios de forma permanente."
                details={deleting ? [
                    { label: 'Socio', value: personFullName(deleting.person) },
                    { label: 'DNI', value: deleting.person?.dni },
                ] : []}
            />
        </>
    );
});

export default SociosTab;
