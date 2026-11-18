import { useCallback, useEffect, useState } from 'react';
import http from '../../http';
import { useToast } from '../../Components/Toast';
import errorMessage from '../../errorMessage';

const BASE = '/api/dashboard/sistema/change-history';

const MODULE_LABELS = {
    socios: 'Socios',
    beneficiarios: 'Beneficiarios',
    personas: 'Personas',
    comites: 'Comités',
    resoluciones: 'Resoluciones',
    pecosas: 'Pecosas',
    usuarios: 'Usuarios',
};

const ACTION_STYLES = {
    created: 'bg-leaf-light text-charcoal',
    updated: 'bg-blue-100 text-blue-800',
    deleted: 'bg-red-100 text-red-800',
};

const EMPTY_FILTERS = { search: '', module: '', action: '', user_id: '', from: '', to: '' };

export default function HistorialCambiosTab() {
    const toast = useToast();
    const [rows, setRows] = useState([]);
    const [meta, setMeta] = useState({ current_page: 1, last_page: 1 });
    const [filters, setFilters] = useState(EMPTY_FILTERS);
    const [options, setOptions] = useState({ modules: [], actions: [], users: [] });
    const [loading, setLoading] = useState(true);
    const [openId, setOpenId] = useState(null);
    const [detail, setDetail] = useState(null);
    const [detailLoading, setDetailLoading] = useState(false);

    const setFilter = (key) => (event) => setFilters((prev) => ({ ...prev, [key]: event.target.value }));

    useEffect(() => {
        http.get(`${BASE}/options`)
            .then(({ data }) => setOptions(data.data))
            .catch((error) => toast.error(errorMessage(error, 'No se pudieron cargar los filtros.')));
        // eslint-disable-next-line react-hooks/exhaustive-deps
    }, []);

    const load = useCallback(async (page = 1) => {
        setLoading(true);
        try {
            const params = Object.fromEntries(Object.entries(filters).filter(([, value]) => value !== ''));
            const { data } = await http.get(BASE, { params: { ...params, page } });
            setRows(data.data ?? []);
            setMeta({ current_page: data.current_page ?? 1, last_page: data.last_page ?? 1 });
        } catch (error) {
            toast.error(errorMessage(error, 'No se pudo cargar el historial de cambios.'));
        } finally {
            setLoading(false);
        }
        // eslint-disable-next-line react-hooks/exhaustive-deps
    }, [filters]);

    useEffect(() => {
        const timer = setTimeout(() => load(1), 250);
        return () => clearTimeout(timer);
    }, [load]);

    const toggle = async (id) => {
        if (openId === id) {
            setOpenId(null);
            return;
        }
        setOpenId(id);
        setDetail(null);
        setDetailLoading(true);
        try {
            const { data } = await http.get(`${BASE}/${id}`);
            setDetail(data.data);
        } catch (error) {
            toast.error(errorMessage(error, 'No se pudo cargar el detalle.'));
            setOpenId(null);
        } finally {
            setDetailLoading(false);
        }
    };

    return (
        <div className="space-y-4">
            <div className="grid grid-cols-1 gap-2 sm:grid-cols-2 lg:grid-cols-6">
                <input type="search" value={filters.search} onChange={setFilter('search')} placeholder="Buscar usuario o registro" className="form-input lg:col-span-2" aria-label="Buscar en el historial" />
                <select value={filters.module} onChange={setFilter('module')} className="form-input" aria-label="Módulo">
                    <option value="">Todos los módulos</option>
                    {options.modules.map((m) => <option key={m} value={m}>{MODULE_LABELS[m] ?? m}</option>)}
                </select>
                <select value={filters.action} onChange={setFilter('action')} className="form-input" aria-label="Acción">
                    <option value="">Todas las acciones</option>
                    {options.actions.map((a) => <option key={a.value} value={a.value}>{a.label}</option>)}
                </select>
                <select value={filters.user_id} onChange={setFilter('user_id')} className="form-input" aria-label="Usuario">
                    <option value="">Todos los usuarios</option>
                    {options.users.map((u) => <option key={u.user_id} value={u.user_id}>{u.user_name}</option>)}
                </select>
                <div className="flex gap-2">
                    <input type="date" value={filters.from} onChange={setFilter('from')} className="form-input" aria-label="Desde" />
                    <input type="date" value={filters.to} onChange={setFilter('to')} className="form-input" aria-label="Hasta" />
                </div>
            </div>

            <div className="overflow-x-auto rounded-xl border-2 border-wheat">
                <table className="w-full min-w-[820px] text-left text-sm">
                    <thead className="bg-fog text-xs uppercase tracking-wide text-earth">
                        <tr>
                            <th className="px-4 py-3">Fecha</th>
                            <th className="px-4 py-3">Usuario</th>
                            <th className="px-4 py-3">Módulo</th>
                            <th className="px-4 py-3">Acción</th>
                            <th className="px-4 py-3">Registro</th>
                            <th className="px-4 py-3 text-right">Detalle</th>
                        </tr>
                    </thead>
                    <tbody className="divide-y divide-wheat">
                        {loading ? (
                            <tr><td colSpan="6" className="px-4 py-10 text-center text-earth"><i className="fas fa-spinner fa-spin mr-2" />Cargando historial…</td></tr>
                        ) : rows.length === 0 ? (
                            <tr><td colSpan="6" className="px-4 py-10 text-center text-earth">No hay cambios registrados.</td></tr>
                        ) : rows.map((row) => (
                            <FragmentRow
                                key={row.id}
                                row={row}
                                open={openId === row.id}
                                detail={openId === row.id ? detail : null}
                                detailLoading={openId === row.id && detailLoading}
                                onToggle={() => toggle(row.id)}
                            />
                        ))}
                    </tbody>
                </table>
            </div>

            <div className="flex items-center justify-end gap-2">
                <button type="button" onClick={() => load(meta.current_page - 1)} disabled={loading || meta.current_page <= 1} className="btn-secondary disabled:opacity-40">Anterior</button>
                <span className="text-sm font-bold text-earth">{meta.current_page} / {meta.last_page}</span>
                <button type="button" onClick={() => load(meta.current_page + 1)} disabled={loading || meta.current_page >= meta.last_page} className="btn-secondary disabled:opacity-40">Siguiente</button>
            </div>
        </div>
    );
}

function FragmentRow({ row, open, detail, detailLoading, onToggle }) {
    return (
        <>
            <tr className="hover:bg-fog/70">
                <td className="whitespace-nowrap px-4 py-3 text-earth">{row.occurred_at}</td>
                <td className="px-4 py-3 text-charcoal">
                    <span className="font-bold">{row.user_name ?? '—'}</span>
                    {row.user_role && <><br /><span className="text-xs text-earth">{row.user_role}</span></>}
                </td>
                <td className="px-4 py-3 text-charcoal">{MODULE_LABELS[row.module] ?? row.module}</td>
                <td className="px-4 py-3">
                    <span className={`rounded-full px-2 py-1 text-xs font-extrabold ${ACTION_STYLES[row.action] ?? 'bg-fog text-charcoal'}`}>{row.action_label}</span>
                </td>
                <td className="px-4 py-3 text-charcoal">{row.auditable_label ?? row.description}</td>
                <td className="px-4 py-3 text-right">
                    {row.changes_count > 0 ? (
                        <button type="button" onClick={onToggle} className="text-xs font-bold text-leaf hover:underline" aria-expanded={open}>
                            {open ? 'Ocultar' : `Ver ${row.changes_count} campo${row.changes_count === 1 ? '' : 's'}`}
                        </button>
                    ) : <span className="text-xs text-earth">—</span>}
                </td>
            </tr>
            {open && (
                <tr className="bg-fog/50">
                    <td colSpan="6" className="px-4 py-3">
                        {detailLoading || !detail ? (
                            <span className="text-earth"><i className="fas fa-spinner fa-spin mr-2" />Cargando…</span>
                        ) : (
                            <div className="space-y-3">
                                <table className="w-full text-left text-xs">
                                    <thead className="uppercase text-earth">
                                        <tr><th className="py-1 pr-4">Campo</th><th className="py-1 pr-4">Valor anterior</th><th className="py-1">Valor nuevo</th></tr>
                                    </thead>
                                    <tbody className="divide-y divide-wheat">
                                        {detail.changes.map((c) => (
                                            <tr key={c.field}>
                                                <td className="py-1 pr-4 font-bold text-charcoal">{c.field_label ?? c.field}</td>
                                                <td className="max-w-xs break-words py-1 pr-4 text-earth">{c.old_value ?? '—'}</td>
                                                <td className="max-w-xs break-words py-1 text-charcoal">{c.new_value ?? '—'}</td>
                                            </tr>
                                        ))}
                                    </tbody>
                                </table>
                                <p className="text-xs text-earth">
                                    IP: {detail.ip_address ?? '—'} · {detail.http_method ?? ''} /{detail.url ?? ''}
                                </p>
                            </div>
                        )}
                    </td>
                </tr>
            )}
        </>
    );
}
