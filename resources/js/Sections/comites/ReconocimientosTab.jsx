import { forwardRef, useCallback, useEffect, useImperativeHandle, useState } from 'react';
import http from '../../http';
import { useToast } from '../../Components/Toast';
import Modal from '../../Components/Modal';
import ConfirmDialog from '../../Components/ConfirmDialog';
import Pagination from '../../Components/Pagination';
import ResolucionExternaModal from './ResolucionExternaModal';
import { useDebounced } from '../socios/hooks';
import { fmtDate, fmtDateTime, vigenciaBadge, stateBadge, datetimeInputValue, datetimeToSubmit } from './format';
import errorMessage from '../../errorMessage';

const BASE = '/api/dashboard/club-madres';

const labelCls = 'block text-[11px] font-bold text-earth uppercase tracking-wider mb-1';
const inputCls =
    'w-full px-4 py-2.5 border-2 border-wheat rounded-xl text-sm font-semibold text-charcoal bg-white focus:outline-none focus:border-leaf transition-all';

function ReconocimientoFormModal({ mode, resolution, options, onClose, onSaved }) {
    const toast = useToast();
    const [document, setDocument] = useState(mode === 'edit' && resolution ? resolution.document || '' : '');
    const [dateDocument, setDateDocument] = useState(
        mode === 'edit' && resolution ? datetimeInputValue(resolution.date_document) : ''
    );
    const [dateStart, setDateStart] = useState(mode === 'edit' && resolution ? resolution.date_start || '' : '');
    const [dateEnd, setDateEnd] = useState(mode === 'edit' && resolution ? resolution.date_end || '' : '');
    const [stateId, setStateId] = useState(mode === 'edit' && resolution ? resolution.state_id ?? '' : '');
    const [submitting, setSubmitting] = useState(false);

    const handleSubmit = async (e) => {
        e.preventDefault();
        if (!document || !dateDocument || !dateStart || !dateEnd || !stateId) {
            toast.error('Complete los campos obligatorios de la resolución.');
            return;
        }
        if (dateEnd < dateStart) {
            toast.error('La fecha de fin debe ser posterior o igual a la fecha de inicio.');
            return;
        }
        setSubmitting(true);
        try {
            const payload = {
                document,
                date_document: datetimeToSubmit(dateDocument),
                date_start: dateStart,
                date_end: dateEnd,
                state_id: stateId,
            };
            if (mode === 'edit') {
                await http.put(`${BASE}/reconocimientos/${resolution.id}`, payload);
                toast.success('Resolución actualizada correctamente.');
            } else {
                await http.post(`${BASE}/reconocimientos`, payload);
                toast.success('Resolución creada exitosamente.');
            }
            onSaved();
        } catch (err) {
            toast.error(errorMessage(err, 'Ocurrió un error al guardar la resolución.'));
        } finally {
            setSubmitting(false);
        }
    };

    return (
        <Modal
            open
            onClose={onClose}
            title={mode === 'edit' ? 'Editar Resolución' : 'Nueva Resolución'}
            icon={mode === 'edit' ? 'fa-edit' : 'fa-plus-circle'}
            iconClass={mode === 'edit' ? 'text-sun' : 'text-leaf'}
            maxWidth="sm:max-w-2xl"
        >
            <form onSubmit={handleSubmit} className="p-6">
                <div className="grid grid-cols-1 sm:grid-cols-2 gap-4">
                    <div className="sm:col-span-2">
                        <label className={labelCls}>Documento *</label>
                        <input type="text" value={document} onChange={(e) => setDocument(e.target.value)} className={inputCls} required maxLength={100} />
                    </div>
                    <div>
                        <label className={labelCls}>Fecha de Documento *</label>
                        <input type="datetime-local" value={dateDocument} onChange={(e) => setDateDocument(e.target.value)} className={inputCls} required />
                    </div>
                    <div>
                        <label className={labelCls}>Estado *</label>
                        <select value={stateId} onChange={(e) => setStateId(e.target.value)} className={inputCls} required>
                            <option value="">Seleccione</option>
                            {(options.states || []).map((s) => (
                                <option key={s.id} value={s.id}>{s.title}</option>
                            ))}
                        </select>
                    </div>
                    <div>
                        <label className={labelCls}>Fecha de Inicio *</label>
                        <input type="date" value={dateStart} onChange={(e) => setDateStart(e.target.value)} className={inputCls} required />
                    </div>
                    <div>
                        <label className={labelCls}>Fecha de Fin *</label>
                        <input type="date" value={dateEnd} onChange={(e) => setDateEnd(e.target.value)} className={inputCls} required />
                    </div>
                </div>
                <div className="flex justify-end gap-3 mt-6 pt-4 border-t-2 border-wheat">
                    <button type="button" onClick={onClose} className="btn-secondary">Cancelar</button>
                    <button type="submit" disabled={submitting} className="btn-primary">
                        <i className={`fas ${submitting ? 'fa-spinner fa-spin' : 'fa-save'} mr-2`} />
                        {mode === 'edit' ? 'Actualizar' : 'Guardar'}
                    </button>
                </div>
            </form>
        </Modal>
    );
}

function ReconocimientoViewModal({ resolution, onClose, onEdit, onOpenExterna }) {
    if (!resolution) return null;
    const status = vigenciaBadge(resolution);
    return (
        <Modal open onClose={onClose} title="Detalle de la Resolución" icon="fa-eye" iconClass="text-[#0284C7]" maxWidth="sm:max-w-lg">
            <div className="p-6 grid grid-cols-2 gap-4">
                <div className="col-span-2">
                    <p className="text-[11px] font-bold text-earth uppercase tracking-wider mb-1">Documento</p>
                    <p className="font-semibold text-charcoal">{resolution.document || '-'}</p>
                </div>
                <div>
                    <p className="text-[11px] font-bold text-earth uppercase tracking-wider mb-1">F. Documento</p>
                    <p className="font-semibold text-charcoal">{fmtDateTime(resolution.date_document)}</p>
                </div>
                <div>
                    <p className="text-[11px] font-bold text-earth uppercase tracking-wider mb-1">Estado</p>
                    <span className={`badge ${stateBadge(resolution.state).cls}`}>{stateBadge(resolution.state).label}</span>
                </div>
                <div>
                    <p className="text-[11px] font-bold text-earth uppercase tracking-wider mb-1">Inicio</p>
                    <p className="font-semibold text-charcoal">{fmtDate(resolution.date_start)}</p>
                </div>
                <div>
                    <p className="text-[11px] font-bold text-earth uppercase tracking-wider mb-1">Fin</p>
                    <p className="font-semibold text-charcoal">{fmtDate(resolution.date_end)}</p>
                </div>
                <div className="col-span-2">
                    <p className="text-[11px] font-bold text-earth uppercase tracking-wider mb-1">Vigencia</p>
                    <span className={`px-3 py-1 rounded-full text-xs font-bold ${status.cls}`}>{status.label}</span>
                </div>
            </div>
            <div className="flex justify-end gap-3 px-6 pb-6">
                <button type="button" onClick={onClose} className="btn-secondary">Cerrar</button>
                <button type="button" onClick={() => { onClose(); onOpenExterna(); }} className="btn-primary">
                    <i className="fas fa-external-link-alt mr-2" /> Consultar en Portal
                </button>
            </div>
        </Modal>
    );
}

const ReconocimientosTab = forwardRef(function ReconocimientosTab({ options, can }, ref) {
    const toast = useToast();
    const [data, setData] = useState(null);
    const [loading, setLoading] = useState(true);
    const [filters, setFilters] = useState({ search: '', state_id: '', vigencia: 'vigentes', anio: '' });
    const [page, setPage] = useState(1);
    const [formOpen, setFormOpen] = useState(false);
    const [formMode, setFormMode] = useState('create');
    const [editing, setEditing] = useState(null);
    const [viewing, setViewing] = useState(null);
    const [deleting, setDeleting] = useState(null);
    const [externa, setExterna] = useState(null);

    const debouncedFilters = useDebounced(filters, 400);

    useEffect(() => {
        setPage(1);
    }, [debouncedFilters]);

    const load = useCallback(async () => {
        setLoading(true);
        try {
            const params = { per_page: 10, page, vigencia: debouncedFilters.vigencia };
            if (debouncedFilters.search) params.search = debouncedFilters.search;
            if (debouncedFilters.state_id) params.state_id = debouncedFilters.state_id;
            if (debouncedFilters.anio) params.anio = debouncedFilters.anio;
            const res = await http.get(`${BASE}/reconocimientos`, { params });
            setData(res.data);
        } catch {
            toast.error('No se pudo cargar la lista de resoluciones.');
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

    const openEdit = (resolution) => {
        setEditing(resolution);
        setFormMode('edit');
        setFormOpen(true);
    };

    const confirmDelete = async () => {
        if (!deleting) return;
        try {
            await http.delete(`${BASE}/reconocimientos/${deleting.id}`);
            toast.success('Resolución eliminada exitosamente.');
            setDeleting(null);
            load();
        } catch (err) {
            toast.error(errorMessage(err, 'No se pudo eliminar la resolución.'));
            setDeleting(null);
        }
    };

    return (
        <>
            <form onSubmit={(e) => e.preventDefault()} className="mb-4 sm:mb-6 flex flex-col sm:flex-row gap-2 sm:gap-4 flex-wrap">
                <div className="w-full sm:w-72">
                    <input
                        type="text"
                        value={filters.search}
                        onChange={(e) => setFilter('search', e.target.value)}
                        placeholder="Buscar por documento..."
                        className={inputCls}
                    />
                </div>
                <div className="flex-1 min-w-36">
                    <select value={filters.state_id} onChange={(e) => setFilter('state_id', e.target.value)} className={inputCls}>
                        <option value="">Todos los Estados</option>
                        {(options.states || []).map((s) => (
                            <option key={s.id} value={s.id}>{s.title}</option>
                        ))}
                    </select>
                </div>
                <div className="flex-1 min-w-36">
                    <select value={filters.vigencia} onChange={(e) => setFilter('vigencia', e.target.value)} className={inputCls}>
                        <option value="vigentes">Vigentes</option>
                        <option value="vencidas">Vencidas</option>
                        <option value="">Todas</option>
                    </select>
                </div>
                <div className="flex-1 min-w-32">
                    <select value={filters.anio} onChange={(e) => setFilter('anio', e.target.value)} className={inputCls}>
                        <option value="">Todos los Años</option>
                        {(options.years || []).map((y) => (
                            <option key={y} value={y}>{y}</option>
                        ))}
                    </select>
                </div>
                <div className="flex gap-2">
                    <button
                        type="button"
                        onClick={() => {
                            setFilters({ search: '', state_id: '', vigencia: 'vigentes', anio: '' });
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
                    <i className="fas fa-spinner fa-spin mr-2" /> Cargando resoluciones...
                </div>
            )}

            {data && (
                <div className="overflow-x-auto -mx-4 sm:mx-0">
                    <table className="data-table w-full text-xs sm:text-sm min-w-[720px]">
                        <thead>
                            <tr>
                                <th className="px-3 sm:px-4 py-3 text-left">ID</th>
                                <th className="px-3 sm:px-4 py-3 text-left">Documento</th>
                                <th className="px-3 sm:px-4 py-3 text-left">F. Documento</th>
                                <th className="px-3 sm:px-4 py-3 text-left">Vigencia</th>
                                <th className="px-3 sm:px-4 py-3 text-left">Estado</th>
                                <th className="px-3 sm:px-4 py-3 text-center">Acciones</th>
                            </tr>
                        </thead>
                        <tbody>
                            {data.data.length === 0 ? (
                                <tr>
                                    <td colSpan={6}>
                                        <div className="empty-state">
                                            <i className="fas fa-scroll" />
                                            <p>No hay resoluciones registradas</p>
                                        </div>
                                    </td>
                                </tr>
                            ) : (
                                data.data.map((resolution) => {
                                    const status = vigenciaBadge(resolution);
                                    return (
                                        <tr key={resolution.id} className="row-enter">
                                            <td className="px-3 sm:px-4 py-3 text-earth font-mono">#{resolution.id}</td>
                                            <td className="px-3 sm:px-4 py-3 font-semibold">{resolution.document || '-'}</td>
                                            <td className="px-3 sm:px-4 py-3 text-earth">{fmtDateTime(resolution.date_document)}</td>
                                            <td className="px-3 sm:px-4 py-3">
                                                <span className={`px-2.5 py-1 text-xs font-bold rounded-full ${status.cls}`}>{status.label}</span>
                                            </td>
                                            <td className="px-3 sm:px-4 py-3">
                                                <span className={`badge ${stateBadge(resolution.state).cls}`}>{stateBadge(resolution.state).label}</span>
                                            </td>
                                            <td className="px-3 sm:px-4 py-3 text-center">
                                                <div className="flex items-center justify-center gap-1 sm:gap-2">
                                                    <button
                                                        type="button"
                                                        onClick={() => setExterna(resolution)}
                                                        className="btn-action bg-blue-light text-[#1E5799] hover:bg-blue hover:text-white"
                                                        title="Consultar en el portal municipal"
                                                    >
                                                        <i className="fas fa-external-link-alt" />
                                                    </button>
                                                    <button
                                                        type="button"
                                                        onClick={() => setViewing(resolution)}
                                                        className="btn-action bg-sky-light text-[#0284C7] hover:bg-sky hover:text-white"
                                                        title="Ver"
                                                    >
                                                        <i className="fas fa-eye" />
                                                    </button>
                                                    {can.edit && (
                                                        <button
                                                            type="button"
                                                            onClick={() => openEdit(resolution)}
                                                            className="btn-action bg-sun-light text-[#D97706] hover:bg-sun hover:text-white"
                                                            title="Editar"
                                                        >
                                                            <i className="fas fa-edit" />
                                                        </button>
                                                    )}
                                                    {can.del && (
                                                        <button
                                                            type="button"
                                                            onClick={() => setDeleting(resolution)}
                                                            className="btn-action bg-clay-light text-clay hover:bg-clay hover:text-white"
                                                            title="Eliminar"
                                                        >
                                                            <i className="fas fa-trash" />
                                                        </button>
                                                    )}
                                                </div>
                                            </td>
                                        </tr>
                                    );
                                })
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
                <ReconocimientoFormModal
                    key={editing ? editing.id : 'create'}
                    mode={formMode}
                    resolution={editing}
                    options={options}
                    onClose={() => setFormOpen(false)}
                    onSaved={() => {
                        setFormOpen(false);
                        load();
                    }}
                />
            )}

            <ReconocimientoViewModal
                resolution={viewing}
                onClose={() => setViewing(null)}
                onEdit={() => viewing && openEdit(viewing)}
                onOpenExterna={() => viewing && setExterna(viewing)}
            />

            <ResolucionExternaModal
                key={externa ? externa.id : 'none'}
                open={!!externa}
                resolution={externa}
                onClose={() => setExterna(null)}
            />

            <ConfirmDialog
                open={!!deleting}
                onCancel={() => setDeleting(null)}
                onConfirm={confirmDelete}
                title="Eliminar Resolución"
                message="Se eliminará esta resolución de forma permanente."
                details={deleting ? [
                    { label: 'Documento', value: deleting.document },
                    { label: 'Fecha', value: fmtDateTime(deleting.date_document) },
                ] : []}
            />
        </>
    );
});

export default ReconocimientosTab;
