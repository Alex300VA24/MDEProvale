import { forwardRef, useCallback, useEffect, useImperativeHandle, useState } from 'react';
import http from '../../http';
import { useToast } from '../../Components/Toast';
import Modal from '../../Components/Modal';
import DetailModal, { DetailGroup, Field, FieldGrid } from '../../Components/DetailModal';
import ConfirmDialog from '../../Components/ConfirmDialog';
import Pagination from '../../Components/Pagination';
import ResolucionExternaModal from './ResolucionExternaModal';
import { useDebounced } from '../socios/hooks';
import { fmtDate, stateBadge } from './format';
import errorMessage from '../../errorMessage';

const BASE = '/api/dashboard/club-madres';
const CURRENT_YEAR = new Date().getFullYear();
const DOCUMENT_PATTERN = new RegExp(`^\\d{4}-${CURRENT_YEAR}$`);

const labelCls = 'block text-xs font-bold text-slate-600 uppercase tracking-wider mb-1';
const inputCls =
    'w-full px-4 py-2.5 border-2 border-wheat rounded-xl text-xs sm:text-sm font-semibold text-charcoal bg-white focus:outline-none focus:border-leaf transition-all';

const MONTHS = [
    'Enero',
    'Febrero',
    'Marzo',
    'Abril',
    'Mayo',
    'Junio',
    'Julio',
    'Agosto',
    'Septiembre',
    'Octubre',
    'Noviembre',
    'Diciembre',
];

const fmtPeriod = (resolution) => {
    const start = fmtDate(resolution.date_start);
    const end = fmtDate(resolution.date_end);

    if (!start && !end) return 'Sin periodo';
    return `${start || 'Sin inicio'} - ${end || 'Sin fin'}`;
};

function ReconocimientoFormModal({ mode, resolution, onClose, onSaved }) {
    const toast = useToast();
    const [document, setDocument] = useState(mode === 'edit' && resolution ? resolution.document || '' : '');
    const [dateStart, setDateStart] = useState(mode === 'edit' && resolution ? resolution.date_start || '' : '');
    const [dateEnd, setDateEnd] = useState(mode === 'edit' && resolution ? resolution.date_end || '' : '');
    const [submitting, setSubmitting] = useState(false);
    const [pending, setPending] = useState(false);
    const [observation, setObservation] = useState('');
    const isPendingResolution = mode === 'edit' && resolution?.state?.abbreviation === 'PEN';
    const requiresCurrentFormat = mode === 'create' || isPendingResolution;

    const handleSubmit = async (e) => {
        e.preventDefault();
        if (!document || !dateStart || !dateEnd) {
            toast.error('Complete los campos obligatorios de la resolución.');
            return;
        }
        if (requiresCurrentFormat && !DOCUMENT_PATTERN.test(document.trim())) {
            toast.error(`El documento debe tener el formato 1234-${CURRENT_YEAR}.`);
            return;
        }
        if (pending && !observation.trim()) {
            toast.error('La observación es obligatoria para una resolución Pendiente.');
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
                date_start: dateStart,
                date_end: dateEnd,
            };
            if (isPendingResolution) {
                // Pendiente: al actualizar se valida el número en el portal municipal.
                // Si no existe, el backend responde error y el modal permanece abierto.
                await http.post(`${BASE}/reconocimientos/${resolution.id}/regularizar`, {
                    ...payload,
                    document: document.trim(),
                });
                toast.success('Resolución verificada en el portal: ahora está Vigente.');
            } else if (mode === 'edit') {
                await http.put(`${BASE}/reconocimientos/${resolution.id}`, payload);
                toast.success('Resolución actualizada correctamente.');
            } else {
                if (!pending) {
                    // Verifica en el portal municipal antes de guardar (bloquea si no existe).
                    await http.get(`${BASE}/reconocimientos/verificar-externa`, {
                        params: { document: document.trim() },
                        timeout: 60000,
                    });
                }
                await http.post(`${BASE}/reconocimientos`, {
                    ...payload,
                    pending,
                    observation: pending ? observation.trim() : null,
                }, { timeout: 60000 });
                toast.success(
                    pending
                        ? 'Resolución registrada como Pendiente. Se generó una notificación de Regularización.'
                        : 'Resolución creada exitosamente.'
                );
            }
            window.dispatchEvent(new CustomEvent('notifications:changed'));
            onSaved();
        } catch (err) {
            toast.error(errorMessage(
                err,
                isPendingResolution ? 'La resolución es incorrecta o no se encontró en el portal.' : 'Ocurrió un error al guardar la resolución.'
            ));
        } finally {
            setSubmitting(false);
        }
    };

    return (
        <Modal
            open
            onClose={onClose}
            title={mode === 'edit' ? 'Editar Resolución' : 'Registrar Resolución'}
            icon={mode === 'edit' ? 'fa-edit' : 'fa-plus-circle'}
            iconClass={mode === 'edit' ? 'text-sun' : 'text-leaf'}
            maxWidth="sm:max-w-2xl"
        >
            <form onSubmit={handleSubmit} className="p-6">
                <div className="grid grid-cols-1 sm:grid-cols-2 gap-4">
                    <div className="sm:col-span-2">
                        <label className={labelCls}>Documento *</label>
                        <input
                            type="text"
                            value={document}
                            onChange={(e) => setDocument(requiresCurrentFormat
                                ? e.target.value.replace(/[^\d-]/g, '').slice(0, 9)
                                : e.target.value.slice(0, 100))}
                            className={inputCls}
                            required
                            maxLength={requiresCurrentFormat ? 9 : 100}
                            pattern={requiresCurrentFormat ? `[0-9]{4}-${CURRENT_YEAR}` : undefined}
                            placeholder={`1234-${CURRENT_YEAR}`}
                            aria-describedby="resolution-document-help"
                        />
                        <p id="resolution-document-help" className="mt-1 text-xs text-earth">
                            {requiresCurrentFormat
                                ? <>Formato obligatorio: 4 dígitos y año actual. Ejemplo: 1234-{CURRENT_YEAR}.</>
                                : 'Conserve el número original de la resolución histórica.'}
                        </p>
                    </div>
                    <div>
                        <label className={labelCls}>Fecha de Inicio *</label>
                        <input type="date" value={dateStart} onChange={(e) => setDateStart(e.target.value)} className={inputCls} required />
                    </div>
                    <div>
                        <label className={labelCls}>Fecha de Fin *</label>
                        <input type="date" value={dateEnd} onChange={(e) => setDateEnd(e.target.value)} className={inputCls} required />
                    </div>

                    {mode === 'create' && (
                        <>
                            <div className="sm:col-span-2">
                                <label className="flex items-center gap-2 text-sm font-semibold text-charcoal cursor-pointer">
                                    <input
                                        type="checkbox"
                                        checked={pending}
                                        onChange={(e) => {
                                            setPending(e.target.checked);
                                            if (!e.target.checked) setObservation('');
                                        }}
                                        className="h-4 w-4 accent-leaf"
                                    />
                                    Registrar como Pendiente (por regularizar)
                                </label>
                            </div>
                            <div className="sm:col-span-2">
                                <label className={labelCls}>Observación {pending && '*'}</label>
                                <textarea
                                    value={observation}
                                    onChange={(e) => setObservation(e.target.value)}
                                    disabled={!pending}
                                    required={pending}
                                    maxLength={1000}
                                    rows={3}
                                    placeholder={pending ? 'Motivo por el cual la resolución queda Pendiente' : 'Marque "Pendiente" para habilitar'}
                                    className={`${inputCls} disabled:opacity-60 disabled:cursor-not-allowed`}
                                />
                            </div>
                        </>
                    )}

                    {isPendingResolution && (
                        <div className="sm:col-span-2 rounded-xl border-2 border-sun bg-sun-light/40 p-3 text-sm">
                            <p className="font-bold text-charcoal mb-1">Resolución Pendiente</p>
                            {resolution.observation && <p className="text-earth mb-2">Motivo: {resolution.observation}</p>}
                            <p className="text-earth mb-2">
                                Ingrese el número de resolución correcto en «Documento» y presione Actualizar. Se verificará
                                que exista en el portal municipal antes de pasarla a Vigente.
                            </p>
                        </div>
                    )}
                </div>
                <div className="flex gap-3 mt-6 pt-4 border-t-2 border-wheat">
                    <button type="button" onClick={onClose} className="btn-secondary flex-1">Cancelar</button>
                    <button type="submit" disabled={submitting} className="btn-primary flex-1">
                        <i className={`fas ${submitting ? 'fa-spinner fa-spin' : 'fa-save'} mr-2`} />
                        {mode === 'edit' ? 'Actualizar' : 'Guardar'}
                    </button>
                </div>
            </form>
        </Modal>
    );
}

function ReconocimientoViewModal({ resolution, onClose }) {
    if (!resolution) return null;
    return (
        <DetailModal open onClose={onClose} title="Detalle de la resolución" icon="fa-file-contract" maxWidth="sm:max-w-lg">
            <DetailGroup>
                <Field label="Documento" wide>
                    <span className="text-base font-bold text-charcoal">{resolution.document || '—'}</span>
                </Field>
                <FieldGrid>
                    <Field label="Estado">
                        <span className={`badge ${stateBadge(resolution.state).cls}`}>{stateBadge(resolution.state).label}</span>
                    </Field>
                    <Field label="Periodo" value={fmtPeriod(resolution)} />
                </FieldGrid>
            </DetailGroup>
            <DetailGroup title="Comités asociados" icon="fa-people-roof">
                {(resolution.associations || []).length > 0 ? (
                    <div className="flex flex-wrap gap-2">
                        {resolution.associations.map((association) => (
                            <span key={association.id} className="badge badge-unknown">
                                {association.code ? `${association.code} · ` : ''}{association.name}
                            </span>
                        ))}
                    </div>
                ) : (
                    <p className="text-sm text-earth">Sin comités asociados</p>
                )}
            </DetailGroup>
        </DetailModal>
    );
}

const ReconocimientosTab = forwardRef(function ReconocimientosTab({ options, can, onResolutionsChanged }, ref) {
    const toast = useToast();
    const [data, setData] = useState(null);
    const [loading, setLoading] = useState(true);
    const [filters, setFilters] = useState({ search: '', state_id: '', anio: '', mes: '' });
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
            const params = { per_page: 10, page };
            if (debouncedFilters.search) params.search = debouncedFilters.search;
            if (debouncedFilters.state_id) params.state_id = debouncedFilters.state_id;
            if (debouncedFilters.anio) params.anio = debouncedFilters.anio;
            if (debouncedFilters.mes) params.mes = debouncedFilters.mes;
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
        reload: load,
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
            window.dispatchEvent(new CustomEvent('notifications:changed'));
            setDeleting(null);
            load();
            onResolutionsChanged?.();
        } catch (err) {
            toast.error(errorMessage(err, 'No se pudo eliminar la resolución.'));
            setDeleting(null);
        }
    };

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
                            aria-hidden="true"
                        />
                        <input
                            type="text"
                            value={filters.search}
                            onChange={(e) => setFilter('search', e.target.value)}
                            placeholder="Buscar por documento o comité..."
                            className="w-full pl-10 pr-4 py-2.5 border-2 border-wheat rounded-xl text-xs sm:text-sm font-semibold text-charcoal bg-white focus:outline-none focus:border-leaf transition-all"
                        />
                    </div>
                </div>
                <div className="w-full sm:w-36">
                    <label className={labelCls}>Mes</label>
                    <select value={filters.mes} onChange={(e) => setFilter('mes', e.target.value)} className={inputCls}>
                        <option value="">Todos</option>
                        {MONTHS.map((month, index) => (
                            <option key={month} value={index + 1}>{month}</option>
                        ))}
                    </select>
                </div>
                <div className="w-full sm:w-28">
                    <label className={labelCls}>Año</label>
                    <select value={filters.anio} onChange={(e) => setFilter('anio', e.target.value)} className={inputCls}>
                        <option value="">Años</option>
                        {(options.years || []).map((y) => (
                            <option key={y} value={y}>{y}</option>
                        ))}
                    </select>
                </div>
                <div className="w-full sm:w-40 lg:w-40 shrink-0">
                    <label className={labelCls}>Estado</label>
                    <select value={filters.state_id} onChange={(e) => setFilter('state_id', e.target.value)} className={inputCls}>
                        <option value="">Estados</option>
                        {(options.resolution_states || []).map((state) => (
                            <option key={state.id} value={state.id}>{state.title}</option>
                        ))}
                    </select>
                </div>
                
                <div className="w-full sm:w-auto shrink-0 flex flex-col">
                    <button
                        type="button"
                        onClick={() => {
                            setFilters({ search: '', state_id: '', anio: '', mes: '' });
                            setPage(1);
                        }}
                        className="flex items-center gap-1.5 text-xs sm:text-sm font-bold text-leaf border border-leaf rounded-md px-2.5 py-1.5 hover:opacity-80 whitespace-nowrap shrink-0 self-end"
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
                    <i className="fas fa-spinner fa-spin mr-2" /> Cargando resoluciones...
                </div>
            )}

            {data && (
                <div className="overflow-x-auto -mx-4 sm:mx-0">
                    <table className="data-table w-full text-xs sm:text-sm min-w-[760px]">
                        <thead>
                            <tr>
                                <th className="px-3 sm:px-4 py-3 text-left">Documento</th>
                                <th className="px-3 sm:px-4 py-3 text-left">Periodo</th>
                                <th className="px-3 sm:px-4 py-3 text-left">Comités</th>
                                <th className="px-3 sm:px-4 py-3 text-left">Estado</th>
                                <th className="px-3 sm:px-4 py-3 text-center">Acciones</th>
                            </tr>
                        </thead>
                        <tbody>
                            {data.data.length === 0 ? (
                                <tr>
                                    <td colSpan={5}>
                                        <div className="empty-state">
                                            <i className="fas fa-scroll" />
                                            <p>No hay resoluciones registradas</p>
                                        </div>
                                    </td>
                                </tr>
                            ) : (
                                data.data.map((resolution) => {
                                    return (
                                        <tr key={resolution.id} className="row-enter">
                                            <td className="px-3 sm:px-4 py-3 font-semibold">{resolution.document || '-'}</td>
                                            <td className="px-3 sm:px-4 py-3 text-earth tabular-nums whitespace-nowrap">{fmtPeriod(resolution)}</td>
                                            <td className="px-3 sm:px-4 py-3">
                                                {(resolution.associations || []).length > 0 ? (
                                                    <div className="flex flex-wrap gap-1.5">
                                                        {resolution.associations.map((association) => (
                                                            <span key={association.id} className="badge badge-unknown">
                                                                {association.name}
                                                            </span>
                                                        ))}
                                                    </div>
                                                ) : (
                                                    <span className="text-xs text-earth">Sin comités</span>
                                                )}
                                            </td>
                                            <td className="px-3 sm:px-4 py-3">
                                                <span className={`badge ${stateBadge(resolution.state).cls}`}>{stateBadge(resolution.state).label}</span>
                                            </td>
                                            <td className="px-3 sm:px-4 py-3 text-center">
                                                <div className="inline-grid grid-cols-[repeat(4,2.25rem)] items-center justify-items-center gap-1 sm:gap-2">
                                                    <button
                                                        type="button"
                                                        onClick={() => setExterna(resolution)}
                                                        className="btn-action col-start-1 bg-blue-light text-[#1E5799] hover:bg-blue hover:text-white"
                                                        title="Consultar en el portal municipal"
                                                    >
                                                        <i className="fas fa-external-link-alt" />
                                                    </button>
                                                    <button
                                                        type="button"
                                                        onClick={() => setViewing(resolution)}
                                                        className="btn-action col-start-2 bg-sky-light text-[#0284C7] hover:bg-sky hover:text-white"
                                                        title="Ver"
                                                    >
                                                        <i className="fas fa-eye" />
                                                    </button>
                                                    {can.edit && (
                                                        <button
                                                            type="button"
                                                            onClick={() => openEdit(resolution)}
                                                            className="btn-action col-start-3 bg-sun-light text-[#D97706] hover:bg-sun hover:text-white"
                                                            title="Editar"
                                                        >
                                                            <i className="fas fa-edit" />
                                                        </button>
                                                    )}
                                                    {can.del && (
                                                        <button
                                                            type="button"
                                                            onClick={() => setDeleting(resolution)}
                                                            className="btn-action col-start-4 bg-clay-light text-clay hover:bg-clay hover:text-white"
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
                    onClose={() => setFormOpen(false)}
                    onSaved={() => {
                        setFormOpen(false);
                        load();
                        onResolutionsChanged?.();
                    }}
                />
            )}

            <ReconocimientoViewModal
                resolution={viewing}
                onClose={() => setViewing(null)}
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
                message="Advertencia: al eliminar esta resolución también se eliminará el comité asociado a ella. Esta acción es permanente."
                details={deleting ? [
                    { label: 'Documento', value: deleting.document },
                    { label: 'Periodo', value: fmtPeriod(deleting) },
                ] : []}
            />
        </>
    );
});

export default ReconocimientosTab;
