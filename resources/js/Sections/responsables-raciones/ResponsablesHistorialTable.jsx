import { useEffect, useState } from 'react';
import http from '../../http';
import { useToast } from '../../Components/Toast';
import Pagination from '../../Components/Pagination';
import ConfirmDialog from '../../Components/ConfirmDialog';
import DetailModal, { DetailGroup, Field, FieldGrid } from '../../Components/DetailModal';
import { useDebounced } from '../socios/hooks';
import errorMessage from '../../errorMessage';

const BASE = '/api/dashboard/responsables-raciones';

const labelCls = 'block text-xs font-bold text-slate-600 uppercase tracking-wider mb-1';
const inputCls =
    'w-full px-4 py-2.5 border-2 border-wheat rounded-xl text-xs sm:text-sm font-semibold text-charcoal bg-white focus:outline-none focus:border-leaf transition-all';

const TYPE_OPTIONS = [
    { value: '', label: 'Todos los cargos' },
    { value: 'chief', label: 'Subgerente de Programas Sociales' },
    { value: 'storekeeper', label: 'Encargado de PROVALE' },
];

const VIGENCIA_OPTIONS = [
    { value: '', label: 'Todos' },
    { value: 'vigente', label: 'Vigentes' },
    { value: 'finalizado', label: 'Finalizados' },
];

function fmtDate(iso) {
    if (!iso) return null;
    const d = new Date(iso);
    if (Number.isNaN(d.getTime())) return null;
    return d.toLocaleDateString('es-PE', { day: '2-digit', month: '2-digit', year: 'numeric' });
}

function periodoLabel(row) {
    const from = fmtDate(row.start_date) ?? '—';
    const to = row.active ? 'Actualidad' : (fmtDate(row.end_date) ?? '—');
    return `${from} → ${to}`;
}

function duracionLabel(row) {
    if (row.duration_days == null) return '—';
    const d = row.duration_days;
    if (d < 1) return 'Menos de 1 día';
    if (d < 30) return `${d} día${d === 1 ? '' : 's'}`;
    if (d < 365) {
        const m = Math.floor(d / 30);
        return `${m} mes${m === 1 ? '' : 'es'}`;
    }
    const y = Math.floor(d / 365);
    const rm = Math.floor((d % 365) / 30);
    return rm > 0 ? `${y} año${y === 1 ? '' : 's'} ${rm} mes${rm === 1 ? '' : 'es'}` : `${y} año${y === 1 ? '' : 's'}`;
}

function DetalleModal({ id, onClose }) {
    const toast = useToast();
    const [row, setRow] = useState(null);

    useEffect(() => {
        let alive = true;
        http.get(`${BASE}/responsibles/detail/${id}`)
            .then((res) => {
                if (alive) setRow(res.data.data);
            })
            .catch((err) => {
                toast.error(errorMessage(err, 'No se pudo cargar el detalle.'));
                onClose();
            });
        return () => {
            alive = false;
        };
    }, [id]); // eslint-disable-line react-hooks/exhaustive-deps

    if (!row) return null;
    const person = row.person;

    return (
        <DetailModal open onClose={onClose} title="Detalle del responsable" icon="fa-user-tie" maxWidth="sm:max-w-lg">
            <DetailGroup>
                <Field label="Nombre completo" wide>
                    <span className="text-base font-bold text-charcoal">{row.person_name || '—'}</span>
                </Field>
                <FieldGrid>
                    <Field label="DNI" value={row.person_dni} mono />
                    <Field label="Cargo" value={row.type_label} />
                </FieldGrid>
            </DetailGroup>
            <DetailGroup title="Periodo" icon="fa-calendar-days">
                <FieldGrid>
                    <Field label="Inicio" value={fmtDate(row.start_date) ?? '—'} />
                    <Field label="Fin" value={row.active ? 'En curso' : (fmtDate(row.end_date) ?? '—')} />
                    <Field label="Duración" value={duracionLabel(row)} />
                    <Field label="Vigencia">
                        <span className={`badge ${row.active ? 'badge-current' : 'badge-expired'}`}>
                            {row.active ? 'Vigente' : 'Finalizado'}
                        </span>
                    </Field>
                </FieldGrid>
            </DetailGroup>
            {person && (
                <DetailGroup title="Contacto" icon="fa-address-book">
                    <FieldGrid>
                        <Field label="Celular" value={person.phone_number || person.telephone_number} />
                        <Field label="Género" value={person.gender_label} />
                    </FieldGrid>
                    <Field label="Dirección" value={person.address} wide />
                </DetailGroup>
            )}
        </DetailModal>
    );
}

export default function ResponsablesHistorialTable({ can }) {
    const toast = useToast();
    const [data, setData] = useState(null);
    const [loading, setLoading] = useState(true);
    const [filters, setFilters] = useState({ search: '', type: '', vigencia: '' });
    const [page, setPage] = useState(1);
    const [viewingId, setViewingId] = useState(null);
    const [ending, setEnding] = useState(null);

    const debounced = useDebounced(filters, 400);

    useEffect(() => {
        setPage(1);
    }, [debounced]);

    const load = async () => {
        setLoading(true);
        try {
            const params = { per_page: 10, page };
            if (debounced.search) params.search = debounced.search;
            if (debounced.type) params.type = debounced.type;
            if (debounced.vigencia) params.vigencia = debounced.vigencia;
            const res = await http.get(`${BASE}/responsibles/history`, { params });
            setData(res.data);
        } catch {
            toast.error('No se pudo cargar el historial de responsables.');
        } finally {
            setLoading(false);
        }
    };

    useEffect(() => {
        load();
        // eslint-disable-next-line react-hooks/exhaustive-deps
    }, [debounced, page]);

    const setFilter = (key, value) => setFilters((prev) => ({ ...prev, [key]: value }));
    const hasFilters = filters.search || filters.type || filters.vigencia;

    const confirmEnd = async () => {
        if (!ending) return;
        try {
            await http.post(`${BASE}/responsibles/${ending.id}/end`);
            toast.success('Periodo finalizado correctamente.');
            setEnding(null);
            load();
        } catch (err) {
            toast.error(errorMessage(err, 'No se pudo finalizar el periodo.'));
            setEnding(null);
        }
    };

    return (
        <div className="mt-6 border-t-2 border-wheat pt-6">
            <h4 className="font-extrabold text-charcoal text-lg flex items-center gap-2 mb-1">
                <i className="fas fa-clock-rotate-left text-leaf" /> Historial de Responsables
            </h4>
            <p className="text-earth text-xs sm:text-sm mb-4">
                Personas que han ocupado un cargo, su periodo y si continúan vigentes.
            </p>

            <div className="bg-gray-50 rounded-xl p-3 sm:p-4 mb-4">
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
                                placeholder="Buscar por nombre o DNI"
                                className="w-full pl-10 pr-4 py-2.5 border-2 border-wheat rounded-xl text-xs sm:text-sm font-semibold text-charcoal bg-white focus:outline-none focus:border-leaf transition-all"
                            />
                        </div>
                    </div>
                    <div className="w-full sm:w-64 shrink-0">
                        <label className={labelCls}>Cargo</label>
                        <select value={filters.type} onChange={(e) => setFilter('type', e.target.value)} className={inputCls}>
                            {TYPE_OPTIONS.map((o) => (
                                <option key={o.value} value={o.value}>{o.label}</option>
                            ))}
                        </select>
                    </div>
                    <div className="w-full sm:w-40 shrink-0">
                        <label className={labelCls}>Vigencia</label>
                        <select value={filters.vigencia} onChange={(e) => setFilter('vigencia', e.target.value)} className={inputCls}>
                            {VIGENCIA_OPTIONS.map((o) => (
                                <option key={o.value} value={o.value}>{o.label}</option>
                            ))}
                        </select>
                    </div>
                    {hasFilters && (
                        <button
                            type="button"
                            onClick={() => setFilters({ search: '', type: '', vigencia: '' })}
                            className="flex items-center gap-1.5 text-xs sm:text-sm font-bold text-leaf border border-leaf rounded-md px-2.5 py-2 hover:opacity-80 whitespace-nowrap shrink-0"
                        >
                            <i className="fa-solid fa-eraser" /> Limpiar
                        </button>
                    )}
                </form>
            </div>

            {loading && !data && (
                <div className="flex items-center justify-center py-10 text-earth">
                    <i className="fas fa-spinner fa-spin mr-2" /> Cargando historial...
                </div>
            )}

            {data && (
                <div className="overflow-x-auto -mx-4 sm:mx-0">
                    <table className="data-table w-full text-xs sm:text-sm min-w-[720px]">
                        <thead>
                            <tr>
                                <th className="px-3 sm:px-4 py-3 text-left">Nombre</th>
                                <th className="px-3 sm:px-4 py-3 text-left">DNI</th>
                                <th className="px-3 sm:px-4 py-3 text-left">Cargo</th>
                                <th className="px-3 sm:px-4 py-3 text-left">Periodo</th>
                                <th className="px-3 sm:px-4 py-3 text-left">Duración</th>
                                <th className="px-3 sm:px-4 py-3 text-center">Vigencia</th>
                                <th className="px-3 sm:px-4 py-3 text-center">Acciones</th>
                            </tr>
                        </thead>
                        <tbody>
                            {data.data.length === 0 ? (
                                <tr>
                                    <td colSpan={7}>
                                        <div className="empty-state">
                                            <i className="fas fa-user-clock" />
                                            <p>No hay responsables en el historial</p>
                                        </div>
                                    </td>
                                </tr>
                            ) : (
                                data.data.map((row) => (
                                    <tr key={row.id} className="row-enter">
                                        <td className="px-3 sm:px-4 py-3 font-medium">{row.person_name || '—'}</td>
                                        <td className="px-3 sm:px-4 py-3 font-mono">{row.person_dni || '—'}</td>
                                        <td className="px-3 sm:px-4 py-3">{row.type_label}</td>
                                        <td className="px-3 sm:px-4 py-3 whitespace-nowrap">{periodoLabel(row)}</td>
                                        <td className="px-3 sm:px-4 py-3">{duracionLabel(row)}</td>
                                        <td className="px-3 sm:px-4 py-3 text-center">
                                            <span className={`badge ${row.active ? 'badge-current' : 'badge-expired'}`}>
                                                {row.active ? 'Vigente' : 'Finalizado'}
                                            </span>
                                        </td>
                                        <td className="px-3 sm:px-4 py-3 text-center">
                                            <div className="inline-grid grid-cols-[repeat(2,2.25rem)] items-center justify-items-center gap-2">
                                                <button
                                                    type="button"
                                                    onClick={() => setViewingId(row.id)}
                                                    className="btn-action col-start-1 bg-sky-light text-[#0284C7] hover:bg-sky hover:text-white"
                                                    title="Ver detalle"
                                                >
                                                    <i className="fas fa-eye" />
                                                </button>
                                                {can.edit && row.active && (
                                                    <button
                                                        type="button"
                                                        onClick={() => setEnding(row)}
                                                        className="btn-action col-start-2 bg-clay-light text-clay hover:bg-clay hover:text-white"
                                                        title="Finalizar periodo"
                                                    >
                                                        <i className="fas fa-flag-checkered" />
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

            {viewingId && <DetalleModal id={viewingId} onClose={() => setViewingId(null)} />}

            <ConfirmDialog
                open={!!ending}
                onCancel={() => setEnding(null)}
                onConfirm={confirmEnd}
                title="Finalizar periodo"
                message="Se marcará el periodo de este responsable como terminado. El cargo quedará sin responsable vigente hasta asignar uno nuevo."
                details={ending ? [
                    { label: 'Nombre', value: ending.person_name },
                    { label: 'DNI', value: ending.person_dni },
                    { label: 'Cargo', value: ending.type_label },
                ] : []}
            />
        </div>
    );
}
