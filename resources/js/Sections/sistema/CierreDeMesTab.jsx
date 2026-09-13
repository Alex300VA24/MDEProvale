import { useCallback, useEffect, useState } from 'react';
import http from '../../http';
import { useToast } from '../../Components/Toast';
import ConfirmDialog from '../../Components/ConfirmDialog';
import errorMessage from '../../errorMessage';

const BASE = '/api/dashboard/sistema';

const labelCls = 'block text-xs font-bold text-slate-600 uppercase tracking-wider mb-1';
const inputCls =
    'w-full px-4 py-2.5 border-2 border-wheat rounded-xl text-sm font-semibold text-charcoal bg-white focus:outline-none focus:border-leaf transition-all';

export default function CierreDeMesTab({ can }) {
    const toast = useToast();
    const [state, setState] = useState(null);
    const [loadError, setLoadError] = useState(false);
    const [selected, setSelected] = useState(null);
    const [pendingClose, setPendingClose] = useState(null);
    const [submitting, setSubmitting] = useState(false);

    const load = useCallback(async () => {
        try {
            const res = await http.get(`${BASE}/cierre-mes`);
            setState(res.data);
            setLoadError(false);
        } catch {
            setLoadError(true);
        }
    }, []);

    useEffect(() => {
        load();
    }, [load]);

    const confirmClose = async () => {
        if (!pendingClose) return;
        setSubmitting(true);
        try {
            await http.post(`${BASE}/cierre-mes`, {
                anio: pendingClose.anio,
                mes: pendingClose.mes,
            });
            toast.success(`${pendingClose.label}: mes cerrado correctamente.`);
            setPendingClose(null);
            load();
        } catch (err) {
            toast.error(errorMessage(err, 'No se pudo cerrar el mes.'));
            setPendingClose(null);
        } finally {
            setSubmitting(false);
        }
    };

    if (loadError) {
        return (
            <div className="empty-state">
                <i className="fas fa-exclamation-triangle" />
                <p>No se pudieron cargar los datos de la sección. Recarga la página.</p>
            </div>
        );
    }

    if (!state) {
        return (
            <div className="flex items-center justify-center py-10 text-earth">
                <i className="fas fa-spinner fa-spin mr-2" /> Cargando...
            </div>
        );
    }

    const disponibles = state.disponibles ?? [];
    const cerrados = state.cerrados ?? [];

    return (
        <div className="space-y-4">
            <div className="grid grid-cols-1 sm:grid-cols-2 gap-3">
                <div className="p-4 rounded-xl border-2 border-wheat bg-white">
                    <div className="flex items-center justify-between mb-2">
                        <span className="text-xs font-bold text-slate-600 uppercase tracking-wider">Último mes cerrado</span>
                        <i className="fas fa-lock-open text-leaf" aria-hidden="true" />
                    </div>
                    <div className="text-xl sm:text-2xl font-extrabold text-navy tabular-nums">
                        {state.ultimo_cerrado ? state.ultimo_cerrado.label : 'Sin cierres'}
                    </div>
                    <p className="text-xs text-slate mt-1">
                        Solo los meses cerrados muestran avisos y detalles definitivos en el panel de Inicio.
                    </p>
                </div>

                <div className="p-4 rounded-xl border-2 border-wheat bg-white">
                    <div className="flex items-center justify-between mb-2">
                        <span className="text-xs font-bold text-slate-600 uppercase tracking-wider">Mes en curso</span>
                        <i className="fas fa-calendar-day text-amber" aria-hidden="true" />
                    </div>
                    <div className="text-xl sm:text-2xl font-extrabold text-navy tabular-nums">
                        {state.mes_actual?.label ?? ''}
                    </div>
                    <p className="text-xs text-slate mt-1">El mes vigente solo podrá cerrarse cuando haya terminado.</p>
                </div>
            </div>

            {can?.edit && (
                <div className="p-4 sm:p-5 rounded-xl border-2 border-wheat bg-white">
                    <div className="flex flex-col sm:flex-row sm:items-end gap-3">
                        <div className="w-full sm:w-64">
                            <label className={labelCls}>Mes a cerrar</label>
                            <select
                                value={selected ? `${selected.anio}-${selected.mes}` : ''}
                                onChange={(e) => {
                                    const [anio, mes] = e.target.value.split('-').map(Number);
                                    const found = disponibles.find((m) => m.anio === anio && m.mes === mes);
                                    setSelected(found || null);
                                }}
                                className={inputCls}
                                disabled={submitting || disponibles.length === 0}
                            >
                                <option value="">
                                    {disponibles.length === 0 ? 'No hay meses pendientes' : 'Seleccionar mes...'}
                                </option>
                                {disponibles.map((m) => (
                                    <option key={`${m.anio}-${m.mes}`} value={`${m.anio}-${m.mes}`}>{m.label}</option>
                                ))}
                            </select>
                        </div>
                        <button
                            type="button"
                            disabled={!selected || submitting}
                            onClick={() => selected && setPendingClose(selected)}
                            className="btn-primary flex items-center gap-2 text-xs sm:text-sm disabled:opacity-50 disabled:cursor-not-allowed"
                        >
                            <i className={`fas ${submitting ? 'fa-spinner fa-spin' : 'fa-lock'}`} aria-hidden="true" /> Cerrar mes
                        </button>
                    </div>
                    <p className="text-xs text-slate mt-3">
                        Al cerrar el mes se habilitan los avisos y el detalle de stock (utilizado y faltante) del panel de Inicio para ese período. La acción no se puede deshacer.
                    </p>
                </div>
            )}

            <div className="p-4 sm:p-5 rounded-xl border-2 border-wheat bg-white">
                <h4 className="text-xs font-bold text-slate-600 uppercase tracking-wider mb-3 flex items-center gap-2">
                    <i className="fas fa-history text-leaf" aria-hidden="true" /> Meses cerrados
                </h4>
                {cerrados.length === 0 ? (
                    <div className="text-sm text-slate">Aún no hay cierres registrados.</div>
                ) : (
                    <div className="flex flex-wrap gap-2">
                        {cerrados.slice(-12).reverse().map((m) => (
                            <span key={`${m.anio}-${m.mes}`} className="badge badge-current">{m.label}</span>
                        ))}
                    </div>
                )}
            </div>

            <ConfirmDialog
                open={!!pendingClose}
                onCancel={() => setPendingClose(null)}
                onConfirm={confirmClose}
                title="Cerrar mes"
                message={`Se cerrará el mes de ${pendingClose?.label ?? ''}. A partir de este momento sus avisos y detalles aparecerán en el panel de Inicio.`}
                details={pendingClose ? [{ label: 'Período', value: pendingClose.label }] : []}
                confirmLabel="Cerrar mes"
                danger={false}
                loading={submitting}
            />
        </div>
    );
}