import { forwardRef, useCallback, useEffect, useImperativeHandle, useState } from 'react';
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

const selectCls =
    'w-full px-4 py-2.5 border-2 border-wheat rounded-xl text-xs sm:text-sm font-semibold text-charcoal bg-white focus:outline-none focus:border-leaf transition-all';
const labelCls = 'block text-[11px] font-bold text-earth uppercase tracking-wider mb-1';
const inputCls = selectCls;

const peopleSearch = async (q) => {
    const res = await http.get('/api/search/people', { params: { q, limit: 30 } });
    return (res.data.results || []).map((r) => ({ id: r.id, label: r.text }));
};

function SocioFormModal({ mode, partner, options, onClose, onSaved }) {
    const toast = useToast();
    const [personId, setPersonId] = useState(mode === 'edit' && partner ? partner.person_id : null);
    const [personLabelState, setPersonLabelState] = useState(mode === 'edit' && partner?.person ? personLabel(partner.person) : '');
    const [associationId, setAssociationId] = useState(mode === 'edit' && partner ? partner.association_id : '');
    const [dateBegin, setDateBegin] = useState(partner?.date_begin || '');
    const [dateEnd, setDateEnd] = useState(partner?.date_end || '');
    const [stateId, setStateId] = useState(mode === 'edit' && partner ? partner.state_id : '');
    const [observations, setObservations] = useState(partner?.observations || '');
    const [submitting, setSubmitting] = useState(false);

    const associationOptions = (options.associations || []).map((a) => ({ id: a.id, label: a.name }));
    const stateOptions = (options.states || []).map((s) => ({ id: s.id, label: s.title }));

    const handleSubmit = async (e) => {
        e.preventDefault();
        if (!personId || !associationId || !stateId || !dateBegin) {
            toast.error('Complete los campos obligatorios del socio.');
            return;
        }
        setSubmitting(true);
        try {
            const payload = {
                person_id: personId,
                association_id: associationId,
                state_id: stateId,
                date_begin: dateBegin,
                date_end: dateEnd || null,
                observations: observations || null,
            };

            if (mode === 'edit') {
                await http.put(`${BASE}/partners/${partner.id}`, payload);
                toast.success('Socio actualizado correctamente.');
            } else {
                await http.post(`${BASE}/partners`, payload);
                toast.success('Socio creado exitosamente.');
            }
            onSaved();
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
            title={mode === 'edit' ? 'Editar Socio' : 'Nuevo Socio'}
            icon={mode === 'edit' ? 'fa-edit' : 'fa-user-plus'}
            iconClass={mode === 'edit' ? 'text-sun' : 'text-leaf'}
            maxWidth="sm:max-w-2xl"
        >
            <form onSubmit={handleSubmit} className="p-4 sm:p-6 space-y-4">
                <div>
                    <label className={labelCls}>Persona *</label>
                    <Combobox
                        value={personId}
                        onChange={setPersonId}
                        onSelect={(opt) => setPersonLabelState(opt.label)}
                        onSearch={peopleSearch}
                        selectedLabel={personLabelState}
                        placeholder="Buscar persona por nombre o DNI..."
                        minQuery={2}
                    />
                </div>
                <div>
                    <label className={labelCls}>Club *</label>
                    <Combobox value={associationId} onChange={setAssociationId} options={associationOptions} placeholder="Seleccionar club..." />
                </div>
                <div className="grid grid-cols-1 sm:grid-cols-2 gap-4">
                    <div>
                        <label className={labelCls}>Fecha Inicio *</label>
                        <input type="date" value={dateBegin} onChange={(e) => setDateBegin(e.target.value)} className={inputCls} required />
                    </div>
                    <div>
                        <label className={labelCls}>Fecha Fin</label>
                        <input type="date" value={dateEnd} onChange={(e) => setDateEnd(e.target.value)} className={inputCls} />
                    </div>
                </div>
                <div>
                    <label className={labelCls}>Estado *</label>
                    <Combobox value={stateId} onChange={setStateId} options={stateOptions} placeholder="Seleccionar..." />
                </div>
                <div>
                    <label className={labelCls}>Observaciones</label>
                    <textarea
                        rows="2"
                        value={observations}
                        onChange={(e) => setObservations(e.target.value)}
                        className={inputCls}
                    />
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

function SocioViewModal({ partner, onClose }) {
    if (!partner) return null;
    const latestHistory = (b) => (b.histories && b.histories.length ? b.histories[0] : null);

    return (
        <Modal open onClose={onClose} title="Detalle del Socio" icon="fa-user" iconClass="text-leaf" maxWidth="sm:max-w-4xl">
            <div className="p-4 sm:p-6 space-y-3 text-sm">
                <div>
                    <span className="text-[11px] font-bold text-earth uppercase">Nombre</span>
                    <p className="font-semibold text-charcoal">{personFullName(partner.person) || 'Sin nombre'}</p>
                </div>
                <div className="grid grid-cols-1 sm:grid-cols-2 gap-3">
                    <div>
                        <span className="text-[11px] font-bold text-earth uppercase">DNI</span>
                        <p>{partner.person?.dni || '-'}</p>
                    </div>
                    <div>
                        <span className="text-[11px] font-bold text-earth uppercase">Estado</span>
                        <p>
                            <span
                                className={`px-2 py-1 text-[10px] font-bold rounded-full ${
                                    partner.state && partner.state.title === 'Activo'
                                        ? 'bg-green-100 text-green-800'
                                        : 'bg-red-100 text-red-800'
                                }`}
                            >
                                {partner.state?.title || 'N/A'}
                            </span>
                        </p>
                    </div>
                </div>
                <div>
                    <span className="text-[11px] font-bold text-earth uppercase">Club</span>
                    <p>{partner.association?.name || '-'}</p>
                </div>
                <div className="grid grid-cols-1 sm:grid-cols-2 gap-3">
                    <div>
                        <span className="text-[11px] font-bold text-earth uppercase">Fecha Inicio</span>
                        <p>{formatDate(partner.date_begin) || '-'}</p>
                    </div>
                    <div>
                        <span className="text-[11px] font-bold text-earth uppercase">Fecha Fin</span>
                        <p>{formatDate(partner.date_end) || '-'}</p>
                    </div>
                </div>
                <div>
                    <span className="text-[11px] font-bold text-earth uppercase">Beneficiarios</span>
                    <p className="font-bold text-leaf text-lg">{partner.beneficiaries_count ?? 0}</p>
                </div>
                {partner.beneficiaries_count > 0 && (
                    <div className="mt-3">
                        <span className="text-[11px] font-bold text-earth uppercase">Lista de Beneficiarios</span>
                        <div className="mt-2 space-y-2">
                            {(partner.beneficiaries || []).map((b) => {
                                const h = latestHistory(b);
                                return (
                                    <div key={b.id} className="p-3 bg-gray-50 rounded-lg border border-wheat text-sm">
                                        <span className="text-xs font-bold text-leaf uppercase mb-2 block">Beneficiario</span>
                                        <div className="grid grid-cols-1 md:grid-cols-2 gap-2 mb-2">
                                            <div>
                                                <span className="text-[10px] font-bold text-earth uppercase">Nombre</span>
                                                <p className="font-semibold">{personFullName(b.person) || '-'}</p>
                                            </div>
                                            <div>
                                                <span className="text-[10px] font-bold text-earth uppercase">DNI</span>
                                                <p>{b.person?.dni || '-'}</p>
                                            </div>
                                        </div>
                                        <div className="grid grid-cols-1 sm:grid-cols-2 gap-2 mb-2">
                                            <div>
                                                <span className="text-[10px] font-bold text-earth uppercase">Parentesco</span>
                                                <p>{b.relationship?.title || '-'}</p>
                                            </div>
                                        </div>
                                        <div className="border-t border-wheat pt-2 mt-2">
                                            <span className="text-[10px] font-bold text-earth uppercase">Datos Clínicos</span>
                                            <div className="grid grid-cols-1 sm:grid-cols-3 gap-2 mt-1">
                                                <div>
                                                    <span className="text-[9px] text-earth uppercase">Peso</span>
                                                    <p className="text-xs">{h?.weight ?? '-'}</p>
                                                </div>
                                                <div>
                                                    <span className="text-[9px] text-earth uppercase">Talla</span>
                                                    <p className="text-xs">{h?.height ?? '-'}</p>
                                                </div>
                                                <div>
                                                    <span className="text-[9px] text-earth uppercase">HMG</span>
                                                    <p className="text-xs">{h?.hmg ?? '-'}</p>
                                                </div>
                                            </div>
                                            <div className="grid grid-cols-1 sm:grid-cols-2 gap-2 mt-1">
                                                <div>
                                                    <span className="text-[9px] text-earth uppercase">F. Inicio</span>
                                                    <p className="text-xs">{formatDate(h?.date_begin) || '-'}</p>
                                                </div>
                                                <div>
                                                    <span className="text-[9px] text-earth uppercase">F. Fin</span>
                                                    <p className="text-xs">{formatDate(h?.date_end) || '-'}</p>
                                                </div>
                                            </div>
                                            <div className="grid grid-cols-1 sm:grid-cols-3 gap-2 mt-1">
                                                <div>
                                                    <span className="text-[9px] text-earth uppercase">Tipo Beneficio</span>
                                                    <p className="text-xs">{h?.type_benefit?.title || '-'}</p>
                                                </div>
                                                <div>
                                                    <span className="text-[9px] text-earth uppercase">Estado</span>
                                                    <p className="text-xs">{h?.state?.title || '-'}</p>
                                                </div>
                                                <div>
                                                    <span className="text-[9px] text-earth uppercase">Motivo Descalif.</span>
                                                    <p className="text-xs">{h?.reason_disqualification?.title || 'Ninguno'}</p>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                );
                            })}
                        </div>
                    </div>
                )}
                {partner.observations && (
                    <div>
                        <span className="text-[11px] font-bold text-earth uppercase">Observaciones</span>
                        <p className="text-earth">{partner.observations}</p>
                    </div>
                )}
            </div>
            <div className="px-6 pb-6">
                <button type="button" onClick={onClose} className="btn-secondary w-full text-xs sm:text-sm">
                    Cerrar
                </button>
            </div>
        </Modal>
    );
}

const SociosTab = forwardRef(function SociosTab({ options, can }, ref) {
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
            <form
                onSubmit={(e) => e.preventDefault()}
                className="mb-4 sm:mb-6 flex flex-col sm:flex-row gap-2 sm:gap-4"
            >
                <div className="w-full sm:w-72">
                    <input
                        type="text"
                        value={filters.search}
                        onChange={(e) => setFilter('search', e.target.value)}
                        placeholder="Buscar por nombre o DNI"
                        className="w-full px-4 sm:px-10 py-2.5 border-2 border-wheat rounded-xl text-xs sm:text-sm font-semibold text-charcoal bg-white focus:outline-none focus:border-leaf transition-all"
                    />
                </div>
                <div className="flex-1 sm:min-w-40">
                    <Combobox
                        value={filters.association_id}
                        onChange={(v) => setFilter('association_id', v ?? '')}
                        options={associationOptions}
                        placeholder="Todos los Clubes"
                        allowClear
                    />
                </div>
                <div className="flex-1 sm:min-w-36">
                    <Combobox
                        value={filters.state_id}
                        onChange={(v) => setFilter('state_id', v ?? '')}
                        options={stateOptions}
                        placeholder="Todos los Estados"
                        allowClear
                    />
                </div>
                <div className="flex gap-2">
                    <button
                        type="button"
                        onClick={() => {
                            setFilters({ search: '', association_id: '', state_id: '' });
                            setPage(1);
                        }}
                        className="btn-secondary text-xs sm:text-sm"
                    >
                        <i className="fas fa-broom mr-1 sm:mr-2" /> Limpiar
                    </button>
                </div>
            </form>

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
                                <th className="px-3 sm:px-4 py-3 text-left">Benef.</th>
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
                                            <div className="flex items-center justify-center gap-1 sm:gap-2">
                                                <button
                                                    type="button"
                                                    onClick={() => setViewing(partner)}
                                                    className="btn-action bg-sky-light text-[#0284C7] hover:bg-sky hover:text-white"
                                                    title="Ver"
                                                >
                                                    <i className="fas fa-eye" />
                                                </button>
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
                <SocioFormModal
                    key={editing ? editing.id : 'create'}
                    mode={formMode}
                    partner={editing}
                    options={options}
                    onClose={() => setFormOpen(false)}
                    onSaved={() => {
                        setFormOpen(false);
                        load();
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
