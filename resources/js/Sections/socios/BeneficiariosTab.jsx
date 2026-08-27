import { forwardRef, useEffect, useImperativeHandle, useState } from 'react';
import http from '../../http';
import { useToast } from '../../Components/Toast';
import Modal from '../../Components/Modal';
import ConfirmDialog from '../../Components/ConfirmDialog';
import Combobox from '../../Components/Combobox';
import Pagination from '../../Components/Pagination';
import { useDebounced } from './hooks';
import { formatDate, personFullName, personLabel } from './format';
import errorMessage from '../../errorMessage';

const BASE = '/api/dashboard/socios-beneficiarios';
const SEARCH_PEOPLE = '/api/search/people';

const labelCls = 'block text-xs font-bold text-slate-600 uppercase tracking-wider mb-1';
const inputCls =
    'w-full px-4 py-2.5 border-2 border-wheat rounded-xl text-xs sm:text-sm font-semibold text-charcoal bg-white focus:outline-none focus:border-leaf transition-all';

function BeneficiarioFormModal({ mode, beneficiario, options, onClose, onSaved }) {
    const toast = useToast();
    const history = mode === 'edit' ? beneficiario?.histories?.[0] ?? null : null;
    const [personId, setPersonId] = useState(mode === 'edit' ? beneficiario?.person_id ?? '' : '');
    const [personLabelState, setPersonLabelState] = useState(mode === 'edit' ? personLabel(beneficiario?.person) : '');
    const [partnerId, setPartnerId] = useState(mode === 'edit' ? beneficiario?.partner_id ?? '' : '');
    const [relationshipId, setRelationshipId] = useState(mode === 'edit' ? beneficiario?.relationship_id ?? '' : '');
    const [weight, setWeight] = useState(history?.weight ?? '');
    const [height, setHeight] = useState(history?.height ?? '');
    const [hmg, setHmg] = useState(history?.hmg ?? '');
    const [dateBegin, setDateBegin] = useState(history?.date_begin ?? '');
    const [dateEnd, setDateEnd] = useState(history?.date_end ?? '');
    const [typeBenefitId, setTypeBenefitId] = useState(history?.type_benefit_id ?? '');
    const [historyStateId, setHistoryStateId] = useState(history?.state_id ?? '');
    const [reasonDisqualificationId, setReasonDisqualificationId] = useState(history?.reason_disqualification_id ?? '');
    const [submitting, setSubmitting] = useState(false);

    const partnerOptions = (options.partners ?? []).map((p) => ({ id: p.id, label: p.name }));
    const relationshipOptions = (options.relationships ?? []).map((r) => ({ id: r.id, label: r.title }));
    const typeBenefitOptions = (options.type_benefits ?? []).map((t) => ({ id: t.id, label: t.title }));
    const stateOptions = (options.states ?? []).map((s) => ({ id: s.id, label: s.title }));
    const reasonOptions = (options.reason_disqualifications ?? []).map((r) => ({ id: r.id, label: r.title }));

    const searchPeople = async (search) => {
        try {
            const res = await http.get(SEARCH_PEOPLE, { params: { q: search, limit: 30 } });
            return (res.data?.results ?? []).map((r) => ({ id: r.id, label: r.text }));
        } catch {
            return [];
        }
    };

    const handleSubmit = async (e) => {
        e.preventDefault();
        if (!personId || !partnerId || !relationshipId) {
            toast.error('Complete todos los campos.');
            return;
        }
        setSubmitting(true);
        try {
            const payload = {
                person_id: personId,
                partner_id: partnerId,
                relationship_id: relationshipId,
                weight: weight === '' ? null : weight,
                height: height === '' ? null : height,
                hmg: hmg === '' ? null : hmg,
                date_begin: dateBegin || null,
                date_end: dateEnd || null,
                type_benefit_id: typeBenefitId || null,
                history_state_id: historyStateId || null,
                reason_disqualification_id: reasonDisqualificationId || null,
            };
            if (mode === 'edit') {
                await http.put(`${BASE}/beneficiarios/${beneficiario.id}`, payload);
            } else {
                await http.post(`${BASE}/beneficiarios`, payload);
            }
            toast.success(mode === 'edit' ? 'Beneficiario actualizado correctamente.' : 'Beneficiario creado exitosamente.');
            onSaved();
        } catch (err) {
            toast.error(errorMessage(err, 'Ocurrió un error al guardar el beneficiario.'));
        } finally {
            setSubmitting(false);
        }
    };

    return (
        <Modal
            open
            onClose={onClose}
            title={mode === 'edit' ? 'Editar Beneficiario' : 'Nuevo Beneficiario'}
            icon={mode === 'edit' ? 'fa-edit' : 'fa-hand-holding-heart'}
            iconClass={mode === 'edit' ? 'text-sun' : 'text-leaf'}
            maxWidth="sm:max-w-2xl"
        >
            <form onSubmit={handleSubmit} className="p-6 space-y-4">
                <div>
                    <label className={labelCls}>
                        Persona Beneficiaria <span className="text-clay">*</span>
                    </label>
                    <Combobox
                        value={personId}
                        onChange={(v) => setPersonId(v ?? '')}
                        onSelect={(opt) => setPersonLabelState(opt.label)}
                        options={[]}
                        selectedLabel={personLabelState}
                        onSearch={searchPeople}
                        placeholder="Buscar por nombre o DNI..."
                        minQuery={2}
                        allowClear
                    />
                </div>
                <div>
                    <label className={labelCls}>
                        Socio (Titular) <span className="text-clay">*</span>
                    </label>
                    <Combobox
                        value={partnerId}
                        onChange={(v) => setPartnerId(v ?? '')}
                        options={partnerOptions}
                        placeholder="Seleccionar socio..."
                        allowClear
                    />
                </div>
                <div>
                    <label className={labelCls}>
                        Parentesco <span className="text-clay">*</span>
                    </label>
                    <Combobox
                        value={relationshipId}
                        onChange={(v) => setRelationshipId(v ?? '')}
                        options={relationshipOptions}
                        placeholder="Seleccionar parentesco..."
                        allowClear
                    />
                </div>

                <div className="border-t-2 border-wheat pt-4">
                    <p className={`${labelCls} mb-3`}>
                        Datos Clínicos <span className="font-normal normal-case text-earth">(opcional)</span>
                    </p>
                    <div className="grid grid-cols-1 sm:grid-cols-3 gap-4 mb-4">
                        <div>
                            <label className={labelCls}>Peso (kg)</label>
                            <input type="number" step="0.01" min="0" value={weight} onChange={(e) => setWeight(e.target.value)} className={inputCls} placeholder="65.50" />
                        </div>
                        <div>
                            <label className={labelCls}>Talla (cm)</label>
                            <input type="number" step="0.01" min="0" value={height} onChange={(e) => setHeight(e.target.value)} className={inputCls} placeholder="160.00" />
                        </div>
                        <div>
                            <label className={labelCls}>HMG (g/dL)</label>
                            <input type="number" step="0.01" min="0" value={hmg} onChange={(e) => setHmg(e.target.value)} className={inputCls} placeholder="12.50" />
                        </div>
                    </div>

                    <div className="grid grid-cols-1 sm:grid-cols-2 gap-4 mb-4">
                        <div>
                            <label className={labelCls}>F. Inicio Beneficio</label>
                            <input type="date" value={dateBegin} onChange={(e) => setDateBegin(e.target.value)} className={inputCls} />
                        </div>
                        <div>
                            <label className={labelCls}>F. Fin Beneficio</label>
                            <input type="date" value={dateEnd} onChange={(e) => setDateEnd(e.target.value)} className={inputCls} />
                        </div>
                    </div>

                    <div className="grid grid-cols-1 sm:grid-cols-3 gap-4">
                        <div>
                            <label className={labelCls}>Tipo de Beneficio</label>
                            <Combobox
                                value={typeBenefitId}
                                onChange={(v) => setTypeBenefitId(v ?? '')}
                                options={typeBenefitOptions}
                                placeholder="Seleccionar..."
                                allowClear
                            />
                        </div>
                        <div>
                            <label className={labelCls}>Estado</label>
                            <Combobox
                                value={historyStateId}
                                onChange={(v) => setHistoryStateId(v ?? '')}
                                options={stateOptions}
                                placeholder="Seleccionar..."
                                allowClear
                            />
                        </div>
                        <div>
                            <label className={labelCls}>Motivo Descalificación</label>
                            <Combobox
                                value={reasonDisqualificationId}
                                onChange={(v) => setReasonDisqualificationId(v ?? '')}
                                options={reasonOptions}
                                placeholder="Ninguno"
                                allowClear
                            />
                        </div>
                    </div>
                </div>

                <div className="flex gap-3 pt-2">
                    <button type="button" onClick={onClose} className="btn-secondary flex-1 text-xs sm:text-sm">
                        Cancelar
                    </button>
                    <button type="submit" disabled={submitting} className="btn-primary flex-1 text-xs sm:text-sm">
                        <i className={`fas ${submitting ? 'fa-spinner fa-spin' : 'fa-save'} mr-2`} />
                        {mode === 'edit' ? 'Actualizar' : 'Guardar'}
                    </button>
                </div>
            </form>
        </Modal>
    );
}

function BeneficiarioViewModal({ beneficiario, onClose }) {
    if (!beneficiario) return null;
    const history = beneficiario.histories?.[0] ?? null;
    return (
        <Modal
            open
            onClose={onClose}
            title="Detalle de Beneficiario"
            icon="fa-hand-holding-heart"
            iconClass="text-leaf"
            maxWidth="sm:max-w-lg"
        >
            <div className="p-6 space-y-6 text-sm">
                <div>
                    <span className={labelCls}>Beneficiario</span>
                    <p className="text-base font-bold text-charcoal">{personFullName(beneficiario.person)}</p>
                </div>
                <div className="grid grid-cols-2 gap-4">
                    <div>
                        <span className={labelCls}>DNI</span>
                        <p className="font-mono">{beneficiario.person?.dni || '-'}</p>
                    </div>
                    <div>
                        <span className={labelCls}>Parentesco</span>
                        <p>{beneficiario.relationship?.title || '-'}</p>
                    </div>
                </div>
                <div>
                    <span className={labelCls}>Socio (Titular)</span>
                    <p className="font-medium">{beneficiario.partner?.name || '-'}</p>
                </div>
                <div>
                    <span className={labelCls}>Fecha de Nacimiento</span>
                    <p>{formatDate(beneficiario.person?.birthdate) || '-'}</p>
                </div>

                <div className="border-t-2 border-wheat pt-4">
                    <span className={labelCls}>Datos Clínicos</span>
                    <div className="grid grid-cols-3 gap-4 mt-2">
                        <div>
                            <span className="text-[10px] text-earth uppercase">Peso</span>
                            <p className="font-semibold">{history?.weight ?? '-'} {history?.weight != null ? 'kg' : ''}</p>
                        </div>
                        <div>
                            <span className="text-[10px] text-earth uppercase">Talla</span>
                            <p className="font-semibold">{history?.height ?? '-'} {history?.height != null ? 'cm' : ''}</p>
                        </div>
                        <div>
                            <span className="text-[10px] text-earth uppercase">HMG</span>
                            <p className="font-semibold">{history?.hmg ?? '-'} {history?.hmg != null ? 'g/dL' : ''}</p>
                        </div>
                    </div>
                    <div className="grid grid-cols-2 gap-4 mt-3">
                        <div>
                            <span className="text-[10px] text-earth uppercase">F. Inicio Beneficio</span>
                            <p>{formatDate(history?.date_begin) || '-'}</p>
                        </div>
                        <div>
                            <span className="text-[10px] text-earth uppercase">F. Fin Beneficio</span>
                            <p>{formatDate(history?.date_end) || '-'}</p>
                        </div>
                    </div>
                    <div className="grid grid-cols-1 sm:grid-cols-3 gap-4 mt-3">
                        <div>
                            <span className="text-[10px] text-earth uppercase">Tipo de Beneficio</span>
                            <p>{history?.type_benefit?.title || '-'}</p>
                        </div>
                        <div>
                            <span className="text-[10px] text-earth uppercase">Estado</span>
                            <p>{history?.state?.title || '-'}</p>
                        </div>
                        <div>
                            <span className="text-[10px] text-earth uppercase">Motivo Descalif.</span>
                            <p>{history?.reason_disqualification?.title || 'Ninguno'}</p>
                        </div>
                    </div>
                </div>
            </div>
        </Modal>
    );
}

const BeneficiariosTab = forwardRef(function BeneficiariosTab({ options, can }, ref) {
    const toast = useToast();
    const [data, setData] = useState(null);
    const [loading, setLoading] = useState(true);
    const [filters, setFilters] = useState({ search: '', partner_id: '', relationship_id: '' });
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
            const res = await http.get(`${BASE}/beneficiarios`, { params });
            setData(res.data);
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

    const openEdit = (beneficiario) => {
        setEditing(beneficiario);
        setFormMode('edit');
        setFormOpen(true);
    };

    const confirmDelete = async () => {
        if (!deleting) return;
        try {
            await http.delete(`${BASE}/beneficiarios/${deleting.id}`);
            toast.success('Beneficiario eliminado correctamente.');
            setDeleting(null);
            load();
        } catch (err) {
            toast.error(errorMessage(err, 'No se pudo eliminar el beneficiario.'));
            setDeleting(null);
        }
    };

    const partnerOptions = (options.partners ?? []).map((p) => ({ id: p.id, label: p.name }));
    const relationshipOptions = (options.relationships ?? []).map((r) => ({ id: r.id, label: r.title }));

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
                        <i
                            className="fas fa-search absolute left-3.5 top-1/2 -translate-y-1/2 text-earth pointer-events-none"
                        />
                        <input
                            type="text"
                            value={filters.search}
                            onChange={(e) => setFilter('search', e.target.value)}
                            placeholder="Buscar por nombre o DNI"
                            className="w-full pl-10 pr-4 py-2.5 border-2 border-wheat rounded-xl text-xs sm:text-sm font-semibold text-charcoal bg-white focus:outline-none focus:border-leaf transition-all"
                        />
                    </div>
                </div>
                <div className="w-full sm:w-[300px] lg:w-[300px] shrink-0">
                    <label className={labelCls}>Socio (Titular)</label>
                    <Combobox
                        value={filters.partner_id}
                        onChange={(v) => setFilter('partner_id', v ?? '')}
                        options={partnerOptions}
                        placeholder="Socias"
                        allowClear
                    />
                </div>
                <div className="w-full sm:w-40 lg:w-40 shrink-0">
                    <label className={labelCls}>Parentesco</label>
                    <Combobox
                        value={filters.relationship_id}
                        onChange={(v) => setFilter('relationship_id', v ?? '')}
                        options={relationshipOptions}
                        placeholder="Parentescos"
                        allowClear
                    />
                </div>
                <div className="w-full sm:w-auto shrink-0 flex flex-col">
                    <button
                        type="button"
                        onClick={() => {
                            setFilters({ search: '', partner_id: '', relationship_id: '' });
                            setPage(1);
                        }}
                        className="flex items-center gap-1.5 text-xs sm:text-sm font-bold text-leaf border border-leaf rounded-md px-2.5 py-1.5 hover:opacity-80 whitespace-nowrap"
                    >
                        <i className="fa-solid fa-eraser" /> Limpiar
                    </button>
                    <p style={{ visibility: 'hidden', height: 6, margin: 0, padding: 0 }}>
                        {/* Ocupa espacio pero no se ve */}
                        Hola
                    </p>
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
                    <table className="data-table w-full text-xs sm:text-sm min-w-[640px]">
                        <thead>
                            <tr>
                                <th className="px-3 sm:px-4 py-3 text-left">Beneficiario</th>
                                <th className="px-3 sm:px-4 py-3 text-left">DNI</th>
                                <th className="px-3 sm:px-4 py-3 text-left">Socio</th>
                                <th className="px-3 sm:px-4 py-3 text-left">Parentesco</th>
                                <th className="px-3 sm:px-4 py-3 text-center">Acciones</th>
                            </tr>
                        </thead>
                        <tbody>
                            {data.data.length === 0 ? (
                                <tr>
                                    <td colSpan={5}>
                                        <div className="empty-state">
                                            <i className="fas fa-hand-holding-heart" />
                                            <p>No hay beneficiarios registrados</p>
                                        </div>
                                    </td>
                                </tr>
                            ) : (
                                data.data.map((beneficiario) => (
                                    <tr key={beneficiario.id} className="row-enter">
                                        <td className="px-3 sm:px-4 py-3 font-medium">{personFullName(beneficiario.person)}</td>
                                        <td className="px-3 sm:px-4 py-3 font-mono">{beneficiario.person?.dni || '-'}</td>
                                        <td className="px-3 sm:px-4 py-3">
                                            {beneficiario.partner ? (
                                                <span className="px-2 py-1 rounded-lg bg-leaf-light text-leaf text-xs font-bold">
                                                    <i className="fas fa-user-tie mr-1" /> {beneficiario.partner.name}
                                                </span>
                                            ) : (
                                                '-'
                                            )}
                                        </td>
                                        <td className="px-3 sm:px-4 py-3">{beneficiario.relationship?.title || '-'}</td>
                                        <td className="px-3 sm:px-4 py-3 text-center">
                                            <div className="inline-grid grid-cols-[repeat(3,2.25rem)] items-center justify-items-center gap-1 sm:gap-2">
                                                <button
                                                    type="button"
                                                    onClick={() => setViewing(beneficiario)}
                                                    className="btn-action col-start-1 bg-sky-light text-[#0284C7] hover:bg-sky hover:text-white"
                                                    title="Ver"
                                                >
                                                    <i className="fas fa-eye" />
                                                </button>
                                                {can.edit && (
                                                    <button
                                                        type="button"
                                                        onClick={() => openEdit(beneficiario)}
                                                        className="btn-action col-start-2 bg-sun-light text-[#D97706] hover:bg-sun hover:text-white"
                                                        title="Editar"
                                                    >
                                                        <i className="fas fa-edit" />
                                                    </button>
                                                )}
                                                {can.del && (
                                                    <button
                                                        type="button"
                                                        onClick={() => setDeleting(beneficiario)}
                                                        className="btn-action col-start-3 bg-clay-light text-clay hover:bg-clay hover:text-white"
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

            <BeneficiarioViewModal
                beneficiario={viewing}
                onClose={() => setViewing(null)}
            />

            <ConfirmDialog
                open={!!deleting}
                onCancel={() => setDeleting(null)}
                onConfirm={confirmDelete}
                title="Eliminar Beneficiario"
                message="Se eliminará el registro del beneficiario de forma permanente."
                details={deleting ? [
                    { label: 'Beneficiario', value: personFullName(deleting.person) },
                    { label: 'DNI', value: deleting.person?.dni },
                ] : []}
            />
        </>
    );
});

export default BeneficiariosTab;
