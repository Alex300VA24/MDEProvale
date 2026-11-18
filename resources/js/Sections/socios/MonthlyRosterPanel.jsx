import { useCallback, useEffect, useState } from 'react';
import http from '../../http';
import errorMessage from '../../errorMessage';
import Modal from '../../Components/Modal';
import ConfirmDialog from '../../Components/ConfirmDialog';
import { useToast } from '../../Components/Toast';

const ENDPOINT = '/api/dashboard/socios-beneficiarios/monthly-rosters';
const inputCls = 'min-h-11 w-full rounded-md border-2 border-wheat bg-white px-3 py-2 text-sm font-semibold text-charcoal focus:border-blue focus:outline-none focus:ring-2 focus:ring-blue/20';

const statusMeta = {
    DRAFT: { label: 'Borrador', className: 'bg-sun-light text-[#8A4A00]', icon: 'fa-pen-ruler' },
    CLOSED: { label: 'Cerrado', className: 'bg-leaf-light text-leaf', icon: 'fa-lock' },
    SUPERSEDED: { label: 'Reemplazado', className: 'bg-surface text-earth', icon: 'fa-clock-rotate-left' },
};

function nextPeriod(rosters) {
    const latest = rosters.map((item) => item.period).sort().at(-1);
    const date = latest ? new Date(`${latest}T12:00:00`) : new Date();
    date.setMonth(date.getMonth() + 1);
    return `${date.getFullYear()}-${String(date.getMonth() + 1).padStart(2, '0')}`;
}

export default function MonthlyRosterPanel({ can, onActiveChange, onRosterDataChange, refreshKey = 0 }) {
    const toast = useToast();
    const [payload, setPayload] = useState({ data: [], closed_options: [] });
    const [loading, setLoading] = useState(true);
    const [busy, setBusy] = useState(false);
    const [createOpen, setCreateOpen] = useState(false);
    const [period, setPeriod] = useState('');
    const [sourceId, setSourceId] = useState('');
    const [confirmAction, setConfirmAction] = useState(null);
    const [revision, setRevision] = useState(null);
    const [reason, setReason] = useState('');
    const [detail, setDetail] = useState(null);
    const [detailLoadingId, setDetailLoadingId] = useState(null);
    const [deleteTarget, setDeleteTarget] = useState(null);

    const load = useCallback(async () => {
        setLoading(true);
        try {
            const response = await http.get(ENDPOINT);
            const next = response.data ?? { data: [], closed_options: [] };
            setPayload(next);
            onRosterDataChange?.(next.data ?? []);
            const draft = (next.data ?? []).find((item) => item.status === 'DRAFT') ?? null;
            onActiveChange?.(draft);
        } catch (error) {
            toast.error(errorMessage(error, 'No se pudieron cargar los períodos de padrón.'));
        } finally {
            setLoading(false);
        }
    }, [onActiveChange, onRosterDataChange, toast]);

    useEffect(() => { load(); }, [load, refreshKey]);

    const rows = payload.data ?? [];
    const closedOptions = payload.closed_options ?? [];
    const activeDraft = rows.find((item) => item.status === 'DRAFT') ?? null;

    const openCreate = () => {
        setPeriod(nextPeriod(rows));
        setSourceId(closedOptions[0]?.id ?? '');
        setCreateOpen(true);
    };

    const create = async () => {
        if (!period) return toast.error('Seleccione el mes del nuevo padrón.');
        setBusy(true);
        try {
            const response = await http.post(ENDPOINT, {
                period,
                source_roster_id: sourceId || null,
            }, { timeout: 120000 });
            toast.success(response.data?.message ?? 'Padrón borrador creado.');
            setCreateOpen(false);
            await load();
        } catch (error) {
            toast.error(errorMessage(error, 'No se pudo crear el padrón.'));
        } finally {
            setBusy(false);
        }
    };

    const executeConfirmed = async () => {
        const action = confirmAction;
        if (!action) return;
        setBusy(true);
        try {
            const response = await http.post(`${ENDPOINT}/${action.roster.id}/${action.type}`, {}, { timeout: 120000 });
            toast.success(response.data?.message ?? 'Operación completada.');
            setConfirmAction(null);
            await load();
        } catch (error) {
            toast.error(errorMessage(error, 'No se pudo completar la operación.'));
        } finally {
            setBusy(false);
        }
    };

    const createRevision = async () => {
        if (reason.trim().length < 10) return toast.error('Explique el motivo de la corrección con al menos 10 caracteres.');
        setBusy(true);
        try {
            const response = await http.post(`${ENDPOINT}/${revision.id}/revise`, { reason: reason.trim() }, { timeout: 120000 });
            toast.success(response.data?.message ?? 'Revisión creada.');
            setRevision(null);
            setReason('');
            await load();
        } catch (error) {
            toast.error(errorMessage(error, 'No se pudo crear la revisión.'));
        } finally {
            setBusy(false);
        }
    };

    const openDetail = async (roster) => {
        setDetailLoadingId(roster.id);
        try {
            const response = await http.get(`${ENDPOINT}/${roster.id}`);
            setDetail(response.data?.data ?? null);
        } catch (error) {
            toast.error(errorMessage(error, 'No se pudo cargar el detalle del padrón.'));
        } finally {
            setDetailLoadingId(null);
        }
    };

    const deleteDraft = async () => {
        if (!deleteTarget) return;
        setBusy(true);
        try {
            const response = await http.delete(`${ENDPOINT}/${deleteTarget.id}`, { timeout: 120000 });
            toast.success(response.data?.message ?? 'El borrador fue eliminado.');
            setDeleteTarget(null);
            await load();
        } catch (error) {
            toast.error(errorMessage(error, 'No se pudo eliminar el borrador.'));
        } finally {
            setBusy(false);
        }
    };

    return (
        <section aria-labelledby="monthly-roster-title" className="overflow-hidden rounded-xl border border-mist bg-white">
            <div className="flex flex-col gap-3 border-b border-mist bg-surface px-4 py-4 sm:flex-row sm:items-center sm:justify-between sm:px-5">
                <div>
                    <h4 id="monthly-roster-title" className="flex items-center gap-2 font-heading text-base font-extrabold text-navy">
                        <i className="fas fa-layer-group text-blue" aria-hidden="true" />Cierres mensuales del padrón
                    </h4>
                    <p className="mt-1 max-w-3xl text-sm text-earth">
                        Cada cierre conserva una fotografía completa. Programaciones y PECOSAs seguirán vinculadas al padrón que realmente utilizaron.
                    </p>
                </div>
                {can.create && (
                    <button type="button" onClick={openCreate} disabled={busy || !!activeDraft} className="btn-primary inline-flex min-h-11 items-center justify-center gap-2 disabled:cursor-not-allowed disabled:opacity-40">
                        <i className="fas fa-calendar-plus" aria-hidden="true" />Crear siguiente padrón
                    </button>
                )}
            </div>

            {activeDraft && (
                <div className="flex flex-col gap-3 border-b border-mist bg-blue-light/40 px-4 py-3 sm:flex-row sm:items-center sm:justify-between sm:px-5">
                    <div className="text-sm text-charcoal">
                        <strong className="font-extrabold">Período de trabajo: {activeDraft.label}</strong>
                        <span className="ml-2 text-earth">{activeDraft.beneficiary_count.toLocaleString('es-PE')} beneficiarios · {activeDraft.association_count} comités</span>
                    </div>
                    {can.edit && (
                        <div className="flex flex-wrap gap-2">
                            <button type="button" onClick={() => setConfirmAction({ type: 'sync', roster: activeDraft })} disabled={busy} className="btn-secondary min-h-11">
                                <i className="fas fa-rotate mr-2" aria-hidden="true" />Actualizar fotografía
                            </button>
                            <button type="button" onClick={() => setConfirmAction({ type: 'close', roster: activeDraft })} disabled={busy} className="btn-primary min-h-11">
                                <i className="fas fa-lock mr-2" aria-hidden="true" />Cerrar padrón
                            </button>
                        </div>
                    )}
                </div>
            )}

            <div className="overflow-x-auto">
                <table className="data-table w-full min-w-[760px] text-sm">
                    <thead><tr><th className="px-4 py-3 text-left">Período</th><th className="px-4 py-3 text-left">Estado</th><th className="px-4 py-3 text-left">Origen</th><th className="px-4 py-3 text-right">Comités</th><th className="px-4 py-3 text-right">Beneficiarios</th><th className="px-4 py-3 text-center">Acción</th></tr></thead>
                    <tbody>
                        {loading ? (
                            <tr><td colSpan={6} className="px-4 py-8 text-center text-earth"><i className="fas fa-spinner fa-spin mr-2" aria-hidden="true" />Cargando períodos…</td></tr>
                        ) : rows.length === 0 ? (
                            <tr><td colSpan={6} className="px-4 py-8 text-center text-earth">Aún no existen cierres mensuales.</td></tr>
                        ) : rows.map((roster) => {
                            const meta = statusMeta[roster.status] ?? statusMeta.DRAFT;
                            return (
                                <tr key={roster.id}>
                                    <td className="px-4 py-3 font-extrabold text-charcoal">{roster.label}</td>
                                    <td className="px-4 py-3"><span className={`inline-flex items-center gap-1.5 rounded-full px-2.5 py-1 text-xs font-bold ${meta.className}`}><i className={`fas ${meta.icon}`} aria-hidden="true" />{meta.label}</span></td>
                                    <td className="px-4 py-3 text-earth">{roster.source?.label ?? 'Datos vigentes'}</td>
                                    <td className="px-4 py-3 text-right font-semibold tabular-nums">{roster.association_count}</td>
                                    <td className="px-4 py-3 text-right font-semibold tabular-nums">{roster.beneficiary_count.toLocaleString('es-PE')}</td>
                                    <td className="px-4 py-3 text-center">
                                        <div className="inline-flex flex-wrap items-center justify-center gap-2">
                                            <button type="button" onClick={() => openDetail(roster)} disabled={detailLoadingId === roster.id} className="btn-action bg-sky-light text-[#0369A1] hover:bg-sky hover:text-white disabled:opacity-50" title={`Ver detalle de ${roster.label}`} aria-label={`Ver detalle de ${roster.label}`}>
                                                <i className={`fas ${detailLoadingId === roster.id ? 'fa-spinner fa-spin' : 'fa-eye'}`} aria-hidden="true" />
                                            </button>
                                            {can.edit && roster.status === 'CLOSED' && (
                                                <button type="button" onClick={() => { setRevision(roster); setReason(''); }} disabled={busy || !!activeDraft} className="btn-secondary min-h-11 text-xs disabled:cursor-not-allowed disabled:opacity-40">
                                                    <i className="fas fa-file-pen mr-2" aria-hidden="true" />Crear corrección
                                                </button>
                                            )}
                                            {can.del && roster.status === 'DRAFT' && (
                                                <button type="button" onClick={() => setDeleteTarget(roster)} disabled={busy} className="btn-action bg-clay-light text-clay hover:bg-clay hover:text-white disabled:opacity-50" title={`Eliminar borrador ${roster.label}`} aria-label={`Eliminar borrador ${roster.label}`}>
                                                    <i className="fas fa-trash" aria-hidden="true" />
                                                </button>
                                            )}
                                        </div>
                                    </td>
                                </tr>
                            );
                        })}
                    </tbody>
                </table>
            </div>

            {!loading && rows.length > 0 && (
                <div className="border-t border-mist bg-surface px-4 py-3 text-xs font-semibold text-earth sm:px-5">
                    Se muestran los {rows.length} padrones registrados, incluido todo el historial disponible.
                </div>
            )}

            <Modal open={createOpen} onClose={() => !busy && setCreateOpen(false)} title="Crear padrón mensual" icon="fa-calendar-plus" maxWidth="sm:max-w-lg">
                <div className="space-y-4 p-5">
                    <div><label htmlFor="roster-period" className="mb-1 block text-xs font-bold uppercase tracking-wider text-earth">Mes del padrón</label><input id="roster-period" type="month" value={period} onChange={(event) => setPeriod(event.target.value)} className={inputCls} /></div>
                    <div><label htmlFor="roster-source" className="mb-1 block text-xs font-bold uppercase tracking-wider text-earth">Padrón base</label><select id="roster-source" value={sourceId} onChange={(event) => setSourceId(event.target.value)} className={inputCls}><option value="">Datos vigentes del sistema</option>{closedOptions.map((option) => <option key={option.id} value={option.id}>{option.label} · {option.beneficiary_count.toLocaleString('es-PE')} beneficiarios</option>)}</select><p className="mt-1 text-xs text-earth">Normalmente se copia el último mes cerrado y luego se aplican las altas y bajas del nuevo período. En padrones grandes, la creación puede tomar hasta dos minutos.</p></div>
                    <div className="flex gap-3"><button type="button" onClick={() => setCreateOpen(false)} disabled={busy} className="btn-secondary flex-1 min-h-11">Cancelar</button><button type="button" onClick={create} disabled={busy} className="btn-primary flex-1 min-h-11">{busy ? <><i className="fas fa-spinner fa-spin mr-2" />Creando…</> : 'Crear borrador'}</button></div>
                </div>
            </Modal>

            <Modal open={!!revision} onClose={() => !busy && setRevision(null)} title="Crear corrección auditada" icon="fa-file-pen" maxWidth="sm:max-w-lg">
                <div className="space-y-4 p-5"><p className="text-sm text-earth">La versión cerrada permanecerá disponible y se creará un nuevo borrador del mismo mes.</p><div><label htmlFor="revision-reason" className="mb-1 block text-xs font-bold uppercase tracking-wider text-earth">Motivo de la corrección</label><textarea id="revision-reason" rows={4} value={reason} onChange={(event) => setReason(event.target.value)} className={inputCls} placeholder="Explique el error y qué información debe corregirse." /></div><div className="flex gap-3"><button type="button" onClick={() => setRevision(null)} disabled={busy} className="btn-secondary flex-1 min-h-11">Cancelar</button><button type="button" onClick={createRevision} disabled={busy} className="btn-primary flex-1 min-h-11">Crear revisión</button></div></div>
            </Modal>

            <Modal open={!!detail} onClose={() => setDetail(null)} title={detail ? `Detalle de ${detail.label}` : 'Detalle del padrón'} icon="fa-clipboard-list" maxWidth="sm:max-w-5xl">
                {detail && (
                    <div className="max-h-[78vh] overflow-y-auto p-4 sm:p-6">
                        <dl className="grid grid-cols-2 gap-3 rounded-xl border border-mist bg-surface p-4 text-sm sm:grid-cols-4">
                            <div><dt className="text-xs font-bold uppercase tracking-wider text-earth">Estado</dt><dd className="mt-1 font-extrabold text-charcoal">{statusMeta[detail.status]?.label ?? detail.status}</dd></div>
                            <div><dt className="text-xs font-bold uppercase tracking-wider text-earth">Origen</dt><dd className="mt-1 font-extrabold text-charcoal">{detail.source?.label ?? 'Datos vigentes'}</dd></div>
                            <div><dt className="text-xs font-bold uppercase tracking-wider text-earth">Comités</dt><dd className="mt-1 font-extrabold tabular-nums text-charcoal">{detail.association_count}</dd></div>
                            <div><dt className="text-xs font-bold uppercase tracking-wider text-earth">Beneficiarios</dt><dd className="mt-1 font-extrabold tabular-nums text-charcoal">{detail.beneficiary_count.toLocaleString('es-PE')}</dd></div>
                        </dl>
                        <div className="mt-4 overflow-x-auto rounded-xl border border-mist">
                            <table className="data-table w-full min-w-[720px] text-sm">
                                <thead><tr><th className="px-3 py-3 text-left">Código</th><th className="px-3 py-3 text-left">Comité</th><th className="px-3 py-3 text-left">Presidenta</th><th className="px-3 py-3 text-right">Socias</th><th className="px-3 py-3 text-right">Beneficiarios</th></tr></thead>
                                <tbody>
                                    {detail.associations.length === 0 ? (
                                        <tr><td colSpan={5} className="px-4 py-8 text-center text-earth">Este padrón no contiene comités.</td></tr>
                                    ) : detail.associations.map((association) => (
                                        <tr key={association.id}><td className="px-3 py-3 font-bold text-charcoal">{association.code || '—'}</td><td className="px-3 py-3 text-charcoal">{association.name}</td><td className="px-3 py-3 text-earth">{association.president_name || 'Sin asignar'}</td><td className="px-3 py-3 text-right tabular-nums">{association.partner_count}</td><td className="px-3 py-3 text-right font-bold tabular-nums">{association.beneficiary_count}</td></tr>
                                    ))}
                                </tbody>
                            </table>
                        </div>
                        <div className="mt-4 flex justify-end border-t border-mist pt-4"><button type="button" onClick={() => setDetail(null)} className="btn-secondary min-h-11">Cerrar detalle</button></div>
                    </div>
                )}
            </Modal>

            <ConfirmDialog
                open={!!confirmAction}
                onCancel={() => !busy && setConfirmAction(null)}
                onConfirm={executeConfirmed}
                loading={busy}
                danger={confirmAction?.type === 'close'}
                title={confirmAction?.type === 'close' ? 'Cerrar padrón mensual' : 'Actualizar fotografía del borrador'}
                message={confirmAction?.type === 'close' ? 'El cierre protegerá esta versión contra cambios. Para corregirla después deberá crear una revisión auditada.' : 'Se reemplazará el contenido del borrador con los socios y beneficiarios vigentes para ese período.'}
                confirmLabel={confirmAction?.type === 'close' ? 'Sí, cerrar padrón' : 'Actualizar borrador'}
                details={confirmAction ? [{ label: 'Período', value: confirmAction.roster.label }, { label: 'Beneficiarios', value: confirmAction.roster.beneficiary_count.toLocaleString('es-PE') }] : []}
            />
            <ConfirmDialog
                open={!!deleteTarget}
                onCancel={() => !busy && setDeleteTarget(null)}
                onConfirm={deleteDraft}
                loading={busy}
                danger
                title="Eliminar borrador del padrón"
                message="Se eliminará esta fotografía de trabajo y todos sus comités asociados. Los padrones cerrados no pueden eliminarse."
                confirmLabel="Sí, eliminar borrador"
                details={deleteTarget ? [{ label: 'Período', value: deleteTarget.label }, { label: 'Beneficiarios', value: deleteTarget.beneficiary_count.toLocaleString('es-PE') }] : []}
            />
        </section>
    );
}
