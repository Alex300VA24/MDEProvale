import { useCallback, useEffect, useState } from 'react';
import http from '../../http';
import { useToast } from '../../Components/Toast';
import errorMessage from '../../errorMessage';

const BASE = '/api/dashboard/sistema/audit-events';
const EVENT_LABELS = {
    BENEFIT_AUTO_DEACTIVATED: 'Baja automática de beneficio',
    ASSOCIATION_STATUS_CHANGED: 'Cambio de estado de comité',
};

export default function AuditoriaTab() {
    const toast = useToast();
    const [events, setEvents] = useState([]);
    const [meta, setMeta] = useState({ current_page: 1, last_page: 1 });
    const [source, setSource] = useState('');
    const [search, setSearch] = useState('');
    const [loading, setLoading] = useState(true);

    const load = useCallback(async (page = 1) => {
        setLoading(true);
        try {
            const { data } = await http.get(BASE, { params: { page, source: source || undefined, search: search || undefined } });
            setEvents(data.data ?? []);
            setMeta({ current_page: data.current_page ?? 1, last_page: data.last_page ?? 1 });
        } catch (error) {
            toast.error(errorMessage(error, 'No se pudo cargar la auditoría.'));
        } finally {
            setLoading(false);
        }
    }, [source, search]);

    useEffect(() => {
        const timer = setTimeout(() => load(1), 250);
        return () => clearTimeout(timer);
    }, [load]);

    return (
        <div className="space-y-4">
            <div className="flex flex-col gap-2 sm:flex-row">
                <input type="search" value={search} onChange={(event) => setSearch(event.target.value)} placeholder="Buscar evento, motivo o registro" className="form-input flex-1" aria-label="Buscar auditoría" />
                <select value={source} onChange={(event) => setSource(event.target.value)} className="form-input sm:w-48" aria-label="Origen del evento">
                    <option value="">Todos los orígenes</option>
                    <option value="SYSTEM">Sistema</option>
                    <option value="USER">Usuario</option>
                </select>
            </div>

            <div className="overflow-x-auto rounded-xl border-2 border-wheat">
                <table className="w-full min-w-[780px] text-left text-sm">
                    <thead className="bg-base text-xs uppercase tracking-wide text-earth">
                        <tr>
                            <th className="px-4 py-3">Fecha</th>
                            <th className="px-4 py-3">Evento</th>
                            <th className="px-4 py-3">Registro</th>
                            <th className="px-4 py-3">Cambio</th>
                            <th className="px-4 py-3">Origen</th>
                            <th className="px-4 py-3">Ejecutado por</th>
                        </tr>
                    </thead>
                    <tbody className="divide-y divide-wheat">
                        {loading ? (
                            <tr><td colSpan="6" className="px-4 py-10 text-center text-earth"><i className="fas fa-spinner fa-spin mr-2" />Cargando auditoría…</td></tr>
                        ) : events.length === 0 ? (
                            <tr><td colSpan="6" className="px-4 py-10 text-center text-earth">No hay eventos registrados.</td></tr>
                        ) : events.map((event) => (
                            <tr key={event.id} className="hover:bg-base/70">
                                <td className="whitespace-nowrap px-4 py-3 text-earth">{event.occurred_at}</td>
                                <td className="px-4 py-3 font-bold text-charcoal">{EVENT_LABELS[event.event_type] ?? event.event_type}</td>
                                <td className="px-4 py-3 text-charcoal">{event.subject}</td>
                                <td className="px-4 py-3"><span className="font-bold">{event.from_state ?? '—'} → {event.to_state ?? '—'}</span><br /><span className="text-xs text-earth">{event.reason_code ?? ''}</span></td>
                                <td className="px-4 py-3"><span className={`rounded-full px-2 py-1 text-xs font-extrabold ${event.source === 'SYSTEM' ? 'bg-blue-100 text-blue-800' : 'bg-leaf-light text-charcoal'}`}>{event.source === 'SYSTEM' ? 'Sistema' : 'Usuario'}</span></td>
                                <td className="px-4 py-3 text-charcoal">{event.executed_by}</td>
                            </tr>
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
