import { forwardRef, useEffect, useImperativeHandle, useState } from 'react';
import http from '../../http';
import { useToast } from '../../Components/Toast';
import Modal from '../../Components/Modal';
import DetailModal, { DetailGroup, Field, FieldGrid } from '../../Components/DetailModal';
import ConfirmDialog from '../../Components/ConfirmDialog';
import Combobox from '../../Components/Combobox';
import Pagination from '../../Components/Pagination';
import { useDebounced } from './hooks';
import { formatDate, personFullName, personLabel } from './format';
import errorMessage from '../../errorMessage';
import ReniecPhoto from './ReniecPhoto';
import PersonaInlineFields, { submitInlinePersona } from './PersonaInlineFields';
import DocumentosSection, { PendingDocumentSection, uploadDocumentAttachment } from './DocumentosSection';

const BASE = '/api/dashboard/socios-beneficiarios';
const MONTHS = ['Enero', 'Febrero', 'Marzo', 'Abril', 'Mayo', 'Junio', 'Julio', 'Agosto', 'Septiembre', 'Octubre', 'Noviembre', 'Diciembre'];
const YEARS = Array.from({ length: Math.max(1, new Date().getFullYear() - 2018) }, (_, index) => new Date().getFullYear() - index);

const labelCls = 'block text-[11px] font-bold text-slate-600 uppercase tracking-wider mb-1';
const inputCls =
    'w-full px-3.5 py-2 border-2 border-wheat rounded-xl text-xs sm:text-sm font-semibold text-charcoal bg-white focus:outline-none focus:border-leaf transition-all';
const Optional = () => <span className="text-earth font-normal normal-case tracking-normal">(opcional)</span>;

function createEmptyBeneficiaryItem(options = {}, defaultDateBegin = '') {
    return {
        _tempId: Math.random().toString(36).substring(2, 9),
        personState: {
            isNew: true,
            personId: null,
            personLabel: '',
            personData: {
                dni: '',
                names: '',
                father_lastname: '',
                mother_lastname: '',
                birthdate: '',
                gender: '',
                address: '',
                phone_number: '',
                place_sector_id: '',
            },
            reniecPhotoToken: null,
        },
        relationshipId: '',
        pendingDocument: { documentType: 'dni_beneficiario', file: null },
        isMalnourished: false,
        isDisabled: false,
        weight: '',
        height: '',
        hmg: '',
        dateBegin: defaultDateBegin || new Date().toISOString().split('T')[0],
        dateEnd: '',
        typeBenefitId: (options.type_benefits || [])[0]?.id ?? '',
        historyStateId: ((options.states || []).find((s) => s.abbreviation === 'VIG')?.id) ?? '',
        reasonDisqualificationId: '',
    };
}

function BeneficiarioFormModal({ mode, beneficiario, options, onClose, onSaved }) {
    const toast = useToast();
    const history = mode === 'edit' ? beneficiario?.histories?.[0] ?? null : null;
    const [partnerId, setPartnerId] = useState(mode === 'edit' ? beneficiario?.partner_id ?? '' : '');

    const [beneficiariesList, setBeneficiariesList] = useState(() => {
        if (mode === 'edit' && beneficiario) {
            return [{
                _tempId: 'edit-' + beneficiario.id,
                personState: {
                    isNew: false,
                    personId: beneficiario.person_id,
                    personLabel: personLabel(beneficiario.person),
                    personData: {
                        dni: beneficiario.person?.dni || '',
                        names: beneficiario.person?.names || '',
                        father_lastname: beneficiario.person?.father_lastname || '',
                        mother_lastname: beneficiario.person?.mother_lastname || '',
                        birthdate: beneficiario.person?.birthdate || '',
                        gender: beneficiario.person?.gender || '',
                        address: beneficiario.person?.address || '',
                        phone_number: beneficiario.person?.phone_number || '',
                        place_sector_id: beneficiario.person?.place_sector_id || '',
                    },
                    reniecPhotoToken: null,
                },
                relationshipId: beneficiario.relationship_id ?? '',
                pendingDocument: {
                    documentType: beneficiario.person?.dni ? 'dni_beneficiario' : 'partida_nacimiento',
                    file: null,
                },
                isMalnourished: Boolean(history?.is_malnourished),
                isDisabled: Boolean(history?.is_disabled),
                weight: history?.weight ?? '',
                height: history?.height ?? '',
                hmg: history?.hmg ?? '',
                dateBegin: history?.date_begin ?? '',
                dateEnd: history?.date_end ?? '',
                typeBenefitId: history?.type_benefit_id ?? '',
                historyStateId: history?.state_id ?? '',
                reasonDisqualificationId: history?.reason_disqualification_id ?? '',
            }];
        }
        return [createEmptyBeneficiaryItem(options)];
    });

    const [submitting, setSubmitting] = useState(false);

    const partnerOptions = (options.partners ?? []).map((p) => ({ id: p.id, label: p.name }));
    const relationshipOptions = (options.relationships ?? []).map((r) => ({ id: r.id, label: r.title }));
    const typeBenefitOptions = (options.type_benefits ?? []).map((t) => ({ id: t.id, label: t.title }));
    const stateOptions = (options.states ?? []).map((s) => ({ id: s.id, label: s.title }));
    const reasonOptions = (options.reason_disqualifications ?? []).map((r) => ({ id: r.id, label: r.title }));

    const addBeneficiaryCard = () => {
        setBeneficiariesList((prev) => [...prev, createEmptyBeneficiaryItem(options)]);
    };

    const removeBeneficiaryCard = (index) => {
        setBeneficiariesList((prev) => prev.filter((_, i) => i !== index));
    };

    const updateBeneficiaryItem = (index, field, val) => {
        setBeneficiariesList((prev) => {
            const copy = [...prev];
            copy[index] = { ...copy[index], [field]: val };
            return copy;
        });
    };

    const handleSubmit = async (e) => {
        e.preventDefault();
        if (!partnerId) {
            toast.error('Debe seleccionar la socia titular.');
            return;
        }

        if (beneficiariesList.length === 0) {
            toast.error('Agregue al menos un beneficiario.');
            return;
        }

        for (let i = 0; i < beneficiariesList.length; i++) {
            const b = beneficiariesList[i];
            if (!b.relationshipId) {
                toast.error(`Seleccione el parentesco para el beneficiario #${i + 1}.`);
                return;
            }
            if (!b.personState.isNew && !b.personState.personId) {
                toast.error(`Seleccione o cree una persona para el beneficiario #${i + 1}.`);
                return;
            }
        }

        setSubmitting(true);
        try {
            let attachmentFailures = 0;
            for (let i = 0; i < beneficiariesList.length; i++) {
                const b = beneficiariesList[i];
                const resolvedPersonId = await submitInlinePersona(b.personState);

                const benPayload = {
                    person_id: resolvedPersonId,
                    partner_id: partnerId,
                    relationship_id: b.relationshipId,
                    weight: b.weight === '' ? null : b.weight,
                    height: b.height === '' ? null : b.height,
                    hmg: b.hmg === '' ? null : b.hmg,
                    date_begin: b.dateBegin || null,
                    date_end: b.dateEnd || null,
                    type_benefit_id: b.typeBenefitId || null,
                    history_state_id: mode === 'edit' ? (b.historyStateId || null) : null,
                    reason_disqualification_id: b.reasonDisqualificationId || null,
                    is_malnourished: Boolean(b.isMalnourished),
                    is_disabled: Boolean(b.isDisabled),
                };

                if (mode === 'edit') {
                    await http.put(`${BASE}/beneficiarios/${beneficiario.id}`, benPayload);
                } else {
                    const response = await http.post(`${BASE}/beneficiarios`, benPayload);
                    const savedBeneficiary = response.data?.data;

                    if (b.pendingDocument?.file) {
                        try {
                            await uploadDocumentAttachment('beneficiarie', savedBeneficiary?.id, b.pendingDocument);
                        } catch {
                            attachmentFailures += 1;
                        }
                    }
                }
            }

            if (mode === 'edit') {
                toast.success('Beneficiario actualizado correctamente.');
            } else if (attachmentFailures > 0) {
                toast.error(
                    `${beneficiariesList.length} beneficiario(s) registrado(s), pero ${attachmentFailures} archivo(s) no se subieron. Use Editar para reintentarlo.`
                );
            } else {
                toast.success(`${beneficiariesList.length} beneficiario(s) registrado(s) con éxito.`);
            }
            onSaved();
        } catch (err) {
            toast.error(errorMessage(err, 'Ocurrió un error al guardar los beneficiarios.'));
        } finally {
            setSubmitting(false);
        }
    };

    return (
        <Modal
            open
            onClose={onClose}
            title={mode === 'edit' ? 'Editar Beneficiario (Estilo Ficha)' : 'Registrar Beneficiarios (Ficha Familiar)'}
            icon={mode === 'edit' ? 'fa-edit' : 'fa-hand-holding-heart'}
            iconClass={mode === 'edit' ? 'text-sun' : 'text-leaf'}
            maxWidth="sm:max-w-5xl"
        >
            <form onSubmit={handleSubmit} className="p-4 sm:p-6 space-y-5 max-h-[85vh] overflow-y-auto">
                {/* Selección de la Socia Titular */}
                <div className="rounded-2xl border-2 border-wheat bg-leaf-light/20 p-4">
                    <label className={labelCls}>
                        Socia Titular (Madre de Familia) <span className="text-clay">*</span>
                    </label>
                    <Combobox
                        value={partnerId}
                        onChange={(v) => setPartnerId(v ?? '')}
                        options={partnerOptions}
                        placeholder="Buscar y seleccionar socia titular..."
                        allowClear
                    />
                    <p className="mt-1 text-xs text-earth">
                        Todos los beneficiarios agregados en esta ficha quedarán vinculados a esta socia.
                    </p>
                </div>

                {/* Lista dinámica de Beneficiarios */}
                <div className="space-y-4">
                    <div className="flex items-center justify-between">
                        <h4 className="text-xs font-bold text-charcoal uppercase tracking-wider flex items-center gap-2">
                            <i className="fas fa-users text-leaf" /> Beneficiarios a Registrar ({beneficiariesList.length})
                        </h4>
                        {mode === 'create' && (
                            <button
                                type="button"
                                onClick={addBeneficiaryCard}
                                className="btn-secondary text-xs font-bold py-1 px-3 flex items-center gap-1.5"
                            >
                                <i className="fas fa-plus text-leaf" /> Agregar otro beneficiario
                            </button>
                        )}
                    </div>

                    {beneficiariesList.map((item, index) => (
                        <div
                            key={item._tempId}
                            className="rounded-2xl border-2 border-wheat bg-white p-4 sm:p-5 space-y-4 shadow-xs relative"
                        >
                            <div className="flex items-center justify-between border-b border-wheat/80 pb-2">
                                <span className="font-bold text-xs sm:text-sm text-charcoal flex items-center gap-2">
                                    <span className="h-6 w-6 rounded-full bg-leaf text-white flex items-center justify-center text-xs font-black">
                                        {index + 1}
                                    </span>
                                    Datos del Beneficiario {index + 1}
                                </span>
                                {mode === 'create' && beneficiariesList.length > 1 && (
                                    <button
                                        type="button"
                                        onClick={() => removeBeneficiaryCard(index)}
                                        className="text-xs text-clay hover:text-red-700 font-bold flex items-center gap-1"
                                    >
                                        <i className="fas fa-trash" /> Quitar
                                    </button>
                                )}
                            </div>

                            {/* Persona Inline */}
                            <PersonaInlineFields
                                value={item.personState}
                                onChange={(val) => updateBeneficiaryItem(index, 'personState', val)}
                                options={options}
                                title={`Persona del Beneficiario #${index + 1}`}
                            />

                            {/* Parentesco y Documento Presentado */}
                            <div className={`grid grid-cols-1 gap-3 ${mode === 'create' ? 'sm:grid-cols-3' : 'sm:grid-cols-2'}`}>
                                <div>
                                    <label className={labelCls}>
                                        Parentesco con la Socia <span className="text-clay">*</span>
                                    </label>
                                    <Combobox
                                        value={item.relationshipId}
                                        onChange={(v) => updateBeneficiaryItem(index, 'relationshipId', v ?? '')}
                                        options={relationshipOptions}
                                        placeholder="Seleccionar..."
                                        allowClear
                                    />
                                </div>
                                {mode === 'create' && (
                                    <div>
                                        <label className={labelCls}>Documento Presentado <Optional /></label>
                                        <select
                                            value={item.pendingDocument.documentType}
                                            onChange={(event) => updateBeneficiaryItem(index, 'pendingDocument', {
                                                ...item.pendingDocument,
                                                documentType: event.target.value,
                                            })}
                                            className={inputCls}
                                        >
                                            <option value="dni_beneficiario">DNI</option>
                                            <option value="partida_nacimiento">Partida de Nacimiento</option>
                                            <option value="constancia_medica">Constancia Médica</option>
                                        </select>
                                    </div>
                                )}
                                <div className="flex items-center gap-4 pt-5">
                                    <label className="flex items-center gap-1.5 cursor-pointer text-xs font-bold text-charcoal">
                                        <input
                                            type="checkbox"
                                            checked={item.isMalnourished}
                                            onChange={(e) => updateBeneficiaryItem(index, 'isMalnourished', e.target.checked)}
                                            className="rounded border-wheat text-leaf focus:ring-leaf h-4 w-4"
                                        />
                                        Desnutrido 7-13 años <Optional />
                                    </label>
                                    <label className="flex items-center gap-1.5 cursor-pointer text-xs font-bold text-charcoal">
                                        <input
                                            type="checkbox"
                                            checked={item.isDisabled}
                                            onChange={(e) => updateBeneficiaryItem(index, 'isDisabled', e.target.checked)}
                                            className="rounded border-wheat text-leaf focus:ring-leaf h-4 w-4"
                                        />
                                        Discapacitado <Optional />
                                    </label>
                                </div>
                            </div>

                            {/* Medidas Antropométricas y Fechas */}
                            <div className="border-t border-wheat/60 pt-3">
                                <span className="text-[11px] font-bold text-slate-500 uppercase tracking-wider block mb-2">
                                    Medidas Antropométricas y Beneficio Clínico
                                </span>
                                <div className="grid grid-cols-1 sm:grid-cols-6 gap-2.5">
                                    <div className="sm:col-span-2">
                                        <label className={labelCls}>Peso (kg) <Optional /></label>
                                        <input
                                            type="number"
                                            step="0.01"
                                            min="0"
                                            value={item.weight}
                                            onChange={(e) => updateBeneficiaryItem(index, 'weight', e.target.value)}
                                            placeholder="Ej: 14.50"
                                            className={inputCls}
                                        />
                                    </div>
                                    <div className="sm:col-span-2">
                                        <label className={labelCls}>Talla (cm) <Optional /></label>
                                        <input
                                            type="number"
                                            step="0.01"
                                            min="0"
                                            value={item.height}
                                            onChange={(e) => updateBeneficiaryItem(index, 'height', e.target.value)}
                                            placeholder="Ej: 95.00"
                                            className={inputCls}
                                        />
                                    </div>
                                    <div className="sm:col-span-2">
                                        <label className={labelCls}>HMG (Hemoglobina) <Optional /></label>
                                        <input
                                            type="number"
                                            step="0.01"
                                            min="0"
                                            value={item.hmg}
                                            onChange={(e) => updateBeneficiaryItem(index, 'hmg', e.target.value)}
                                            placeholder="Ej: 11.80"
                                            className={inputCls}
                                        />
                                    </div>
                                </div>

                                <div className={`grid grid-cols-1 gap-2.5 mt-2.5 ${mode === 'edit' ? 'sm:grid-cols-4' : 'sm:grid-cols-3'}`}>
                                    <div>
                                        <label className={labelCls}>Fecha Inicio Beneficio <Optional /></label>
                                        <input
                                            type="date"
                                            value={item.dateBegin}
                                            onChange={(e) => updateBeneficiaryItem(index, 'dateBegin', e.target.value)}
                                            className={inputCls}
                                        />
                                    </div>
                                    <div>
                                        <label className={labelCls}>Fecha Fin Beneficio <Optional /></label>
                                        <input
                                            type="date"
                                            value={item.dateEnd}
                                            onChange={(e) => updateBeneficiaryItem(index, 'dateEnd', e.target.value)}
                                            className={inputCls}
                                        />
                                    </div>
                                    <div>
                                        <label className={labelCls}>Tipo de Beneficio <Optional /></label>
                                        <Combobox
                                            value={item.typeBenefitId}
                                            onChange={(v) => updateBeneficiaryItem(index, 'typeBenefitId', v ?? '')}
                                            options={typeBenefitOptions}
                                            placeholder="Seleccionar..."
                                            allowClear
                                        />
                                    </div>
                                    {mode === 'edit' && (
                                        <div>
                                            <label className={labelCls}>Estado <Optional /></label>
                                            <Combobox
                                                value={item.historyStateId}
                                                onChange={(v) => updateBeneficiaryItem(index, 'historyStateId', v ?? '')}
                                                options={stateOptions}
                                                placeholder="Seleccionar..."
                                                allowClear
                                            />
                                        </div>
                                    )}
                                </div>
                            </div>

                            {mode === 'create' && (
                                <PendingDocumentSection
                                    attachableType="beneficiarie"
                                    value={item.pendingDocument}
                                    onChange={(document) => updateBeneficiaryItem(index, 'pendingDocument', document)}
                                    inputId={`new-beneficiary-document-${item._tempId}`}
                                    showTypeSelect={false}
                                    title={`Archivo del Beneficiario #${index + 1}`}
                                />
                            )}

                            {mode === 'edit' && beneficiario && (
                                <div className="border-t border-wheat/60 pt-3">
                                    <DocumentosSection
                                        attachableType="beneficiarie"
                                        attachableId={beneficiario.id}
                                        initialDocuments={beneficiario.documents || []}
                                        title="Documentos del Beneficiario (DNI, partida, constancia médica)"
                                    />
                                </div>
                            )}
                        </div>
                    ))}
                </div>

                <div className="flex gap-3 pt-2">
                    <button type="button" onClick={onClose} className="btn-secondary flex-1 text-xs sm:text-sm">
                        Cancelar
                    </button>
                    <button
                        type="submit"
                        disabled={submitting}
                        className="btn-primary flex-1 text-xs sm:text-sm disabled:opacity-50"
                    >
                        <i className={`fas ${submitting ? 'fa-spinner fa-spin' : 'fa-save'} mr-2`} />
                        {mode === 'edit' ? 'Actualizar Beneficiario' : `Guardar Ficha (${beneficiariesList.length} beneficiario/s)`}
                    </button>
                </div>
            </form>
        </Modal>
    );
}

function BeneficiarioViewModal({ beneficiario, onClose }) {
    if (!beneficiario) return null;
    const [photo, setPhoto] = useState(null);
    const [loadingPhoto, setLoadingPhoto] = useState(false);
    const history = beneficiario.histories?.[0] ?? null;
    const withUnit = (v, unit) => (v == null || v === '' ? '' : `${v} ${unit}`);

    useEffect(() => {
        if (!beneficiario?.person?.id) return;
        setLoadingPhoto(true);
        http.get(`${BASE}/personas/${beneficiario.person.id}/reniec-photo`, { timeout: 22000 })
            .then((res) => setPhoto(res.data?.data?.photo ?? null))
            .catch(() => setPhoto(null))
            .finally(() => setLoadingPhoto(false));
    }, [beneficiario?.id, beneficiario?.person?.id]);

    return (
        <DetailModal open onClose={onClose} title="Detalle del Beneficiario" icon="fa-hand-holding-heart" maxWidth="sm:max-w-3xl">
            <DetailGroup>
                <div className="flex flex-col sm:flex-row items-center sm:items-start gap-4 mb-3">
                    <ReniecPhoto persona={{ ...beneficiario.person, photo }} loading={loadingPhoto} />
                    <div className="min-w-0 flex-1 w-full space-y-2">
                        <Field label="Beneficiario" wide>
                            <span className="text-base font-bold text-charcoal">{personFullName(beneficiario.person)}</span>
                        </Field>
                        <FieldGrid cols={3}>
                            <Field label="DNI" value={beneficiario.person?.dni} mono />
                            <Field label="Parentesco" value={beneficiario.relationship?.title} />
                            <Field label="Socia titular" value={beneficiario.partner?.name} />
                        </FieldGrid>
                        <FieldGrid cols={3}>
                            <Field label="Fecha de nacimiento" value={formatDate(beneficiario.person?.birthdate)} />
                            <Field label="Sexo" value={beneficiario.person?.gender === 'M' ? 'Masculino' : beneficiario.person?.gender === 'F' ? 'Femenino' : ''} />
                            <Field label="Edad" value={beneficiario.person?.age_formatted} />
                        </FieldGrid>
                    </div>
                </div>
            </DetailGroup>

            <DetailGroup title="Datos clínicos y Antropométricos" icon="fa-notes-medical">
                <FieldGrid cols={4}>
                    <Field label="Peso" value={withUnit(history?.weight, 'kg')} />
                    <Field label="Talla" value={withUnit(history?.height, 'cm')} />
                    <Field label="Hemoglobina (HMG)" value={withUnit(history?.hmg, 'g/dL')} />
                    <Field
                        label="Condición Especial"
                        value={[
                            history?.is_malnourished ? 'Desnutrido 7-13' : '',
                            history?.is_disabled ? 'Discapacitado' : '',
                        ].filter(Boolean).join(', ') || 'Normal'}
                    />
                </FieldGrid>
            </DetailGroup>

            <DetailGroup title="Beneficio del Programa" icon="fa-hand-holding-medical">
                <FieldGrid cols={4}>
                    <Field label="Tipo de beneficio" value={history?.type_benefit?.title} />
                    <Field label="Estado" value={history?.state?.title} />
                    <Field label="Fecha de inicio" value={formatDate(history?.date_begin)} />
                    <Field label="Fecha de fin" value={formatDate(history?.date_end)} />
                </FieldGrid>
            </DetailGroup>

            <DetailGroup title="Expediente y Documentos Adjuntos" icon="fa-paperclip">
                <DocumentosSection
                    attachableType="beneficiarie"
                    attachableId={beneficiario.id}
                    initialDocuments={beneficiario.documents || []}
                    title="Documentos del Beneficiario (DNI, partida, constancia médica)"
                    canUpload={false}
                    canDelete={false}
                    canDownload={false}
                />
            </DetailGroup>
        </DetailModal>
    );
}

const BeneficiariosTab = forwardRef(function BeneficiariosTab({ options, can }, ref) {
    const toast = useToast();
    const [data, setData] = useState(null);
    const [loading, setLoading] = useState(true);
    const [filters, setFilters] = useState({ search: '', partner_id: '', relationship_id: '', year: '', month: '' });
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

    const load = async () => {
        setLoading(true);
        try {
            const params = { per_page: 10, page };
            if (debouncedFilters.search) params.search = debouncedFilters.search;
            if (debouncedFilters.partner_id) params.partner_id = debouncedFilters.partner_id;
            if (debouncedFilters.relationship_id) params.relationship_id = debouncedFilters.relationship_id;
            if (debouncedFilters.year && debouncedFilters.month) {
                params.year = debouncedFilters.year;
                params.month = debouncedFilters.month;
            }
            const res = await http.get(`${BASE}/beneficiarios`, { params });
            setData(res.data);
            if (!filters.year || !filters.month) {
                setFilters((previous) => ({ ...previous, ...res.data.period }));
            }
        } catch {
            toast.error('No se pudo cargar la lista de beneficiarios.');
        } finally {
            setLoading(false);
        }
    };

    useEffect(() => {
        load();
        // eslint-disable-next-line react-hooks/exhaustive-deps
    }, [debouncedFilters, page]);

    useImperativeHandle(ref, () => ({
        openCreate: () => {
            setFormMode('create');
            setEditing(null);
            setFormOpen(true);
        },
    }));

    const setFilter = (key, value) => setFilters((prev) => ({ ...prev, [key]: value }));

    const openEdit = (b) => {
        setEditing(b);
        setFormMode('edit');
        setFormOpen(true);
    };

    const confirmDelete = async () => {
        if (!deleting) return;
        try {
            await http.delete(`${BASE}/beneficiarios/${deleting.id}`);
            toast.success('Beneficiario dado de baja o eliminado exitosamente.');
            setDeleting(null);
            load();
        } catch (err) {
            toast.error(errorMessage(err, 'No se pudo dar de baja al beneficiario.'));
            setDeleting(null);
        }
    };

    const partnerOptions = (options.partners ?? []).map((p) => ({ id: p.id, label: p.name }));
    const relationshipOptions = (options.relationships ?? []).map((r) => ({ id: r.id, label: r.title }));

    return (
        <>
            {/* Filtros */}
            <div className="bg-gray-50 rounded-xl p-3 sm:p-4 mb-4 sm:mb-6">
                <form onSubmit={(e) => e.preventDefault()} className="flex flex-col sm:flex-row flex-wrap items-end gap-2 sm:gap-3">
                    <div className="w-full sm:flex-1 min-w-[160px]">
                        <label className={labelCls}>Buscar</label>
                        <div className="relative">
                            <i className="fas fa-search absolute left-3.5 top-1/2 -translate-y-1/2 text-earth pointer-events-none" />
                            <input
                                type="text"
                                value={filters.search}
                                onChange={(e) => setFilter('search', e.target.value)}
                                placeholder="           Buscar por nombre o DNI"
                                className={inputCls}
                            />
                        </div>
                    </div>
                    <div className="w-full sm:w-64 shrink-0">
                        <label className={labelCls}>Socio (Titular)</label>
                        <Combobox
                            value={filters.partner_id}
                            onChange={(v) => setFilter('partner_id', v ?? '')}
                            options={partnerOptions}
                            placeholder="Todos los socios"
                            allowClear
                        />
                    </div>
                    <div className="w-full sm:w-48 shrink-0">
                        <label className={labelCls}>Parentesco</label>
                        <Combobox
                            value={filters.relationship_id}
                            onChange={(v) => setFilter('relationship_id', v ?? '')}
                            options={relationshipOptions}
                            placeholder="Todos"
                            allowClear
                        />
                    </div>
                    <div className="w-full sm:w-36 shrink-0">
                        <label className={labelCls}>Mes</label>
                        <select
                            value={filters.month}
                            onChange={(e) => setFilter('month', e.target.value)}
                            className={inputCls}
                        >
                            {MONTHS.map((name, index) => (
                                <option key={name} value={index + 1}>
                                    {name}
                                </option>
                            ))}
                        </select>
                    </div>
                    <div className="w-full sm:w-28 shrink-0">
                        <label className={labelCls}>Año</label>
                        <select
                            value={filters.year}
                            onChange={(e) => setFilter('year', e.target.value)}
                            className={inputCls}
                        >
                            {YEARS.map((y) => (
                                <option key={y} value={y}>
                                    {y}
                                </option>
                            ))}
                        </select>
                    </div>
                    <div className="w-full sm:w-auto shrink-0">
                        <button
                            type="button"
                            onClick={() => {
                                setFilters({ search: '', partner_id: '', relationship_id: '', year: '', month: '' });
                                setPage(1);
                            }}
                            className="flex items-center gap-1.5 text-xs sm:text-sm font-bold text-leaf border border-leaf rounded-md px-2.5 py-1.5 hover:opacity-80 whitespace-nowrap"
                        >
                            <i className="fa-solid fa-eraser" /> Limpiar
                        </button>
                    </div>
                </form>
            </div>

            {loading && !data && (
                <div className="flex items-center justify-center py-10 text-earth">
                    <i className="fas fa-spinner fa-spin mr-2" /> Cargando beneficiarios...
                </div>
            )}

            {data && (
                <div className="overflow-x-auto -mx-4 sm:mx-0">
                    <table className="data-table w-full text-xs sm:text-sm min-w-[600px]">
                        <thead>
                            <tr>
                                <th className="px-3 sm:px-4 py-3 text-left">Beneficiario</th>
                                <th className="px-3 sm:px-4 py-3 text-left">DNI</th>
                                <th className="px-3 sm:px-4 py-3 text-left">Socio (Titular)</th>
                                <th className="px-3 sm:px-4 py-3 text-left">Parentesco</th>
                                <th className="px-3 sm:px-4 py-3 text-center">Acciones</th>
                            </tr>
                        </thead>
                        <tbody>
                            {data.data.length === 0 ? (
                                <tr>
                                    <td colSpan={5}>
                                        <div className="empty-state">
                                            <i className="fas fa-users" />
                                            <p>No hay beneficiarios registrados</p>
                                        </div>
                                    </td>
                                </tr>
                            ) : (
                                data.data.map((b) => (
                                    <tr key={b.id} className="row-enter">
                                        <td className="px-3 sm:px-4 py-3 font-medium">
                                            {b.person ? `${b.person.names} ${b.person.father_lastname}` : 'Sin nombre'}
                                        </td>
                                        <td className="px-3 sm:px-4 py-3">{b.person?.dni || 'Sin DNI'}</td>
                                        <td className="px-3 sm:px-4 py-3">{b.partner?.name || 'Sin socio'}</td>
                                        <td className="px-3 sm:px-4 py-3">{b.relationship?.title || 'N/A'}</td>
                                        <td className="px-3 sm:px-4 py-3 text-center">
                                            <div className="inline-grid grid-cols-[repeat(3,2.25rem)] items-center justify-items-center gap-1 sm:gap-2">
                                                <button
                                                    type="button"
                                                    onClick={() => setViewing(b)}
                                                    className="btn-action col-start-1 bg-sky-light text-[#0284C7] hover:bg-sky hover:text-white"
                                                    title="Ver"
                                                >
                                                    <i className="fas fa-eye" />
                                                </button>
                                                {can.edit && (
                                                    <button
                                                        type="button"
                                                        onClick={() => openEdit(b)}
                                                        className="btn-action col-start-2 bg-sun-light text-[#D97706] hover:bg-sun hover:text-white"
                                                        title="Editar"
                                                    >
                                                        <i className="fas fa-edit" />
                                                    </button>
                                                )}
                                                {can.del && (
                                                    <button
                                                        type="button"
                                                        onClick={() => setDeleting(b)}
                                                        className="btn-action col-start-3 bg-clay-light text-clay hover:bg-clay hover:text-white"
                                                        title="Eliminar / Dar de baja"
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
                <BeneficiarioFormModal
                    key={editing ? editing.id : 'create'}
                    mode={formMode}
                    beneficiario={editing}
                    options={options}
                    onClose={() => setFormOpen(false)}
                    onSaved={() => {
                        setFormOpen(false);
                        load();
                    }}
                />
            )}

            <BeneficiarioViewModal beneficiario={viewing} onClose={() => setViewing(null)} />

            <ConfirmDialog
                open={!!deleting}
                onCancel={() => setDeleting(null)}
                onConfirm={confirmDelete}
                title="Dar de baja / Eliminar Beneficiario"
                message="Se dará de baja el beneficio activo del beneficiario en este período."
                details={deleting ? [
                    { label: 'Beneficiario', value: personFullName(deleting.person) },
                    { label: 'DNI', value: deleting.person?.dni },
                ] : []}
            />
        </>
    );
});

export default BeneficiariosTab;
